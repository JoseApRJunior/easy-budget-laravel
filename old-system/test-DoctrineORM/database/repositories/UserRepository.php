<?php

namespace app\database\repositories;

use app\database\entitiesORM\UserEntity;
use app\database\entitiesORM\UserRolesEntity;
use app\interfaces\EntityORMInterface;

/**
 * Repositório para gerenciar usuários.
 *
 * Estende AbstractRepository para ter todos os métodos básicos com tenant
 * implementados automaticamente, adicionando métodos específicos de usuários.
 *
 * @template T of UserEntity
 * @extends AbstractRepository<T>
 */
class UserRepository extends AbstractRepository
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
     * Busca as permissões/roles de um usuário em um tenant específico.
     *
     * @param int $userId O ID do usuário
     * @param int $tenantId O ID do tenant
     * @return array<int, string> Array com os nomes das permissões
     */
    public function findUserRolesByTenantId( int $userId, int $tenantId ): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select( 'role.name' )
            ->from( UserRolesEntity::class, 'userRole' )
            ->join( 'userRole.role', 'role' )
            ->where( 'userRole.user = :userId' )
            ->andWhere( 'userRole.tenant_id = :tenantId' )
            ->setParameter( 'userId', $userId )
            ->setParameter( 'tenantId', $tenantId );

        $result = $qb->getQuery()->getScalarResult();
        return array_column( $result, 'name' );
    }

    /**
     * Busca as permissões de um usuário em um tenant específico.
     *
     * @param int $userId O ID do usuário
     * @param int $tenantId O ID do tenant
     * @return array<int, string> Array com os nomes das permissões
     */
    public function findUserPermissionsByTenantId( int $userId, int $tenantId ): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select( 'p.name' )
            ->from( UserRolesEntity::class, 'userRole' )
            ->join( 'userRole.role', 'role' )
            ->join( 'role.rolePermissions', 'rolePermission' )
            ->join( 'rolePermission.permission', 'p' )
            ->where( 'userRole.user = :userId' )
            ->andWhere( 'userRole.tenant_id = :tenantId' )
            ->setParameter( 'userId', $userId )
            ->setParameter( 'tenantId', $tenantId );

        $result = $qb->getQuery()->getScalarResult();
        return array_column( $result, 'name' );
    }

    /**
     * Busca usuários de um tenant que possuem roles/permissões.
     *
     * @param int $tenantId O ID do tenant
     * @return array<int,EntityORMInterface>
     */
    public function findUsersByTenantWithRoles( int $tenantId ): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $subQb = $this->getEntityManager()->createQueryBuilder();
        $subQb->select( 'DISTINCT ur.user' )
            ->from( UserRolesEntity::class, 'ur' )
            ->where( 'ur.tenant_id = :tenantId' );

        $qb->select( 'u' )
            ->from( $this->getEntityName(), 'u' )
            ->where( 'u.tenant_id = :tenantId' )
            ->andWhere( $qb->expr()->in( 'u.id', $subQb->getDQL() ) )
            ->setParameter( 'tenantId', $tenantId );

        return $qb->getQuery()->getResult();
    }

}
