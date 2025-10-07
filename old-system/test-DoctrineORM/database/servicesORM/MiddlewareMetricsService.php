<?php
declare(strict_types=1);

namespace app\database\servicesORM;

use app\database\entitiesORM\MiddlewareMetricHistoryEntity;
use app\database\entitiesORM\TenantEntity;
use app\database\entitiesORM\UserEntity;
use app\enums\OperationStatus;
use app\support\ServiceResult;
use Doctrine\ORM\EntityManager;

/**
 * Service para gerenciar métricas de middlewares
 *
 * Responsável por persistir, consultar e analisar métricas de performance
 * dos middlewares do sistema.
 */
class MiddlewareMetricsService
{
    private EntityManager $entityManager;
    private               $repository;

    public function __construct( EntityManager $entityManager = null )
    {
        if ( $entityManager ) {
            $this->entityManager = $entityManager;
        } else {
            global $container;
            /** @var \DI\Container $container */
            $this->entityManager = $container->get( EntityManager::class);
        }
        $this->repository = $this->entityManager->getRepository( MiddlewareMetricHistoryEntity::class);
    }

    /**
     * Registra métricas de execução de middleware
     *
     * @param array $metricsData Dados das métricas coletadas
     * @return ServiceResult
     */
    public function recordMetrics( array $metricsData ): ServiceResult
    {
        try {
            // Validar dados obrigatórios
            $validationResult = $this->validateMetricsData( $metricsData );
            if ( !$validationResult->isSuccess() ) {
                return $validationResult;
            }

            // Obter tenant e user se disponíveis
            $tenant = null;
            $user   = null;

            if ( isset( $metricsData[ 'tenant_id' ] ) && $metricsData[ 'tenant_id' ] ) {
                $tenant = $this->entityManager->getRepository( TenantEntity::class)->find( $metricsData[ 'tenant_id' ] );
            }

            if ( isset( $metricsData[ 'user_id' ] ) && $metricsData[ 'user_id' ] ) {
                $user = $this->entityManager->getRepository( UserEntity::class)->find( $metricsData[ 'user_id' ] );
            }

            // Se não há tenant, usar o primeiro disponível ou criar um padrão
            if ( !$tenant ) {
                $tenant = $this->entityManager->getRepository( TenantEntity::class)->findOneBy( [] ) ?? $this->getDefaultTenant();
            }

            // Criar entidade de métrica
            $metric = new MiddlewareMetricHistoryEntity(
                $tenant,
                $metricsData[ 'middleware_name' ],
                $metricsData[ 'route' ] ?? $_SERVER[ 'REQUEST_URI' ] ?? '/',
                $metricsData[ 'method' ] ?? $_SERVER[ 'REQUEST_METHOD' ] ?? 'GET',
                (float) $metricsData[ 'execution_time' ],
                (int) $metricsData[ 'memory_usage' ],
                (int) ( $metricsData[ 'status_code' ] ?? 200 )
            );

            // Configurar campos opcionais
            if ( $user ) {
                $metric->setUser( $user );
            }

            if ( !empty( $metricsData[ 'error_message' ] ) ) {
                $metric->setErrorMessage( $metricsData[ 'error_message' ] );
            }

            if ( !empty( $metricsData[ 'ip_address' ] ) ) {
                $metric->setIpAddress( $metricsData[ 'ip_address' ] );
            }

            if ( !empty( $metricsData[ 'user_agent' ] ) ) {
                $metric->setUserAgent( $metricsData[ 'user_agent' ] );
            }

            // Configurar métricas adicionais se disponíveis
            if ( isset( $metricsData[ 'database_queries' ] ) ) {
                $metric->setDatabaseQueries( (int) $metricsData[ 'database_queries' ] );
            }

            if ( isset( $metricsData[ 'cache_hits' ] ) ) {
                $metric->setCacheHits( (int) $metricsData[ 'cache_hits' ] );
            }

            if ( isset( $metricsData[ 'cache_misses' ] ) ) {
                $metric->setCacheMisses( (int) $metricsData[ 'cache_misses' ] );
            }

            if ( isset( $metricsData[ 'cpu_usage' ] ) ) {
                $metric->setCpuUsage( (float) $metricsData[ 'cpu_usage' ] );
            }

            // Persistir no banco
            $this->entityManager->persist( $metric );
            $this->entityManager->flush();

            return ServiceResult::success( [ 
                'metric_id'       => $metric->getId(),
                'middleware_name' => $metric->getMiddlewareName(),
                'execution_time'  => $metric->getResponseTime()
            ], 'Métricas registradas com sucesso' );

        } catch ( \Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao registrar métricas: ' . $e->getMessage() );
        }
    }

    /**
     * Obtém métricas de performance por período
     *
     * @param string $middlewareName Nome do middleware
     * @param \DateTime $startDate Data inicial
     * @param \DateTime $endDate Data final
     * @return ServiceResult
     */
    public function getPerformanceMetrics( string $middlewareName, \DateTime $startDate, \DateTime $endDate ): ServiceResult
    {
        try {
            // Buscar diretamente na tabela via DBAL
            $connection = $this->entityManager->getConnection();
            
            $sql = "SELECT * FROM middleware_metrics_history 
                    WHERE created_at BETWEEN ? AND ? ";
            $params = [$startDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d H:i:s')];
            
            if (!empty($middlewareName)) {
                $sql .= " AND middleware_name = ?";
                $params[] = $middlewareName;
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            $metrics = $connection->executeQuery($sql, $params)->fetchAllAssociative();

            $summary = [ 
                'total_executions'   => count( $metrics ),
                'avg_execution_time' => 0,
                'max_execution_time' => 0,
                'min_execution_time' => PHP_FLOAT_MAX,
                'avg_memory_usage'   => 0,
                'success_rate'       => 0,
                'error_count'        => 0
            ];

            if ( !empty( $metrics ) ) {
                $totalTime    = 0;
                $totalMemory  = 0;
                $successCount = 0;

                foreach ( $metrics as $metric ) {
                    $execTime    = (float) $metric['response_time'];
                    $totalTime += $execTime;
                    $totalMemory += (int) $metric['memory_usage'];

                    if ( $metric['status_code'] >= 200 && $metric['status_code'] < 300 ) {
                        $successCount++;
                    } else {
                        $summary[ 'error_count' ]++;
                    }

                    $summary[ 'max_execution_time' ] = max( $summary[ 'max_execution_time' ], $execTime );
                    $summary[ 'min_execution_time' ] = min( $summary[ 'min_execution_time' ], $execTime );
                }

                $summary[ 'avg_execution_time' ] = round( $totalTime / count( $metrics ), 2 );
                $summary[ 'avg_memory_usage' ]   = round( $totalMemory / count( $metrics ) );
                $summary[ 'success_rate' ]       = round( ( $successCount / count( $metrics ) ) * 100, 2 );
            }

            return ServiceResult::success( [ 
                'summary' => $summary,
                'metrics' => $metrics
            ], 'Métricas obtidas com sucesso' );

        } catch ( \Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao obter métricas: ' . $e->getMessage() );
        }
    }

    /**
     * Identifica gargalos de performance
     *
     * @param float $thresholdMs Limite de tempo em milissegundos
     * @return ServiceResult
     */
    public function identifyBottlenecks( float $thresholdMs = 1000.0 ): ServiceResult
    {
        try {
            $qb = $this->repository->createQueryBuilder( 'm' )
                ->where( 'm.responseTime > :threshold' )
                ->setParameter( 'threshold', $thresholdMs )
                ->orderBy( 'm.responseTime', 'DESC' )
                ->setMaxResults( 100 );

            $bottlenecks = $qb->getQuery()->getResult();

            $analysis = [ 
                'total_slow_executions' => count( $bottlenecks ),
                'threshold_ms'          => $thresholdMs,
                'affected_middlewares'  => [],
                'slowest_routes'        => []
            ];

            foreach ( $bottlenecks as $bottleneck ) {
                $middlewareName = $bottleneck->getMiddlewareName();
                $route          = $bottleneck->getRoute();

                if ( !isset( $analysis[ 'affected_middlewares' ][ $middlewareName ] ) ) {
                    $analysis[ 'affected_middlewares' ][ $middlewareName ] = 0;
                }
                $analysis[ 'affected_middlewares' ][ $middlewareName ]++;

                $analysis[ 'slowest_routes' ][] = [ 
                    'middleware'     => $middlewareName,
                    'route'          => $route,
                    'execution_time' => $bottleneck->getResponseTime(),
                    'timestamp'      => $bottleneck->getCreatedAt()->format( 'Y-m-d H:i:s' )
                ];
            }

            // Ordenar rotas mais lentas
            usort( $analysis[ 'slowest_routes' ], function ($a, $b) {
                return $b[ 'execution_time' ] <=> $a[ 'execution_time' ];
            } );

            return ServiceResult::success( $analysis, 'Análise de gargalos concluída' );

        } catch ( \Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao analisar gargalos: ' . $e->getMessage() );
        }
    }

    /**
     * Limpa métricas antigas
     *
     * @param int $daysToKeep Dias para manter as métricas
     * @return ServiceResult
     */
    public function cleanOldMetrics( int $daysToKeep = 30 ): ServiceResult
    {
        try {
            $cutoffDate = new \DateTime();
            $cutoffDate->modify( "-{$daysToKeep} days" );

            $qb = $this->entityManager->createQueryBuilder()
                ->delete( MiddlewareMetricHistoryEntity::class, 'm' )
                ->where( 'm.createdAt < :cutoffDate' )
                ->setParameter( 'cutoffDate', $cutoffDate );

            $deletedCount = $qb->getQuery()->execute();

            return ServiceResult::success( [ 
                'deleted_count' => $deletedCount,
                'cutoff_date'   => $cutoffDate->format( 'Y-m-d H:i:s' )
            ], 'Limpeza concluída' );

        } catch ( \Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro na limpeza: ' . $e->getMessage() );
        }
    }

    /**
     * Valida dados de métricas
     *
     * @param array $data Dados para validar
     * @return ServiceResult
     */
    private function validateMetricsData( array $data ): ServiceResult
    {
        $required = [ 'middleware_name', 'execution_time', 'memory_usage' ];

        foreach ( $required as $field ) {
            if ( !isset( $data[ $field ] ) ) {
                return ServiceResult::error( OperationStatus::INVALID_DATA, "Campo obrigatório ausente: {$field}" );
            }
        }

        if ( !is_numeric( $data[ 'execution_time' ] ) || $data[ 'execution_time' ] < 0 ) {
            return ServiceResult::error( OperationStatus::INVALID_DATA, 'Tempo de execução deve ser um número positivo' );
        }

        if ( !is_numeric( $data[ 'memory_usage' ] ) ) {
            return ServiceResult::error( OperationStatus::INVALID_DATA, 'Uso de memória deve ser um número' );
        }

        return ServiceResult::success( [], 'Dados válidos' );
    }

    /**
     * Obtém ou cria um tenant padrão para métricas
     *
     * @return TenantEntity
     */
    private function getDefaultTenant(): TenantEntity
    {
        // Tentar encontrar um tenant padrão
        $tenant = $this->entityManager->getRepository( TenantEntity::class)
            ->findOneBy( [ 'name' => 'default' ] ) ??
            $this->entityManager->getRepository( TenantEntity::class)
                ->findOneBy( [] );

        if ( !$tenant ) {
            // Criar tenant padrão se não existir
            $tenant = new TenantEntity( 'default' );

            $this->entityManager->persist( $tenant );
            $this->entityManager->flush();
        }

        return $tenant;
    }

}
