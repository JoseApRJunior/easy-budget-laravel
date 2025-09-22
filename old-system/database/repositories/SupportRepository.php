<?php

namespace app\database\repositories;

use app\database\entitiesORM\SupportEntity;
use app\interfaces\EntityORMInterface;
use Exception;
use RuntimeException;

/**
 * Repositório para gerenciar operações de dados da entidade Support
 * 
 * Estende AbstractRepository para ter todos os métodos básicos com tenant
 * implementados automaticamente, adicionando métodos específicos de suporte.
 * 
 * @template T of SupportEntity
 * @extends AbstractRepository<T>
 */
class SupportRepository extends AbstractRepository
{
    // Todos os métodos obrigatórios já estão implementados na classe abstrata:
    // - findByIdAndTenantId(int $id, int $tenant_id): ?EntityORMInterface
    // - findAllByTenantId(int $tenant_id, array $criteria = []): array
    // - save(EntityORMInterface $entity, int $tenant_id): EntityORMInterface
    // - deleteByIdAndTenantId(int $id, int $tenant_id): bool
    
    // Métodos auxiliares disponíveis da classe pai:
    // - findBySlugAndTenantId(string $slug, int $tenant_id): ?EntityORMInterface (protegido)
    // - findActiveByTenantId(int $tenant_id): array (protegido)
    // - countByTenantId(int $tenant_id, array $criteria = []): int (protegido)
    // - existsByTenantId(int $id, int $tenant_id): bool (protegido)
    // - validateTenantOwnership(EntityORMInterface $entity, int $tenant_id): void (protegido)
    // - isSlugUniqueInTenant(string $slug, int $tenant_id, ?int $excludeId = null): bool (protegido)

    // Métodos legados removidos conforme refatoração:
    // - findById(int $id): array (removido - duplicava funcionalidade de findByIdAndTenantId)
    // - findByEmail(string $email): array (removido - pode ser implementado com findBy)
    // - findByTenantId(int $tenantId): array (removido - duplicava funcionalidade de findAllByTenantId)
    // - findBySubject(string $subject): array (removido - pode ser implementado com métodos do Doctrine)
    // - saveSupport(SupportEntity $support): array (removido - duplicava funcionalidade de save)
    // - delete(SupportEntity $support): array (removido - duplicava funcionalidade de deleteByIdAndTenantId)
    // - findAllPaginated(int $page, int $limit): array (removido - pode usar findAllPaginatedHybrid da classe base)
}