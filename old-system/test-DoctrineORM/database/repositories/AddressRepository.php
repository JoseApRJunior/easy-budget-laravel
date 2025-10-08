<?php

namespace app\database\repositories;

use app\database\entitiesORM\AddressEntity;
use Exception;

/**
 * Repositório para gerenciar operações de endereços.
 *
 * Esta classe estende AbstractNoTenantRepository e fornece métodos
 * para buscar, criar, atualizar e deletar endereços no banco de dados usando Doctrine ORM.
 *
 * @template T of AddressEntity
 * @extends AbstractNoTenantRepository<T>
 * @package app\database\repositories
 */
class AddressRepository extends AbstractNoTenantRepository
{
    // Todos os métodos obrigatórios já estão implementados na classe abstrata:
    // - findById(int $id): ?EntityORMInterface
    // - findBy(array $criteria, ...): array
    // - findAll(array $criteria = [], ...): array
    // - save(EntityORMInterface $entity): EntityORMInterface|false
    // - delete(int $id): bool

    // Métodos auxiliares disponíveis da classe pai:
    // - findBySlug(string $slug): ?EntityORMInterface (protegido)
    // - findActive(): array (protegido)
    // - count(array $criteria = []): int (público)
    // - exists(int $id): bool (protegido)

    /**
     * Lista todos os endereços com paginação usando método híbrido da classe base.
     *
     * @param int $limit Limite de resultados por página
     * @param int $offset Offset para paginação
     * @return array<string, mixed> Array com entities e informações de paginação
     */
    public function findAllPaginated( int $limit = 20, int $offset = 0 ): array
    {
        return $this->findAllPaginatedHybrid(
            limit: $limit,
            offset: $offset,
            criteria: [],
            orderBy: [ 'id' => 'DESC' ],
            alias: 'a',
        );
    }

}
