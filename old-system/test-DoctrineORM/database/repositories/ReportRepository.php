<?php

namespace app\database\repositories;

use app\database\entitiesORM\ReportEntity;
use app\interfaces\EntityORMInterface;
use Exception;

/**
 * Repositório para gerenciar operações de ReportEntity.
 *
 * Estende AbstractRepository para ter todos os métodos básicos com tenant
 * implementados automaticamente, adicionando métodos específicos de relatórios.
 *
 * @template T of ReportEntity
 * @extends AbstractRepository<T>
 */
class ReportRepository extends AbstractRepository
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
     * Busca relatórios por tenant ID usando método da classe pai.
     *
     * @param int $tenantId ID do tenant
     * @return array<int, EntityORMInterface> Array de entidades de relatório
     */
    public function findByTenantId( int $tenantId ): array
    {
        return $this->findAllByTenantId( $tenantId, [], [ 'createdAt' => 'DESC' ] );
    }

    /**
     * Busca relatórios por tipo em um tenant específico.
     *
     * @param string $type Tipo do relatório
     * @param int $tenantId ID do tenant
     * @return array<int, EntityORMInterface> Array de entidades de relatório
     */
    public function findByTypeAndTenantId( string $type, int $tenantId ): array
    {
        return $this->findAllByTenantId( $tenantId, [ 'type' => $type ], [ 'createdAt' => 'DESC' ] );
    }

    /**
     * Busca relatórios por status em um tenant específico.
     *
     * @param string $status Status do relatório
     * @param int $tenantId ID do tenant
     * @return array<int, EntityORMInterface> Array de entidades de relatório
     */
    public function findByStatusAndTenantId( string $status, int $tenantId ): array
    {
        return $this->findAllByTenantId( $tenantId, [ 'status' => $status ], [ 'createdAt' => 'DESC' ] );
    }

}
