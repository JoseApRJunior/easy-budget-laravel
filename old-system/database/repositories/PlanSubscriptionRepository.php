<?php

namespace app\database\repositories;

use app\database\entitiesORM\PlanSubscriptionEntity;

/**
 * Repositório para gerenciar assinaturas de planos.
 * 
 * Estende AbstractRepository para ter todos os métodos básicos com tenant
 * implementados automaticamente, adicionando métodos específicos de assinaturas.
 *
 * @template T of PlanSubscriptionEntity
 * @extends AbstractRepository<T>
 */
class PlanSubscriptionRepository extends AbstractRepository
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
    /**
     * Busca assinatura ativa por tenant e provider.
     *
     * @param int $tenant_id ID do tenant
     * @param int $providerId ID do provider
     * @return PlanSubscriptionEntity|null Assinatura ativa ou null
     */
    public function findActiveByTenantIdAndProvider(int $tenant_id, int $providerId): ?PlanSubscriptionEntity
    {
        /** @var PlanSubscriptionEntity|null $result */
        $result = $this->findOneBy([
            'tenantId' => $tenant_id,
            'providerId' => $providerId,
            'status' => 'active'
        ], ['createdAt' => 'DESC']);
        return $result;
    }
    
    /**
     * Busca assinaturas por status e tenant.
     *
     * @param string $status Status da assinatura
     * @param int $tenant_id ID do tenant
     * @return array<int, PlanSubscriptionEntity> Lista de assinaturas
     */
    public function findByStatusAndTenantId(string $status, int $tenant_id): array
    {
        return $this->findBy(['status' => $status, 'tenantId' => $tenant_id], ['createdAt' => 'DESC']);
    }
}

