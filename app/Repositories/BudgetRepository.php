<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\Budget\BudgetDTO;
use App\Models\Budget;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Support\Carbon;
use App\Repositories\Traits\RepositoryFiltersTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class BudgetRepository extends AbstractTenantRepository
{
    use RepositoryFiltersTrait;

    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new Budget;
    }

    /**
     * Lista budgets por status dentro do tenant atual.
     *
     * @param  array<string>  $statuses  Lista de status
     * @param  array<string, string>|null  $orderBy  Ordenação
     * @param  int|null  $limit  Limite de registros
     * @return \Illuminate\Database\Eloquent\Collection<int, Budget> Budgets encontrados
     */
    public function listByStatuses(array $statuses, ?array $orderBy = null, ?int $limit = null): Collection
    {
        return $this->model->newQuery()
            ->whereIn('status', $statuses)
            ->when($orderBy, fn ($q) => $this->applyOrderBy($q, $orderBy))
            ->when($limit, fn ($q) => $q->limit($limit))
            ->get();
    }

    /**
     * Conta budgets por status dentro do tenant atual.
     *
     * @param  string  $status  Status dos budgets
     * @param  array  $filters  Filtros adicionais
     * @return int Número de budgets
     */
    public function countByStatus(string $status, array $filters = []): int
    {
        $query = $this->model->newQuery()->where('status', $status);

        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->count();
    }

    /**
     * Lista budgets por cliente dentro do tenant atual.
     *
     * @param  int  $customerId  ID do cliente
     * @param  array<string, string>|null  $orderBy  Ordenação
     * @return \Illuminate\Database\Eloquent\Collection<int, Budget> Budgets do cliente
     */
    public function listByCustomerId(int $customerId, ?array $orderBy = null): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->newQuery()
            ->where('customer_id', $customerId)
            ->when($orderBy, fn ($q) => $this->applyOrderBy($q, $orderBy))
            ->get();
    }

    /**
     * Lista budgets com filtros (compatibilidade com service).
     *
     * @param  array<string, mixed>  $filters  Filtros a aplicar
     * @param  array<string, string>|null  $orderBy  Ordenação
     * @param  int|null  $limit  Limite de registros
     * @param  int|null  $offset  Offset para paginação
     * @return \Illuminate\Database\Eloquent\Collection<int, Budget> Budgets filtrados
     */
    public function listByFilters(array $filters = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->newQuery()
            ->tap(fn ($q) => $this->applyFilters($q, $filters))
            ->when($orderBy, fn ($q) => $this->applyOrderBy($q, $orderBy))
            ->when($limit, fn ($q) => $q->limit($limit))
            ->when($offset, fn ($q) => $q->offset($offset))
            ->get();
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

        if (! empty($with)) {
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
            'total_count' => (clone $baseQuery)->count(),
            'pending_count' => (clone $baseQuery)->where('status', \App\Enums\BudgetStatus::PENDING->value)->count(),
            'approved_count' => (clone $baseQuery)->where('status', \App\Enums\BudgetStatus::APPROVED->value)->count(),
            'rejected_count' => (clone $baseQuery)->where('status', \App\Enums\BudgetStatus::REJECTED->value)->count(),
            'total_value' => (float) (clone $baseQuery)->sum('total'),
            'approved_value' => (float) (clone $baseQuery)->where('status', \App\Enums\BudgetStatus::APPROVED->value)->sum('total'),
            'recent_budgets' => $this->getRecentBudgets(5),
        ];
    }

    /**
     * Cria um orçamento a partir de um DTO.
     */
    public function createFromDTO(BudgetDTO $dto): Model
    {
        return $this->create($dto->toArrayWithoutNulls());
    }

    /**
     * Atualiza um orçamento a partir de um DTO.
     */
    public function updateFromDTO(int $id, BudgetDTO $dto): ?Model
    {
        return $this->update($id, $dto->toArrayWithoutNulls());
    }

    /**
     * Paginação de orçamentos com filtros.
     */
    public function getPaginatedBudgets(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with(['customer.commonData'])
            ->tap(fn ($q) => $this->applyAllBudgetFilters($q, $filters))
            ->latest()
            ->paginate($this->getEffectivePerPage($filters, $perPage));
    }

    /**
     * Aplica todos os filtros de orçamento de forma segura.
     */
    protected function applyAllBudgetFilters(Builder $query, array $filters): void
    {
        $this->applySearchFilter($query, $filters, ['code']);

        // Busca por nome do cliente
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->orWhereHas('customer.commonData', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        $query->when(! empty($filters['status']), fn ($q) => $q->where('status', $filters['status']));
        $query->when(! empty($filters['customer_id']), fn ($q) => $q->where('customer_id', $filters['customer_id']));

        // Filtros de data
        $query->when(! empty($filters['date_from']), fn ($q) => $q->where('created_at', '>=', $filters['date_from']));
        $query->when(! empty($filters['date_to']), fn ($q) => $q->where('created_at', '<=', $filters['date_to']));
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
