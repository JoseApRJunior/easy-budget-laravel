<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\RepositoryInterface;
use App\Models\Budget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BudgetRepository extends AbstractRepository
{
    protected string $modelClass = Budget::class;

    /**
     * Lista budgets por status e tenant.
     */
    public function listByStatusAndTenantId( int $tenantId, array $statuses, ?array $orderBy = null, ?int $limit = null ): array
    {
        $query = $this->applyTenantFilter( $this->model::query(), $tenantId )
            ->whereIn( 'status', $statuses );
        if ( $orderBy ) {
            foreach ( $orderBy as $column => $direction ) {
                $query->orderBy( $column, $direction );
            }
        }
        if ( $limit ) {
            $query->limit( $limit );
        }
        return $query->get()->all();
    }

    /**
     * Conta budgets por status e tenant.
     */
    public function countByStatusByTenantId( int $tenantId, string $status ): int
    {
        return $this->applyTenantFilter( $this->model::query(), $tenantId )
            ->where( 'status', $status )
            ->count();
    }

    /**
     * Lista budgets por cliente e tenant.
     */
    public function listByCustomerIdAndTenantId( int $customerId, int $tenantId, ?array $orderBy = null ): array
    {
        $query = $this->applyTenantFilter( $this->model::query(), $tenantId )
            ->where( 'customer_id', $customerId );
        if ( $orderBy ) {
            foreach ( $orderBy as $column => $direction ) {
                $query->orderBy( $column, $direction );
            }
        }
        return $query->get()->all();
    }

    /**
     * Alias para listagem por tenant (compatibilidade com service).
     */
    public function listByTenantId( int $tenantId, array $filters = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array
    {
        return $this->findAllByTenantId( $tenantId, $filters, $orderBy, $limit, $offset );
    }

    // ========== MÉTODOS BASEADOS NO SISTEMA ANTIGO ==========

    /**
     * Busca último orçamento por tenant e mês para geração de código.
     */
    public function getLastBudgetByTenantAndMonth(int $tenantId, string $year, string $month): ?Budget
    {
        return $this->applyTenantFilter($this->model::query(), $tenantId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('code', 'desc')
            ->first();
    }

    /**
     * Busca orçamento com todos os detalhes (relacionamentos).
     */
    public function getBudgetWithFullDetails(int $budgetId, int $tenantId): ?Budget
    {
        return $this->applyTenantFilter($this->model::query(), $tenantId)
            ->with([
                'customer:id,name,email,phone,document',
                'category:id,name,color',
                'items:id,budget_id,description,quantity,price,total',
                'user:id,name,email',
                'activities' => function ($query) {
                    $query->orderBy('created_at', 'desc')->limit(10);
                },
                'payments:id,budget_id,amount,payment_date,status',
                'invoices:id,budget_id,invoice_number,amount,status'
            ])
            ->where('id', $budgetId)
            ->first();
    }

    /**
     * Lista orçamentos com filtros avançados.
     */
    public function getFilteredBudgets(int $tenantId, array $filters = []): Builder
    {
        $query = $this->applyTenantFilter($this->model::query(), $tenantId)
            ->with(['customer:id,name', 'category:id,name,color', 'user:id,name']);

        // Filtro por status
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        // Filtro por cliente
        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        // Filtro por categoria
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Filtro por usuário
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Filtro por período
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Filtro por valor mínimo
        if (!empty($filters['amount_min'])) {
            $query->where('total', '>=', $filters['amount_min']);
        }

        // Filtro por valor máximo
        if (!empty($filters['amount_max'])) {
            $query->where('total', '<=', $filters['amount_max']);
        }

        // Busca por texto (título, descrição, código)
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                  ->orWhere('description', 'like', $search)
                  ->orWhere('code', 'like', $search);
            });
        }

        // Ordenação
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query;
    }

    /**
     * Busca orçamentos por slug e tenant.
     */
    public function findBySlugAndTenant(string $slug, int $tenantId): ?Budget
    {
        return $this->applyTenantFilter($this->model::query(), $tenantId)
            ->with(['customer', 'category', 'items', 'user'])
            ->where('slug', $slug)
            ->first();
    }

    /**
     * Lista orçamentos ativos por tenant.
     */
    public function getActiveBudgetsByTenant(int $tenantId, int $limit = 10): Collection
    {
        return $this->applyTenantFilter($this->model::query(), $tenantId)
            ->with(['customer:id,name', 'category:id,name,color'])
            ->whereIn('status', ['pending', 'approved'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Busca orçamentos próximos ao vencimento.
     */
    public function getBudgetsNearExpiration(int $tenantId, int $days = 7): Collection
    {
        return $this->applyTenantFilter($this->model::query(), $tenantId)
            ->with(['customer:id,name,email', 'user:id,name'])
            ->where('status', 'approved')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays($days)])
            ->orderBy('expires_at', 'asc')
            ->get();
    }

    /**
     * Relatório de orçamentos por período.
     */
    public function getBudgetReportByPeriod(int $tenantId, string $startDate, string $endDate): array
    {
        $budgets = $this->applyTenantFilter($this->model::query(), $tenantId)
            ->select([
                'status',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as total_amount'),
                DB::raw('AVG(total) as avg_amount')
            ])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('status')
            ->get();

        $summary = [
            'total_budgets' => $budgets->sum('count'),
            'total_amount' => $budgets->sum('total_amount'),
            'avg_amount' => $budgets->avg('avg_amount'),
            'by_status' => $budgets->keyBy('status')->toArray()
        ];

        return $summary;
    }

    /**
     * Busca orçamentos duplicados (mesmo cliente, valor similar).
     */
    public function findPotentialDuplicates(int $tenantId, int $customerId, float $amount, int $excludeId = null): Collection
    {
        $query = $this->applyTenantFilter($this->model::query(), $tenantId)
            ->with(['customer:id,name'])
            ->where('customer_id', $customerId)
            ->whereBetween('total', [$amount * 0.95, $amount * 1.05]) // 5% de tolerância
            ->where('created_at', '>=', now()->subDays(30)); // Últimos 30 dias

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->get();
    }

    /**
     * Atualiza status em lote.
     */
    public function bulkUpdateStatus(array $budgetIds, string $status, int $tenantId, int $userId): int
    {
        return $this->applyTenantFilter($this->model::query(), $tenantId)
            ->whereIn('id', $budgetIds)
            ->update([
                'status' => $status,
                'status_updated_at' => now(),
                'status_updated_by' => $userId,
                'updated_at' => now()
            ]);
    }

    /**
     * Busca orçamentos por código.
     */
    public function findByCode(string $code, int $tenantId): ?Budget
    {
        return $this->applyTenantFilter($this->model::query(), $tenantId)
            ->with(['customer', 'category', 'items'])
            ->where('code', $code)
            ->first();
    }

    /**
     * Lista orçamentos com paginação customizada.
     */
    public function getPaginatedBudgets(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->getFilteredBudgets($tenantId, $filters);
        
        return $query->paginate($perPage, [
            'id', 'code', 'title', 'status', 'total', 'created_at',
            'customer_id', 'category_id', 'user_id'
        ]);
    }

    /**
     * Estatísticas de conversão de orçamentos.
     */
    public function getConversionStats(int $tenantId, string $period = '30 days'): array
    {
        $startDate = now()->sub($period);

        $stats = $this->applyTenantFilter($this->model::query(), $tenantId)
            ->select([
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed'),
                DB::raw('SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected'),
                DB::raw('AVG(total) as avg_value'),
                DB::raw('SUM(total) as total_value')
            ])
            ->where('created_at', '>=', $startDate)
            ->first();

        $conversionRate = $stats->total > 0 ? ($stats->approved / $stats->total) * 100 : 0;
        $completionRate = $stats->approved > 0 ? ($stats->completed / $stats->approved) * 100 : 0;

        return [
            'total_budgets' => $stats->total,
            'approved_budgets' => $stats->approved,
            'completed_budgets' => $stats->completed,
            'rejected_budgets' => $stats->rejected,
            'conversion_rate' => round($conversionRate, 2),
            'completion_rate' => round($completionRate, 2),
            'average_value' => $stats->avg_value,
            'total_value' => $stats->total_value,
            'period' => $period
        ];
    }
}
