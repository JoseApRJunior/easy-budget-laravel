<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Category;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

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
        return new Category();
    }

    /**
     * Busca categoria por slug dentro do tenant.
     */
    public function findBySlugAndTenantId( string $slug ): ?Model
    {
        return $this->findByTenantAndSlug( $slug );
    }

    /**
     * Verifica se slug existe dentro do tenant.
     */
    public function existsBySlugAndTenantId( string $slug, ?int $excludeId = null ): bool
    {
        return $this->isUniqueInTenant( 'slug', $slug, $excludeId );
    }

    /**
     * Lista categorias ativas do tenant, garantindo que o pai também não esteja deletado.
     */
    public function listActiveByTenantId( ?array $orderBy = null ): Collection
    {
        return $this->model->newQuery()
            ->where( 'is_active', true )
            ->where( function ( $q ) {
                $q->whereNull( 'parent_id' )
                    ->orWhereHas( 'parent', fn( $pq ) => $pq->withoutTrashed() );
            } )
            ->tap( fn( $q ) => $this->applyOrderBy( $q, $orderBy ) )
            ->get();
    }

    /**
     * Busca categorias ordenadas por nome dentro do tenant.
     */
    public function findOrderedByNameAndTenantId( string $direction = 'asc' ): Collection
    {
        return $this->getAllByTenant( [], [ 'name' => $direction ] );
    }

    /**
     * Conta categorias do tenant.
     */
    public function countByTenantId(): int
    {
        return $this->countByTenant();
    }

    /**
     * Conta categorias ativas do tenant.
     */
    public function countActiveByTenantId(): int
    {
        return $this->countByTenant( [ 'is_active' => true ] );
    }

    /**
     * Conta apenas categorias deletadas.
     */
    public function countDeletedByTenantId(): int
    {
        return $this->countOnlyTrashedByTenant();
    }

    /**
     * Obtém categorias recentes do tenant.
     */
    public function getRecentByTenantId( int $limit = 10 ): Collection
    {
        return $this->model->newQuery()
            ->orderBy( 'created_at', 'desc' )
            ->limit( $limit )
            ->get();
    }

    /**
     * Verifica se slug existe (método compatível com testes).
     */
    public function existsBySlug( string $slug, ?int $excludeId = null ): bool
    {
        return $this->existsBySlugAndTenantId( $slug, $excludeId );
    }

    /**
     * Busca categorias por nome/descrição com pesquisa parcial.
     */
    public function searchCategories( string $search, array $filters = [], ?array $orderBy = null, ?int $limit = null ): Collection
    {
        return $this->searchByTenant( $search, $filters, $orderBy, $limit );
    }

    /**
     * Busca categorias ativas (não deletadas) do tenant.
     */
    public function getActiveCategories( array $filters = [], ?array $orderBy = null, ?int $limit = null ): Collection
    {
        return $this->getActiveByTenant( $filters, $orderBy, $limit );
    }

    /**
     * Busca categorias deletadas (soft delete) do tenant.
     */
    public function getDeletedCategories( array $filters = [], ?array $orderBy = null, ?int $limit = null ): Collection
    {
        return $this->getDeletedByTenant( $filters, $orderBy, $limit );
    }

    /**
     * Restaura categorias deletadas (soft delete) por IDs.
     */
    public function restoreCategories( array $ids ): int
    {
        return $this->restoreManyByTenant( $ids );
    }

    /**
     * {@inheritdoc}
     *
     * Implementação específica para categorias com suporte a hierarquia e filtros avançados.
     */
    public function getPaginated(
        array $filters = [],
        int $perPage = 15,
        array $with = [ 'parent' ],
        ?array $orderBy = null,
    ): LengthAwarePaginator {
        return $this->model->newQuery()
            ->with( $with )
            ->withCount( [ 'children', 'services', 'products' ] )
            ->tap( fn( $q ) => $this->applyAllCategoryFilters( $q, $filters ) )
            ->when( !$orderBy, function ( $q ) {
                $q->orderByRaw( 'COALESCE((SELECT name FROM categories AS p WHERE p.id = categories.parent_id LIMIT 1), name), parent_id IS NULL DESC, name' );
            } )
            ->when( $orderBy, fn( $q ) => $this->applyOrderBy( $q, $orderBy ) )
            ->paginate( $this->getEffectivePerPage( $filters, $perPage ) );
    }

    /**
     * Aplica todos os filtros de categoria.
     */
    protected function applyAllCategoryFilters( $query, array $filters ): void
    {
        $this->applySearchFilter( $query, $filters, [ 'name', 'slug' ] );
        $this->applyOperatorFilter( $query, $filters, 'name', 'name' );
        $this->applyBooleanFilter( $query, $filters, 'is_active', 'is_active' );
        $this->applySoftDeleteFilter( $query, $filters );
    }

}
