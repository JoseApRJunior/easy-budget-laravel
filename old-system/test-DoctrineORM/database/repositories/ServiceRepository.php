<?php

namespace app\database\repositories;

use app\database\entitiesORM\ServiceEntity;
use Doctrine\ORM\NoResultException;

class ServiceRepository extends AbstractRepository
{

    /**
     * Busca serviço por ID e tenant.
     *
     * @param int $id ID do serviço
     * @param int $tenantId ID do tenant
     * @return ServiceEntity|null
     */
    public function findByIdAndTenantId( int $id, int $tenantId ): ?ServiceEntity
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select( 's' )
            ->from( ServiceEntity::class, 's' )
            ->where( 's.id = :id' )
            ->andWhere( 's.tenantId = :tenantId' )
            ->setParameter( 'id', $id )
            ->setParameter( 'tenantId', $tenantId );

        try {
            return $qb->getQuery()->getSingleResult();
        } catch ( NoResultException ) {
            return null;
        }
    }

    /**
     * Busca todos os serviços completos por ID de orçamento.
     *
     * @param int $budgetId ID do orçamento
     * @param int $tenantId ID do tenant
     * @return array<ServiceEntity>
     */
    public function getAllServiceFullByIdBudget( int $budgetId, int $tenantId ): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select( 's', 'ss', 'si', 'p' )
            ->from( ServiceEntity::class, 's' )
            ->leftJoin( 's.serviceStatuses', 'ss' )
            ->leftJoin( 's.serviceItems', 'si' )
            ->leftJoin( 'si.product', 'p' )
            ->where( 's.budgetId = :budgetId' )
            ->andWhere( 's.tenantId = :tenantId' )
            ->setParameter( 'budgetId', $budgetId )
            ->setParameter( 'tenantId', $tenantId );

        return $qb->getQuery()->getResult();
    }

    /**
     * Busca serviços por ID de orçamento.
     *
     * @param int $budgetId ID do orçamento
     * @param int $tenantId ID do tenant
     * @return array<ServiceEntity>
     */
    public function getServiceByBudgetId( int $budgetId, int $tenantId ): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select( 's' )
            ->from( ServiceEntity::class, 's' )
            ->where( 's.budgetId = :budgetId' )
            ->andWhere( 's.tenantId = :tenantId' )
            ->setParameter( 'budgetId', $budgetId )
            ->setParameter( 'tenantId', $tenantId );

        return $qb->getQuery()->getResult();
    }

    /**
     * Busca último código de serviço por orçamento.
     *
     * @param int $budgetId ID do orçamento
     * @param int $tenantId ID do tenant
     * @return string Último código ou vazio
     */
    public function getLastCode( int $budgetId, int $tenantId ): string
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select( 'MAX(s.code) as lastCode' )
            ->from( ServiceEntity::class, 's' )
            ->where( 's.budgetId = :budgetId' )
            ->andWhere( 's.tenantId = :tenantId' )
            ->setParameter( 'budgetId', $budgetId )
            ->setParameter( 'tenantId', $tenantId );

        $result = $qb->getQuery()->getSingleScalarResult();
        return $result ?: '';
    }

}