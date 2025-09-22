<?php

declare(strict_types=1);

namespace app\database\repositories;

use app\database\entitiesORM\RolePermissionEntity;
use app\database\repositories\AbstractNoTenantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Mapping\ClassMetadata;

/**
 * Repositório para gerenciar operações de dados da entidade RolePermissionEntity
 *
 * Esta classe fornece métodos para interagir com a tabela role_permissions no banco de dados,
 * incluindo operações de busca, criação, atualização e exclusão de associações entre roles e permissões.
 *
 * @author Sistema Easy Budget
 * @version 1.0
 */
class RolePermissionRepository extends AbstractNoTenantRepository
{
    /**
     * Construtor do repositório de role-permissions
     *
     * @param EntityManagerInterface $entityManager Gerenciador de entidades do Doctrine
     * @param ClassMetadata $classMetadata Metadados da classe RolePermissionEntity
     */
    public function __construct( EntityManagerInterface $entityManager, ClassMetadata $classMetadata )
    {
        parent::__construct( $entityManager, $classMetadata );
    }

    /**
     * Busca todas as permissões de um role específico
     *
     * @param int $roleId ID do role
     * @return RolePermissionEntity[] Array de entidades de role-permissions
     */
    public function findByRoleId( int $roleId ): array
    {
        return $this->createQueryBuilder( 'rp' )
            ->innerJoin( 'rp.role', 'r' )
            ->innerJoin( 'rp.permission', 'p' )
            ->where( 'r.id = :roleId' )
            ->setParameter( 'roleId', $roleId )
            ->orderBy( 'p.name', 'ASC' )
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca todos os roles que possuem uma permissão específica
     *
     * @param int $permissionId ID da permissão
     * @return RolePermissionEntity[] Array de entidades de role-permissions
     */
    public function findByPermissionId( int $permissionId ): array
    {
        return $this->createQueryBuilder( 'rp' )
            ->innerJoin( 'rp.role', 'r' )
            ->innerJoin( 'rp.permission', 'p' )
            ->where( 'p.id = :permissionId' )
            ->setParameter( 'permissionId', $permissionId )
            ->orderBy( 'r.name', 'ASC' )
            ->getQuery()
            ->getResult();
    }

    /**
     * Verifica se um role possui uma permissão específica
     *
     * @param int $roleId ID do role
     * @param int $permissionId ID da permissão
     * @return bool True se o role possui a permissão, false caso contrário
     */
    public function hasPermission( int $roleId, int $permissionId ): bool
    {
        $result = $this->createQueryBuilder( 'rp' )
            ->select( 'COUNT(rp.id)' )
            ->innerJoin( 'rp.role', 'r' )
            ->innerJoin( 'rp.permission', 'p' )
            ->where( 'r.id = :roleId' )
            ->andWhere( 'p.id = :permissionId' )
            ->setParameter( 'roleId', $roleId )
            ->setParameter( 'permissionId', $permissionId )
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result > 0;
    }

    /**
     * Remove todas as permissões de um role específico
     *
     * @param int $roleId ID do role
     * @return int Número de registros removidos
     */
    public function removeAllByRoleId( int $roleId ): int
    {
        return $this->createQueryBuilder( 'rp' )
            ->delete()
            ->where( 'rp.role = :roleId' )
            ->setParameter( 'roleId', $roleId )
            ->getQuery()
            ->execute();
    }

    /**
     * Remove uma permissão específica de todos os roles
     *
     * @param int $permissionId ID da permissão
     * @return int Número de registros removidos
     */
    public function removeAllByPermissionId( int $permissionId ): int
    {
        return $this->createQueryBuilder( 'rp' )
            ->delete()
            ->where( 'rp.permission = :permissionId' )
            ->setParameter( 'permissionId', $permissionId )
            ->getQuery()
            ->execute();
    }

    /**
     * Busca uma associação específica entre role e permissão
     *
     * @param int $roleId ID do role
     * @param int $permissionId ID da permissão
     * @return RolePermissionEntity|null Entidade da associação ou null se não encontrada
     */
    public function findByRoleAndPermission( int $roleId, int $permissionId ): ?RolePermissionEntity
    {
        return $this->createQueryBuilder( 'rp' )
            ->innerJoin( 'rp.role', 'r' )
            ->innerJoin( 'rp.permission', 'p' )
            ->where( 'r.id = :roleId' )
            ->andWhere( 'p.id = :permissionId' )
            ->setParameter( 'roleId', $roleId )
            ->setParameter( 'permissionId', $permissionId )
            ->getQuery()
            ->getOneOrNullResult();
    }

}
