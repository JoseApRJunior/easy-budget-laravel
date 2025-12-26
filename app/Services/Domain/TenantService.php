<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\OperationStatus;
use App\Models\Tenant;
use App\Repositories\TenantRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;

/**
 * Serviço para gerenciamento de tenants.
 *
 * Esta classe implementa toda a lógica de negócio relacionada a tenants.
 */
class TenantService extends AbstractBaseService
{
    public function __construct(
        private TenantRepository $tenantRepository
    ) {
        parent::__construct($tenantRepository);
    }

    /**
     * Obtém dados financeiros do tenant
     */
    public function getFinancialData(int $tenantId): ServiceResult
    {
        return $this->safeExecute(function () use ($tenantId) {
            $tenant = $this->tenantRepository->find($tenantId);

            if (!$tenant) {
                return $this->error(OperationStatus::NOT_FOUND, 'Tenant não encontrado');
            }

            // Dados financeiros básicos - pode ser expandido conforme necessário
            $financialData = [
                'tenant' => $tenant,
                'total_revenue' => 0, // Implementar lógica de receita
                'total_expenses' => 0, // Implementar lógica de despesas
                'active_subscriptions' => $tenant->planSubscriptions()->where('status', 'active')->count(),
                'total_users' => $tenant->users()->count(),
            ];

            return $this->success($financialData);
        }, 'Erro ao obter dados financeiros');
    }

    /**
     * Obtém analytics do tenant
     */
    public function getAnalytics(int $tenantId): ServiceResult
    {
        return $this->safeExecute(function () use ($tenantId) {
            $tenant = $this->tenantRepository->find($tenantId);

            if (!$tenant) {
                return $this->error(OperationStatus::NOT_FOUND, 'Tenant não encontrado');
            }

            // Analytics básicos - pode ser expandido conforme necessário
            $analytics = [
                'tenant' => $tenant,
                'total_users' => $tenant->users()->count(),
                'active_users' => $tenant->users()->where('is_active', true)->count(),
                'total_customers' => $tenant->customers()->count(),
                'total_budgets' => $tenant->budgets()->count(),
                'total_services' => $tenant->services()->count(),
                'total_invoices' => $tenant->invoices()->count(),
            ];

            return $this->success($analytics);
        }, 'Erro ao obter analytics');
    }

    /**
     * Obtém dados de cobrança do tenant
     */
    public function getBillingData(int $tenantId): ServiceResult
    {
        return $this->safeExecute(function () use ($tenantId) {
            $tenant = $this->tenantRepository->find($tenantId);

            if (!$tenant) {
                return $this->error(OperationStatus::NOT_FOUND, 'Tenant não encontrado');
            }

            // Dados de cobrança básicos
            $billingData = [
                'tenant' => $tenant,
                'active_subscription' => $tenant->planSubscriptions()->where('status', 'active')->first(),
                'invoices' => $tenant->invoices()->orderBy('created_at', 'desc')->limit(5)->get(),
            ];

            return $this->success($billingData);
        }, 'Erro ao obter dados de cobrança');
    }
}