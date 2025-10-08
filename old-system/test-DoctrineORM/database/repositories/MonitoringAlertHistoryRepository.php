<?php

declare(strict_types=1);

namespace app\database\repositories;

use app\database\entitiesORM\MonitoringAlertHistoryEntity;
use app\database\entitiesORM\TenantEntity;
use app\database\entitiesORM\UserEntity;
use app\database\repositories\AbstractRepository;
use DateTime;
use Doctrine\ORM\QueryBuilder;

/**
 * Repositório para gerenciar histórico de alertas de monitoramento
 *
 * Este repositório fornece métodos para consultar, filtrar e analisar
 * alertas de monitoramento do sistema, incluindo funcionalidades para
 * gerenciar status, severidade e resolução de alertas.
 */
class MonitoringAlertHistoryRepository extends AbstractRepository
{
    /**
     * Busca alertas por período específico
     *
     * @param TenantEntity $tenant Tenant para filtrar os dados
     * @param DateTime $startDate Data inicial do período
     * @param DateTime $endDate Data final do período
     * @param array $filters Filtros adicionais (tipo, severidade, etc.)
     * @return array Lista de alertas encontrados
     */
    public function findByPeriod(
        TenantEntity $tenant,
        DateTime $startDate,
        DateTime $endDate,
        array $filters = [],
    ): array {
        $qb = $this->createQueryBuilder( 'mah' )
            ->where( 'mah.tenant = :tenant' )
            ->andWhere( 'mah.createdAt >= :startDate' )
            ->andWhere( 'mah.createdAt <= :endDate' )
            ->setParameter( 'tenant', $tenant )
            ->setParameter( 'startDate', $startDate )
            ->setParameter( 'endDate', $endDate )
            ->orderBy( 'mah.createdAt', 'DESC' );

        $this->applyFilters( $qb, $filters );

        return $qb->getQuery()->getResult();
    }

    /**
     * Busca alertas ativos (não resolvidos)
     *
     * @param TenantEntity $tenant Tenant para filtrar os dados
     * @param string|null $severity Severidade específica (opcional)
     * @param string|null $alertType Tipo de alerta específico (opcional)
     * @return array Lista de alertas ativos
     */
    public function findActiveAlerts(
        TenantEntity $tenant,
        ?string $severity = null,
        ?string $alertType = null,
    ): array {
        $qb = $this->createQueryBuilder( 'mah' )
            ->where( 'mah.tenant = :tenant' )
            ->andWhere( 'mah.status IN (:activeStatuses)' )
            ->setParameter( 'tenant', $tenant )
            ->setParameter( 'activeStatuses', [ 'open', 'acknowledged' ] )
            ->orderBy( 'mah.severity', 'DESC' )
            ->addOrderBy( 'mah.createdAt', 'DESC' );

        if ( $severity ) {
            $qb->andWhere( 'mah.severity = :severity' )
                ->setParameter( 'severity', $severity );
        }

        if ( $alertType ) {
            $qb->andWhere( 'mah.alertType = :alertType' )
                ->setParameter( 'alertType', $alertType );
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Busca alertas por severidade
     *
     * @param TenantEntity $tenant Tenant para filtrar os dados
     * @param string $severity Severidade do alerta (critical, high, medium, low)
     * @param DateTime|null $since Data a partir da qual buscar (opcional)
     * @param int $limit Limite de resultados (padrão: 100)
     * @return array Lista de alertas da severidade especificada
     */
    public function findBySeverity(
        TenantEntity $tenant,
        string $severity,
        ?DateTime $since = null,
        int $limit = 100,
    ): array {
        $qb = $this->createQueryBuilder( 'mah' )
            ->where( 'mah.tenant = :tenant' )
            ->andWhere( 'mah.severity = :severity' )
            ->setParameter( 'tenant', $tenant )
            ->setParameter( 'severity', $severity )
            ->orderBy( 'mah.createdAt', 'DESC' )
            ->setMaxResults( $limit );

        if ( $since ) {
            $qb->andWhere( 'mah.createdAt >= :since' )
                ->setParameter( 'since', $since );
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Busca alertas por tipo específico
     *
     * @param TenantEntity $tenant Tenant para filtrar os dados
     * @param string $alertType Tipo do alerta
     * @param DateTime|null $since Data a partir da qual buscar (opcional)
     * @param int $limit Limite de resultados (padrão: 100)
     * @return array Lista de alertas do tipo especificado
     */
    public function findByType(
        TenantEntity $tenant,
        string $alertType,
        ?DateTime $since = null,
        int $limit = 100,
    ): array {
        $qb = $this->createQueryBuilder( 'mah' )
            ->where( 'mah.tenant = :tenant' )
            ->andWhere( 'mah.alertType = :alertType' )
            ->setParameter( 'tenant', $tenant )
            ->setParameter( 'alertType', $alertType )
            ->orderBy( 'mah.createdAt', 'DESC' )
            ->setMaxResults( $limit );

        if ( $since ) {
            $qb->andWhere( 'mah.createdAt >= :since' )
                ->setParameter( 'since', $since );
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Busca alertas por componente específico
     *
     * @param TenantEntity $tenant Tenant para filtrar os dados
     * @param string $component Componente a ser analisado
     * @param DateTime|null $since Data a partir da qual buscar (opcional)
     * @param int $limit Limite de resultados (padrão: 100)
     * @return array Lista de alertas do componente
     */
    public function findByComponent(
        TenantEntity $tenant,
        string $component,
        ?DateTime $since = null,
        int $limit = 100,
    ): array {
        $qb = $this->createQueryBuilder( 'mah' )
            ->where( 'mah.tenant = :tenant' )
            ->andWhere( 'mah.component = :component' )
            ->setParameter( 'tenant', $tenant )
            ->setParameter( 'component', $component )
            ->orderBy( 'mah.createdAt', 'DESC' )
            ->setMaxResults( $limit );

        if ( $since ) {
            $qb->andWhere( 'mah.createdAt >= :since' )
                ->setParameter( 'since', $since );
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Busca alertas por endpoint específico
     *
     * @param TenantEntity $tenant Tenant para filtrar os dados
     * @param string $endpoint Endpoint a ser analisado
     * @param string|null $method Método HTTP (opcional)
     * @param int $limit Limite de resultados (padrão: 100)
     * @return array Lista de alertas do endpoint
     */
    public function findByEndpoint(
        TenantEntity $tenant,
        string $endpoint,
        ?string $method = null,
        int $limit = 100,
    ): array {
        $qb = $this->createQueryBuilder( 'mah' )
            ->where( 'mah.tenant = :tenant' )
            ->andWhere( 'mah.endpoint = :endpoint' )
            ->setParameter( 'tenant', $tenant )
            ->setParameter( 'endpoint', $endpoint )
            ->orderBy( 'mah.createdAt', 'DESC' )
            ->setMaxResults( $limit );

        if ( $method ) {
            $qb->andWhere( 'mah.method = :method' )
                ->setParameter( 'method', $method );
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Busca alertas reconhecidos por usuário específico
     *
     * @param TenantEntity $tenant Tenant para filtrar os dados
     * @param UserEntity $user Usuário que reconheceu os alertas
     * @param DateTime|null $since Data a partir da qual buscar (opcional)
     * @param int $limit Limite de resultados (padrão: 100)
     * @return array Lista de alertas reconhecidos pelo usuário
     */
    public function findAcknowledgedByUser(
        TenantEntity $tenant,
        UserEntity $user,
        ?DateTime $since = null,
        int $limit = 100,
    ): array {
        $qb = $this->createQueryBuilder( 'mah' )
            ->where( 'mah.tenant = :tenant' )
            ->andWhere( 'mah.acknowledgedBy = :user' )
            ->setParameter( 'tenant', $tenant )
            ->setParameter( 'user', $user )
            ->orderBy( 'mah.acknowledgedAt', 'DESC' )
            ->setMaxResults( $limit );

        if ( $since ) {
            $qb->andWhere( 'mah.acknowledgedAt >= :since' )
                ->setParameter( 'since', $since );
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Busca alertas resolvidos por usuário específico
     *
     * @param TenantEntity $tenant Tenant para filtrar os dados
     * @param UserEntity $user Usuário que resolveu os alertas
     * @param DateTime|null $since Data a partir da qual buscar (opcional)
     * @param int $limit Limite de resultados (padrão: 100)
     * @return array Lista de alertas resolvidos pelo usuário
     */
    public function findResolvedByUser(
        TenantEntity $tenant,
        UserEntity $user,
        ?DateTime $since = null,
        int $limit = 100,
    ): array {
        $qb = $this->createQueryBuilder( 'mah' )
            ->where( 'mah.tenant = :tenant' )
            ->andWhere( 'mah.resolvedBy = :user' )
            ->setParameter( 'tenant', $tenant )
            ->setParameter( 'user', $user )
            ->orderBy( 'mah.resolvedAt', 'DESC' )
            ->setMaxResults( $limit );

        if ( $since ) {
            $qb->andWhere( 'mah.resolvedAt >= :since' )
                ->setParameter( 'since', $since );
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Calcula estatísticas de alertas por período
     *
     * @param TenantEntity $tenant Tenant para filtrar os dados
     * @param DateTime $startDate Data inicial do período
     * @param DateTime $endDate Data final do período
     * @param string|null $alertType Tipo de alerta específico (opcional)
     * @return array Estatísticas calculadas
     */
    public function getAlertStats(
        TenantEntity $tenant,
        DateTime $startDate,
        DateTime $endDate,
        ?string $alertType = null,
    ): array {
        $qb = $this->createQueryBuilder( 'mah' )
            ->select( [ 
                'COUNT(mah.id) as total_alerts',
                'SUM(CASE WHEN mah.severity = \'critical\' THEN 1 ELSE 0 END) as critical_alerts',
                'SUM(CASE WHEN mah.severity = \'high\' THEN 1 ELSE 0 END) as high_alerts',
                'SUM(CASE WHEN mah.severity = \'medium\' THEN 1 ELSE 0 END) as medium_alerts',
                'SUM(CASE WHEN mah.severity = \'low\' THEN 1 ELSE 0 END) as low_alerts',
                'SUM(CASE WHEN mah.status = \'resolved\' THEN 1 ELSE 0 END) as resolved_alerts',
                'SUM(CASE WHEN mah.status = \'acknowledged\' THEN 1 ELSE 0 END) as acknowledged_alerts',
                'SUM(CASE WHEN mah.status = \'open\' THEN 1 ELSE 0 END) as open_alerts',
                'AVG(mah.occurrenceCount) as avg_occurrence_count'
            ] )
            ->where( 'mah.tenant = :tenant' )
            ->andWhere( 'mah.createdAt >= :startDate' )
            ->andWhere( 'mah.createdAt <= :endDate' )
            ->setParameter( 'tenant', $tenant )
            ->setParameter( 'startDate', $startDate )
            ->setParameter( 'endDate', $endDate );

        if ( $alertType ) {
            $qb->andWhere( 'mah.alertType = :alertType' )
                ->setParameter( 'alertType', $alertType );
        }

        $result = $qb->getQuery()->getSingleResult();

        // Calcula taxas de resolução
        $totalAlerts                 = (int) $result[ 'total_alerts' ];
        $result[ 'resolution_rate' ] = $totalAlerts > 0
            ? round( ( (int) $result[ 'resolved_alerts' ] / $totalAlerts ) * 100, 2 )
            : 0;

        $result[ 'acknowledgment_rate' ] = $totalAlerts > 0
            ? round( ( (int) $result[ 'acknowledged_alerts' ] / $totalAlerts ) * 100, 2 )
            : 0;

        return $result;
    }

    /**
     * Busca alertas recorrentes (com alta contagem de ocorrências)
     *
     * @param TenantEntity $tenant Tenant para filtrar os dados
     * @param int $minOccurrences Número mínimo de ocorrências
     * @param DateTime|null $since Data a partir da qual buscar (opcional)
     * @param int $limit Limite de resultados (padrão: 50)
     * @return array Lista de alertas recorrentes
     */
    public function findRecurringAlerts(
        TenantEntity $tenant,
        int $minOccurrences = 5,
        ?DateTime $since = null,
        int $limit = 50,
    ): array {
        $qb = $this->createQueryBuilder( 'mah' )
            ->where( 'mah.tenant = :tenant' )
            ->andWhere( 'mah.occurrenceCount >= :minOccurrences' )
            ->setParameter( 'tenant', $tenant )
            ->setParameter( 'minOccurrences', $minOccurrences )
            ->orderBy( 'mah.occurrenceCount', 'DESC' )
            ->addOrderBy( 'mah.createdAt', 'DESC' )
            ->setMaxResults( $limit );

        if ( $since ) {
            $qb->andWhere( 'mah.createdAt >= :since' )
                ->setParameter( 'since', $since );
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Busca alertas não reconhecidos há mais de X tempo
     *
     * @param TenantEntity $tenant Tenant para filtrar os dados
     * @param DateTime $threshold Data limite para considerar alerta antigo
     * @param string|null $severity Severidade específica (opcional)
     * @return array Lista de alertas não reconhecidos antigos
     */
    public function findUnacknowledgedOldAlerts(
        TenantEntity $tenant,
        DateTime $threshold,
        ?string $severity = null,
    ): array {
        $qb = $this->createQueryBuilder( 'mah' )
            ->where( 'mah.tenant = :tenant' )
            ->andWhere( 'mah.status = :status' )
            ->andWhere( 'mah.createdAt <= :threshold' )
            ->setParameter( 'tenant', $tenant )
            ->setParameter( 'status', 'open' )
            ->setParameter( 'threshold', $threshold )
            ->orderBy( 'mah.createdAt', 'ASC' );

        if ( $severity ) {
            $qb->andWhere( 'mah.severity = :severity' )
                ->setParameter( 'severity', $severity );
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Aplica filtros adicionais à query
     *
     * @param QueryBuilder $qb Query builder a ser modificado
     * @param array $filters Filtros a serem aplicados
     * @return void
     */
    private function applyFilters( QueryBuilder $qb, array $filters ): void
    {
        if ( isset( $filters[ 'alert_type' ] ) ) {
            $qb->andWhere( 'mah.alertType = :alertType' )
                ->setParameter( 'alertType', $filters[ 'alert_type' ] );
        }

        if ( isset( $filters[ 'severity' ] ) ) {
            $qb->andWhere( 'mah.severity = :severity' )
                ->setParameter( 'severity', $filters[ 'severity' ] );
        }

        if ( isset( $filters[ 'status' ] ) ) {
            $qb->andWhere( 'mah.status = :status' )
                ->setParameter( 'status', $filters[ 'status' ] );
        }

        if ( isset( $filters[ 'component' ] ) ) {
            $qb->andWhere( 'mah.component LIKE :component' )
                ->setParameter( 'component', '%' . $filters[ 'component' ] . '%' );
        }

        if ( isset( $filters[ 'endpoint' ] ) ) {
            $qb->andWhere( 'mah.endpoint LIKE :endpoint' )
                ->setParameter( 'endpoint', '%' . $filters[ 'endpoint' ] . '%' );
        }

        if ( isset( $filters[ 'method' ] ) ) {
            $qb->andWhere( 'mah.method = :method' )
                ->setParameter( 'method', $filters[ 'method' ] );
        }

        if ( isset( $filters[ 'min_occurrence_count' ] ) ) {
            $qb->andWhere( 'mah.occurrenceCount >= :minOccurrenceCount' )
                ->setParameter( 'minOccurrenceCount', $filters[ 'min_occurrence_count' ] );
        }

        if ( isset( $filters[ 'acknowledged_by' ] ) && $filters[ 'acknowledged_by' ] ) {
            $qb->andWhere( 'mah.acknowledgedBy = :acknowledgedBy' )
                ->setParameter( 'acknowledgedBy', $filters[ 'acknowledged_by' ] );
        }

        if ( isset( $filters[ 'resolved_by' ] ) && $filters[ 'resolved_by' ] ) {
            $qb->andWhere( 'mah.resolvedBy = :resolvedBy' )
                ->setParameter( 'resolvedBy', $filters[ 'resolved_by' ] );
        }
    }

    /**
     * Retorna o nome da entidade gerenciada por este repositório
     *
     * @return string Nome da classe da entidade
     */
    protected function getEntityClass(): string
    {
        return MonitoringAlertHistoryEntity::class;
    }

}
