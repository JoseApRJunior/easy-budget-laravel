<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface base para repositórios.
 * 
 * Define contratos para operações CRUD padronizadas com suporte a multi-tenancy.
 * Baseada no padrão do sistema antigo adaptada para Eloquent ORM.
 */
interface RepositoryInterface
{
    /**
     * Encontra um registro por ID.
     */
    public function find(int $id): ?Model;

    /**
     * Encontra um registro por ID ou falha.
     */
    public function findOrFail(int $id): Model;

    /**
     * Encontra registros por critérios.
     */
    public function findBy(array $criteria): Collection;

    /**
     * Encontra um registro por critérios.
     */
    public function findOneBy(array $criteria): ?Model;

    /**
     * Obtém todos os registros.
     */
    public function all(): Collection;

    /**
     * Obtém registros paginados.
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * Cria um novo registro.
     */
    public function create(array $data): Model;

    /**
     * Atualiza um registro.
     */
    public function update(Model $model, array $data): Model;

    /**
     * Salva um registro (create ou update).
     */
    public function save(Model $model): Model;

    /**
     * Exclui um registro.
     */
    public function delete(Model $model): bool;

    /**
     * Exclui um registro por ID.
     */
    public function deleteById(int $id): bool;

    /**
     * Conta registros por critérios.
     */
    public function count(array $criteria = []): int;

    /**
     * Verifica se existe registro com critérios.
     */
    public function exists(array $criteria): bool;

    /**
     * Obtém o primeiro registro ou cria um novo.
     */
    public function firstOrCreate(array $attributes, array $values = []): Model;

    /**
     * Atualiza ou cria um registro.
     */
    public function updateOrCreate(array $attributes, array $values = []): Model;

    /**
     * Aplica filtros de busca.
     */
    public function search(string $term, array $fields = []): Collection;

    /**
     * Obtém registros com relacionamentos.
     */
    public function with(array $relations): self;

    /**
     * Aplica ordenação.
     */
    public function orderBy(string $column, string $direction = 'asc'): self;

    /**
     * Aplica filtros WHERE.
     */
    public function where(string $column, mixed $operator, mixed $value = null): self;

    /**
     * Aplica filtros WHERE IN.
     */
    public function whereIn(string $column, array $values): self;

    /**
     * Aplica filtros WHERE NOT IN.
     */
    public function whereNotIn(string $column, array $values): self;

    /**
     * Aplica filtros WHERE BETWEEN.
     */
    public function whereBetween(string $column, array $values): self;

    /**
     * Aplica filtros WHERE NULL.
     */
    public function whereNull(string $column): self;

    /**
     * Aplica filtros WHERE NOT NULL.
     */
    public function whereNotNull(string $column): self;

    /**
     * Limita o número de resultados.
     */
    public function limit(int $limit): self;

    /**
     * Pula um número de registros.
     */
    public function skip(int $offset): self;

    /**
     * Executa a query e retorna os resultados.
     */
    public function get(): Collection;

    /**
     * Executa a query e retorna o primeiro resultado.
     */
    public function first(): ?Model;

    /**
     * Reseta os filtros aplicados.
     */
    public function reset(): self;
}