<?php

namespace app\database\repositories;

use app\database\entitiesORM\ActivityEntity;

/**
 * Repositório para gerenciar atividades do sistema.
 * 
 * Estende AbstractRepository para ter todos os métodos básicos com tenant
 * implementados automaticamente, adicionando apenas métodos específicos da entidade.
 *
 * @template T of ActivityEntity
 * @extends AbstractRepository<T>
 */
class ActivityRepository extends AbstractRepository
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
     * Busca atividades por usuário e tenant.
     *
     * @param int $userId ID do usuário
     * @param int $tenant_id ID do tenant
     * @param array<string, string>|null $orderBy Ordenação dos resultados
     * @param int|null $limit Limite de resultados
     * @param int|null $offset Deslocamento na consulta
     * @return array<int, ActivityEntity> Lista de atividades
     */
    public function findByUserIdAndTenantId(int $userId, int $tenant_id, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        return $this->findBy(['userId' => $userId, 'tenantId' => $tenant_id], $orderBy, $limit, $offset);
    }

    /**
     * Busca atividades por tipo de ação e tenant.
     *
     * @param string $actionType Tipo da ação
     * @param int $tenant_id ID do tenant
     * @param array<string, string>|null $orderBy Ordenação dos resultados
     * @param int|null $limit Limite de resultados
     * @param int|null $offset Deslocamento na consulta
     * @return array<int, ActivityEntity> Lista de atividades
     */
    public function findByActionTypeAndTenantId(string $actionType, int $tenant_id, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        return $this->findBy(['actionType' => $actionType, 'tenantId' => $tenant_id], $orderBy, $limit, $offset);
    }
}
