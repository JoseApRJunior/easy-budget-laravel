<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Category;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Repositório simplificado para gerenciamento de categorias.
 *
 * Categorias são isoladas por tenant - cada empresa gerencia suas próprias categorias.
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
     *
     * @param string $slug Slug da categoria
     * @param int $tenantId ID do tenant
     * @return Category|null Categoria encontrada
     */
    public function findBySlugAndTenantId( string $slug, int $tenantId ): ?Model
    {
        return $this->model
            ->where( 'slug', $slug )
            ->where( 'tenant_id', $tenantId )
            ->first();
    }

    /**
     * Verifica se slug existe dentro do tenant.
     *
     * @param string $slug Slug a ser verificado
     * @param int $tenantId ID do tenant
     * @param int|null $excludeId ID da categoria a ser excluído da verificação (para updates)
     * @return bool True se existe, false caso contrário
     */
    public function existsBySlugAndTenantId( string $slug, int $tenantId, ?int $excludeId = null ): bool
    {
        $query = $this->model
            ->where( 'slug', $slug )
            ->where( 'tenant_id', $tenantId );

        if ( $excludeId ) {
            $query->where( 'id', '!=', $excludeId );
        }

        return $query->exists();
    }

    /**
     * Lista categorias ativas do tenant.
     *
     * Exclui categorias órfãs (com parent deletado).
     *
     * @param int $tenantId ID do tenant
     * @param array<string, string>|null $orderBy Ordenação
     * @return Collection<Category> Categorias ativas
     */
    public function listActiveByTenantId( int $tenantId, ?array $orderBy = null ): Collection
    {
        $query = $this->model->newQuery()
            ->where( 'tenant_id', $tenantId )
            ->where( 'is_active', true )
            ->where( function ( $q ) {
                // Incluir categorias sem parent OU com parent não deletado
                $q->whereNull( 'parent_id' )
                    ->orWhereHas( 'parent', function ( $parentQuery ) {
                    $parentQuery->withoutTrashed();
                } );
            } );

        $this->applyOrderBy( $query, $orderBy );

        return $query->get();
    }

    /**
     * Busca categorias ordenadas por nome dentro do tenant.
     *
     * @param int $tenantId ID do tenant
     * @param string $direction Direção da ordenação (asc/desc)
     * @return Collection<Category> Categorias ordenadas
     */
    public function findOrderedByNameAndTenantId( int $tenantId, string $direction = 'asc' ): Collection
    {
        return $this->getAllByTenant( [], [ 'name' => $direction ] );
    }

    /**
     * Pagina categorias do tenant.
     *
     * @param int $tenantId ID do tenant
     * @param int $perPage
     * @param array<string, mixed> $filters
     * @param array<string, string>|null $orderBy
     * @param bool $onlyTrashed
     * @return LengthAwarePaginator
     */
    public function paginateByTenantId(
        int $tenantId,
        int $perPage = 15,
        array $filters = [],
        ?array $orderBy = [ 'name' => 'asc' ],
        bool $onlyTrashed = false,
    ): LengthAwarePaginator {
        $query = $this->model->newQuery()
            ->where( 'tenant_id', $tenantId )
            ->leftJoin( 'categories as parent', 'parent.id', '=', 'categories.parent_id' )
            ->select( 'categories.*' )
            ->orderByRaw( 'COALESCE(parent.name, categories.name) ASC' )
            ->orderByRaw( 'CASE WHEN categories.parent_id IS NULL THEN 0 ELSE 1 END' )
            ->orderBy( 'categories.name', 'ASC' );

        if ( $onlyTrashed ) {
            $query->withTrashed();
        }

        // Aplicar filtros
        if ( !empty( $filters[ 'search' ] ) ) {
            $search = (string) $filters[ 'search' ];
            unset( $filters[ 'search' ] );
            $query->where( function ( $q ) use ( $search ) {
                $q->where( 'categories.name', 'like', "%{$search}%" )
                    ->orWhere( 'categories.slug', 'like', "%{$search}%" )
                    ->orWhere( 'parent.name', 'like', "%{$search}%" );
            } );
        }

        if ( !empty( $filters[ 'name' ] ) && is_array( $filters[ 'name' ] ) && isset( $filters[ 'name' ][ 'operator' ], $filters[ 'name' ][ 'value' ] ) ) {
            $op  = $filters[ 'name' ][ 'operator' ];
            $val = $filters[ 'name' ][ 'value' ];
            unset( $filters[ 'name' ] );
            $query->where( function ( $q ) use ( $op, $val ) {
                $q->where( 'categories.name', $op, $val )
                    ->orWhere( 'parent.name', $op, $val );
            } );
        }

        if ( !empty( $filters[ 'slug' ] ) && is_array( $filters[ 'slug' ] ) && isset( $filters[ 'slug' ][ 'operator' ], $filters[ 'slug' ][ 'value' ] ) ) {
            $op  = $filters[ 'slug' ][ 'operator' ];
            $val = $filters[ 'slug' ][ 'value' ];
            unset( $filters[ 'slug' ] );
            $query->where( 'categories.slug', $op, $val );
        }

        if ( array_key_exists( 'is_active', $filters ) ) {
            $val = $filters[ 'is_active' ];
            unset( $filters[ 'is_active' ] );
            $query->where( 'categories.is_active', $val );
        }

        if ( array_key_exists( 'active', $filters ) && $filters[ 'active' ] !== '' ) {
            $val = $filters[ 'active' ];
            unset( $filters[ 'active' ] );
            $bool = in_array( (string) $val, [ '1', 'true', 'on' ], true );
            $query->where( 'categories.is_active', $bool );
        }

        unset( $filters[ 'per_page' ] );

        $this->applyFilters( $query, $filters );

        return $query->paginate( $perPage );
    }

    /**
     * Conta categorias do tenant.
     *
     * @param int $tenantId ID do tenant
     * @return int
     */
    public function countByTenantId( int $tenantId ): int
    {
        return $this->model->newQuery()
            ->where( 'tenant_id', $tenantId )
            ->count();
    }

    /**
     * Conta categorias ativas do tenant.
     *
     * @param int $tenantId ID do tenant
     * @return int
     */
    public function countActiveByTenantId( int $tenantId ): int
    {
        return $this->model->newQuery()
            ->where( 'tenant_id', $tenantId )
            ->where( 'is_active', true )
            ->count();
    }

    /**
     * Obtém categorias recentes do tenant.
     *
     * @param int $tenantId ID do tenant
     * @param int $limit
     * @return Collection
     */
    public function getRecentByTenantId( int $tenantId, int $limit = 10 ): Collection
    {
        return $this->model->newQuery()
            ->where( 'tenant_id', $tenantId )
            ->orderBy( 'created_at', 'desc' )
            ->limit( $limit )
            ->get();
    }

}
