<?php

declare(strict_types=1);

namespace App\Contracts\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Interface para repositórios que trabalham com modelos tenant-aware.
 *
 * Define operações CRUD completas para modelos com tenant_id,
 * garantindo isolamento completo de dados por empresa.
 */
interface TenantRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Busca uma entidade pelo seu ID.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant
     * @return Model|null Retorna a entidade encontrada ou null.
     */
    public function findByIdAndTenantId( int $id, int $tenant_id ): ?Model;

    /**
     * Busca todas as entidades de um tenant.
     *
     * @param int $tenant_id ID do tenant
     * @param array<string, mixed> $criteria Critérios adicionais de busca
     * @param array<string, string>|null $orderBy Ordenação dos resultados
     * @param int|null $limit Limite de resultados
     * @param int|null $offset Deslocamento na consulta
     * @return Collection<Model> Retorna uma coleção de entidades (pode ser vazia).
     */
    public function findAllByTenantId( int $tenant_id, array $criteria = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): Collection;

    /**
     * Salva uma entidade no banco de dados.
     *
     * @param Model $entity Entidade a ser salva
     * @param int $tenant_id ID do tenant
     * @return Model|bool Entidade completa com dados atualizados ou false em caso de falha
     */
    public function save( Model $entity, int $tenant_id ): Model|bool;

    /**
     * Exclui uma entidade do banco de dados.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant
     * @return bool Retorna true em caso de sucesso na exclusão, false caso contrário.
     */
    public function deleteByIdAndTenantId( int $id, int $tenant_id ): bool;

    /**
     * Busca uma entidade pelo seu ID e tenant ou lança exceção se não encontrada.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant
     * @return Model Entidade encontrada
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Se entidade não encontrada
     */
    public function findByIdAndTenantOrFail( int $id, int $tenant_id ): Model;

    /**
     * Busca a primeira entidade do tenant que corresponda aos critérios ou lança exceção se nenhuma for encontrada.
     *
     * @param int $tenant_id ID do tenant
     * @param array<string, mixed> $criteria Critérios de busca
     * @return Model Primeira entidade encontrada
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Se nenhuma entidade encontrada
     */
    public function firstByTenantOrFail( int $tenant_id, array $criteria ): Model;

    /**
     * Atualiza uma entidade ou lança exceção se a operação falhar.
     *
     * @param Model $entity Entidade a ser atualizada
     * @param array<string, mixed> $data Dados para atualização
     * @return bool Resultado da operação
     * @throws \Exception Se a atualização falhar
     */
    public function updateOrFail( Model $entity, array $data ): bool;

    /**
     * Exclui uma entidade ou lança exceção se a operação falhar.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant
     * @return bool Resultado da operação
     * @throws \Exception Se a exclusão falhar
     */
    public function deleteOrFail( int $id, int $tenant_id ): bool;
}
