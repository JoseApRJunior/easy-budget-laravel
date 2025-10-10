<?php
declare(strict_types=1);

namespace App\Repositories\Abstracts;

use App\Repositories\Contracts\GlobalRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Repositório abstrato base para operações globais (sem tenant_id).
 */
abstract class AbstractGlobalRepository implements GlobalRepositoryInterface
{
    protected Model $model;

    public function __construct()
    {
        $this->model = $this->makeModel();
    }

    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    abstract protected function makeModel(): Model;

    // --------------------------------------------------------------------------
    // MÉTODOS DE LEITURA (READ) - C/ SUFIXO GLOBAL
    // --------------------------------------------------------------------------

    public function findGlobal( int $id ): ?Model
    {
        return $this->model->find( $id );
    }

    public function getAllGlobal(
        array $criteria = [],
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): Collection {
        $query = $this->model->newQuery();

        // Lógica de filtro (where)
        if ( !empty( $criteria ) ) {
            foreach ( $criteria as $field => $value ) {
                // Simplificado para whereIn e where simples
                if ( is_array( $value ) ) {
                    $query->whereIn( $field, $value );
                } else {
                    $query->where( $field, $value );
                }
            }
        }

        // Lógica de ordenação, limite e offset...
        if ( $orderBy !== null ) {
            foreach ( $orderBy as $field => $direction ) {
                $query->orderBy( $field, $direction );
            }
        }
        if ( $offset !== null ) {
            $query->offset( $offset );
        }
        if ( $limit !== null ) {
            $query->limit( $limit );
        }

        return $query->get();
    }

    // --------------------------------------------------------------------------
    // MÉTODOS DE ESCRITA (WRITE) - C/ SUFIXO GLOBAL
    // --------------------------------------------------------------------------

    public function createGlobal( array $data ): Model
    {
        return $this->model->create( $data );
    }

    public function updateGlobal( int $id, array $data ): ?Model
    {
        $model = $this->findGlobal( $id );

        if ( !$model ) {
            return null;
        }

        $model->update( $data );
        return $model;
    }

    public function deleteGlobal( int $id ): bool
    {
        // Usa a função estática destroy para deleção por ID
        return (bool) $this->model->destroy( $id );
    }

    // --------------------------------------------------------------------------
    // MÉTODOS DE UTILIDADE (UTILITY)
    // --------------------------------------------------------------------------

    public function paginateGlobal( int $perPage = 15, array $filters = [] ): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if ( !empty( $filters ) ) {
            foreach ( $filters as $field => $value ) {
                if ( is_array( $value ) ) {
                    $query->whereIn( $field, $value );
                } else {
                    $query->where( $field, $value );
                }
            }
        }

        return $query->paginate( $perPage );
    }

    public function countGlobal( array $filters = [] ): int
    {
        $query = $this->model->newQuery();

        if ( !empty( $filters ) ) {
            foreach ( $filters as $field => $value ) {
                if ( is_array( $value ) ) {
                    $query->whereIn( $field, $value );
                } else {
                    $query->where( $field, $value );
                }
            }
        }

        return $query->count();
    }

}
