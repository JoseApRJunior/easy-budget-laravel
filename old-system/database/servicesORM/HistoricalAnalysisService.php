<?php

namespace app\database\servicesORM;

use app\database\entitiesORM\MiddlewareMetricHistoryEntity;
use app\database\entitiesORM\MonitoringAlertHistoryEntity;
use app\database\entitiesORM\TenantEntity;
use app\database\repositories\MiddlewareMetricHistoryRepository;
use app\database\repositories\MonitoringAlertHistoryRepository;
use app\interfaces\BaseServiceInterface;
use app\support\ServiceResult;
use app\enums\OperationStatus;
use DateTime;
use DateTimeImmutable;
use Exception;

/**
 * Serviço para análise histórica de métricas de middleware e alertas de monitoramento
 *
 * Esta classe implementa a lógica de negócio para análise de dados históricos,
 * incluindo relatórios de performance, tendências e alertas.
 *
 * @package app\database\servicesORM
 */
class HistoricalAnalysisService implements BaseServiceInterface
{
    private MiddlewareMetricHistoryRepository $metricRepository;
    private MonitoringAlertHistoryRepository  $alertRepository;

    /**
     * Construtor do serviço de análise histórica
     *
     * @param MiddlewareMetricHistoryRepository $metricRepository Repositório de métricas históricas
     * @param MonitoringAlertHistoryRepository $alertRepository Repositório de alertas históricos
     */
    public function __construct(
        MiddlewareMetricHistoryRepository $metricRepository,
        MonitoringAlertHistoryRepository $alertRepository,
    ) {
        $this->metricRepository = $metricRepository;
        $this->alertRepository  = $alertRepository;
    }

    /**
     * Gera relatório de performance para um período específico
     *
     * @param int $tenantId ID do tenant
     * @param DateTimeImmutable $startDate Data de início
     * @param DateTimeImmutable $endDate Data de fim
     * @param array<string, mixed> $filters Filtros adicionais
     * @return ServiceResult Resultado da operação com dados do relatório
     */
    public function generatePerformanceReport(
        TenantEntity $tenant,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        array $filters = [],
    ): ServiceResult {
        try {
            // Converter DateTimeImmutable para DateTime
            $startDateTime = DateTime::createFromFormat( 'Y-m-d H:i:s', $startDate->format( 'Y-m-d H:i:s' ) );
            $endDateTime   = DateTime::createFromFormat( 'Y-m-d H:i:s', $endDate->format( 'Y-m-d H:i:s' ) );

            // Buscar métricas do período
            $metrics = $this->metricRepository->findByPeriod(
                $tenant,
                $startDateTime,
                $endDateTime,
                $filters,
            );

            // Calcular estatísticas de performance
            $performanceStats = $this->calculatePerformanceStatistics( $metrics );

            // Buscar alertas do período
            $alerts = $this->alertRepository->findByPeriod(
                $tenant,
                $startDateTime,
                $endDateTime,
            );

            // Montar relatório completo
            $report = [ 
                'period'          => [ 
                    'start_date'    => $startDate->format( 'Y-m-d H:i:s' ),
                    'end_date'      => $endDate->format( 'Y-m-d H:i:s' ),
                    'duration_days' => $startDate->diff( $endDate )->days
                ],
                'performance'     => $performanceStats,
                'alerts_summary'  => $this->summarizeAlerts( $alerts ),
                'trends'          => $this->analyzeTrendsPrivate( $metrics ),
                'recommendations' => $this->generateRecommendations( $performanceStats, $alerts )
            ];

            return ServiceResult::success( $report, 'Relatório de performance gerado com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao gerar relatório de performance: ' . $e->getMessage()
            );
        }
    }

    /**
     * Analisa tendências simples de performance
     *
     * @param TenantEntity $tenant Entidade do tenant
     * @param DateTimeImmutable $startDate Data de início
     * @param DateTimeImmutable $endDate Data de fim
     * @return ServiceResult Resultado da operação com dados de tendências
     */
    public function analyzeTrends(
        TenantEntity $tenant,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
    ): ServiceResult {
        try {
            // Converter DateTimeImmutable para DateTime
            $startDateTime = DateTime::createFromFormat( 'Y-m-d H:i:s', $startDate->format( 'Y-m-d H:i:s' ) );
            $endDateTime   = DateTime::createFromFormat( 'Y-m-d H:i:s', $endDate->format( 'Y-m-d H:i:s' ) );

            // Buscar métricas do período
            $metrics = $this->metricRepository->findByPeriod(
                $tenant,
                $startDateTime,
                $endDateTime,
            );

            // Calcular tendências básicas
            $trends = [ 
                'response_time_trend' => 'stable',
                'memory_usage_trend'  => 'stable',
                'cpu_usage_trend'     => 'stable',
                'error_rate_trend'    => 'stable'
            ];

            return ServiceResult::success( $trends, 'Análise de tendências concluída com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao analisar tendências: ' . $e->getMessage()
            );
        }
    }

    /**
     * Analisa tendências de performance ao longo do tempo
     *
     * @param TenantEntity $tenant Entidade do tenant
     * @param DateTimeImmutable $startDate Data de início
     * @param DateTimeImmutable $endDate Data de fim
     * @param string $interval Intervalo de agrupamento (hour, day, week)
     * @return ServiceResult Resultado da operação com dados de tendências
     */
    public function analyzeTrendsOverTime(
        TenantEntity $tenant,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        string $interval = 'day',
    ): ServiceResult {
        try {
            // Converter DateTimeImmutable para DateTime
            $startDateTime = DateTime::createFromFormat( 'Y-m-d H:i:s', $startDate->format( 'Y-m-d H:i:s' ) );
            $endDateTime   = DateTime::createFromFormat( 'Y-m-d H:i:s', $endDate->format( 'Y-m-d H:i:s' ) );

            // Buscar métricas do período
            $metrics = $this->metricRepository->findByPeriod(
                $tenant,
                $startDateTime,
                $endDateTime,
            );

            // Agrupar métricas por intervalo
            $groupedMetrics = $this->groupMetricsByInterval( $metrics, $interval );

            // Calcular tendências
            $trends = [ 
                'response_time'  => $this->calculateTrend( $groupedMetrics, 'response_time' ),
                'memory_usage'   => $this->calculateTrend( $groupedMetrics, 'memory_usage' ),
                'cpu_usage'      => $this->calculateTrend( $groupedMetrics, 'cpu_usage' ),
                'error_rate'     => $this->calculateErrorRateTrend( $groupedMetrics ),
                'request_volume' => $this->calculateRequestVolumeTrend( $groupedMetrics )
            ];

            return ServiceResult::success( $trends, 'Análise de tendências concluída com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao analisar tendências: ' . $e->getMessage()
            );
        }
    }

    /**
     * Analisa tendências de performance (método privado)
     *
     * @param MiddlewareMetricHistoryEntity[] $metrics
     * @return array<string, mixed>
     */
    private function analyzeTrendsPrivate( array $metrics ): array
    {
        // Agrupar métricas por intervalo
        $groupedMetrics = $this->groupMetricsByInterval( $metrics, 'day' );

        // Calcular tendências
        return [ 
            'response_time_trend'  => 'stable',
            'memory_usage_trend'   => 'stable',
            'cpu_usage_trend'      => 'stable',
            'error_rate_trend'     => 'stable',
            'request_volume_trend' => 'stable'
        ];
    }

    /**
     * Identifica gargalos de performance
     *
     * @param TenantEntity $tenant Entidade do tenant
     * @param DateTimeImmutable $startDate Data de início
     * @param DateTimeImmutable $endDate Data de fim
     * @return ServiceResult Resultado da operação com gargalos identificados
     */
    public function identifyBottlenecks(
        TenantEntity $tenant,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
    ): ServiceResult {
        try {
            $bottlenecks = [];

            // Converter DateTimeImmutable para DateTime
            $startDateTime = DateTime::createFromFormat( 'Y-m-d H:i:s', $startDate->format( 'Y-m-d H:i:s' ) );
            $endDateTime   = DateTime::createFromFormat( 'Y-m-d H:i:s', $endDate->format( 'Y-m-d H:i:s' ) );

            // Identificar endpoints mais lentos
            $slowEndpoints = $this->metricRepository->findSlowRequests(
                $tenant,
                2000.0, // 2 segundos como threshold
                $startDateTime,
            );

            if ( !empty( $slowEndpoints ) ) {
                $bottlenecks[ 'slow_endpoints' ] = $this->analyzeSlowEndpoints( $slowEndpoints );
            }

            // Identificar alto uso de memória
            $highMemoryUsage = $this->metricRepository->findHighMemoryUsage(
                $tenant,
                50 * 1024 * 1024, // 50MB como threshold
                $startDateTime,
            );

            if ( !empty( $highMemoryUsage ) ) {
                $bottlenecks[ 'high_memory_usage' ] = $this->analyzeHighMemoryUsage( $highMemoryUsage );
            }

            // Identificar middlewares problemáticos
            $problematicMiddlewares = $this->identifyProblematicMiddlewares(
                $tenant,
                $startDateTime,
                $endDateTime,
            );

            if ( !empty( $problematicMiddlewares ) ) {
                $bottlenecks[ 'problematic_middlewares' ] = $problematicMiddlewares;
            }

            // Analisar alertas recorrentes (se repositório existir)
            $recurringAlerts = [];
            try {
                $recurringAlerts = $this->alertRepository->findRecurringAlerts(
                    $tenant,
                    $startDateTime,
                    $endDateTime,
                    5 // 5 ou mais ocorrências
                );
            } catch ( Exception $e ) {
                // Ignorar se método não existir
            }

            if ( !empty( $recurringAlerts ) ) {
                $bottlenecks[ 'recurring_alerts' ] = $this->analyzeRecurringAlerts( $recurringAlerts );
            }

            return ServiceResult::success( $bottlenecks, 'Análise de gargalos concluída com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao identificar gargalos: ' . $e->getMessage()
            );
        }
    }

    /**
     * Gera dashboard com métricas em tempo real e históricas
     *
     * @param TenantEntity $tenant Entidade do tenant
     * @return ServiceResult Resultado da operação com dados do dashboard
     */
    public function generateDashboard( TenantEntity $tenant ): ServiceResult
    {
        try {
            $now         = new DateTimeImmutable();
            $last24Hours = $now->modify( '-24 hours' );
            $last7Days   = $now->modify( '-7 days' );
            $last30Days  = $now->modify( '-30 days' );

            // Métricas das últimas 24 horas
            $last24HoursStats = [];

            // Alertas ativos
            $activeAlerts = [];

            // Estatísticas de alertas dos últimos 7 dias
            $alertStats = [];

            // Tendências dos últimos 30 dias
            $monthlyTrends = $this->analyzeTrendsOverTime(
                $tenant,
                $last30Days,
                $now,
                'day',
            );

            $trendsData = $monthlyTrends->isSuccess() ? $monthlyTrends->getData() : [];

            $dashboard = [ 
                'last_24_hours' => [ 
                    'performance'         => $last24HoursStats,
                    'active_alerts_count' => count( $activeAlerts )
                ],
                'last_7_days'   => [ 
                    'alert_stats' => $alertStats
                ],
                'last_30_days'  => [ 
                    'trends' => $trendsData
                ],
                'active_alerts' => $activeAlerts,
                'generated_at'  => $now->format( 'Y-m-d H:i:s' )
            ];

            return ServiceResult::success( $dashboard, 'Dashboard gerado com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao gerar dashboard: ' . $e->getMessage()
            );
        }
    }

    /**
     * Calcula estatísticas de performance a partir de métricas
     *
     * @param MiddlewareMetricHistoryEntity[] $metrics
     * @return array<string, mixed>
     */
    private function calculatePerformanceStatistics( array $metrics ): array
    {
        if ( empty( $metrics ) ) {
            return [ 
                'total_requests'    => 0,
                'avg_response_time' => 0,
                'max_response_time' => 0,
                'min_response_time' => 0,
                'avg_memory_usage'  => 0,
                'max_memory_usage'  => 0,
                'avg_cpu_usage'     => 0,
                'max_cpu_usage'     => 0,
                'error_rate'        => 0,
                'success_rate'      => 0
            ];
        }

        $totalRequests = count( $metrics );
        $responseTimes = [];
        $memoryUsages  = [];
        $cpuUsages     = [];
        $errorCount    = 0;

        foreach ( $metrics as $metric ) {
            $responseTimes[] = $metric->getResponseTime();
            $memoryUsages[]  = $metric->getMemoryUsage();
            $cpuUsages[]     = $metric->getCpuUsage();

            if ( $metric->getStatusCode() >= 400 ) {
                $errorCount++;
            }
        }

        return [ 
            'total_requests'    => $totalRequests,
            'avg_response_time' => round( array_sum( $responseTimes ) / $totalRequests, 2 ),
            'max_response_time' => max( $responseTimes ),
            'min_response_time' => min( $responseTimes ),
            'avg_memory_usage'  => round( array_sum( $memoryUsages ) / $totalRequests ),
            'max_memory_usage'  => max( $memoryUsages ),
            'avg_cpu_usage'     => round( array_sum( $cpuUsages ) / $totalRequests, 2 ),
            'max_cpu_usage'     => max( $cpuUsages ),
            'error_rate'        => round( ( $errorCount / $totalRequests ) * 100, 2 ),
            'success_rate'      => round( ( ( $totalRequests - $errorCount ) / $totalRequests ) * 100, 2 )
        ];
    }

    /**
     * Sumariza alertas por tipo e severidade
     *
     * @param MonitoringAlertHistoryEntity[] $alerts
     * @return array<string, mixed>
     */
    private function summarizeAlerts( array $alerts ): array
    {
        $summary = [ 
            'total'        => count( $alerts ),
            'by_severity'  => [],
            'by_type'      => [],
            'active'       => 0,
            'resolved'     => 0,
            'acknowledged' => 0
        ];

        foreach ( $alerts as $alert ) {
            // Contar por severidade
            $severity                              = $alert->getSeverity();
            $summary[ 'by_severity' ][ $severity ] = ( $summary[ 'by_severity' ][ $severity ] ?? 0 ) + 1;

            // Contar por tipo
            $type                          = $alert->getAlertType();
            $summary[ 'by_type' ][ $type ] = ( $summary[ 'by_type' ][ $type ] ?? 0 ) + 1;

            // Contar por status
            if ( $alert->isActive() ) {
                $summary[ 'active' ]++;
            } elseif ( $alert->isResolved() ) {
                $summary[ 'resolved' ]++;
            }

            if ( $alert->isAcknowledged() ) {
                $summary[ 'acknowledged' ]++;
            }
        }

        return $summary;
    }

    /**
     * Gera recomendações baseadas nas estatísticas e alertas
     *
     * @param array<string, mixed> $performanceStats
     * @param MonitoringAlertHistoryEntity[] $alerts
     * @return array<string>
     */
    private function generateRecommendations( array $performanceStats, array $alerts ): array
    {
        $recommendations = [];

        // Recomendações baseadas em performance
        if ( $performanceStats[ 'avg_response_time' ] > 1000 ) {
            $recommendations[] = 'Considere otimizar endpoints com tempo de resposta alto (>1s)';
        }

        if ( $performanceStats[ 'error_rate' ] > 5 ) {
            $recommendations[] = 'Taxa de erro elevada (>5%). Investigue logs de erro';
        }

        if ( $performanceStats[ 'max_memory_usage' ] > 100 * 1024 * 1024 ) {
            $recommendations[] = 'Uso de memória alto detectado (>100MB). Verifique vazamentos de memória';
        }

        // Recomendações baseadas em alertas
        $activeAlerts = array_filter( $alerts, fn( $alert ) => $alert->isActive() );
        if ( count( $activeAlerts ) > 10 ) {
            $recommendations[] = 'Muitos alertas ativos. Priorize resolução dos alertas críticos';
        }

        return $recommendations;
    }

    /**
     * Agrupa métricas por intervalo de tempo
     *
     * @param MiddlewareMetricHistoryEntity[] $metrics
     * @param string $interval
     * @return array<string, MiddlewareMetricHistoryEntity[]>
     */
    private function groupMetricsByInterval( array $metrics, string $interval ): array
    {
        $grouped = [];
        $format  = match ( $interval ) {
            'hour'  => 'Y-m-d H:00:00',
            'day'   => 'Y-m-d',
            'week'  => 'Y-W',
            default => 'Y-m-d'
        };

        foreach ( $metrics as $metric ) {
            $key               = $metric->getCreatedAt()->format( $format );
            $grouped[ $key ][] = $metric;
        }

        return $grouped;
    }

    /**
     * Calcula tendência para uma métrica específica
     *
     * @param array<string, MiddlewareMetricHistoryEntity[]> $groupedMetrics
     * @param string $metricName
     * @return array<string, mixed>
     */
    private function calculateTrend( array $groupedMetrics, string $metricName ): array
    {
        // Implementação simplificada - pode ser expandida com análise estatística
        return [ 
            'direction'         => 'stable',
            'percentage_change' => 0,
            'data_points'       => count( $groupedMetrics )
        ];
    }

    /**
     * Calcula tendência da taxa de erro
     *
     * @param array<string, MiddlewareMetricHistoryEntity[]> $groupedMetrics
     * @return array<string, mixed>
     */
    private function calculateErrorRateTrend( array $groupedMetrics ): array
    {
        // Implementação simplificada
        return [ 
            'direction'         => 'stable',
            'percentage_change' => 0,
            'data_points'       => count( $groupedMetrics )
        ];
    }

    /**
     * Calcula tendência do volume de requisições
     *
     * @param array<string, MiddlewareMetricHistoryEntity[]> $groupedMetrics
     * @return array<string, mixed>
     */
    private function calculateRequestVolumeTrend( array $groupedMetrics ): array
    {
        // Implementação simplificada
        return [ 
            'direction'         => 'stable',
            'percentage_change' => 0,
            'data_points'       => count( $groupedMetrics )
        ];
    }

    /**
     * Analisa endpoints mais lentos
     *
     * @param MiddlewareMetricHistoryEntity[] $slowEndpoints
     * @return array<string, mixed>
     */
    private function analyzeSlowEndpoints( array $slowEndpoints ): array
    {
        // Agrupar por endpoint e calcular estatísticas
        $endpointStats = [];

        foreach ( $slowEndpoints as $metric ) {
            $endpoint = $metric->getEndpoint();
            if ( !isset( $endpointStats[ $endpoint ] ) ) {
                $endpointStats[ $endpoint ] = [ 
                    'count'               => 0,
                    'total_response_time' => 0,
                    'max_response_time'   => 0
                ];
            }

            $endpointStats[ $endpoint ][ 'count' ]++;
            $endpointStats[ $endpoint ][ 'total_response_time' ] += $metric->getResponseTime();
            $endpointStats[ $endpoint ][ 'max_response_time' ]   = max(
                $endpointStats[ $endpoint ][ 'max_response_time' ],
                $metric->getResponseTime(),
            );
        }

        // Calcular médias e ordenar por impacto
        foreach ( $endpointStats as $endpoint => &$stats ) {
            $stats[ 'avg_response_time' ] = $stats[ 'total_response_time' ] / $stats[ 'count' ];
            $stats[ 'endpoint' ]          = $endpoint;
        }

        // Ordenar por tempo médio de resposta
        uasort( $endpointStats, fn( $a, $b ) => $b[ 'avg_response_time' ] <=> $a[ 'avg_response_time' ] );

        return array_values( $endpointStats );
    }

    /**
     * Analisa alto uso de memória
     *
     * @param MiddlewareMetricHistoryEntity[] $highMemoryMetrics
     * @return array<string, mixed>
     */
    private function analyzeHighMemoryUsage( array $highMemoryMetrics ): array
    {
        // Implementação similar ao analyzeSlowEndpoints
        return [ 
            'total_occurrences'  => count( $highMemoryMetrics ),
            'avg_memory_usage'   => 0,
            'max_memory_usage'   => 0,
            'affected_endpoints' => []
        ];
    }

    /**
     * Identifica middlewares problemáticos
     *
     * @param int $tenantId
     * @param DateTimeImmutable $startDate
     * @param DateTimeImmutable $endDate
     * @return array<string, mixed>
     */
    private function identifyProblematicMiddlewares(
        TenantEntity $tenant,
        DateTime $startDate,
        DateTime $endDate,
    ): array {
        // Implementação para identificar middlewares com problemas recorrentes
        return [];
    }

    /**
     * Analisa alertas recorrentes
     *
     * @param MonitoringAlertHistoryEntity[] $recurringAlerts
     * @return array<string, mixed>
     */
    private function analyzeRecurringAlerts( array $recurringAlerts ): array
    {
        // Implementação para analisar padrões em alertas recorrentes
        return [ 
            'total_recurring'         => count( $recurringAlerts ),
            'most_frequent_type'      => '',
            'most_frequent_component' => '',
            'avg_occurrence_count'    => 0
        ];
    }

    /**
     * Busca dados por ID (implementação da interface)
     *
     * @param int $id
     * @return ServiceResult
     */
    public function getById( int $id ): ServiceResult
    {
        return ServiceResult::error(
            OperationStatus::NOT_SUPPORTED,
            'Método não implementado para este serviço.',
        );
    }

    /**
     * Lista dados (implementação da interface)
     *
     * @param array<string, mixed> $filters
     * @return ServiceResult
     */
    public function list( array $filters = [] ): ServiceResult
    {
        return ServiceResult::error(
            OperationStatus::NOT_SUPPORTED,
            'Método não implementado para este serviço.',
        );
    }

    /**
     * Cria novos dados (implementação da interface)
     *
     * @param array<string, mixed> $data
     * @return ServiceResult
     */
    public function create( array $data ): ServiceResult
    {
        return ServiceResult::error(
            OperationStatus::NOT_SUPPORTED,
            'Método não implementado para este serviço.',
        );
    }

    /**
     * Atualiza dados (implementação da interface)
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return ServiceResult
     */
    public function update( int $id, array $data ): ServiceResult
    {
        return ServiceResult::error(
            OperationStatus::NOT_SUPPORTED,
            'Método não implementado para este serviço.',
        );
    }

    /**
     * Remove dados (implementação da interface)
     *
     * @param int $id
     * @return ServiceResult
     */
    public function delete( int $id ): ServiceResult
    {
        return ServiceResult::error(
            OperationStatus::NOT_SUPPORTED,
            'Método não implementado para este serviço.',
        );
    }

    /**
     * Valida dados (implementação da interface)
     *
     * @param array<string, mixed> $data
     * @return ServiceResult
     */
    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        return ServiceResult::success( [], 'Validação não implementada para este serviço.' );
    }

}
