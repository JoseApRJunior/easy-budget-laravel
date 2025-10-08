<?php

namespace app\controllers\admin;

use app\controllers\AbstractController;
use app\database\servicesORM\MiddlewareMetricsService;
use core\library\Response;
use core\library\Twig;
use DateTime;
use http\Request;

/**
 * Controller unificado para Dashboard Executivo
 */
class DashboardController extends AbstractController
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
        $startDate = ( clone $endDate )->modify( '-24 hours' );

        $result = $this->metricsService->getPerformanceMetrics( '', $startDate, $endDate );

        if ( !$result->isSuccess() ) {
            return $this->errorResponse( $result->getMessage() );
        }

        $rawData = $result->getData();

        // KPIs Executivos (100% reais)
        $growth = $this->calculateGrowth($rawData['metrics']);
        $kpis = [ 
            'total_requests'     => $rawData[ 'summary' ][ 'total_executions' ],
            'success_rate'       => $rawData[ 'summary' ][ 'success_rate' ],
            'avg_response_time'  => $rawData[ 'summary' ][ 'avg_execution_time' ],
            'active_middlewares' => count( array_unique( array_column( $rawData[ 'metrics' ], 'middleware_name' ) ) ),
            'growth_rate'        => $growth,
        ];

        // Dados do gráfico por hora
        $chartData = $this->getHourlyChartData( $rawData[ 'metrics' ] );

        // Status do Sistema (100% real)
        $uptime = $this->calculateUptime($rawData['metrics']);
        $systemStatus = [ 
            'health'      => $kpis[ 'success_rate' ] >= 95 ? 'healthy' : ( $kpis[ 'success_rate' ] >= 90 ? 'warning' : 'critical' ),
            'performance' => $kpis[ 'avg_response_time' ] < 50 ? 'excellent' : ( $kpis[ 'avg_response_time' ] < 100 ? 'good' : 'poor' ),
            'uptime'      => $uptime,
        ];

        // Alertas Críticos
        $alerts = [];
        if ( $kpis[ 'success_rate' ] < 95 ) {
            $alerts[] = [ 
                'type'    => 'warning',
                'message' => 'Taxa de sucesso abaixo do esperado: ' . number_format( $kpis[ 'success_rate' ], 1 ) . '%'
            ];
        }
        if ( $kpis[ 'avg_response_time' ] > 100 ) {
            $alerts[] = [ 
                'type'    => 'danger',
                'message' => 'Tempo de resposta alto: ' . number_format( $kpis[ 'avg_response_time' ], 1 ) . 'ms'
            ];
        }

        $templateData = [ 
            'kpis'          => $kpis,
            'system_status' => $systemStatus,
            'alerts'        => $alerts,
            'chart_data'    => $chartData,
            'page_title'    => 'Dashboard Executivo'
        ];

        return new Response(
            $this->twig->env->render( 'pages/admin/dashboard.twig', $templateData ),
        );
    }

    private function getHourlyChartData( array $metrics ): array
    {
        $hourlyRequests      = [];
        $hourlyResponseTimes = [];

        // Inicializar arrays para últimas 24 horas
        for ( $i = 23; $i >= 0; $i-- ) {
            $hour                       = ( new DateTime() )->modify( "-$i hours" )->format( 'H:00' );
            $hourlyRequests[ $hour ]      = 0;
            $hourlyResponseTimes[ $hour ] = [];
        }

        // Processar métricas
        foreach ( $metrics as $metric ) {
            $createdAt = new DateTime( $metric[ 'created_at' ] );
            $hour      = $createdAt->format( 'H:00' );

            if ( isset( $hourlyRequests[ $hour ] ) ) {
                $hourlyRequests[ $hour ]++;
                $hourlyResponseTimes[ $hour ][] = (float) $metric[ 'response_time' ];
            }
        }

        // Calcular médias de tempo de resposta
        $avgResponseTimes = [];
        foreach ( $hourlyResponseTimes as $hour => $times ) {
            $avgResponseTimes[ $hour ] = !empty( $times ) ? array_sum( $times ) / count( $times ) : 0;
        }

        return [ 
            'labels'         => array_keys( $hourlyRequests ),
            'requests'       => array_values( $hourlyRequests ),
            'response_times' => array_values( $avgResponseTimes )
        ];
    }
    
    private function calculateUptime(array $metrics): string
    {
        if (empty($metrics)) {
            return '0%';
        }
        
        $totalRequests = count($metrics);
        $successfulRequests = 0;
        
        foreach ($metrics as $metric) {
            if ($metric['status_code'] >= 200 && $metric['status_code'] < 400) {
                $successfulRequests++;
            }
        }
        
        $uptime = ($successfulRequests / $totalRequests) * 100;
        return number_format($uptime, 1) . '%';
    }
    
    private function calculateGrowth(array $metrics): string
    {
        // Dividir métricas em duas metades (primeira vs segunda metade do período)
        $total = count($metrics);
        if ($total < 2) {
            return '0%';
        }
        
        $midpoint = intval($total / 2);
        $firstHalf = array_slice($metrics, 0, $midpoint);
        $secondHalf = array_slice($metrics, $midpoint);
        
        $firstCount = count($firstHalf);
        $secondCount = count($secondHalf);
        
        if ($firstCount == 0) {
            return '+100%';
        }
        
        $growth = (($secondCount - $firstCount) / $firstCount) * 100;
        $sign = $growth >= 0 ? '+' : '';
        
        return $sign . number_format($growth, 0) . '%';
    }

}
