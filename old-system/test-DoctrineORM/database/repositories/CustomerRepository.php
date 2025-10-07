<?php

declare(strict_types=1);

namespace app\database\repositories;

use app\database\entitiesORM\CustomerEntity;
use Doctrine\ORM\EntityRepository;

/**
 * Repositório para entidades CustomerEntity.
 * Fornece métodos para operações de busca de clientes.
 *
 * @extends EntityRepository<CustomerEntity>
 */
class CustomerRepository extends EntityRepository
{
    /**
     * Busca cliente completo por ID, incluindo dados relacionados.
     *
     * @param int $id ID do cliente
     * @param int $tenantId ID do tenant
     * @return CustomerEntity|null
     */
    public function findFullById( int $id, int $tenantId ): ?CustomerEntity
    {
        $qb = $this->createQueryBuilder( 'c' )
            ->leftJoin( 'c.contacts', 'cont' )
            ->leftJoin( 'c.address', 'a' )
            ->addSelect( 'cont', 'a' )
            ->where( 'c.id = :id' )
            ->andWhere( 'c.tenant_id = :tenantId' )
            ->setParameter( 'id', $id )
            ->setParameter( 'tenantId', $tenantId );

        return $qb->getQuery()->getOneOrNullResult();
    }

}