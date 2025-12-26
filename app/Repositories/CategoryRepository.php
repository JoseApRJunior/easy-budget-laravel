<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Category;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Repositório para gerenciamento de categorias com arquitetura refinada.
 *
 * Categorias são isoladas por tenant - cada empresa gerencia suas próprias categorias.
 * Implementa a arquitetura padronizada com suporte a hierarquia e filtros avançados.
 */
class CategoryRepository extends AbstractTenantRepository
{
    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new Category;
    }

    /**
     * Busca categoria por slug dentro do tenant.
     */
    public function findBySlug(string $slug, bool $withTrashed = true): ?Model
    {
        return $this->findByTenantAndSlug($slug, $withTrashed);
    }

    /**
     * Verifica se slug existe dentro do tenant.
     */
    public function existsBySlug(string $slug, ?int $excludeId = null): bool
    {
        return $this->isUniqueInTenant('slug', $slug, $excludeId);
    }

    /**
     * Lista categorias ativas do tenant, garantindo que o pai também não esteja deletado.
     */
    public function listActiveByTenant(?array $orderBy = null): Collection
    {
        return $this->model->newQuery()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('parent_id')
                    ->orWhereHas('parent', fn ($pq) => $pq->withoutTrashed());
            })
            ->tap(fn ($q) => $this->applyOrderBy($q, $orderBy))
            ->get();
    }

    /**
     * Busca categorias ordenadas por nome dentro do tenant.
     */
    public function findOrderedByName(string $direction = 'asc'): Collection
    {
        return $this->getAllByTenant([], ['name' => $direction]);
    }

    /**
     * Conta categorias ativas do tenant.
     */
    public function countActiveByTenant(): int
    {
        return $this->countByTenant(['is_active' => true]);
    }

    /**
     * Conta apenas categorias deletadas.
     */
    public function countDeletedByTenant(): int
    {
        return $this->countOnlyTrashedByTenant();
    }

    /**
     * Obtém categorias recentes do tenant.
     */
    public function getRecentByTenant(int $limit = 10): Collection
    {
        return $this->model->newQuery()
            ->with('parent') // Eager load parent relationship to avoid N+1
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Busca categorias por nome/descrição com pesquisa parcial.
     */
    public function search(string $search, array $filters = [], ?array $orderBy = null, ?int $limit = null): Collection
    {
        return $this->searchByTenant($search, $filters, $orderBy, $limit);
    }

    /**
     * Busca categorias ativas (não deletadas) do tenant.
     */
    public function getActive(array $filters = [], ?array $orderBy = null, ?int $limit = null): Collection
    {
        return $this->getActiveByTenant($filters, $orderBy, $limit);
    }

    /**
     * Busca categorias deletadas (soft delete) do tenant.
     */
    public function getDeleted(array $filters = [], ?array $orderBy = null, ?int $limit = null): Collection
    {
        return $this->getDeletedByTenant($filters, $orderBy, $limit);
    }

    /**
     * Restaura categorias deletadas (soft delete) por IDs.
     */
    public function restoreMany(array $ids): int
    {
        return $this->restoreManyByTenant($ids);
    }

    /**
     * Restaura uma categoria deletada por slug.
     */
    public function restoreBySlug(string $slug): bool
    {
        $category = $this->model->newQuery()
            ->onlyTrashed()
            ->where('slug', $slug)
            ->first();

        return $category ? $category->restore() : false;
    }

    /**
     * Lista apenas categorias pai (sem parent_id) que estão ativas.
     */
    public function listParents(): Collection
    {
        return $this->model->newQuery()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Cria uma nova categoria a partir de um DTO.
     */
    public function createFromDTO(\App\DTOs\Category\CategoryDTO $dto): Model
    {
        return $this->create($dto->toArray());
    }

    /**
     * Atualiza uma categoria a partir de um DTO.
     */
    public function updateFromDTO(int $id, \App\DTOs\Category\CategoryDTO $dto): bool
    {
        return $this->update($id, $dto->toArray());
    }

    /**
     * {@inheritdoc}
     *
     * Implementação específica para categorias com suporte a hierarquia e filtros avançados.
     */
    public function getPaginated(
        array $filters = [],
        int $perPage = 15,
        array $with = ['parent'],
        ?array $orderBy = null,
    ): LengthAwarePaginator {
        return $this->model->newQuery()
            ->with($with)
            ->withCount(['children', 'services', 'products'])
            ->tap(fn ($q) => $this->applyAllCategoryFilters($q, $filters))
            ->when(! $orderBy, function ($q) {
                $q->orderByRaw('COALESCE((SELECT name FROM categories AS p WHERE p.id = categories.parent_id LIMIT 1), name), parent_id IS NULL DESC, name');
            })
            ->when($orderBy, fn ($q) => $this->applyOrderBy($q, $orderBy))
            ->paginate($this->getEffectivePerPage($filters, $perPage));
    }

    /**
     * Aplica todos os filtros de categoria.
     */
    protected function applyAllCategoryFilters($query, array $filters): void
    {
        $this->applySearchFilter($query, $filters, ['name', 'slug']);
        $this->applyOperatorFilter($query, $filters, 'name', 'name');
        $this->applyBooleanFilter($query, $filters, 'is_active', 'is_active');
        $this->applySoftDeleteFilter($query, $filters);
    }
}
