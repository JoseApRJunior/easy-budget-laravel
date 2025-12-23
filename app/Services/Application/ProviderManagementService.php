<?php

declare(strict_types=1);

namespace App\Services\Application;

use App\Enums\OperationStatus;

use App\Models\Address;
use App\Models\AreaOfActivity;
use App\Models\AuditLog;
use App\Models\Budget;
use App\Models\BusinessData;
use App\Models\CommonData;
use App\Models\Contact;
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
use App\Services\Domain\ProviderService;
use App\Services\Infrastructure\FileUploadService;
use App\Services\Infrastructure\FinancialSummary;
use App\Services\Shared\EntityDataService;
use App\Support\ServiceResult;
use Carbon\Carbon;
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

        private ProviderService $providerService,
        private EntityDataService $entityDataService,
        private FileUploadService $fileUploadService,
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
        $activities = AuditLog::where( 'tenant_id', $tenantId )
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

        // Buscar compromissos do dia
        $events = [];
        if (class_exists('App\Models\Schedule')) {
            $events = \App\Models\Schedule::where( 'tenant_id', $tenantId )
                ->with( 'service' ) // Carregar o relacionamento com o serviço
                ->whereDate( 'start_date_time', today() )
                ->where(function($query) {
                    // Verificar se end_date_time é nulo ou no futuro (compromissos não concluídos)
                    $query->whereNull('end_date_time')
                          ->orWhere('end_date_time', '>', now());
                })
                ->orderBy( 'start_date_time' )
                ->limit( 5 )
                ->get();
        }

        return [
            'budgets'           => $budgets,
            'activities'        => $activities,
            'financial_summary' => $financialSummary,
            'events'            => $events,
        ];
    }

    /**
     * Get provider data for update form.
     */
    public function getProviderForUpdate(): ServiceResult
    {
        $user     = Auth::user();
        $provider = $this->providerService->getByUserId( $user->id, $user->tenant_id );

        if ( !$provider ) {
            return ServiceResult::error( OperationStatus::NOT_FOUND, 'Provider não encontrado' );
        }

        return ServiceResult::success( [
            'provider'          => $provider,
            'areas_of_activity' => AreaOfActivity::get(),
            'professions'       => Profession::get(),
        ] );
    }

    /**
     * Update provider data.
     */
    public function updateProvider( array $data ): ServiceResult
    {
        try {
            $user     = Auth::user();
            $provider = $user->provider;

            if ( !$provider ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Provider não encontrado' );
            }

            // Load relationships if not already loaded
            $provider->load( [ 'commonData', 'contact', 'address', 'businessData' ] );

            // Transação garante rollback automático se qualquer operação falhar
            DB::transaction( function () use ($provider, $data, $user) {
                // Handle logo upload
                if ( isset( $data[ 'logo' ] ) && $data[ 'logo' ] instanceof UploadedFile ) {
                    $logoPath       = $this->fileUploadService->uploadProviderLogo( $data[ 'logo' ], $user->logo );
                    $data[ 'logo' ] = $logoPath;
                }

                // Update User (email and logo only)
                $userUpdate = array_filter( [
                    'email' => $data[ 'email' ] ?? null,
                    'logo'  => $data[ 'logo' ] ?? null,
                ], fn( $value ) => $value !== null );

                if ( !empty( $userUpdate ) ) {
                    $this->userRepository->update( $user->id, $userUpdate );
                }

                // Detectar tipo (PF ou PJ)
                $type = !empty( $data[ 'cnpj' ] ) ? CommonData::TYPE_COMPANY : CommonData::TYPE_INDIVIDUAL;

                // Limpar máscaras e converter datas
                $data[ 'cpf' ]        = !empty($data['cpf']) ? preg_replace('/\D/', '', $data['cpf']) : null;
                $data[ 'cnpj' ]       = !empty($data['cnpj']) ? preg_replace('/\D/', '', $data['cnpj']) : null;
                $data[ 'birth_date' ] = !empty( $data[ 'birth_date' ] ) ? Carbon::createFromFormat( 'd/m/Y', $data[ 'birth_date' ] )->format( 'Y-m-d' ) : null;

                // Atualizar CommonData (preserva dados de ambos os tipos)
                if ( $provider->commonData ) {
                    $provider->commonData->update( [
                        'type'                => $type,
                        'first_name'          => $data[ 'first_name' ] ?? null,
                        'last_name'           => $data[ 'last_name' ] ?? null,
                        'cpf'                 => $data[ 'cpf' ],
                        'birth_date'          => $data[ 'birth_date' ],
                        'company_name'        => $data[ 'company_name' ] ?? null,
                        'cnpj'                => $data[ 'cnpj' ],
                        'description'         => $data[ 'description' ] ?? null,
                        'area_of_activity_id' => $data[ 'area_of_activity_id' ] ?? null,
                        'profession_id'       => $data[ 'profession_id' ] ?? null,
                    ] );
                }

                // Atualizar Contact
                if ( $provider->contact ) {
                    $provider->contact->update( [
                        'email_personal' => $data[ 'email_personal' ] ?? $data[ 'email' ] ?? null,
                        'phone_personal' => $data[ 'phone_personal' ] ?? $data[ 'phone' ] ?? null,
                        'email_business' => $data[ 'email_business' ] ?? null,
                        'phone_business' => $data[ 'phone_business' ] ?? null,
                        'website'        => $data[ 'website' ] ?? null,
                    ] );
                }

                // Atualizar Address
                if ( $provider->address ) {
                    $provider->address->update( [
                        'address'        => $data[ 'address' ] ?? null,
                        'address_number' => $data[ 'address_number' ] ?? null,
                        'neighborhood'   => $data[ 'neighborhood' ] ?? null,
                        'city'           => $data[ 'city' ] ?? null,
                        'state'          => $data[ 'state' ] ?? null,
                        'cep'            => $data[ 'cep' ] ?? null,
                    ] );
                }

                // Atualizar dados empresariais (sempre atualiza/cria se for PJ)
                if ( $type === CommonData::TYPE_COMPANY ) {
                    $businessData = [
                        'fantasy_name'           => $data[ 'fantasy_name' ] ?? null,
                        'state_registration'     => $data[ 'state_registration' ] ?? null,
                        'municipal_registration' => $data[ 'municipal_registration' ] ?? null,
                        'founding_date'          => !empty( $data[ 'founding_date' ] ) ? Carbon::createFromFormat( 'd/m/Y', $data[ 'founding_date' ] )->format( 'Y-m-d' ) : null,
                        'industry'               => $data[ 'industry' ] ?? null,
                        'company_size'           => $data[ 'company_size' ] ?? null,
                        'notes'                  => $data[ 'notes' ] ?? null,
                    ];

                    if ( $provider->businessData ) {
                        $provider->businessData->update( $businessData );
                    } else {
                        BusinessData::create( array_merge( $businessData, [
                            'tenant_id'   => $provider->tenant_id,
                            'provider_id' => $provider->id,
                        ] ) );
                    }
                }
            } );

            $provider->refresh();
            return ServiceResult::success( $provider, 'Provider atualizado com sucesso' );

        } catch ( Exception $e ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao atualizar provider: ' . $e->getMessage()
            );
        }
    }

    /**
     * Change provider password.
     */
    public function changePassword( string $newPassword ): void
    {
        $user = Auth::user();

        $this->userRepository->update( $user->id, [
            'password' => Hash::make( $newPassword )
        ] );

        // Activity logged automatically by UserObserver
    }

    /**
     * Get provider by user ID.
     */
    public function getProviderByUserId( int $userId, int $tenantId ): ?Provider
    {
        return $this->providerService->getByUserId( $userId, $tenantId );
    }

    /**
     * Check if email exists for another user.
     */
    public function isEmailAvailable( string $email, int $excludeUserId, int $tenantId ): bool
    {
        return $this->providerService->isEmailAvailable( $email, $excludeUserId, $tenantId );
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
            ->sum( 'total' );

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
            'total_value'      => $budgets->sum( 'total' ),
            'average_value'    => $budgets->count() > 0 ? $budgets->avg( 'total' ) : 0,
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
            'total_value'        => $services->sum( 'total' ),
            'average_value'      => $services->count() > 0 ? $services->avg( 'total' ) : 0,
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
            ->with( [ 'budgets', 'invoices', 'commonData', 'contact' ] )
            ->latest()
            ->limit( 50 )
            ->get();

        // Estatísticas dos clientes
        $customerStats = [
            'total_customers'     => $customers->count(),
            'active_customers'    => $customers->where( 'status', 'active' )->count(),
            'inactive_customers'  => $customers->where( 'status', 'inactive' )->count(),
            'new_customers_month' => $customers->filter( function ( $customer ) {
                return $customer->created_at->month === now()->month && 
                       $customer->created_at->year === now()->year;
            } )->count(),
            'total_budgets'       => $customers->sum( function ( $customer ) {
                return $customer->budgets->count();
            } ),
            'total_invoices'      => $customers->sum( function ( $customer ) {
                return $customer->invoices->count();
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
            // Criar Provider primeiro
            $provider = Provider::firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'user_id'   => $user->id,
                ],
                [
                    'terms_accepted' => $userData[ 'terms_accepted' ],
                ]
            );

            // Criar CommonData vinculado ao Provider
            $commonData = CommonData::firstOrCreate(
                [
                    'tenant_id'   => $tenant->id,
                    'provider_id' => $provider->id,
                ],
                [
                    'type'         => CommonData::TYPE_INDIVIDUAL,
                    'first_name'   => $userData[ 'first_name' ],
                    'last_name'    => $userData[ 'last_name' ],
                    'cpf'          => null,
                    'birth_date'   => null,
                    'company_name' => null,
                    'cnpj'         => null,
                    'description'  => null,
                ]
            );

            // Criar Contact vinculado ao Provider
            $contact = Contact::firstOrCreate(
                [
                    'tenant_id'   => $tenant->id,
                    'provider_id' => $provider->id,
                ],
                [
                    'email_personal' => $userData[ 'email_personal' ] ?? $userData[ 'email' ],
                    'phone_personal' => $userData[ 'phone_personal' ] ?? $userData[ 'phone' ] ?? null,
                ]
            );

            // Criar Address vinculado ao Provider (vazio inicialmente)
            $address = Address::firstOrCreate(
                [
                    'tenant_id'   => $tenant->id,
                    'provider_id' => $provider->id,
                ],
                []
            );

            $savedProvider = $provider;

            // Assign Provider Role
            $providerRole = Role::where( 'name', ROLE_PROVIDER )->first();

            if ( !$providerRole ) {
                return ServiceResult::error(
                    OperationStatus::ERROR,
                    'Role provider não encontrado no banco de dados',
                );
            }

            $user->roles()->syncWithoutDetaching( [
                $providerRole->id => [
                    'tenant_id'  => $tenant->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
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
