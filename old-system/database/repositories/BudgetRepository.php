<?php

declare(strict_types=1);

namespace app\database\repositories;

use app\database\entitiesORM\BudgetEntity;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * Repositório para entidades BudgetEntity.
 * Fornece métodos para operações de busca e manipulação de orçamentos.
 *
 * @extends EntityRepository<BudgetEntity>
 */
class BudgetRepository extends EntityRepository
{

    /**
     * Busca o último código de orçamento para um tenant.
     *
     * @param int $tenantId ID do tenant
     * @return string Último código ou '0000' se não houver
     */
    public function getLastCode( int $tenantId ): string
    {
        $qb = $this->createQueryBuilder( 'b' )
            ->select( 'SUBSTRING(b.code, -4) as lastCode' )
            ->where( 'b.tenant_id = :tenantId' )
            ->orderBy( 'b.id', 'DESC' )
            ->setMaxResults( 1 )
            ->setParameter( 'tenantId', $tenantId );

        $result = $qb->getQuery()->getOneOrNullResult();
        return $result ? (string) $result[ 'lastCode' ] : '0000';
    }

    /**
     * Busca orçamento por código e tenant ID.
     *
     * @param string $code Código do orçamento
     * @param int $tenantId ID do tenant
     * @return BudgetEntity|null
     */
    public function findByCode( string $code, int $tenantId ): ?BudgetEntity
    {
        return $this->findOneBy( [ 
            'code'      => $code,
            'tenant_id' => $tenantId
        ] );
    }

    /**
     * Busca orçamento completo por ID, incluindo dados do cliente.
     *
     * @param string $code Código do orçamento
     * @param int $tenantId ID do tenant
     * @return BudgetEntity|null
     */
    public function findFullByCode( string $code, int $tenantId ): ?BudgetEntity
    {
        $qb = $this->createQueryBuilder( 'b' )
            ->leftJoin( 'b.customer', 'c' )
            ->addSelect( 'c' )
            ->where( 'b.code = :code' )
            ->andWhere( 'b.tenant_id = :tenantId' )
            ->setParameter( 'code', $code )
            ->setParameter( 'tenantId', $tenantId );

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Busca orçamento por ID com dados do cliente.
     *
     * @param int $id ID do orçamento
     * @param int $tenantId ID do tenant
     * @return BudgetEntity|null
     */
    public function findByIdWithCustomer( int $id, int $tenantId ): ?BudgetEntity
    {
        $qb = $this->createQueryBuilder( 'b' )
            ->leftJoin( 'b.customer', 'c' )
            ->addSelect( 'c' )
            ->where( 'b.id = :id' )
            ->andWhere( 'b.tenant_id = :tenantId' )
            ->setParameter( 'id', $id )
            ->setParameter( 'tenantId', $tenantId );

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Verifica relacionamentos para deleção.
     *
     * @param int $id ID do orçamento
     * @param int $tenantId ID do tenant
     * @return array Informações de relacionamentos
     */
    public function checkRelationships( int $id, int $tenantId ): array
    {
        // Implementar contagem de serviços, invoices, etc. vinculados
        $qbServices = $this->getEntityManager()->createQueryBuilder()
            ->select( 'COUNT(s.id)' )
            ->from( 'app\database\entitiesORM\ServiceEntity', 's' )
            ->where( 's.budget_id = :id' )
            ->andWhere( 's.tenant_id = :tenantId' )
            ->setParameter( 'id', $id )
            ->setParameter( 'tenantId', $tenantId );

        $serviceCount = (int) $qbServices->getQuery()->getSingleScalarResult();

        // Similar para outros relacionamentos...

        return [ 
            'status' => 'success',
            'data'   => [ 
                'countRelationships' => $serviceCount,
                'tables'             => $serviceCount > 0 ? 'serviços' : 'nenhum'
            ]
        ];
    }

}