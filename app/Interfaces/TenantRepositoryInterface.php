<?php

declare(strict_types=1);

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface para repositórios tenant-aware
 *
 * Define métodos para repositórios que trabalham com dados isolados por tenant,
 * garantindo que todas as operações incluam o tenant_id para isolamento de dados
 */
interface TenantRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Define o ID do tenant para o contexto do repositório
     *
     * @param int $tenantId ID do tenant
     * @return self
     */
    public function setTenantId( int $tenantId ): self;

    /**
     * Obtém o ID do tenant atual do contexto
     *
     * @return int|null ID do tenant ou null se não definido
     */
    public function getTenantId(): ?int;

    /**
     * Cria um novo registro com tenant_id
     *
     * @param array $data Dados do registro
     * @return Model Registro criado
     */
    public function create( array $data ): Model;

    /**
     * Atualiza um registro existente
     *
     * @param Model $model Registro a ser atualizado
     * @param array $data Dados para atualização
     * @return Model Registro atualizado
     */
    public function update( Model $model, array $data ): Model;

    /**
     * Remove um registro
     *
     * @param Model $model Registro a ser removido
     * @return bool True se removido com sucesso
     */
    public function delete( Model $model ): bool;

    /**
     * Busca um registro por ID e tenant_id
     *
     * @param int $id ID do registro
     * @param int $tenantId ID do tenant
     * @return Model|null Registro encontrado ou null
     */
    public function findByIdAndTenant( int $id, int $tenantId ): ?Model;

    /**
     * Busca registros por critérios e tenant_id
     *
     * @param array $criteria Critérios de busca
     * @param int $tenantId ID do tenant
     * @return Collection Coleção de registros encontrados
     */
    public function findByCriteriaAndTenant( array $criteria, int $tenantId ): Collection;

    /**
     * Busca um registro por critérios e tenant_id
     *
     * @param array $criteria Critérios de busca
     * @param int $tenantId ID do tenant
     * @return Model|null Registro encontrado ou null
     */
    public function findOneByCriteriaAndTenant( array $criteria, int $tenantId ): ?Model;

    /**
     * Busca registro por slug e tenant_id
     *
     * @param string $slug Slug do registro
     * @param int $tenantId ID do tenant
     * @return Model|null Registro encontrado ou null
     */
    public function findBySlugAndTenant( string $slug, int $tenantId ): ?Model;

    /**
     * Busca registro por código e tenant_id
     *
     * @param string $code Código do registro
     * @param int $tenantId ID do tenant
     * @return Model|null Registro encontrado ou null
     */
    public function findByCodeAndTenant( string $code, int $tenantId ): ?Model;

    /**
     * Atualiza múltiplos registros por critérios e tenant_id
     *
     * @param array $criteria Critérios para seleção
     * @param array $updates Dados para atualização
     * @param int $tenantId ID do tenant
     * @return int Número de registros atualizados
     */
    public function updateManyByTenant( array $criteria, array $updates, int $tenantId ): int;

    /**
     * Remove múltiplos registros por critérios e tenant_id
     *
     * @param array $criteria Critérios para seleção
     * @param int $tenantId ID do tenant
     * @return int Número de registros removidos
     */
    public function deleteManyByTenant( array $criteria, int $tenantId ): int;

    /**
     * Busca registros paginados por tenant_id
     *
     * @param int $tenantId ID do tenant
     * @param int $perPage Itens por página
     * @param array $criteria Critérios de busca
     * @return LengthAwarePaginator Paginator com resultados
     */
    public function paginateByTenant( int $tenantId, int $perPage = 15, array $criteria = [] ): LengthAwarePaginator;

    /**
     * Valida se um registro pertence ao tenant especificado
     *
     * @param Model $model Registro a ser validado
     * @param int $tenantId ID do tenant
     * @return bool True se pertence ao tenant, false caso contrário
     */
    public function validateTenantOwnership( Model $model, int $tenantId ): bool;

    /**
     * Valida se um valor é único em um campo para um tenant
     *
     * @param string $field Campo a ser verificado
     * @param mixed $value Valor a ser verificado
     * @param int $tenantId ID do tenant
     * @param int|null $excludeId ID a ser excluído da verificação (para updates)
     * @return bool True se é único, false caso contrário
     */
    public function validateUniqueInTenant( string $field, mixed $value, int $tenantId, ?int $excludeId = null ): bool;
}
