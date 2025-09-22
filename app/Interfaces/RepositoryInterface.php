<?php

declare(strict_types=1);

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Model;

/**
 * Interface para repositórios que são tenant-aware (multi-tenant)
 *
 * Esta interface define os métodos padrão para repositórios que trabalham
 * com dados isolados por tenant, garantindo que todas as operações
 * incluam o tenant_id para isolamento de dados
 */
interface RepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Busca uma entidade pelo ID e tenant_id
     *
     * @param int $id ID da entidade
     * @param int $tenantId ID do tenant
     * @return Model|null Entidade encontrada ou null
     */
    public function findByIdAndTenantId( int $id, int $tenantId ): ?Model;

    /**
     * Busca múltiplas entidades por seus IDs e tenant_id
     *
     * @param array $id Array de IDs das entidades
     * @param int $tenantId ID do tenant
     * @return Model[] Array de entidades encontradas
     */
    public function findManyByIdsAndTenantId( array $id, int $tenantId ): array;

    /**
     * Busca entidades por critérios específicos e tenant_id
     *
     * @param array $criteria Critérios de busca
     * @param int $tenantId ID do tenant
     * @param array|null $orderBy Ordenação opcional [campo => direção]
     * @param int|null $limit Limite de resultados
     * @param int|null $offset Offset para paginação
     * @return Model[] Array de entidades encontradas
     */
    public function findByAndTenantId(
        array $criteria,
        int $tenantId,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): array;

    /**
     * Busca uma entidade por critérios específicos e tenant_id
     *
     * @param array $criteria Critérios de busca
     * @param int $tenantId ID do tenant
     * @return Model|null Entidade encontrada ou null
     */
    public function findOneByAndTenantId( array $criteria, int $tenantId ): ?Model;

    /**
     * Busca todas as entidades de um tenant
     *
     * @param int $tenantId ID do tenant
     * @param array $criteria Critérios adicionais de busca
     * @param array|null $orderBy Ordenação opcional [campo => direção]
     * @param int|null $limit Limite de resultados
     * @param int|null $offset Offset para paginação
     * @return Model[] Array de todas as entidades do tenant
     */
    public function findAllByTenantId(
        int $tenantId,
        array $criteria = [],
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): array;

    /**
     * Busca entidade por slug e tenant_id
     *
     * @param string $slug Slug da entidade
     * @param int $tenantId ID do tenant
     * @return Model|null Entidade encontrada ou null
     */
    public function findBySlugAndTenantId( string $slug, int $tenantId ): ?Model;

    /**

    /**
     * Salva uma entidade no banco com tenant_id (insert ou update)
     *
     * @param Model $entity Entidade a ser salva
     * @param int $tenantId ID do tenant
     * @return Model|false Entidade salva ou false em caso de erro
     */
    public function save( Model $entity, int $tenantId ): Model|false;

    /**
     * Remove uma entidade pelo ID e tenant_id
     *
     * @param int $id ID da entidade a ser removida
     * @param int $tenantId ID do tenant
     * @return bool True se removeu com sucesso, false caso contrário
     */
    public function deleteByIdAndTenantId( int $id, int $tenantId ): bool;

    /**
     * Remove uma entidade
     *
     * @param Model $entity Entidade a ser removida
     * @param int $tenantId ID do tenant
     * @return bool True se removeu com sucesso, false caso contrário
     */
    public function delete( Model $entity, int $tenantId ): bool;

    /**
     * Conta entidades por tenant_id
     *
     * @param int $tenantId ID do tenant
     * @param array $criteria Critérios opcionais de busca
     * @return int Número de entidades encontradas
     */
    public function countByTenantId( int $tenantId, array $criteria = [] ): int;

    /**
     * Verifica se existe uma entidade por critérios e tenant_id
     *
     * @param array $criteria Critérios de busca
     * @param int $tenantId ID do tenant
     * @return bool True se existe, false caso contrário
     */
    public function existsByTenantId( array $criteria, int $tenantId ): bool;

    /**
     * Busca entidades com paginação por tenant_id
     *
     * @param int $tenantId ID do tenant
     * @param int $page Página atual (inicia em 1)
     * @param int $perPage Itens por página
     * @param array $criteria Critérios opcionais de busca
     * @param array|null $orderBy Ordenação opcional
     * @return array ['data' => Model[], 'total' => int, 'current_page' => int, 'per_page' => int]
     */
    public function paginateByTenantId(
        int $tenantId,
        int $page = 1,
        int $perPage = 15,
        array $criteria = [],
        ?array $orderBy = null,
    ): array;

    /**
     * Remove múltiplas entidades por IDs e tenant_id
     *
     * @param array $id Array de IDs das entidades
     * @param int $tenantId ID do tenant
     * @return int Número de entidades removidas
     */
    public function deleteManyByIdsAndTenantId( array $id, int $tenantId ): int;

    /**
     * Atualiza múltiplas entidades por critérios e tenant_id
     *
     * @param array $criteria Critérios para seleção
     * @param array $updates Dados para atualização
     * @param int $tenantId ID do tenant
     * @return int Número de entidades atualizadas
     */
    public function updateManyByTenantId( array $criteria, array $updates, int $tenantId ): int;
}
