<?php

declare(strict_types=1);

namespace app\database\repositories;

use app\database\entitiesORM\MiddlewareMetricHistoryEntity;
use app\database\entitiesORM\TenantEntity;
use app\support\ServiceResult;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Repository para histórico de métricas de middleware
 */
class MiddlewareMetricHistoryRepository extends AbstractRepository
{
    public function __construct( EntityManagerInterface $entityManager )
    {
        parent::__construct( $entityManager, $entityManager->getClassMetadata( MiddlewareMetricHistoryEntity::class) );
    }

    /**
     * Busca métricas por período
     */
    public function findByDateRange( TenantEntity $tenant, \DateTimeInterface $startDate, \DateTimeInterface $endDate )
    {
        return $this->createQueryBuilder( 'm' )
            ->where( 'm.tenant = :tenant' )
            ->andWhere( 'm.createdAt >= :startDate' )
            ->andWhere( 'm.createdAt <= :endDate' )
            ->setParameter( 'tenant', $tenant )
            ->setParameter( 'startDate', $startDate )
            ->setParameter( 'endDate', $endDate )
            ->orderBy( 'm.createdAt', 'DESC' )
            ->getQuery()->getResult();
    }

    /**
     * Busca métricas por middleware
     */
    public function findByMiddleware( TenantEntity $tenant, string $middlewareName )
    {
        return $this->createQueryBuilder( 'm' )
            ->where( 'm.tenant = :tenant' )
            ->andWhere( 'm.middlewareName = :middlewareName' )
            ->setParameter( 'tenant', $tenant )
            ->setParameter( 'middlewareName', $middlewareName )
            ->orderBy( 'm.createdAt', 'DESC' )
            ->getQuery()->getResult();
    }

}
