<?php

declare(strict_types=1);

namespace app\database\repositories;

use app\database\entitiesORM\PermissionEntity;
use app\database\repositories\AbstractNoTenantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Mapping\ClassMetadata;

/**
 * Repositório para gerenciar operações de dados da entidade PermissionEntity
 *
 * Esta classe fornece métodos para interagir com a tabela permissions no banco de dados,
 * incluindo operações de busca, criação, atualização e exclusão de permissões.
 *
 * @author Sistema Easy Budget
 * @version 1.0
 */
class PermissionRepository extends AbstractNoTenantRepository
{
    /**
     * Construtor do repositório de permissões
     *
     * @param EntityManagerInterface $entityManager Gerenciador de entidades do Doctrine
     * @param ClassMetadata $classMetadata Metadados da classe PermissionEntity
     */
    public function __construct( EntityManagerInterface $entityManager, ClassMetadata $classMetadata )
    {
        parent::__construct( $entityManager, $classMetadata );
    }

    /**
     * Busca uma permissão pelo seu nome
     *
     * @param string $name Nome da permissão
     * @return PermissionEntity|null Entidade da permissão ou null se não encontrada
     */
    public function findByName( string $name ): ?PermissionEntity
    {
        return $this->findOneBy( [ 'name' => $name ] );
    }

    /**
     * Busca todas as permissões ativas
     *
     * @return PermissionEntity[] Array de entidades de permissões ativas
     */
    public function findActivePermissions(): array
    {
        return $this->createQueryBuilder( 'p' )
            ->where( 'p.isActive = :active' )
            ->setParameter( 'active', true )
            ->orderBy( 'p.name', 'ASC' )
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca permissões por IDs
     *
     * @param array $ids Array de IDs das permissões
     * @return PermissionEntity[] Array de entidades de permissões
     */
    public function findByIds( array $ids ): array
    {
        if ( empty( $ids ) ) {
            return [];
        }

        return $this->createQueryBuilder( 'p' )
            ->where( 'p.id IN (:ids)' )
            ->setParameter( 'ids', $ids )
            ->orderBy( 'p.name', 'ASC' )
            ->getQuery()
            ->getResult();
    }

    /**
     * Conta o total de permissões no sistema
     *
     * @return int Número total de permissões
     */
    public function countAll(): int
    {
        return (int) $this->createQueryBuilder( 'p' )
            ->select( 'COUNT(p.id)' )
            ->getQuery()
            ->getSingleScalarResult();
    }

}
