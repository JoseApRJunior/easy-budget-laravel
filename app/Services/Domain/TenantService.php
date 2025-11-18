<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Models\Tenant;
use App\Repositories\TenantRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Exception;

/**
 * Serviço para gerenciamento de tenants.
 *
 * Esta classe implementa toda a lógica de negócio relacionada a tenants.
 */
class TenantService extends AbstractBaseService
{
    public function __construct( TenantRepository $repository )
    {
        parent::__construct( $repository );
    }

    /**
     * Define o Model a ser utilizado pelo Service.
     */
    public function getModel(): string
    {
        return Tenant::class;
    }

    /**
     * Obtém dados financeiros do tenant
     */
    public function getFinancialData(int $tenantId): array
    {
        try {
            $tenant = $this->findById($tenantId);
            
            if (!$tenant) {
                return ServiceResult::error('Tenant não encontrado');
            }

            // Dados financeiros básicos - pode ser expandido conforme necessário
            $financialData = [
                'tenant' => $tenant,
                'total_revenue' => 0, // Implementar lógica de receita
                'total_expenses' => 0, // Implementar lógica de despesas
                'active_subscriptions' => $tenant->subscriptions()->where('status', 'active')->count(),
                'total_users' => $tenant->users()->count(),
            ];

            return ServiceResult::success($financialData);
        } catch (Exception $e) {
            return ServiceResult::error('Erro ao obter dados financeiros: ' . $e->getMessage());
        }
    }

    /**
     * Obtém analytics do tenant
     */
    public function getAnalytics(int $tenantId): array
    {
        try {
            $tenant = $this->findById($tenantId);
            
            if (!$tenant) {
                return ServiceResult::error('Tenant não encontrado');
            }

            // Analytics básicos - pode ser expandido conforme necessário
            $analytics = [
                'tenant' => $tenant,
                'total_users' => $tenant->users()->count(),
                'active_users' => $tenant->users()->where('status', 'active')->count(),
                'total_customers' => $tenant->customers()->count(),
                'total_budgets' => $tenant->budgets()->count(),
                'total_services' => $tenant->services()->count(),
                'total_invoices' => $tenant->invoices()->count(),
            ];

            return ServiceResult::success($analytics);
        } catch (Exception $e) {
            return ServiceResult::error('Erro ao obter analytics: ' . $e->getMessage());
        }
    }

    /**
     * Obtém dados de cobrança do tenant
     */
    public function getBillingData(int $tenantId): array
    {
        try {
            $tenant = $this->findById($tenantId);
            
            if (!$tenant) {
                return ServiceResult::error('Tenant não encontrado');
            }

            // Dados de cobrança básicos - pode ser expandido conforme necessário
            $billingData = [
                'tenant' => $tenant,
                'current_plan' => $tenant->subscriptions()->where('status', 'active')->first()?->plan,
                'subscription_history' => $tenant->subscriptions()->with('plan')->latest()->get(),
                'payment_history' => [], // Implementar histórico de pagamentos
                'next_billing_date' => null, // Implementar próxima data de cobrança
            ];

            return ServiceResult::success($billingData);
        } catch (Exception $e) {
            return ServiceResult::error('Erro ao obter dados de cobrança: ' . $e->getMessage());
        }
    }
}