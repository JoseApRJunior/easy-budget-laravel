<?php

declare(strict_types=1);

namespace core\traits;

use app\database\entitiesORM\MiddlewareMetricHistoryEntity;
use app\database\entitiesORM\TenantEntity;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Trait para coleta automática de métricas em middlewares
 */
trait MetricsCollectionTrait
{
    private float $startTime;
    private int   $startMemory;

    /**
     * Inicia coleta de métricas
     */
    protected function startMetricsCollection(): void
    {
        $this->startTime   = microtime( true );
        $this->startMemory = memory_get_usage( true );
    }

    /**
     * Finaliza coleta e armazena métricas
     */
    protected function endMetricsCollection( string $middlewareName, int $statusCode = 200 ): void
    {
        $endTime   = microtime( true );
        $endMemory = memory_get_usage( true );

        $metrics = [
            'middleware_name' => $middlewareName,
            'execution_time'  => ( $endTime - $this->startTime ) * 1000, // ms
            'memory_usage'    => $endMemory - $this->startMemory,
            'status_code'     => $statusCode,
            'timestamp'       => new DateTime(),
            'ip_address'      => $_SERVER[ 'REMOTE_ADDR' ] ?? null,
            'user_agent'      => $_SERVER[ 'HTTP_USER_AGENT' ] ?? null,
            'request_uri'     => $_SERVER[ 'REQUEST_URI' ] ?? null,
            'request_method'  => $_SERVER[ 'REQUEST_METHOD' ] ?? 'GET'
        ];

        $this->storeMetrics( $metrics );
    }

    /**
     * Armazena métricas no banco
     */
    private function storeMetrics( array $metrics ): void
    {
        try {
            $pdo = new \PDO( 'mysql:host=localhost;dbname=easybudget;charset=utf8mb4', 'root', '' );
            $pdo->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );

            $sql = "INSERT INTO middleware_metrics_history
                    (tenant_id, middleware_name, endpoint, method, response_time, memory_usage, status_code, created_at, ip_address, user_agent)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $pdo->prepare( $sql );
            $stmt->execute( [
                1, // tenant_id padrão
                $metrics[ 'middleware_name' ],
                $metrics[ 'request_uri' ], // endpoint
                $metrics[ 'request_method' ], // method
                $metrics[ 'execution_time' ], // response_time
                $metrics[ 'memory_usage' ],
                $metrics[ 'status_code' ],
                $metrics[ 'timestamp' ]->format( 'Y-m-d H:i:s' ),
                $metrics[ 'ip_address' ],
                $metrics[ 'user_agent' ]
            ] );
        } catch ( \Exception $e ) {
            // Log silencioso para não afetar performance
            error_log( "Metrics collection failed: " . $e->getMessage() );
        }
    }

}
