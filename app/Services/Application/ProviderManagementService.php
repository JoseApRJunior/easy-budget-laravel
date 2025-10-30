<?php

declare(strict_types=1);

namespace App\Services\Application;

use App\Enums\OperationStatus;
use App\Models\Activity;
use App\Models\AreaOfActivity;
use App\Models\Budget;
use App\Models\CommonData;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\PlanSubscription;
use App\Models\Profession;
use App\Models\Provider;
use App\Models\Role;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Repositories\CommonDataRepository;
use App\Repositories\PlanRepository;
use App\Repositories\ProviderRepository;
use App\Repositories\RoleRepository;
use App\Repositories\TenantRepository;
use App\Repositories\UserRepository;
use App\Services\Domain\ActivityService;
use App\Services\Infrastructure\FinancialSummary;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// Constants for magic strings to improve maintainability
const ROLE_PROVIDER              = 'provider';
const PLAN_SLUG_TRIAL            = 'trial';
const SUBSCRIPTION_STATUS_ACTIVE = 'active';
const PAYMENT_METHOD_TRIAL       = 'trial';
const TRIAL_DAYS                 = 7;

class ProviderManagementService
{
    public function __construct(
        private FinancialSummary $financialSummary,
        private ActivityService $activityService,
        private CommonDataRepository $commonDataRepository,
        private ProviderRepository $providerRepository,
        private PlanRepository $planRepository,
        private RoleRepository $roleRepository,
        private TenantRepository $tenantRepository,
        private UserRepository $userRepository,
    ) {}

    /**
     * Get provider dashboard data.
     */
    public function getDashboardData( int $tenantId ): array
    {
        $user = Auth::user();

        // Buscar orçamentos recentes
        $budgets = Budget::where( 'tenant_id', $tenantId )
            ->with( [ 'customer', 'items' ] )
            ->latest()
            ->limit( 10 )
            ->get();

        // Buscar atividades recentes
        $activities = Activity::where( 'tenant_id', $tenantId )
            ->where( 'user_id', $user->id )
            ->latest()
            ->limit( 10 )
            ->get();

        // Buscar resumo financeiro
        $financialResult = $this->financialSummary->getMonthlySummary( $tenantId );

        $financialSummary = [];
        if ( $financialResult->isSuccess() ) {
            $financialSummary = $financialResult->getData();
        }

        return [
            'budgets'           => $budgets,
            'activities'        => $activities,
            'financial_summary' => $financialSummary,
        ];
    }

    /**
     * Get provider data for update form.
     */
    public function getProviderForUpdate(): ServiceResult
    {
        $user     = Auth::user();
        $provider = $user->provider;

        if ( !$provider ) {
            return ServiceResult::error( OperationStatus::NOT_FOUND, 'Provider não encontrado' );
        }

        return ServiceResult::success( [
            'provider'          => $provider->load( [ 'user', 'commonData', 'contact', 'address' ] ),
            'areas_of_activity' => AreaOfActivity::get(),
            'professions'       => Profession::get(),
        ] );
    }

    /**
     * Update provider data.
     */
    public function updateProvider( array $data ): ServiceResult
    {
        $user     = Auth::user();
        $provider = $user->provider;

        if ( !$provider ) {
            return ServiceResult::error( OperationStatus::NOT_FOUND, 'Provider não encontrado' );
        }

        // Load relationships if not already loaded
        $provider->load( [ 'commonData', 'contact', 'address' ] );

        DB::transaction( function () use ($provider, $data, $user) {
            // Handle logo upload
            if ( isset( $data[ 'logo' ] ) && $data[ 'logo' ] instanceof UploadedFile ) {
                $logoPath       = $this->handleLogoUpload( $data[ 'logo' ], $user->logo );
                $data[ 'logo' ] = $logoPath;
            }

            // Update User
            $user->update( [
                'email' => $data[ 'email' ] ?? $user->email,
                'logo'  => $data[ 'logo' ] ?? $user->logo,
            ] );

            // Update CommonData if exists
            if ( $provider->commonData ) {
                $commonDataUpdate = [
                    'company_name'        => $data[ 'company_name' ] ?? $provider->commonData->company_name,
                    'cnpj'                => $this->cleanDocumentNumber( $data[ 'cnpj' ] ?? $provider->commonData->cnpj ),
                    'cpf'                 => $this->cleanDocumentNumber( $data[ 'cpf' ] ?? $provider->commonData->cpf ),
                    'area_of_activity_id' => $data[ 'area_of_activity_id' ] ?? $provider->commonData->area_of_activity_id,
                    'profession_id'       => $data[ 'profession_id' ] ?? $provider->commonData->profession_id,
                    'description'         => $data[ 'description' ] ?? $provider->commonData->description,
                ];

                // Only update if there are changes - compare only the fields being updated
                $currentCommonData = $provider->commonData->only( array_keys( $commonDataUpdate ) );
                if ( !empty( array_diff_assoc( $commonDataUpdate, $currentCommonData->toArray() ) ) ) {
                    $provider->commonData->update( $commonDataUpdate );
                }
            }

            // Update Contact if exists
            if ( $provider->contact ) {
                $contactUpdate = [
                    'email_business' => $data[ 'email_business' ] ?? $provider->contact->email_business,
                    'phone_business' => $data[ 'phone_business' ] ?? $provider->contact->phone_business,
                    'website'        => $data[ 'website' ] ?? $provider->contact->website,
                ];

                // Only update if there are changes - compare only the fields being updated
                $currentContact = $provider->contact->only( array_keys( $contactUpdate ) );
                if ( !empty( array_diff_assoc( $contactUpdate, $currentContact->toArray() ) ) ) {
                    $provider->contact->update( $contactUpdate );
                }
            }

            // Update Address if exists
            if ( $provider->address ) {
                $addressUpdate = [
                    'address'        => $data[ 'address' ] ?? $provider->address->address,
                    'address_number' => $data[ 'address_number' ] ?? $provider->address->address_number,
                    'neighborhood'   => $data[ 'neighborhood' ] ?? $provider->address->neighborhood,
                    'city'           => $data[ 'city' ] ?? $provider->address->city,
                    'state'          => $data[ 'state' ] ?? $provider->address->state,
                    'cep'            => $data[ 'cep' ] ?? $provider->address->cep,
                ];

                // Only update if there are changes - compare only the fields being updated
                $currentAddress = $provider->address->only( array_keys( $addressUpdate ) );
                if ( !empty( array_diff_assoc( $addressUpdate, $currentAddress->toArray() ) ) ) {
                    $provider->address->update( $addressUpdate );
                }
            }

            // Log activity
            $this->activityService->logActivity(
                $user->tenant_id,
                $user->id,
                'provider_updated',
                'provider',
                $provider->id,
                "Provider atualizado com sucesso!",
                $data,
            );
        } );

        return ServiceResult::success( $provider, 'Provider atualizado com sucesso' );
    }

    /**
     * Change provider password.
     */
    public function changePassword( string $newPassword ): void
    {
        $user         = Auth::user();
        $isGoogleUser = is_null( $user->password );

        $user->update( [
            'password' => Hash::make( $newPassword )
        ] );

        // Log activity
        $activityType    = $isGoogleUser ? 'password_set' : 'password_changed';
        $activityMessage = $isGoogleUser ? 'Primeira senha definida com sucesso!' : 'Senha atualizada com sucesso!';

        $this->activityService->logActivity(
            $user->tenant_id,
            $user->id,
            $activityType,
            'user',
            $user->id,
            $activityMessage,
            [
                'email'          => $user->email,
                'is_google_user' => $isGoogleUser,
            ],
        );
    }

    /**
     * Clean document number (CNPJ/CPF) by removing formatting.
     */
    private function cleanDocumentNumber( ?string $documentNumber ): ?string
    {
        if ( empty( $documentNumber ) ) {
            return null;
        }

        // Remove all non-digit characters (points, hyphens, slashes)
        $cleaned = preg_replace( '/[^0-9]/', '', $documentNumber );

        // Ensure it's exactly the expected length
        if ( strlen( $cleaned ) === 14 || strlen( $cleaned ) === 11 ) {
            return $cleaned;
        }

        // Return null if invalid length
        return null;
    }

    /**
     * Handle logo file upload.
     */
    private function handleLogoUpload( UploadedFile $file, ?string $currentLogo ): ?string
    {
        // Delete old logo if exists
        if ( $currentLogo && Storage::disk( 'public' )->exists( $currentLogo ) ) {
            Storage::disk( 'public' )->delete( $currentLogo );
        }

        // Store new logo
        return $file->store( 'providers/logos', 'public' );
    }

    /**
     * Get provider by user ID.
     */
    public function getProviderByUserId( int $userId, int $tenantId ): ?Provider
    {
        return Provider::where( 'user_id', $userId )
            ->where( 'tenant_id', $tenantId )
            ->with( [ 'user', 'commonData', 'contact', 'address' ] )
            ->first();
    }

    /**
     * Check if email exists for another user.
     */
    public function isEmailAvailable( string $email, int $excludeUserId ): bool
    {
        return !User::where( 'email', $email )
            ->where( 'id', '!=', $excludeUserId )
            ->exists();
    }

    /**
     * Get financial reports data for provider.
     */
    public function getFinancialReports( int $tenantId ): array
    {
        // Buscar resumo financeiro mensal
        $financialResult = $this->financialSummary->getMonthlySummary( $tenantId );

        $financialSummary = [];
        if ( $financialResult->isSuccess() ) {
            $financialSummary = $financialResult->getData();
        }

        // Buscar receitas mensais
        $monthlyRevenue = Budget::where( 'tenant_id', $tenantId )
            ->where( 'status', 'approved' )
            ->whereMonth( 'created_at', now()->month )
            ->whereYear( 'created_at', now()->year )
            ->sum( 'total_gross' );

        // Buscar orçamentos pendentes
        $pendingBudgets = Budget::where( 'tenant_id', $tenantId )
            ->where( 'status', 'pending' )
            ->with( [ 'customer' ] )
            ->latest()
            ->limit( 10 )
            ->get();

        // Buscar pagamentos em atraso
        $overduePayments = Budget::where( 'tenant_id', $tenantId )
            ->where( 'status', 'approved' )
            ->where( 'due_date', '<', now() )
            ->whereNull( 'paid_at' )
            ->with( [ 'customer' ] )
            ->latest()
            ->limit( 10 )
            ->get();

        return [
            'financial_summary' => $financialSummary,
            'monthly_revenue'   => $monthlyRevenue,
            'pending_budgets'   => $pendingBudgets,
            'overdue_payments'  => $overduePayments,
        ];
    }

    /**
     * Get budget reports data for provider.
     */
    public function getBudgetReports( int $tenantId ): array
    {
        // Buscar orçamentos do mês atual
        $budgets = Budget::where( 'tenant_id', $tenantId )
            ->with( [ 'customer', 'items' ] )
            ->whereMonth( 'created_at', now()->month )
            ->whereYear( 'created_at', now()->year )
            ->latest()
            ->get();

        // Estatísticas dos orçamentos
        $budgetStats = [
            'total_budgets'    => $budgets->count(),
            'approved_budgets' => $budgets->where( 'status', 'approved' )->count(),
            'pending_budgets'  => $budgets->where( 'status', 'pending' )->count(),
            'rejected_budgets' => $budgets->where( 'status', 'rejected' )->count(),
            'total_value'      => $budgets->sum( 'total_gross' ),
            'average_value'    => $budgets->count() > 0 ? $budgets->avg( 'total_gross' ) : 0,
        ];

        return [
            'budgets'      => $budgets,
            'budget_stats' => $budgetStats,
            'period'       => now()->format( 'F Y' ),
        ];
    }

    /**
     * Get service reports data for provider.
     */
    public function getServiceReports( int $tenantId ): array
    {
        // Buscar serviços do mês atual
        $services = Service::where( 'tenant_id', $tenantId )
            ->with( [ 'budget.customer', 'items' ] )
            ->whereMonth( 'created_at', now()->month )
            ->whereYear( 'created_at', now()->year )
            ->latest()
            ->get();

        // Estatísticas dos serviços
        $serviceStats = [
            'total_services'     => $services->count(),
            'completed_services' => $services->where( 'status', 'completed' )->count(),
            'pending_services'   => $services->where( 'status', 'pending' )->count(),
            'cancelled_services' => $services->where( 'status', 'cancelled' )->count(),
            'total_value'        => $services->sum( 'total_gross' ),
            'average_value'      => $services->count() > 0 ? $services->avg( 'total_gross' ) : 0,
        ];

        return [
            'services'      => $services,
            'service_stats' => $serviceStats,
            'period'        => now()->format( 'F Y' ),
        ];
    }

    /**
     * Get customer reports data for provider.
     */
    public function getCustomerReports( int $tenantId ): array
    {
        // Buscar clientes ativos
        $customers = Customer::where( 'tenant_id', $tenantId )
            ->with( [ 'budgets', 'services' ] )
            ->latest()
            ->limit( 50 )
            ->get();

        // Estatísticas dos clientes
        $customerStats = [
            'total_customers'     => $customers->count(),
            'active_customers'    => $customers->where( 'is_active', true )->count(),
            'inactive_customers'  => $customers->where( 'is_active', false )->count(),
            'new_customers_month' => $customers->whereMonth( 'created_at', now()->month )
                ->whereYear( 'created_at', now()->year )->count(),
            'total_budgets'       => $customers->sum( function ( $customer ) {
                return $customer->budgets->count();
            } ),
            'total_services'      => $customers->sum( function ( $customer ) {
                return $customer->services->count();
            } ),
        ];

        return [
            'customers'      => $customers,
            'customer_stats' => $customerStats,
            'period'         => now()->format( 'F Y' ),
        ];
    }

    /**
     * Create complete registration from user data.
     *
     * This method handles the complete registration flow:
     * 1. Create Tenant
     * 2. Create User
     * 3. Create Provider with all related data
     *
     * @param array $userData User registration data
     * @return ServiceResult Result of the operation with user, tenant, provider, plan, and subscription
     */
    public function createProviderFromRegistration( array $userData ): ServiceResult
    {
        try {
            DB::beginTransaction();

            // Step 1: Create Tenant
            $tenantResult = $this->createTenant( $userData );
            if ( !$tenantResult->isSuccess() ) {
                DB::rollBack();
                return $tenantResult;
            }
            $tenant = $tenantResult->getData();

            // Step 2: Create User
            $userResult = $this->createUser( $userData, $tenant );
            if ( !$userResult->isSuccess() ) {
                DB::rollBack();
                return $userResult;
            }
            $user = $userResult->getData();

            // Step 3: Create Provider with all related data
            $providerResult = $this->createProviderWithRelatedData( $userData, $user, $tenant );
            if ( !$providerResult->isSuccess() ) {
                DB::rollBack();
                return $providerResult;
            }

            $providerData = $providerResult->getData();

            DB::commit();

            return ServiceResult::success( [
                'user'         => $user,
                'tenant'       => $tenant,
                'provider'     => $providerData[ 'provider' ],
                'plan'         => $providerData[ 'plan' ],
                'subscription' => $providerData[ 'subscription' ],
            ], 'Registro completo realizado com sucesso.' );

        } catch ( Exception $e ) {
            DB::rollBack();
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao criar provider: ' . $e->getMessage()
            );
        }
    }

    /**
     * Create provider with all related data (CommonData, Role, Plan, Subscription).
     *
     * @param array $userData User registration data
     * @param User $user The created user
     * @param Tenant $tenant The created tenant
     * @return ServiceResult Result of the operation
     */
    private function createProviderWithRelatedData( array $userData, User $user, Tenant $tenant ): ServiceResult
    {
        try {
            // Create CommonData
            $commonData = new CommonData( [
                'tenant_id'    => $tenant->id,
                'first_name'   => $userData[ 'first_name' ],
                'last_name'    => $userData[ 'last_name' ],
                'cpf'          => null,
                'cnpj'         => null,
                'company_name' => null,
                'description'  => null,
            ] );

            $savedCommonData = $this->commonDataRepository->create( $commonData->toArray() );

            // Create Contact
            $contact = \App\Models\Contact::create( [
                'tenant_id'      => $tenant->id,
                'email_personal' => $userData[ 'email_personal' ] ?? $userData[ 'email' ],
                'phone_personal' => $userData[ 'phone_personal' ] ?? $userData[ 'phone' ] ?? null,
                'email_business' => null,
                'phone_business' => null,
                'website'        => null,
            ] );

            // Create Address
            $address = \App\Models\Address::create( [
                'tenant_id'      => $tenant->id,
                'address'        => null,
                'address_number' => null,
                'neighborhood'   => null,
                'city'           => null,
                'state'          => null,
                'cep'            => null,
            ] );

            // Create Provider
            $provider = new Provider( [
                'tenant_id'      => $tenant->id,
                'user_id'        => $user->id,
                'common_data_id' => $savedCommonData->id,
                'contact_id'     => $contact->id,
                'address_id'     => $address->id,
                'terms_accepted' => $userData[ 'terms_accepted' ],
            ] );

            $savedProvider = $this->providerRepository->create( $provider->toArray() );

            // Assign Provider Role
            $providerRole = Role::where( 'name', ROLE_PROVIDER )->first();

            if ( !$providerRole ) {
                return ServiceResult::error(
                    OperationStatus::ERROR,
                    'Role provider não encontrado no banco de dados',
                );
            }

            $user->roles()->attach( $providerRole->id, [
                'tenant_id'  => $tenant->id,
                'created_at' => now(),
                'updated_at' => now()
            ] );

            // Find Trial Plan
            $plan = Plan::where( 'slug', PLAN_SLUG_TRIAL )->first();

            if ( !$plan ) {
                $plan = Plan::where( 'status', true )->where( 'price', 0.00 )->first();
            }

            if ( !$plan ) {
                return ServiceResult::error(
                    OperationStatus::ERROR,
                    'Plano trial não encontrado. Entre em contato com nosso suporte para ativar seu acesso gratuito.',
                );
            }

            // Create Plan Subscription
            $planSubscription = new PlanSubscription( [
                'tenant_id'          => $tenant->id,
                'plan_id'            => $plan->id,
                'user_id'            => $user->id,
                'provider_id'        => $savedProvider->id,
                'status'             => SUBSCRIPTION_STATUS_ACTIVE,
                'transaction_amount' => $plan->price ?? 0.00,
                'start_date'         => now(),
                'end_date'           => now()->addDays( TRIAL_DAYS ),
                'transaction_date'   => now(),
                'payment_method'     => PAYMENT_METHOD_TRIAL,
                'payment_id'         => 'TRIAL_' . uniqid(),
                'public_hash'        => 'TRIAL_HASH_' . uniqid(),
            ] );

            $savedSubscription = $this->planRepository->saveSubscription( $planSubscription );

            return ServiceResult::success( [
                'provider'     => $savedProvider,
                'role'         => $providerRole,
                'plan'         => $plan,
                'subscription' => $savedSubscription,
            ], 'Provider criado com sucesso.' );

        } catch ( Exception $e ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao criar provider: ' . $e->getMessage()
            );
        }
    }

    /**
     * Cria um tenant único para o usuário durante o registro.
     *
     * @param array $userData Dados do usuário
     * @return ServiceResult Resultado da operação
     */
    private function createTenant( array $userData ): ServiceResult
    {
        try {
            $tenantName = $this->generateUniqueTenantName( $userData[ 'first_name' ], $userData[ 'last_name' ] );

            $tenant = new Tenant( [
                'name'      => $tenantName,
                'is_active' => true,
            ] );

            $savedTenant = $this->tenantRepository->create( $tenant->toArray() );

            return ServiceResult::success( $savedTenant, 'Tenant criado com sucesso.' );

        } catch ( Exception $e ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao criar tenant: ' . $e->getMessage()
            );
        }
    }

    /**
     * Cria um usuário no sistema durante o registro.
     *
     * @param array $userData Dados do usuário
     * @param Tenant $tenant Tenant do usuário
     * @return ServiceResult Resultado da operação
     */
    private function createUser( array $userData, Tenant $tenant ): ServiceResult
    {
        try {
            // Handle password: let the model cast handle hashing for regular registration
            // For social registration, password is null
            $password            = isset( $userData[ 'password' ] ) && $userData[ 'password' ] !== null
                ? $userData[ 'password' ]  // Plain password, model will hash it
                : null;
            $userDataForCreation = [
                'tenant_id' => $tenant->id,
                'email'     => $userData[ 'email' ],
                'password'  => $password,
                'is_active' => true,
            ];
            $savedUser           = $this->userRepository->create( $userDataForCreation );

            return ServiceResult::success( $savedUser, 'Usuário criado com sucesso.' );

        } catch ( Exception $e ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao criar usuário: ' . $e->getMessage()
            );
        }
    }

    /**
     * Gera um nome único para o tenant baseado no nome do usuário.
     *
     * @param string $firstName Primeiro nome
     * @param string $lastName Sobrenome
     * @return string Nome único para o tenant
     */
    private function generateUniqueTenantName( string $firstName, string $lastName ): string
    {
        $baseName   = Str::slug( $firstName . '-' . $lastName );
        $tenantName = $baseName;
        $counter    = 1;

        while ( $this->tenantRepository->findByName( $tenantName ) ) {
            $tenantName = $baseName . '-' . $counter;
            $counter++;
        }

        return $tenantName;
    }

}
