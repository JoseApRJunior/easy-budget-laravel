<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\Service\ServiceDTO;
use App\Models\Service;
use App\Repositories\Abstracts\AbstractTenantRepository;
use App\Repositories\Traits\RepositoryFiltersTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Repositório para gerenciamento de serviços.
 *
 * Estende AbstractTenantRepository para operações tenant-aware
 * com isolamento automático de dados por empresa.
 */
class ServiceRepository extends AbstractTenantRepository
{
    use RepositoryFiltersTrait;

    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new Service;
    }

    /**
     * Lista serviços por status dentro do tenant atual.
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
     * Lista serviços por provider dentro do tenant atual.
     */
    public function listByProviderId(int $providerId, ?array $orderBy = null): Collection
    {
        return $this->model->newQuery()
            ->where('provider_id', $providerId)
            ->when($orderBy, fn ($q) => $this->applyOrderBy($q, $orderBy))
            ->get();
    }

    /**
     * Conta serviços agrupados por status dentro do tenant atual.
     */
    public function countByStatus(): array
    {
        return $this->model->newQuery()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    /**
     * Conta serviços ativos dentro do tenant atual.
     */
    public function countActive(): int
    {
        return $this->model->newQuery()
            ->where('status', 'active')
            ->count();
    }

    /**
     * Conta serviços por categoria dentro do tenant atual.
     */
    public function countByCategory(int $categoryId): int
    {
        return $this->model->newQuery()
            ->where('category_id', $categoryId)
            ->count();
    }

    /**
     * Atualiza o status de todos os serviços vinculados a um orçamento.
     */
    public function updateStatusByBudgetId(int $budgetId, string $status): void
    {
        $this->model->newQuery()->where('budget_id', $budgetId)->update(['status' => $status]);
    }

    /**
     * Busca um serviço por código com relações opcionais.
     */
    public function findByCode(string $code, array $with = []): ?Service
    {
        return $this->model->newQuery()
            ->where('code', $code)
            ->when(! empty($with), fn ($q) => $q->with($with))
            ->first();
    }

    /**
     * Busca serviços ativos dentro do tenant atual.
     */
    public function findActive(?array $orderBy = null): Collection
    {
        return $this->model->newQuery()
            ->where('status', 'active')
            ->when($orderBy, fn ($q) => $this->applyOrderBy($q, $orderBy))
            ->get();
    }

    /**
     * Retorna serviços paginados com filtros avançados.
     */
    public function getPaginated(
        array $filters = [],
        int $perPage = 15,
        array $with = [],
        ?array $orderBy = null,
    ): \Illuminate\Pagination\LengthAwarePaginator {
        return $this->model->newQuery()
            ->when(! empty($with), fn ($q) => $q->with($with))
            ->tap(fn ($q) => $this->applyAllServiceFilters($q, $filters))
            ->tap(fn ($q) => $this->applySoftDeleteFilter($q, $filters))
            ->when($orderBy, fn ($q) => $this->applyOrderBy($q, $orderBy), fn ($q) => $q->latest())
            ->paginate($this->getEffectivePerPage($filters, $perPage));
    }

    /**
     * Busca serviços com filtros avançados, paginação e eager loading.
     */
    public function getFiltered(array $filters = [], ?array $orderBy = null, ?int $limit = null): Collection
    {
        return $this->model->newQuery()
            ->tap(fn ($q) => $this->applyAllServiceFilters($q, $filters))
            ->with(['category', 'budget.customer', 'serviceStatus'])
            ->when($orderBy, fn ($q) => $this->applyOrderBy($q, $orderBy), fn ($q) => $q->latest())
            ->when($limit, fn ($q) => $q->limit($limit))
            ->get();
    }

    /**
     * Aplica todos os filtros de serviço.
     */
    protected function applyAllServiceFilters(Builder $query, array $filters): void
    {
        if (isset($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['category_id']) && $filters['category_id'] !== 'all') {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['price_min'])) {
            $query->where('total', '>=', $filters['price_min']);
        }

        if (isset($filters['price_max'])) {
            $query->where('total', '<=', $filters['price_max']);
        }

        $this->applyDateRangeFilter($query, $filters, 'created_at', 'date_from', 'date_to');
        $this->applyDateRangeFilter($query, $filters, 'created_at', 'start_date', 'end_date');
        $this->applySearchFilter($query, $filters, ['code', 'description']);
    }

    /**
     * Busca serviços de um mês específico por tenant.
     */
    public function getServicesByMonth(int $month, int $year): Collection
    {
        return $this->model->newQuery()
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->get();
    }

    /**
     * Cria um serviço a partir de um DTO.
     */
    public function createFromDTO(ServiceDTO $dto): Model
    {
        return $this->create($dto->toDatabaseArray());
    }

    /**
     * Atualiza um serviço a partir de um DTO.
     */
    public function updateFromDTO(int $id, ServiceDTO $dto): ?Model
    {
        $data = $dto->toDatabaseArray();
        $filteredData = array_filter($data, fn ($value) => $value !== null);

        return $this->update($id, $filteredData);
    }
}
