<?php

declare(strict_types=1);

namespace App\Services\Application;

use App\Models\Activity;
use App\Models\Address;
use App\Models\Budget;
use App\Models\CommonData;
use App\Models\Contact;
use App\Models\Provider;
use App\Models\User;
use App\Services\FinancialSummary;
use Illuminate\Http\UploadedFile;
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
        $user = auth()->user();

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
    public function getProviderForUpdate(): array
    {
        $user     = auth()->user();
        $provider = $user->provider;

        if ( !$provider ) {
            throw new \Exception( 'Provider não encontrado' );
        }

        return [
            'provider'          => $provider->load( [ 'user', 'commonData', 'contact', 'address' ] ),
            'areas_of_activity' => \App\Models\AreaOfActivity::where( 'tenant_id', $user->tenant_id )->get(),
            'professions'       => \App\Models\Profession::where( 'tenant_id', $user->tenant_id )->get(),
        ];
    }

    /**
     * Update provider data.
     */
    public function updateProvider( array $data ): void
    {
        $user     = auth()->user();
        $provider = $user->provider;

        if ( !$provider ) {
            throw new \Exception( 'Provider não encontrado' );
        }

        DB::transaction( function () use ($provider, $data, $user) {
            // Handle logo upload
            if ( isset( $data[ 'logo' ] ) && $data[ 'logo' ] instanceof UploadedFile ) {
                $logoPath       = $this->handleLogoUpload( $data[ 'logo' ], $user->logo );
                $data[ 'logo' ] = $logoPath;
            }

            // Update User
            $user->update( [
                'email' => $data[ 'email' ],
                'logo'  => $data[ 'logo' ] ?? $user->logo,
            ] );

            // Update CommonData
            $provider->commonData->update( [
                'first_name'          => $data[ 'first_name' ],
                'last_name'           => $data[ 'last_name' ],
                'birth_date'          => $data[ 'birth_date' ] ?? null,
                'cnpj'                => $data[ 'cnpj' ] ?? null,
                'cpf'                 => $data[ 'cpf' ] ?? null,
                'company_name'        => $data[ 'company_name' ] ?? null,
                'description'         => $data[ 'description' ] ?? null,
                'area_of_activity_id' => $data[ 'area_of_activity_id' ] ?? null,
                'profession_id'       => $data[ 'profession_id' ] ?? null,
            ] );

            // Update Contact
            $provider->contact->update( [
                'email'          => $data[ 'email' ], // Sync with user email
                'phone'          => $data[ 'phone' ] ?? null,
                'email_business' => $data[ 'email_business' ] ?? null,
                'phone_business' => $data[ 'phone_business' ] ?? null,
                'website'        => $data[ 'website' ] ?? null,
            ] );

            // Update Address
            $provider->address->update( [
                'address'        => $data[ 'address' ],
                'address_number' => $data[ 'address_number' ] ?? null,
                'neighborhood'   => $data[ 'neighborhood' ],
                'city'           => $data[ 'city' ],
                'state'          => $data[ 'state' ],
                'cep'            => $data[ 'cep' ],
            ] );

            // Log activity
            $this->activityService->logActivity(
                $user->tenant_id,
                $user->id,
                'provider_updated',
                'provider',
                $provider->id,
                "Prestador {$data[ 'first_name' ]} {$data[ 'last_name' ]} atualizado com sucesso!",
                $data,
            );
        } );
    }

    /**
     * Change provider password.
     */
    public function changePassword( string $newPassword ): void
    {
        $user = auth()->user();

        $user->update( [
            'password' => Hash::make( $newPassword )
        ] );

        // Log activity
        $this->activityService->logActivity(
            $user->tenant_id,
            $user->id,
            'user_updated',
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
