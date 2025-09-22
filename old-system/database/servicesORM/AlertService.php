<?php

declare(strict_types=1);

namespace app\database\servicesORM;

use app\database\entitiesORM\AlertSettingsEntity;
use app\database\entitiesORM\MonitoringAlertHistoryEntity;
use app\database\entitiesORM\TenantEntity;
use app\interfaces\ServiceCustomInterface;
use app\interfaces\ServiceNoTenantInterface;
use core\services\NotificationService;
use DateTime;

/**
 * Serviço de alertas automáticos para monitoramento
 */
class AlertService implements ServiceCustomInterface
{
    private array               $thresholds;
    private NotificationService $notificationService;

    public function __construct()
    {
        $this->notificationService = new NotificationService();
        $this->loadThresholds();
    }

    /**
     * Carrega thresholds das configurações salvas ou usa padrões
     */
    private function loadThresholds(): void
    {
        $tenantId = $_SESSION[ 'tenant_id' ] ?? null;

        if ( $tenantId ) {
            $settings = $this->getSettingsFromDatabase();
            if ( $settings ) {
                $this->thresholds = [ 
                    'response_time_critical' => (float) $settings[ 'thresholds' ][ 'critical_response_time' ],
                    'response_time_warning'  => (float) $settings[ 'thresholds' ][ 'warning_response_time' ],
                    'success_rate_critical'  => (float) $settings[ 'thresholds' ][ 'critical_success_rate' ],
                    'success_rate_warning'   => (float) $settings[ 'thresholds' ][ 'warning_success_rate' ],
                    'error_count_critical'   => 10,
                    'memory_usage_critical'  => (int) $settings[ 'thresholds' ][ 'max_memory_mb' ] * 1024 * 1024
                ];
                return;
            }
        }

        // Thresholds padrão
        $this->thresholds = [ 
            'response_time_critical' => 200.0,
            'response_time_warning'  => 100.0,
            'success_rate_critical'  => 90.0,
            'success_rate_warning'   => 95.0,
            'error_count_critical'   => 10,
            'memory_usage_critical'  => 50 * 1024 * 1024
        ];
    }

    /**
     * Verifica métricas e dispara alertas se necessário
     */
    public function checkMetricsAndAlert(): void
    {
        $recentMetrics = $this->getRecentMetrics();

        foreach ( $recentMetrics as $middleware => $metrics ) {
            $this->checkMiddlewareMetrics( $middleware, $metrics );
        }
    }

    /**
     * Obtém métricas dos últimos 5 minutos
     */
    private function getRecentMetrics(): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select( 'm.middlewareName, COUNT(m) as totalRequests, AVG(m.responseTime) as avgResponseTime, MAX(m.responseTime) as maxResponseTime, AVG(m.memoryUsage) as avgMemoryUsage, MAX(m.memoryUsage) as maxMemoryUsage' )
            ->from( 'app\\database\\entitiesORM\\MiddlewareMetricHistoryEntity', 'm' )
            ->where( 'm.createdAt >= :fiveMinutesAgo' )
            ->groupBy( 'm.middlewareName' )
            ->setParameter( 'fiveMinutesAgo', new \DateTime( '-5 minutes' ) );

        $results = $qb->getQuery()->getResult();

        $metrics = [];
        foreach ( $results as $row ) {
            $metrics[ $row[ 'middlewareName' ] ] = [ 
                'total_requests'    => (int) $row[ 'totalRequests' ],
                'success_rate'      => 100.0, // Calcular baseado em status_code
                'avg_response_time' => (float) $row[ 'avgResponseTime' ],
                'max_response_time' => (float) $row[ 'maxResponseTime' ],
                'avg_memory_usage'  => (int) $row[ 'avgMemoryUsage' ],
                'max_memory_usage'  => (int) $row[ 'maxMemoryUsage' ]
            ];
        }

        return $metrics;
    }

    /**
     * Verifica métricas de um middleware específico
     */
    private function checkMiddlewareMetrics( string $middleware, array $metrics ): void
    {
        // Verificar tempo de resposta
        if ( $metrics[ 'max_response_time' ] >= $this->thresholds[ 'response_time_critical' ] ) {
            $this->createAlert(
                $middleware,
                'CRITICAL',
                'response_time',
                "Tempo de resposta crítico: {$metrics[ 'max_response_time' ]}ms",
            );
        } elseif ( $metrics[ 'avg_response_time' ] >= $this->thresholds[ 'response_time_warning' ] ) {
            $this->createAlert(
                $middleware,
                'WARNING',
                'response_time',
                "Tempo de resposta alto: {$metrics[ 'avg_response_time' ]}ms",
            );
        }

        // Verificar taxa de sucesso
        if ( $metrics[ 'success_rate' ] <= $this->thresholds[ 'success_rate_critical' ] ) {
            $this->createAlert(
                $middleware,
                'CRITICAL',
                'success_rate',
                "Taxa de sucesso crítica: {$metrics[ 'success_rate' ]}%",
            );
        } elseif ( $metrics[ 'success_rate' ] <= $this->thresholds[ 'success_rate_warning' ] ) {
            $this->createAlert(
                $middleware,
                'WARNING',
                'success_rate',
                "Taxa de sucesso baixa: {$metrics[ 'success_rate' ]}%",
            );
        }

        // Verificar uso de memória
        if ( $metrics[ 'max_memory_usage' ] >= $this->thresholds[ 'memory_usage_critical' ] ) {
            $this->createAlert(
                $middleware,
                'WARNING',
                'memory_usage',
                "Alto uso de memória: " . round( $metrics[ 'max_memory_usage' ] / 1024 / 1024, 2 ) . "MB"
            );
        }
    }

    /**
     * Cria um alerta no sistema
     */
    public function createAlert( string $middleware, string $severity, string $type, string $message ): void
    {
        // Verificar se já existe alerta similar nos últimos 10 minutos
        if ( $this->alertExists( $middleware, $type, $severity ) ) {
            return;
        }

        $tenant = $this->getCurrentTenant();
        if ( !$tenant ) {
            return;
        }

        $alert = new MonitoringAlertHistoryEntity(
            $tenant,
            $type,
            $severity,
            $message,
            $message,
            $middleware,
        );

        $this->entityManager->persist( $alert );
        $this->entityManager->flush();

        // Enviar notificação
        $this->sendNotification( $middleware, $severity, $message );
    }

    /**
     * Verifica se já existe alerta similar recente
     */
    private function alertExists( string $middleware, string $type, string $severity ): bool
    {
        $qb    = $this->entityManager->createQueryBuilder();
        $count = $qb->select( 'COUNT(a)' )
            ->from( 'app\\database\\entitiesORM\\MonitoringAlertHistoryEntity', 'a' )
            ->where( 'a.component = :middleware' )
            ->andWhere( 'a.alertType = :type' )
            ->andWhere( 'a.severity = :severity' )
            ->andWhere( 'a.createdAt >= :tenMinutesAgo' )
            ->andWhere( 'a.status = :status' )
            ->setParameter( 'middleware', $middleware )
            ->setParameter( 'type', $type )
            ->setParameter( 'severity', $severity )
            ->setParameter( 'tenMinutesAgo', new \DateTime( '-10 minutes' ) )
            ->setParameter( 'status', 'active' )
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Obtém o tenant atual do contexto
     */
    private function getCurrentTenant(): ?TenantEntity
    {
        if ( !isset( $_SESSION[ 'tenant_id' ] ) ) {
            return null;
        }

        return $this->entityManager->find( TenantEntity::class, $_SESSION[ 'tenant_id' ] );
    }

    /**
     * Envia notificação do alerta
     */
    private function sendNotification( string $middleware, string $severity, string $message ): void
    {
        // Log do alerta
        error_log( "ALERT: {$severity} - {$middleware} - {$message}" );

        // Enviar email
        $this->notificationService->sendAlertEmail( $middleware, $severity, $message );

        // Enviar SMS para alertas críticos
        if ( $severity === 'CRITICAL' ) {
            $this->notificationService->sendAlertSMS( $middleware, $severity, $message );
        }
    }

    /**
     * Obtém alertas ativos
     */
    public function getActiveAlerts(): array
    {
        $sql = "SELECT * FROM monitoring_alerts_history
                WHERE status = 'ACTIVE'
                ORDER BY created_at DESC
                LIMIT 50";

        $stmt = $this->pdo->prepare( $sql );
        $stmt->execute();

        return $stmt->fetchAll( PDO::FETCH_ASSOC );
    }

    /**
     * Resolve um alerta
     */
    public function resolveAlert( int $alertId ): bool
    {
        $sql = "UPDATE monitoring_alerts_history
                SET status = 'RESOLVED', resolved_at = NOW()
                WHERE id = ?";

        $stmt = $this->pdo->prepare( $sql );
        return $stmt->execute( [ $alertId ] );
    }

    /**
     * Busca configurações do banco de dados
     */
    private function getSettingsFromDatabase(): ?array
    {
        try {
            $entity = $this->entityManager->getRepository( AlertSettingsEntity::class)
                ->findOneBy( [] );

            return $entity ? $entity->getSettings() : null;
        } catch ( \Exception $e ) {
            return null;
        }
    }

    /**
     * Salva configurações no banco de dados
     */
    public function saveSettings( int $tenantId, array $settings ): bool
    {
        try {
            $entity = $this->entityManager->getRepository( 'app\\database\\entitiesORM\\AlertSettingsEntity' )
                ->findOneBy( [ 'tenant_id' => $tenantId ] );

            if ( !$entity ) {
                $entityClass = 'app\\database\\entitiesORM\\AlertSettingsEntity';
                $entity      = new $entityClass();
                $entity->setTenantId( $tenantId );
            }

            $entity->setSettings( $settings );

            $this->entityManager->persist( $entity );
            $this->entityManager->flush();

            // Recarregar thresholds
            $this->loadThresholds();

            return true;
        } catch ( \Exception $e ) {
            return false;
        }
    }

    /**
     * Obtém configurações para um tenant
     */
    public function getSettings(): array
    {
        $settings = $this->getSettingsFromDatabase( $tenantId );

        if ( $settings ) {
            return $settings;
        }

        // Retorna configurações padrão
        return [ 
            'thresholds'    => [ 
                'critical_success_rate'  => 90,
                'warning_success_rate'   => 95,
                'critical_response_time' => 200,
                'warning_response_time'  => 100,
                'max_memory_mb'          => 512,
                'max_cpu_percent'        => 80
            ],
            'notifications' => [ 
                'email_enabled'   => true,
                'email_addresses' => '',
                'webhook_enabled' => false,
                'webhook_url'     => '',
                'slack_enabled'   => false,
                'slack_webhook'   => ''
            ],
            'monitoring'    => [ 
                'check_interval'      => 5,
                'auto_resolve'        => true,
                'min_severity'        => 'WARNING',
                'enabled_middlewares' => [ 'auth', 'admin', 'user', 'provider', 'guest' ]
            ],
            'interface'     => [ 
                'auto_refresh' => 30,
                'theme'        => 'light',
                'timezone'     => 'America/Sao_Paulo'
            ]
        ];
    }

}
