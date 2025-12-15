<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Category;
use App\Repositories\Abstracts\AbstractGlobalRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Repositório para gerenciamento de categorias.
 *
 * Estende AbstractGlobalRepository para operações globais
 * (categorias são compartilhadas entre todos os tenants).
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
     * Verifica se slug existe.
     *
     * @param string $slug Slug a ser verificado
     * @param int|null $excludeId ID da categoria a ser excluído da verificação (para updates)
     * @return bool True se existe, false caso contrário
     */
    public function existsBySlug( string $slug, ?int $excludeId = null ): bool
    {
        $query = $this->model->where( 'slug', $slug );

        if ( $excludeId ) {
            $query->where( 'id', '!=', $excludeId );
        }

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
     * Pagina categorias globais.
     *
     * @param int $perPage
     * @param array<string, mixed> $filters
     * @param array<string, string>|null $orderBy
     * @param bool $isAdminGlobal Indica se o usuário é admin global (apenas para admins globais)
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function paginate( int $perPage = 15, array $filters = [], ?array $orderBy = [ 'name' => 'asc' ], bool $isAdminGlobal = false, bool $onlyTrashed = false ): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->leftJoin( 'categories as parent', 'parent.id', '=', 'categories.parent_id' )
            ->select( 'categories.*' )
            ->orderByRaw( 'COALESCE(parent.name, categories.name) ASC' )
            ->orderByRaw( 'CASE WHEN categories.parent_id IS NULL THEN 0 ELSE 1 END' )
            ->orderBy( 'categories.name', 'ASC' );

        if ( $onlyTrashed ) {
            $query->withTrashed();
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

        // Admin global deve ver apenas categorias globais
        if ( $isAdminGlobal ) {
            $query->where( function ( $q ) {
                $q->whereDoesntHave( 'tenants' )
                    ->orWhereHas( 'tenants', function ( $t ) {
                        $t->where( 'is_custom', false );
                    } );
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
        return $this->model->newQuery()->count();
    }

    /**
     * Count active global categories.
     *
     * @return int
     */
    public function countActiveGlobalCategories(): int
    {
        return $this->model->newQuery()->where( 'is_active', true )->count();
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
            ->orderBy( 'created_at', 'desc' )
            ->limit( $limit )
            ->get();
    }

}
