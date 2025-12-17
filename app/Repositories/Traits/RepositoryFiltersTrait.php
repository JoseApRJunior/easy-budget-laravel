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
        if ( isset( $filters[ 'deleted' ] ) && $filters[ 'deleted' ] === 'only' ) {
            $query->onlyTrashed();
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

}
