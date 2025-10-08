<?php

declare(strict_types=1);

namespace app\database\repositories;

use app\database\entitiesORM\InvoiceEntity;
use Doctrine\ORM\EntityRepository;

/**
 * Repositório para entidades InvoiceEntity.
 *
 * @extends EntityRepository<InvoiceEntity>
 */
class InvoiceRepository extends EntityRepository
{
    /**
     * Busca fatura por critérios.
     *
     * @param array $criteria Critérios de busca
     * @param int $tenantId ID do tenant
     * @return InvoiceEntity|null
     */
    public function findByCriteriaWithTenant( array $criteria, int $tenantId ): ?InvoiceEntity
    {
        $criteria[ 'tenant_id' ] = $tenantId;
        return $this->findOneBy( $criteria );
    }

    /**
     * Busca fatura completa por código, incluindo dados relacionados.
     *
     * @param string $code Código da fatura
     * @param int $tenantId ID do tenant
     * @return InvoiceEntity|null
     */
    public function findFullByCode( string $code, int $tenantId ): ?InvoiceEntity
    {
        $qb = $this->createQueryBuilder( 'i' )
            ->leftJoin( 'i.service', 's' )
            ->leftJoin( 's.budget', 'b' )
            ->leftJoin( 'b.customer', 'c' )
            ->addSelect( 's', 'b', 'c' )
            ->where( 'i.code = :code' )
            ->andWhere( 'i.tenant_id = :tenantId' )
            ->setParameter( 'code', $code )
            ->setParameter( 'tenantId', $tenantId );

        return $qb->getQuery()->getOneOrNullResult();
    }

}