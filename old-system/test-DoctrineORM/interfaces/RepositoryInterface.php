<?php

namespace app\interfaces;

// Removido: use App\Support\ServiceResult;
// Adicionado para tipagem genérica da entidade
use app\interfaces\EntityORMInterface;

/**
 * Interface RepositoryInterface
 *
 * Define o contrato para todas as classes de repositório no sistema.
 * Responsável por fornecer métodos padronizados para acesso e manipulação de dados.
 */
interface RepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Busca uma entidade pelo seu ID.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant
     * @return EntityORMInterface|null Retorna a entidade encontrada ou null.
     */
    public function findByIdAndTenantId( int $id, int $tenant_id ): ?EntityORMInterface;

    /**
     * Busca todas as entidades de um tenant.
     *
     * @param int $tenant_id ID do tenant
     * @param array<string, mixed> $criteria Critérios adicionais de busca
     * @param array<string, string>|null $orderBy Ordenação dos resultados
     * @param int|null $limit Limite de resultados
     * @param int|null $offset Deslocamento na consulta
     * @return array<int, ?EntityORMInterface> Retorna um array de entidades (pode ser vazio).
     */
    public function findAllByTenantId( int $tenant_id, array $criteria = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array;

    /**
     * Salva uma entidade no banco de dados.
     *
     * @param EntityORMInterface $entity Entidade a ser salva
     * @param int $tenant_id ID do tenant
     * @return EntityORMInterface|false Entidade completa com dados atualizados ou false em caso de falha
     */
    public function save( EntityORMInterface $entity, int $tenant_id ): EntityORMInterface|false;

    /**
     * Exclui uma entidade do banco de dados.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant
     * @return bool Retorna true em caso de sucesso na exclusão, false caso contrário.
     */
    public function deleteByIdAndTenantId( int $id, int $tenant_id ): bool;
}
