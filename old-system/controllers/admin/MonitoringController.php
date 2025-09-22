<?php

namespace app\controllers\admin;

use app\controllers\AbstractController;
use app\database\servicesORM\MiddlewareMetricsService;
use core\library\Response;
use core\library\Twig;
use DateTime;
use http\Request;

/**
 * Controller para dashboard de monitoramento de middlewares.
 *
 * Fornece interface web para visualização de métricas de performance,
 * relatórios detalhados e configuração de alertas do sistema de monitoramento.
 */
class MonitoringController extends AbstractController
{
    public function __construct(
        protected Twig $twig,
        private MiddlewareMetricsService $metricsService,
        Request $request,
    ) {
        parent::__construct( $request );
    }

    public function index(): Response
    {
        $endDate   = new DateTime();
        $startDate = ( clone $endDate )->modify( '-7 days' );

        $result = $this->metricsService->getPerformanceMetrics( '', $startDate, $endDate );

        if ( !$result->isSuccess() ) {
            return $this->errorResponse( $result->getMessage() );
        }

        $rawData = $result->getData();
        
        // Processar métricas por middleware
        $middlewares = [];
        foreach ($rawData['metrics'] as $metric) {
            $name = $metric['middleware_name'];
            if (!isset($middlewares[$name])) {
                $middlewares[$name] = [
                    'executions' => 0,
                    'successes' => 0,
                    'average_time' => 0,
                    'type' => 'ORM Middleware'
                ];
            }
            $middlewares[$name]['executions']++;
            if ($metric['status_code'] >= 200 && $metric['status_code'] < 300) {
                $middlewares[$name]['successes']++;
            }
        }
        
        // Calcular tempo médio por middleware
        foreach ($middlewares as $name => &$data) {
            $totalTime = 0;
            $count = 0;
            foreach ($rawData['metrics'] as $metric) {
                if ($metric['middleware_name'] === $name) {
                    $totalTime += (float)$metric['response_time'];
                    $count++;
                }
            }
            $data['average_time'] = $count > 0 ? ($totalTime / $count) / 1000 : 0; // converter para segundos
        }
        
        // Dados do gráfico por hora
        $chartData = $this->getHourlyChartData($rawData['metrics']);
        
        // Status real do sistema
        $systemHealth = $this->calculateSystemHealth($middlewares, $rawData['summary']);
        
        $templateData = [
            'middlewares' => $middlewares,
            'summary' => $rawData['summary'],
            'metrics' => $rawData['metrics'],
            'success_rate' => $rawData['summary']['success_rate'],
            'total_executions' => $rawData['summary']['total_executions'],
            'average_response_time' => $rawData['summary']['avg_execution_time'] / 1000,
            'chart_data' => $chartData,
            'system_health' => $systemHealth,
            'page_title' => 'Monitoramento Técnico'
        ];

        return new Response(
            $this->twig->env->render( 'pages/admin/monitoring/metrics.twig', $templateData ),
        );
    }

    public function apiMetrics(): Response
    {
        $endDate   = new DateTime();
        $startDate = ( clone $endDate )->modify( '-24 hours' );

        $result = $this->metricsService->getPerformanceMetrics( '', $startDate, $endDate );

        if ( !$result->isSuccess() ) {
            return $this->errorResponse( $result->getMessage() );
        }

        return $this->successResponse( 'Métricas obtidas', $result->getData() );
    }

    public function apiReports(): Response
    {
        $result = $this->metricsService->identifyBottlenecks( 1000.0 );

        if ( !$result->isSuccess() ) {
            return $this->errorResponse( $result->getMessage() );
        }

        return $this->successResponse( 'Relatório gerado', $result->getData() );
    }

    public function metrics(): Response
    {
        return $this->index();
    }

    public function performanceReport(): Response
    {
        return $this->apiReports();
    }

    public function trends(): Response
    {
        return $this->apiMetrics();
    }

    public function bottlenecks(): Response
    {
        return $this->apiReports();
    }

    /**
     * Métricas em tempo real (compatibilidade)
     */
    public function realTimeMetrics(): Response
    {
        return $this->apiMetrics();
    }

    /**
     * Registrar métricas (compatibilidade)
     */
    public function recordMetrics(): Response
    {
        $input = json_decode( file_get_contents( 'php://input' ), true );

        if ( !$input ) {
            return $this->errorResponse( 'Dados inválidos' );
        }

        $result = $this->metricsService->recordMetrics( $input );

        if ( $result->isSuccess() ) {
            return $this->successResponse( 'Métricas registradas' );
        } else {
            return $this->errorResponse( $result->getMessage() );
        }
    }

    /**
     * Limpeza de métricas antigas
     */
    public function cleanup(): Response
    {
        $result = $this->metricsService->cleanOldMetrics( 30 );

        if ( $result->isSuccess() ) {
            return $this->successResponse( 'Limpeza executada', $result->getData() );
        } else {
            return $this->errorResponse( $result->getMessage() );
        }
    }

    /**
     * Métricas de middleware específico
     */
    public function middleware( string $middlewareName ): Response
    {
        $data = [ 
            'middlewareName' => $middlewareName,
            'metrics'        => [],
            'pageTitle'      => "Métricas - {$middlewareName}"
        ];

        return new Response(
            $this->twig->env->render( 'pages/admin/monitoring/middleware.twig', $data ),
        );
    }
    
    private function getHourlyChartData(array $metrics): array
    {
        $hourlyData = [];
        
        // Inicializar últimas 7 horas
        for ($i = 6; $i >= 0; $i--) {
            $hour = (new DateTime())->modify("-$i hours")->format('H:00');
            $hourlyData[$hour] = [];
        }
        
        // Processar métricas
        foreach ($metrics as $metric) {
            $createdAt = new DateTime($metric['created_at']);
            $hour = $createdAt->format('H:00');
            
            if (isset($hourlyData[$hour])) {
                $hourlyData[$hour][] = (float)$metric['response_time'];
            }
        }
        
        // Calcular médias
        $labels = array_keys($hourlyData);
        $data = [];
        
        foreach ($hourlyData as $times) {
            $data[] = !empty($times) ? array_sum($times) / count($times) : 0;
        }
        
        return [
            'labels' => $labels,
            'data' => $data
        ];
    }
    
    private function calculateSystemHealth(array $middlewares, array $summary): array
    {
        $totalMiddlewares = count($middlewares);
        $healthyMiddlewares = 0;
        
        foreach ($middlewares as $data) {
            $successRate = $data['executions'] > 0 ? ($data['successes'] / $data['executions'] * 100) : 0;
            if ($successRate >= 95 && $data['average_time'] < 1.0) {
                $healthyMiddlewares++;
            }
        }
        
        $overallHealth = $totalMiddlewares > 0 ? ($healthyMiddlewares / $totalMiddlewares * 100) : 0;
        
        return [
            'status' => $overallHealth >= 80 ? 'Operacional' : ($overallHealth >= 60 ? 'Atenção' : 'Crítico'),
            'performance' => $summary['avg_execution_time'] < 50 ? 'Excelente' : ($summary['avg_execution_time'] < 100 ? 'Boa' : 'Ruim'),
            'activity_level' => $summary['total_executions'] > 100 ? 'Alta' : ($summary['total_executions'] > 50 ? 'Média' : 'Baixa'),
            'healthy_percentage' => round($overallHealth, 1)
        ];
    }

}