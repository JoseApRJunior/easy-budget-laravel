<?php
declare(strict_types=1);

namespace App\Repositories\Traits;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\EloquentBuilder;

/**
 * Trait com métodos auxiliares comuns para repositórios.
 *
 * Esta trait centraliza funcionalidades compartilhadas entre diferentes
 * tipos de repositórios, evitando duplicação de código e promovendo
 * consistência na aplicação de filtros e ordenação.
 *
 * @package App\Repositories\Abstracts
 */
trait RepositoryFiltersTrait
{
    /**
     * Aplica filtros à query de forma segura e consistente.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array<string, mixed> $filters Filtros a aplicar (ex: ['status' => 'active', 'type' => 'premium'])
     *
     * @example Uso típico:
     * ```php
     * $filters = [
     *     'status' => 'active',
     *     'category_id' => [1, 2, 3],
     *     'price' => ['operator' => '>', 'value' => 100]
     * ];
     * $this->applyFilters($query, $filters);
     * ```
     */
    protected function applyFilters( $query, array $filters ): void
    {
        if ( empty( $filters ) ) {
            return;
        }

        foreach ( $filters as $field => $value ) {
            if ( is_array( $value ) ) {
                // Suporte a operadores especiais
                if ( isset( $value[ 'operator' ], $value[ 'value' ] ) ) {
                    $query->where( $field, $value[ 'operator' ], $value[ 'value' ] );
                } else {
                    $query->whereIn( $field, $value );
                }
            } elseif ( $value !== null ) {
                $query->where( $field, $value );
            }
        }
    }

    /**
     * Aplica ordenação à query com validação de direção.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array<string, string>|null $orderBy
     */
    protected function applyOrderBy( $query, ?array $orderBy ): void
    {
        if ( empty( $orderBy ) ) {
            return;
        }

        foreach ( $orderBy as $field => $direction ) {
            $direction = strtolower( $direction ) === 'desc' ? 'desc' : 'asc';
            $query->orderBy( $field, $direction );
        }
    }

    /**
     * Valida se um campo existe no modelo antes de aplicar filtro.
     *
     * @param string $field
     * @return bool
     */
    protected function isValidField( string $field ): bool
    {
        return in_array( $field, $this->getFillableFields() );
    }

    /**
     * Retorna lista de campos fillable do modelo.
     *
     * @return array<string>
     */
    protected function getFillableFields(): array
    {
        return $this->model->getFillable();
    }

    // --------------------------------------------------------------------------
    // MÉTODOS AUXILIARES PARA PAGINAÇÃO PADRONIZADA
    // --------------------------------------------------------------------------

    /**
     * Aplica filtro de soft delete baseado nos filtros.
     *
     * Este método é usado pelo método getPaginated() padrão para aplicar
     * automaticamente filtro de soft delete quando o filtro 'deleted' = 'only' for fornecido.
     *
     * @param Builder $query
     * @param array<string, mixed> $filters
     *
     * @example Uso:
     * ```php
     * $filters = ['deleted' => 'only'];
     * $this->applySoftDeleteFilter($query, $filters);
     * // Aplica onlyTrashed() à query
     * ```
     */
    protected function applySoftDeleteFilter( $query, array $filters ): void
    {
        if ( isset( $filters[ 'deleted' ] ) ) {
            if ( $filters[ 'deleted' ] === 'only' ) {
                $query->onlyTrashed();
            } elseif ( $filters[ 'deleted' ] === 'current' ) {
                // Não faz nada, mostra apenas ativos (default do Eloquent)
            } elseif ( $filters[ 'deleted' ] === '' || $filters[ 'deleted' ] === null ) {
                $query->withTrashed();
            }
        }
    }

    /**
     * Retorna per page efetivo baseado nos filtros.
     *
     * Permite que o método getPaginated() use um per_page customizado
     * via filtro, mantendo o padrão de 15 itens por página.
     *
     * @param array<string, mixed> $filters
     * @param int $defaultPerPage
     * @return int
     *
     * @example Uso:
     * ```php
     * $filters = ['per_page' => 20];
     * $perPage = $this->getEffectivePerPage($filters, 15);
     * // Retorna 20
     *
     * $filters = [];
     * $perPage = $this->getEffectivePerPage($filters, 15);
     * // Retorna 15
     * ```
     */
    protected function getEffectivePerPage( array $filters, int $defaultPerPage ): int
    {
        return $filters[ 'per_page' ] ?? $defaultPerPage;
    }

    /**
     * Aplica filtro de busca genérico
     */
    public function applySearchFilter( $query, array $filters, string $field1, string $field2 = null )
    {
        if ( !empty( $filters[ 'search' ] ) ) {
            $search = (string) $filters[ 'search' ];
            $query->where( function ( $q ) use ( $search, $field1, $field2 ) {
                $q->where( $field1, 'like', "%{$search}%" );
                if ( $field2 ) {
                    $q->orWhere( $field2, 'like', "%{$search}%" );
                }
            } );
        }
        return $query;
    }

    /**
     * Aplica filtro com operador genérico
     */
    public function applyOperatorFilter( $query, array $filters, string $filterName, string $fieldName )
    {
        if (
            !empty( $filters[ $filterName ] ) && is_array( $filters[ $filterName ] )
            && isset( $filters[ $filterName ][ 'operator' ], $filters[ $filterName ][ 'value' ] )
        ) {
            $op  = $filters[ $filterName ][ 'operator' ];
            $val = $filters[ $filterName ][ 'value' ];
            $query->where( $fieldName, $op, $val );
        }
        return $query;
    }

    /**
     * Aplica filtro booleano genérico
     */
    public function applyBooleanFilter( $query, array $filters, string $filterName, string $fieldName )
    {
        if ( array_key_exists( $filterName, $filters ) ) {
            $query->where( $fieldName, $filters[ $filterName ] );
        }
        return $query;
    }

}
