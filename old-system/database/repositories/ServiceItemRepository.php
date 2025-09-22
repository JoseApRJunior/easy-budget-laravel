<?php

namespace app\database\repositories;

use app\database\entitiesORM\ServiceItemEntity;
use app\interfaces\EntityORMInterface;
use Exception;

/**
 * Repository para gerenciar operações de ServiceItemEntity
 *
 * Estende AbstractRepository para ter todos os métodos básicos com tenant
 * implementados automaticamente, adicionando métodos específicos de itens de serviço.
 *
 * @template T of ServiceItemEntity
 * @extends AbstractRepository<T>
 */
class ServiceItemRepository extends AbstractRepository
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
    // Métodos básicos da interface agora são herdados de AbstractRepository

    /**
     * Busca todos os itens de serviço por ID do serviço
     *
     * @param int $serviceId ID do serviço
     * @param int|null $tenantId ID do tenant (opcional)
     * @return array Lista de itens de serviço
     */
    public function getAllServiceItemsByIdService( int $serviceId, ?int $tenantId = null ): array
    {
        $queryBuilder = $this->createQueryBuilder( 'si' )
            ->where( 'si.service = :serviceId' )
            ->setParameter( 'serviceId', $serviceId );

        if ( $tenantId !== null ) {
            $queryBuilder->andWhere( 'si.tenant_id = :tenantId' )
                ->setParameter( 'tenantId', $tenantId );
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Cria um novo item de serviço
     *
     * @param ServiceItemEntity $serviceItem Entidade do item de serviço
     * @param int $tenantId ID do tenant
     * @return EntityORMInterface Resultado da operação
     */
    public function create( ServiceItemEntity $serviceItem, int $tenantId ): EntityORMInterface
    {
        try {
            return $this->save( $serviceItem, $tenantId );
        } catch ( Exception $e ) {
            throw $e;
        }
    }

    /**
     * Atualiza um item de serviço existente
     *
     * @param ServiceItemEntity $serviceItem Entidade do item de serviço
     * @return EntityORMInterface Resultado da operação
     */
    public function update( ServiceItemEntity $serviceItem ): EntityORMInterface
    {
        try {
            $this->getEntityManager()->flush();
            return $serviceItem;
        } catch ( Exception $e ) {
            throw $e;
        }
    }

    /**
     * Remove um item de serviço
     *
     * @param int $id ID do item de serviço
     * @param int $tenantId ID do tenant
     * @return bool Resultado da operação
     */
    public function delete( int $id, int $tenantId ): bool
    {
        try {
            return $this->deleteByIdAndTenantId( $id, $tenantId );
        } catch ( Exception $e ) {
            throw $e;
        }
    }

}
