<?php

namespace app\database\repositories;

use app\database\entitiesORM\SessionEntity;
use DateTime;
use Exception;
use RuntimeException;

/**
 * Repositório para gerenciar sessões de usuários.
 *
 * Estende AbstractRepository para ter todos os métodos básicos com tenant
 * implementados automaticamente, adicionando métodos específicos de sessões.
 *
 * @template T of SessionEntity
 * @extends AbstractRepository<T>
 */
class SessionRepository extends AbstractRepository
{
    // Todos os métodos obrigatórios já estão implementados na classe abstrata:
    // - findByIdAndTenantId(int $id, int $tenant_id): ?EntityORMInterface
    // - findAllByTenantId(int $tenant_id, array $criteria = []): array
    // - save(EntityORMInterface $entity, int $tenant_id): EntityORMInterface
    // - deleteByIdAndTenantId(int $id, int $tenant_id): bool

    /**
     * Busca uma sessão ativa pelo token.
     *
     * @param string $sessionToken Token da sessão
     * @return SessionEntity|null Retorna a sessão encontrada ou null
     */
    public function findActiveByToken( string $sessionToken ): ?SessionEntity
    {
        try {
            $qb = $this->getEntityManager()->createQueryBuilder();

            $qb->select( 's' )
                ->from( $this->getEntityName(), 's' )
                ->where( 's.sessionToken = :token' )
                ->andWhere( 's.isActive = :isActive' )
                ->andWhere( 's.expiresAt > :now' )
                ->setParameter( 'token', $sessionToken )
                ->setParameter( 'isActive', true )
                ->setParameter( 'now', new DateTime() )
                ->setMaxResults( 1 );

            return $qb->getQuery()->getOneOrNullResult();
        } catch ( Exception $e ) {
            error_log( "Erro ao buscar sessão por token: " . $e->getMessage() );
            return null;
        }
    }

    /**
     * Busca todas as sessões ativas de um usuário.
     *
     * @param int $userId ID do usuário
     * @return array<int, SessionEntity> Array de sessões ativas
     */
    public function findActiveSessionsByUserId( int $userId ): array
    {
        try {
            $qb = $this->getEntityManager()->createQueryBuilder();

            $qb->select( 's' )
                ->from( $this->getEntityName(), 's' )
                ->where( 's.user = :userId' )
                ->andWhere( 's.isActive = :isActive' )
                ->andWhere( 's.expiresAt > :now' )
                ->setParameter( 'userId', $userId )
                ->setParameter( 'isActive', true )
                ->setParameter( 'now', new DateTime() )
                ->orderBy( 's.lastActivity', 'DESC' );

            return $qb->getQuery()->getResult();
        } catch ( Exception $e ) {
            throw new RuntimeException(
                "Falha ao buscar sessões ativas do usuário {$userId}: " . $e->getMessage(),
                0,
                $e,
            );
        }
    }

    /**
     * Desativa todas as sessões de um usuário.
     *
     * @param int $userId ID do usuário
     * @return int Número de sessões desativadas
     */
    public function deactivateAllUserSessions( int $userId ): int
    {
        try {
            $qb = $this->getEntityManager()->createQueryBuilder();

            $qb->update( $this->getEntityName(), 's' )
                ->set( 's.isActive', ':isActive' )
                ->where( 's.user = :userId' )
                ->setParameter( 'isActive', false )
                ->setParameter( 'userId', $userId );

            return $qb->getQuery()->execute();
        } catch ( Exception $e ) {
            throw new RuntimeException(
                "Falha ao desativar sessões do usuário {$userId}: " . $e->getMessage(),
                0,
                $e,
            );
        }
    }

    /**
     * Atualiza a última atividade de uma sessão.
     *
     * @param string $sessionToken Token da sessão
     * @return bool True se a sessão foi atualizada, false caso contrário
     */
    public function updateLastActivity( string $sessionToken ): bool
    {
        try {
            $qb = $this->getEntityManager()->createQueryBuilder();

            $qb->update( $this->getEntityName(), 's' )
                ->set( 's.lastActivity', ':now' )
                ->where( 's.sessionToken = :token' )
                ->andWhere( 's.isActive = :isActive' )
                ->setParameter( 'now', new DateTime() )
                ->setParameter( 'token', $sessionToken )
                ->setParameter( 'isActive', true );

            $affectedRows = $qb->getQuery()->execute();
            return $affectedRows > 0;
        } catch ( Exception $e ) {
            error_log( "Erro ao atualizar última atividade da sessão {$sessionToken}: " . $e->getMessage() );
            return false;
        }
    }

    /**
     * Remove sessões expiradas do banco de dados.
     *
     * @param int $daysOld Número de dias para considerar sessões antigas (padrão: 7)
     * @return int Número de sessões removidas
     */
    public function cleanupExpiredSessions( int $daysOld = 7 ): int
    {
        try {
            $cutoffDate = new DateTime( "-{$daysOld} days" );

            $qb = $this->getEntityManager()->createQueryBuilder();

            $qb->delete( $this->getEntityName(), 's' )
                ->where( 's.expiresAt < :cutoffDate' )
                ->andWhere( 's.isActive = :isActive' )
                ->setParameter( 'cutoffDate', $cutoffDate )
                ->setParameter( 'isActive', false );

            return $qb->getQuery()->execute();
        } catch ( Exception $e ) {
            throw new RuntimeException(
                "Falha na limpeza de sessões expiradas: " . $e->getMessage(),
                0,
                $e,
            );
        }
    }

    /**
     * Conta o número de sessões ativas de um usuário.
     *
     * @param int $userId ID do usuário
     * @return int Número de sessões ativas
     */
    public function countActiveSessionsByUserId( int $userId ): int
    {
        try {
            $qb = $this->getEntityManager()->createQueryBuilder();

            $qb->select( 'COUNT(s.id)' )
                ->from( $this->getEntityName(), 's' )
                ->where( 's.user = :userId' )
                ->andWhere( 's.isActive = :isActive' )
                ->andWhere( 's.expiresAt > :now' )
                ->setParameter( 'userId', $userId )
                ->setParameter( 'isActive', true )
                ->setParameter( 'now', new DateTime() );

            return (int) $qb->getQuery()->getSingleScalarResult();
        } catch ( Exception $e ) {
            error_log( "Erro ao contar sessões ativas do usuário {$userId}: " . $e->getMessage() );
            return 0;
        }
    }

    /**
     * Busca sessões por endereço IP.
     *
     * @param string $ipAddress Endereço IP
     * @param int $limit Limite de resultados (padrão: 10)
     * @return array<int, SessionEntity> Array de sessões
     */
    public function findSessionsByIpAddress( string $ipAddress, int $limit = 10 ): array
    {
        try {
            $qb = $this->getEntityManager()->createQueryBuilder();

            $qb->select( 's' )
                ->from( $this->getEntityName(), 's' )
                ->where( 's.ipAddress = :ipAddress' )
                ->setParameter( 'ipAddress', $ipAddress )
                ->orderBy( 's.createdAt', 'DESC' )
                ->setMaxResults( $limit );

            return $qb->getQuery()->getResult();
        } catch ( Exception $e ) {
            throw new RuntimeException(
                "Falha ao buscar sessões por IP {$ipAddress}: " . $e->getMessage(),
                0,
                $e,
            );
        }
    }

    /**
     * Busca uma sessão ativa de um usuário específico.
     *
     * @param int $userId ID do usuário
     * @return SessionEntity|null Sessão ativa ou null
     */
    public function findActiveByUserId( int $userId ): ?SessionEntity
    {
        try {
            $qb = $this->getEntityManager()->createQueryBuilder();

            $qb->select( 's' )
                ->from( $this->getEntityName(), 's' )
                ->where( 's.user = :userId' )
                ->andWhere( 's.isActive = :isActive' )
                ->andWhere( 's.expiresAt > :now' )
                ->setParameter( 'userId', $userId )
                ->setParameter( 'isActive', true )
                ->setParameter( 'now', new DateTime() )
                ->orderBy( 's.lastActivity', 'DESC' )
                ->setMaxResults( 1 );

            return $qb->getQuery()->getOneOrNullResult();
        } catch ( Exception $e ) {
            error_log( "Erro ao buscar sessão ativa do usuário {$userId}: " . $e->getMessage() );
            return null;
        }
    }

    /**
     * Verifica se existe uma sessão ativa para um usuário específico.
     *
     * @param int $userId ID do usuário
     * @return bool True se existe sessão ativa, false caso contrário
     */
    public function hasActiveSession( int $userId ): bool
    {
        return $this->countActiveSessionsByUserId( $userId ) > 0;
    }

    /**
     * Desativa uma sessão específica pelo token.
     *
     * @param string $sessionToken Token da sessão
     * @return bool True se a sessão foi desativada, false caso contrário
     */
    public function deactivateSessionByToken( string $sessionToken ): bool
    {
        try {
            $qb = $this->getEntityManager()->createQueryBuilder();

            $qb->update( $this->getEntityName(), 's' )
                ->set( 's.isActive', ':isActive' )
                ->where( 's.sessionToken = :token' )
                ->setParameter( 'isActive', false )
                ->setParameter( 'token', $sessionToken );

            $affectedRows = $qb->getQuery()->execute();
            return $affectedRows > 0;
        } catch ( Exception $e ) {
            error_log( "Erro ao desativar sessão {$sessionToken}: " . $e->getMessage() );
            return false;
        }
    }

}
