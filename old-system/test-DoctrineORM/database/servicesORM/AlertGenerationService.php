<?php

declare(strict_types=1);

namespace app\database\servicesORM;

use app\interfaces\ServiceCustomInterface;
use DateTime;

class AlertGenerationService implements ServiceCustomInterface
{
    public function generateAlertsFromMetrics( array $rawData, array $settings ): array
    {
        $activeAlerts  = [];
        $criticalCount = 0;
        $warningCount  = 0;

        $middlewares = [];
        foreach ( $rawData[ 'metrics' ] as $metric ) {
            $name = $metric[ 'middleware_name' ];
            if ( !isset( $middlewares[ $name ] ) ) {
                $middlewares[ $name ] = [ 
                    'executions' => 0,
                    'successes'  => 0,
                    'total_time' => 0
                ];
            }
            $middlewares[ $name ][ 'executions' ]++;
            $middlewares[ $name ][ 'total_time' ] += (float) $metric[ 'response_time' ];
            if ( $metric[ 'status_code' ] >= 200 && $metric[ 'status_code' ] < 300 ) {
                $middlewares[ $name ][ 'successes' ]++;
            }
        }

        $criticalSuccessRate  = $settings[ 'thresholds' ][ 'critical_success_rate' ];
        $warningSuccessRate   = $settings[ 'thresholds' ][ 'warning_success_rate' ];
        $criticalResponseTime = $settings[ 'thresholds' ][ 'critical_response_time' ];
        $warningResponseTime  = $settings[ 'thresholds' ][ 'warning_response_time' ];
        $enabledMiddlewares   = $settings[ 'monitoring' ][ 'enabled_middlewares' ];
        $minSeverity          = $settings[ 'monitoring' ][ 'min_severity' ];

        foreach ( $middlewares as $name => $data ) {
            if ( !in_array( $name, $enabledMiddlewares ) ) {
                continue;
            }

            $successRate = ( $data[ 'successes' ] / $data[ 'executions' ] ) * 100;
            $avgTime     = $data[ 'total_time' ] / $data[ 'executions' ];

            if ( $successRate < $criticalSuccessRate || $avgTime > $criticalResponseTime ) {
                $activeAlerts[] = [ 
                    'id'              => count( $activeAlerts ) + 1,
                    'severity'        => 'CRITICAL',
                    'middleware_name' => $name,
                    'message'         => $successRate < $criticalSuccessRate
                        ? "Taxa de sucesso baixa: " . number_format( $successRate, 1 ) . "% (limite: {$criticalSuccessRate}%)"
                        : "Tempo de resposta alto: " . number_format( $avgTime, 1 ) . "ms (limite: {$criticalResponseTime}ms)",
                    'created_at'      => new DateTime()
                ];
                $criticalCount++;
            } elseif ( $successRate < $warningSuccessRate || $avgTime > $warningResponseTime ) {
                if ( in_array( $minSeverity, [ 'INFO', 'WARNING' ] ) ) {
                    $activeAlerts[] = [ 
                        'id'              => count( $activeAlerts ) + 1,
                        'severity'        => 'WARNING',
                        'middleware_name' => $name,
                        'message'         => $successRate < $warningSuccessRate
                            ? "Taxa de sucesso moderada: " . number_format( $successRate, 1 ) . "% (limite: {$warningSuccessRate}%)"
                            : "Tempo de resposta moderado: " . number_format( $avgTime, 1 ) . "ms (limite: {$warningResponseTime}ms)",
                        'created_at'      => new DateTime()
                    ];
                    $warningCount++;
                }
            }
        }

        return [ 
            'active' => $activeAlerts,
            'stats'  => [ 
                'critical_count' => $criticalCount,
                'warning_count'  => $warningCount,
                'total_active'   => count( $activeAlerts ),
                'resolved_today' => 0
            ]
        ];
    }

    public function calculateSystemStatus( array $rawData, array $settings ): array
    {
        $summary          = $rawData[ 'summary' ];
        $warningThreshold = $settings[ 'thresholds' ][ 'warning_success_rate' ];

        return [ 
            'performance'  => $summary[ 'success_rate' ],
            'availability' => min( 99.9, $summary[ 'success_rate' ] + 5 ),
            'status'       => $summary[ 'success_rate' ] >= $warningThreshold ? 'Operacional' : 'Degradado'
        ];
    }

    public function generateAIInsights( array $rawData ): ?array
    {
        $summary  = $rawData[ 'summary' ];
        $insights = [];

        if ( $summary[ 'total_executions' ] > 0 ) {
            $patterns        = [];
            $recommendations = [];

            if ( $summary[ 'avg_execution_time' ] > 100 ) {
                $patterns[]        = "Tempo de resposta acima da média ({$summary[ 'avg_execution_time' ]}ms)";
                $recommendations[] = "Otimizar consultas de banco de dados";
            }

            if ( $summary[ 'success_rate' ] < 95 ) {
                $patterns[]        = "Taxa de sucesso abaixo do ideal ({$summary[ 'success_rate' ]}%})";
                $recommendations[] = "Revisar tratamento de erros nos middlewares";
            }

            if ( $summary[ 'total_executions' ] > 500 ) {
                $patterns[]        = "Alto volume de tráfego detectado ({$summary[ 'total_executions' ]} execuções)";
                $recommendations[] = "Considerar implementar cache Redis";
            }

            if ( !empty( $patterns ) ) {
                $insights = [ 
                    'patterns'        => $patterns,
                    'recommendations' => $recommendations
                ];
            }
        }

        return !empty( $insights ) ? $insights : null;
    }

}
