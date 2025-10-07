<?php

namespace app\interfaces;

/**
 * Interface RepositoryNoTenantInterface
 *
 * Define o contrato para classes de repositório que não utilizam controle de tenant.
 * Responsável por fornecer métodos padronizados para acesso e manipulação de dados sem escopo multi-tenant.
 */
interface RepositoryNoTenantInterface extends BaseRepositoryInterface
{
    /**
     * Busca uma entidade pelo seu ID.
     *
     * @param int $id ID da entidade
     * @return EntityORMInterface|null Retorna a entidade encontrada ou null.
     */
    public function findById( int $id ): ?EntityORMInterface;

    /**
     * Busca entidades com base em critérios específicos.
     *
     * @param array<string, mixed> $criteria Critérios de busca
     * @param array<string, string>|null $orderBy Ordenação dos resultados
     * @param int|null $limit Limite de resultados
     * @param int|null $offset Deslocamento na consulta
     * @return array<int, ?EntityORMInterface> Retorna um array de entidades (pode ser vazio).
     */
    public function findBy( array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array;

    /**
     * Busca todas as entidades.
     *
     * @param array<string, mixed> $criteria Critérios adicionais de busca
     * @param array<string, string>|null $orderBy Ordenação dos resultados
     * @param int|null $limit Limite de resultados
     * @param int|null $offset Deslocamento na consulta
     * @return array<int, ?EntityORMInterface> Retorna um array de entidades (pode ser vazio).
     */
    public function findAll( array $criteria = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array;

    /**
     * Salva uma entidade no banco de dados.
     *
     * @param EntityORMInterface $entity Entidade a ser salva
     * @return EntityORMInterface|false Entidade completa com dados atualizados ou false em caso de falha
     */
    public function save( EntityORMInterface $entity ): EntityORMInterface|false;

    /**
     * Exclui uma entidade do banco de dados.
     *
     * @param int $id ID da entidade
     * @return bool Resultado da operação (true = sucesso, false = falha)
     */
    public function delete( int $id ): bool;
}