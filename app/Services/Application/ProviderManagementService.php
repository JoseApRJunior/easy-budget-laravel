<?php

declare(strict_types=1);

namespace App\Services\Application;

use App\Enums\OperationStatus;
use App\Models\Activity;
use App\Models\AreaOfActivity;
use App\Models\Budget;
use App\Models\Profession;
use App\Models\Provider;
use App\Models\User;
use App\Services\Domain\ActivityService;
use App\Services\Infrastructure\FinancialSummary;
use App\Support\ServiceResult;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProviderManagementService
{
    public function __construct(
        private FinancialSummary $financialSummary,
        private ActivityService $activityService,
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
                    'cnpj'                => $data[ 'cnpj' ] ?? $provider->commonData->cnpj,
                    'cpf'                 => $data[ 'cpf' ] ?? $provider->commonData->cpf,
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
        $user = Auth::user();

        $user->update( [
            'password' => Hash::make( $newPassword )
        ] );

        // Log activity
        $this->activityService->logActivity(
            $user->tenant_id,
            $user->id,
            'password_changed',
            'user',
            $user->id,
            'Senha atualizada com sucesso!',
            [ 'email' => $user->email ],
        );
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
        $services = \App\Models\Service::where( 'tenant_id', $tenantId )
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
        $customers = \App\Models\Customer::where( 'tenant_id', $tenantId )
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

}
