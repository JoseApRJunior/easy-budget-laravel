<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Budget;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BudgetRepository extends AbstractTenantRepository
{
    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new Budget();
    }

    /**
     * Lista budgets por status dentro do tenant atual.
     *
     * @param array<string> $statuses Lista de status
     * @param array<string, string>|null $orderBy Ordenação
     * @param int|null $limit Limite de registros
     * @return \Illuminate\Database\Eloquent\Collection<int, Budget> Budgets encontrados
     */
    public function listByStatuses(array $statuses, ?array $orderBy = null, ?int $limit = null): Collection
    {
        return $this->getAllByTenant(
            ['status' => $statuses],
            $orderBy,
            $limit,
        );
    }

    /**
     * Conta budgets por status dentro do tenant atual.
     *
     * @param string $status Status dos budgets
     * @param array $filters Filtros adicionais
     * @return int Número de budgets
     */
    public function countByStatus(string $status, array $filters = []): int
    {
        $queryFilters = ['status' => $status];

        // Aplicar filtros de data se fornecidos
        if (!empty($filters['date_from'])) {
            $queryFilters['created_at_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $queryFilters['created_at_to'] = $filters['date_to'];
        }

        return $this->countByTenant($queryFilters);
    }

    /**
     * Lista budgets por cliente dentro do tenant atual.
     *
     * @param int $customerId ID do cliente
     * @param array<string, string>|null $orderBy Ordenação
     * @return \Illuminate\Database\Eloquent\Collection<int, Budget> Budgets do cliente
     */
    public function listByCustomerId(int $customerId, ?array $orderBy = null): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getAllByTenant(
            ['customer_id' => $customerId],
            $orderBy,
        );
    }

    /**
     * Lista budgets com filtros (compatibilidade com service).
     *
     * @param array<string, mixed> $filters Filtros a aplicar
     * @param array<string, string>|null $orderBy Ordenação
     * @param int|null $limit Limite de registros
     * @param int|null $offset Offset para paginação
     * @return \Illuminate\Database\Eloquent\Collection<int, Budget> Budgets filtrados
     */
    public function listByFilters(array $filters = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getAllByTenant($filters, $orderBy, $limit, $offset);
    }

    /**
     * Busca orçamentos recentes.
     */
    public function getRecentBudgets(int $limit = 10): Collection
    {
        return $this->model->newQuery()
            ->with(['customer.commonData'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Busca o último orçamento criado em um determinado mês/ano.
     */
    public function getLastBudgetByMonth(string $year, string $month): ?Budget
    {
        return $this->model->newQuery()
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * Busca um orçamento por código.
     */
    public function findByCode(string $code, array $with = []): ?Budget
    {
        $query = $this->model->newQuery()->where('code', $code);

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->first();
    }

    /**
     * Obtém estatísticas de orçamentos para o dashboard.
     */
    public function getDashboardStats(): array
    {
        $baseQuery = $this->model->newQuery();

        return [
            'total_count'    => (clone $baseQuery)->count(),
            'pending_count'  => (clone $baseQuery)->where('status', \App\Enums\BudgetStatus::PENDING->value)->count(),
            'approved_count' => (clone $baseQuery)->where('status', \App\Enums\BudgetStatus::APPROVED->value)->count(),
            'rejected_count' => (clone $baseQuery)->where('status', \App\Enums\BudgetStatus::REJECTED->value)->count(),
            'total_value'    => (float) (clone $baseQuery)->sum('total'),
            'approved_value' => (float) (clone $baseQuery)->where('status', \App\Enums\BudgetStatus::APPROVED->value)->sum('total'),
            'recent_budgets' => $this->getRecentBudgets(5),
        ];
    }

    /**
     * Cria um orçamento a partir de um DTO.
     */
    public function createFromDTO(\App\DTOs\Budget\BudgetDTO $dto): Model
    {
        return $this->create($dto->toArrayWithoutNulls());
    }

    /**
     * Atualiza um orçamento a partir de um DTO.
     */
    public function updateFromDTO(int $id, \App\DTOs\Budget\BudgetDTO $dto): ?Model
    {
        return $this->update($id, $dto->toArrayWithoutNulls());
    }

    /**
     * Paginação de orçamentos com filtros.
     */
    public function getPaginatedBudgets(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with(['customer.commonData']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhereHas('customer.commonData', function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%");
                    });
            });
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Calcula a receita mensal.
     */
    public function getMonthlyRevenue(int $month, int $year): float
    {
        return (float) $this->model->newQuery()
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->whereIn('status', [\App\Enums\BudgetStatus::APPROVED, \App\Enums\BudgetStatus::COMPLETED])
            ->sum('total');
    }

    /**
     * Busca orçamentos pendentes.
     */
    public function getPendingBudgets(int $limit = 10): Collection
    {
        return $this->model->newQuery()
            ->where('status', \App\Enums\BudgetStatus::PENDING)
            ->with('customer')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Busca orçamentos com pagamento em atraso.
     */
    public function getOverduePayments(int $limit = 10): Collection
    {
        return $this->model->newQuery()
            ->where('due_date', '<', now())
            ->whereIn('status', [\App\Enums\BudgetStatus::APPROVED, \App\Enums\BudgetStatus::PENDING])
            ->with('customer')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Busca orçamentos de um mês específico.
     */
    public function getBudgetsByMonth(int $month, int $year): Collection
    {
        return $this->model->newQuery()
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->get();
    }
}
