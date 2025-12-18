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

    /**
     * Verifica se slug existe (método requerido pelos testes).
     *
     * @param string $slug Slug da categoria
     * @param int|null $tenantId ID do tenant (null para admin global)
     * @param int|null $excludeId ID da categoria a ser excluído da verificação
     * @return bool True se existe, false caso contrário
     */
    public function existsBySlug( string $slug, ?int $tenantId = null, ?int $excludeId = null ): bool
    {
        // Para admin global (tenantId = null), sempre retorna false para não ter conflitos
        if ( $tenantId === null ) {
            return false;
        }

        return $this->existsBySlugAndTenantId( $slug, $tenantId, $excludeId );
    }

    /**
     * {@inheritdoc}
     *
     * Implementação específica para categorias com suporte a hierarquia e filtros avançados.
     *
     * @param array<string, mixed> $filters Filtros específicos:
     *   - search: termo de busca em nome, slug ou nome da categoria pai
     *   - active: true/false para filtrar por status ativo
     *   - per_page: número de itens por página
     *   - deleted: 'only' para mostrar apenas categorias deletadas
     *   - name: filtro por nome (com operador e valor)
     *   - slug: filtro por slug (com operador e valor)
     * @param int $perPage Número padrão de itens por página (15)
     * @param array<string> $with Relacionamentos para eager loading (ex: ['parent'])
     * @param array<string, string>|null $orderBy Ordenação personalizada
     * @param bool $onlyTrashed Se true, retorna apenas categorias deletadas (soft delete)
     * @return LengthAwarePaginator Resultado paginado
     */
    public function getPaginated(
        array $filters = [],
        int $perPage = 15,
        array $with = [],
        ?array $orderBy = null,
        bool $onlyTrashed = false,
    ): LengthAwarePaginator {
        $query = $this->model->newQuery()
            ->leftJoin( 'categories as parent', 'parent.id', '=', 'categories.parent_id' )
            ->select( 'categories.*' );

        // Eager loading paramétrico
        if ( !empty( $with ) ) {
            $query->with( $with );
        }

        // Aplicar filtro de soft delete específico se solicitado
        if ( $onlyTrashed ) {
            $query->onlyTrashed();
        }

        // Aplicar todos os filtros de categoria (com tratamento completo de joins)
        $this->applyAllCategoryFilters( $query, $filters );

        // Ordenação hierárquica: categorias pai primeiro, depois filhas, ordenadas por nome
        if ( !$orderBy ) {
            $query->orderByRaw( 'COALESCE(parent.name, categories.name) ASC' )
                ->orderByRaw( 'CASE WHEN categories.parent_id IS NULL THEN 0 ELSE 1 END' )
                ->orderBy( 'categories.name', 'ASC' );
        } else {
            $this->applyOrderBy( $query, $orderBy );
        }

        // Per page dinâmico
        $effectivePerPage = $this->getEffectivePerPage( $filters, $perPage );

        return $query->paginate( $effectivePerPage );
    }

    /**
     * Aplica todos os filtros de categoria com tratamento completo para joins.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array<string, mixed> $filters
     */
    protected function applyAllCategoryFilters( $query, array $filters ): void
    {
        // Filtro por tenant_id - deve ser qualificado para evitar ambiguidade
        if ( !empty( $filters[ 'tenant_id' ] ) ) {
            $query->where( 'categories.tenant_id', $filters[ 'tenant_id' ] );
        }

        // Aplicar filtro de soft delete se necessário
        $this->applySoftDeleteFilter( $query, $filters );

        // Filtros específicos de categoria
        // Filtro por busca (nome, slug ou nome da categoria pai)
        if ( !empty( $filters[ 'search' ] ) ) {
            $search = (string) $filters[ 'search' ];
            $query->where( function ( $q ) use ( $search ) {
                $q->where( 'categories.name', 'like', "%{$search}%" )
                    ->orWhere( 'categories.slug', 'like', "%{$search}%" )
                    ->orWhere( 'parent.name', 'like', "%{$search}%" );
            } );
        }

        // Filtros por nome com operador
        if ( !empty( $filters[ 'name' ] ) && is_array( $filters[ 'name' ] ) && isset( $filters[ 'name' ][ 'operator' ], $filters[ 'name' ][ 'value' ] ) ) {
            $op  = $filters[ 'name' ][ 'operator' ];
            $val = $filters[ 'name' ][ 'value' ];
            $query->where( function ( $q ) use ( $op, $val ) {
                $q->where( 'categories.name', $op, $val )
                    ->orWhere( 'parent.name', $op, $val );
            } );
        }

        // Filtros por slug com operador
        if ( !empty( $filters[ 'slug' ] ) && is_array( $filters[ 'slug' ] ) && isset( $filters[ 'slug' ][ 'operator' ], $filters[ 'slug' ][ 'value' ] ) ) {
            $op  = $filters[ 'slug' ][ 'operator' ];
            $val = $filters[ 'slug' ][ 'value' ];
            $query->where( 'categories.slug', $op, $val );
        }

        // Filtro por ativo/inativo
        if ( array_key_exists( 'is_active', $filters ) ) {
            $query->where( 'categories.is_active', $filters[ 'is_active' ] );
        } elseif ( array_key_exists( 'active', $filters ) && $filters[ 'active' ] !== '' ) {
            $bool = in_array( (string) $filters[ 'active' ], [ '1', 'true', 'on' ], true );
            $query->where( 'categories.is_active', $bool );
        }
    }

}
