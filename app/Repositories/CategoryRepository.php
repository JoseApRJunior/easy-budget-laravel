<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Category;
use App\Models\Traits\TenantScope;
use App\Models\Traits\TenantScoped;
use App\Repositories\Abstracts\AbstractGlobalRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Repositório para gerenciamento de categorias.
 *
 * Estende AbstractGlobalRepository para operações globais
 * (categorias são compartilhadas entre tenants).
 */
class CategoryRepository extends AbstractGlobalRepository
{
    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new Category();
    }

    /**
     * Busca categoria por slug.
     *
     * @param string $slug Slug da categoria
     * @return Category|null Categoria encontrada
     */
    public function findBySlug( string $slug ): ?Model
    {
        return $this->model->where( 'slug', $slug )->first();
    }

    /**
     * Verifica se slug existe no scope especificado.
     *
     * Para providers: verifica apenas categorias custom do tenant específico
     * Para admins: permite qualquer slug (não há restrição de unicidade entre tenants)
     *
     * @param string $slug Slug a ser verificado
     * @param int|null $tenantId Tenant ID (null para admin/sistema)
     * @param int|null $excludeId ID da categoria a ser excluído da verificação (para updates)
     * @return bool True se existe, false caso contrário
     */
    public function existsBySlug( string $slug, ?int $tenantId = null, ?int $excludeId = null ): bool
    {
        // Para admins (tenantId = null): não há restrição de unicidade de slug
        // Admins podem usar qualquer slug, mesmo que já exista em outros tenants
        if ( $tenantId === null ) {
            return false;
        }

        $query = $this->model->where( 'slug', $slug );

        // Excluir categoria específica (para updates)
        if ( $excludeId ) {
            $query->where( 'id', '!=', $excludeId );
        }

        // Para providers: verificar apenas categorias custom do tenant específico
        $query->whereHas( 'tenants', function ( $t ) use ( $tenantId ) {
            $t->where( 'tenant_id', $tenantId )
                ->where( 'is_custom', true );
        } );

        return $query->exists();
    }

    /**
     * Lista categorias ativas.
     *
     * Exclui categorias órfãs (com parent deletado).
     *
     * @param array<string, string>|null $orderBy Ordenação
     * @return Collection<Category> Categorias ativas
     */
    public function listActive( ?array $orderBy = null ): Collection
    {
        $query = $this->model->newQuery()
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
     * Busca categorias ordenadas por nome.
     *
     * @param string $direction Direção da ordenação (asc/desc)
     * @return Collection<Category> Categorias ordenadas
     */
    public function findOrderedByName( string $direction = 'asc' ): Collection
    {
        return $this->getAllGlobal(
            [],
            [ 'name' => $direction ],
        );
    }

    /**
     * Lista categorias do tenant atual junto com categorias globais (tenant_id NULL).
     *
     * @param array<string, string>|null $orderBy
     * @return Collection<Category>
     */
    public function listWithGlobals( ?array $orderBy = null ): Collection
    {
        $tenantId = TenantScoped::getCurrentTenantId();
        $query    = $this->model->newQuery()->forTenant( $tenantId );  // Scope atualizado
        $this->applyOrderBy( $query, $orderBy );
        return $query->get();
    }

    /**
     * Pagina categorias do tenant atual junto com categorias globais (sem vínculo de tenant).
     *
     * @param int $perPage
     * @param array<string, mixed> $filters
     * @param array<string, string>|null $orderBy
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function paginateWithGlobals( int $perPage = 15, array $filters = [], ?array $orderBy = [ 'name' => 'asc' ] ): \Illuminate\Pagination\LengthAwarePaginator
    {
        $tenantId = TenantScoped::getCurrentTenantId();

        // Usar novo scope simplificado do model
        $query = $this->model->newQuery()
            ->forTenant( $tenantId )
            ->leftJoin( 'categories as parent', 'parent.id', '=', 'categories.parent_id' )
            ->select( 'categories.*' )
            ->orderByRaw( 'COALESCE(parent.name, categories.name) ASC' )
            ->orderByRaw( 'CASE WHEN categories.parent_id IS NULL THEN 0 ELSE 1 END' )
            ->orderBy( 'categories.name', 'ASC' );

        // Regras para prestador:
        // - Exibir categorias ativas
        // - Incluir categorias custom do tenant mesmo que não ativas globais
        // - Precedência: se existir custom do tenant com mesmo slug, ocultar a global
        if ( $tenantId !== null ) {
            $query->where( function ( $q ) use ( $tenantId ) {
                // Sempre incluir categorias custom do tenant
                $q->whereHas( 'tenants', function ( $t ) use ( $tenantId ) {
                    $t->where( 'tenant_id', $tenantId )
                        ->where( 'is_custom', true );
                } )
                    // Ou incluir globais ativas APENAS quando não houver custom com mesmo slug
                    ->orWhere( function ( $q2 ) use ( $tenantId ) {
                        $q2->where( 'categories.is_active', true )
                            ->whereDoesntHave( 'tenants', function ( $t ) {
                                $t->where( 'is_custom', true );
                            } )
                            ->whereNotExists( function ( $sub ) use ( $tenantId ) {
                                $sub->selectRaw( 1 )
                                    ->from( 'categories as c2' )
                                    ->join( 'category_tenant as ct2', 'ct2.category_id', '=', 'c2.id' )
                                    ->where( 'ct2.tenant_id', $tenantId )
                                    ->where( 'ct2.is_custom', true )
                                    ->whereColumn( 'c2.slug', 'categories.slug' );
                            } );
                    } );
            } );
        }

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
     * Pagina apenas categorias globais (tenant_id NULL), ignorando filtros de tenant.
     *
     * @param int $perPage
     * @param array<string, mixed> $filters
     * @param array<string, string>|null $orderBy
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function paginateOnlyGlobals( int $perPage = 15, array $filters = [], ?array $orderBy = [ 'name' => 'asc' ] ): \Illuminate\Pagination\LengthAwarePaginator
    {
        // Usar novo scope globalOnly() do model
        $query = $this->model->newQuery()
            ->globalOnly()
            ->leftJoin( 'categories as parent', 'parent.id', '=', 'categories.parent_id' )
            ->select( 'categories.*' )
            ->orderByRaw( 'COALESCE(parent.name, categories.name) ASC' )
            ->orderByRaw( 'CASE WHEN categories.parent_id IS NULL THEN 0 ELSE 1 END' )
            ->orderBy( 'categories.name', 'ASC' );

        unset( $filters[ 'tenant_id' ] );  // Remover filtro tenant_id se presente

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
     * Pagina apenas categorias deletadas (soft delete) de um tenant específico.
     *
     * Mostra APENAS categorias custom do tenant que foram deletadas.
     * Prestadores NÃO podem ver categorias globais deletadas.
     *
     * @param int $perPage Itens por página
     * @param array $filters Filtros de busca
     * @param int $tenantId ID do tenant
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function paginateOnlyTrashedForTenant( int $perPage = 15, array $filters = [], int $tenantId ): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->onlyTrashed()
            ->join( 'category_tenant', 'category_tenant.category_id', '=', 'categories.id' )
            ->where( 'category_tenant.tenant_id', $tenantId )
            ->where( 'category_tenant.is_custom', true )
            ->leftJoin( 'categories as parent', 'parent.id', '=', 'categories.parent_id' )
            ->select( 'categories.*' )
            ->orderByRaw( 'COALESCE(parent.name, categories.name) ASC' )
            ->orderByRaw( 'CASE WHEN categories.parent_id IS NULL THEN 0 ELSE 1 END' )
            ->orderBy( 'categories.name', 'ASC' );

        if ( !empty( $filters[ 'search' ] ) ) {
            $search = (string) $filters[ 'search' ];
            $query->where( function ( $q ) use ( $search ) {
                $q->where( 'categories.name', 'like', "%{$search}%" )
                    ->orWhere( 'categories.slug', 'like', "%{$search}%" )
                    ->orWhere( 'parent.name', 'like', "%{$search}%" );
            } );
        }

        return $query->paginate( $perPage );
    }

    /**
     * Count all global categories.
     *
     * @return int
     */
    public function countGlobalCategories(): int
    {
        return $this->model->newQuery()->globalOnly()->count();
    }

    /**
     * Count active global categories.
     *
     * @return int
     */
    public function countActiveGlobalCategories(): int
    {
        return $this->model->newQuery()->globalOnly()->where( 'is_active', true )->count();
    }

    /**
     * Get recent global categories.
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentGlobalCategories( int $limit = 10 ): Collection
    {
        return $this->model->newQuery()
            ->globalOnly()
            ->orderBy( 'created_at', 'desc' )
            ->limit( $limit )
            ->get();
    }

    /**
     * Count categories for a specific tenant.
     *
     * @param int $tenantId
     * @return int
     */
    public function countCategoriesForTenant( int $tenantId ): int
    {
        return $this->model->newQuery()
            ->forTenant( $tenantId )
            ->count();
    }

    /**
     * Count active categories for a specific tenant.
     *
     * @param int $tenantId
     * @return int
     */
    public function countActiveCategoriesForTenant( int $tenantId ): int
    {
        return $this->model->newQuery()
            ->forTenant( $tenantId )
            ->where( 'is_active', true )
            ->count();
    }

    /**
     * Get recent categories for a specific tenant.
     *
     * @param int $tenantId
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentCategoriesForTenant( int $tenantId, int $limit = 10 ): Collection
    {
        return $this->model->newQuery()
            ->forTenant( $tenantId )
            ->orderBy( 'created_at', 'desc' )
            ->limit( $limit )
            ->get();
    }

}
