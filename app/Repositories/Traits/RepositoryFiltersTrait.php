<?php

declare(strict_types=1);

namespace App\Repositories\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait com métodos auxiliares comuns para repositórios.
 *
 * Esta trait centraliza funcionalidades compartilhadas entre diferentes
 * tipos de repositórios, evitando duplicação de código e promovendo
 * consistência na aplicação de filtros e ordenação.
 */
trait RepositoryFiltersTrait
{
    /**
     * Aplica filtros à query de forma segura e consistente.
     *
     * @param  array<string, mixed>  $filters  Filtros a aplicar (ex: ['status' => 'active', 'type' => 'premium'])
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
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        foreach ($filters as $field => $value) {
            // Ignora campos que não existem no modelo para evitar erros de query
            if (! $this->isValidField($field)) {
                continue;
            }

            // Se o valor for 'all', ignoramos o filtro para este campo (ex: active=all)
            if ($value === 'all') {
                continue;
            }

            $query->when($value !== null && $value !== '', function ($q) use ($field, $value) {
                if (is_array($value)) {
                    if (isset($value['operator'], $value['value'])) {
                        $q->where($field, $value['operator'], $value['value']);
                    } else {
                        $q->whereIn($field, $value);
                    }
                } else {
                    $q->where($field, $value);
                }
            });
        }

        return $query;
    }

    /**
     * Aplica ordenação à query com validação de direção.
     *
     * @param  array<string, string>|null  $orderBy
     */
    protected function applyOrderBy(Builder $query, ?array $orderBy): Builder
    {
        if (empty($orderBy)) {
            return $query;
        }

        foreach ($orderBy as $field => $direction) {
            $direction = strtolower((string) $direction) === 'desc' ? 'desc' : 'asc';
            $query->orderBy($field, $direction);
        }

        return $query;
    }

    /**
     * Aplica filtros de intervalo de data de forma consistente e robusta.
     * Suporta os formatos [date_from, date_to], [start_date, end_date] ou chaves customizadas.
     *
     * @param  Builder  $query  A query do Eloquent
     * @param  array  $filters  Os filtros recebidos
     * @param  string  $column  A coluna do banco de dados (ex: 'created_at', 'due_date')
     * @param  string|null  $paramFrom  O nome do parâmetro 'de' (opcional)
     * @param  string|null  $paramTo  O nome do parâmetro 'até' (opcional)
     */
    public function applyDateRangeFilter(Builder $query, array $filters, string $column = 'created_at', ?string $paramFrom = null, ?string $paramTo = null): Builder
    {
        // Se paramFrom e paramTo não forem passados, tentamos os padrões
        $from = $paramFrom ? ($filters[$paramFrom] ?? null) : ($filters['date_from'] ?? ($filters['start_date'] ?? ($filters['date_start'] ?? null)));
        $to = $paramTo ? ($filters[$paramTo] ?? null) : ($filters['date_to'] ?? ($filters['end_date'] ?? ($filters['date_end'] ?? null)));

        $startCarbon = \App\Helpers\DateHelper::toCarbon($from);
        $endCarbon = \App\Helpers\DateHelper::toCarbon($to);

        return $query->when($startCarbon, fn ($q) => $q->where($column, '>=', $startCarbon->startOfDay()))
            ->when($endCarbon, fn ($q) => $q->where($column, '<=', $endCarbon->endOfDay()));
    }

    /**
     * Valida se um campo existe no modelo antes de aplicar filtro.
     */
    protected function isValidField(string $field): bool
    {
        $commonFields = ['id', 'created_at', 'updated_at', 'deleted_at'];

        return in_array($field, array_merge($this->getFillableFields(), $commonFields));
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
     * @param  array<string, mixed>  $filters
     *
     * @example Uso:
     * ```php
     * $filters = ['deleted' => 'only'];
     * $this->applySoftDeleteFilter($query, $filters);
     * // Aplica onlyTrashed() à query
     * ```
     */
    protected function applySoftDeleteFilter(Builder $query, array $filters): Builder
    {
        if (! method_exists($query->getModel(), 'runSoftDelete')) {
            return $query;
        }

        $deletedFilter = $filters['deleted'] ?? 'current';

        return match ($deletedFilter) {
            'only' => $query->onlyTrashed(), // Apenas deletados
            'current' => $query,             // Apenas ativos (padrão do Eloquent)
            'all' => $query->withTrashed(),        // Todos
            default => $query,                     // Fallback: apenas ativos
        };
    }

    /**
     * Retorna per page efetivo baseado nos filtros.
     *
     * Permite que o método getPaginated() use um per_page customizado
     * via filtro, mantendo o padrão de 15 itens por página.
     *
     * @param  array<string, mixed>  $filters
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
    protected function getEffectivePerPage(array $filters, int $defaultPerPage): int
    {
        $perPage = $filters['per_page'] ?? $defaultPerPage;

        return is_numeric($perPage) ? (int) $perPage : $defaultPerPage;
    }

    /**
     * Aplica filtro de busca genérico
     */
    public function applySearchFilter(Builder $query, array $filters, string|array $fields): Builder
    {
        $search = $filters['search'] ?? null;

        return $query->when(! empty($search), function ($q) use ($search, $fields) {
            $searchString = (string) $search;
            $fieldsArray = is_array($fields) ? $fields : [$fields];

            $q->where(function ($sq) use ($searchString, $fieldsArray) {
                foreach ($fieldsArray as $index => $field) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $sq->{$method}($field, 'like', "%{$searchString}%");
                }
            });
        });
    }

    /**
     * Aplica filtro com operador genérico
     */
    public function applyOperatorFilter(Builder $query, array $filters, string $filterName, string $fieldName): Builder
    {
        $filterValue = $filters[$filterName] ?? null;

        return $query->when(
            is_array($filterValue) && isset($filterValue['operator'], $filterValue['value']),
            fn ($q) => $q->where($fieldName, $filterValue['operator'], $filterValue['value'])
        );
    }

    /**
     * Aplica filtro booleano genérico
     */
    public function applyBooleanFilter(Builder $query, array $filters, string $filterName, string $fieldName): Builder
    {
        $value = $filters[$filterName] ?? null;

        if ($value === null || $value === '' || $value === 'all') {
            return $query;
        }

        // Converte para booleano de forma robusta
        $boolValue = filter_var($value, FILTER_VALIDATE_BOOLEAN);

        return $query->where($fieldName, $boolValue);
    }
}
