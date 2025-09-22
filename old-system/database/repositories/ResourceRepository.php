<?php

namespace app\database\repositories;

use app\database\entitiesORM\ResourceEntity;
use app\interfaces\EntityORMInterface;
use Exception;
use RuntimeException;

/**
 * Repositório para gerenciar operações de dados da entidade Resource
 * 
 * Estende AbstractNoTenantRepository pois ResourceEntity não possui tenant_id.
 * Fornece métodos específicos para gestão de recursos globais do sistema.
 * 
 * @template T of ResourceEntity
 * @extends AbstractNoTenantRepository<T>
 */
class ResourceRepository extends AbstractNoTenantRepository
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
    // - findAllPaginatedHybrid(): array (híbrido para paginação)

    // Métodos legados removidos conforme refatoração:
    // - findByIdLegacy(int $id): array (removido - duplicava funcionalidade de findById)
    // - findBySlugLegacy(string $slug): array (removido - pode ser implementado com métodos protegidos)
    // - findByStatus(string $status): array (removido - pode ser implementado com findBy)
    // - findByInDev(bool $inDev): array (removido - pode ser implementado com findBy)
    // - saveLegacy(ResourceEntity $resource): array (removido - duplicava funcionalidade de save)
    // - deleteLegacy(ResourceEntity $resource): array (removido - duplicava funcionalidade de delete)
    // - findAllPaginated(int $page, int $limit): array (removido - pode usar findAllPaginatedHybrid)
}