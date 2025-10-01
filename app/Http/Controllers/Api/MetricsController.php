<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MetricsController extends Controller
{
    public function __construct(
        private MetricsService $metricsService,
    ) {}

    /**
     * Obtém métricas principais do dashboard
     */
    public function index( Request $request ): JsonResponse
    {
        $request->validate( [
            'period'  => 'string|in:today,week,month,year',
            'refresh' => 'boolean'
        ] );

        $userId       = auth()->id();
        $period       = $request->get( 'period', 'month' );
        $forceRefresh = $request->boolean( 'refresh', false );

        // Cache inteligente com diferentes TTLs por período
        $cacheKey = "dashboard.metrics.{$userId}.{$period}";
        $ttl      = $this->getCacheTtl( $period );

        if ( $forceRefresh ) {
            Cache::forget( $cacheKey );
        }

        $metrics = Cache::remember( $cacheKey, $ttl, function () use ($userId, $period) {
            return $this->metricsService->getMetrics( $userId, $period );
        } );

        return response()->json( [
            'success' => true,
            'data'    => [
                'metrics'      => $metrics,
                'period'       => $period,
                'timestamp'    => now()->toISOString(),
                'cached_until' => now()->addSeconds( $ttl )->toISOString()
            ]
        ] );
    }

    /**
     * Obtém métricas detalhadas para gráficos
     */
    public function charts( Request $request ): JsonResponse
    {
        $request->validate( [
            'period'  => 'string|in:today,week,month,year',
            'refresh' => 'boolean'
        ] );

        $userId       = auth()->id();
        $period       = $request->get( 'period', 'month' );
        $forceRefresh = $request->boolean( 'refresh', false );

        $cacheKey = "dashboard.charts.{$userId}.{$period}";
        $ttl      = $this->getCacheTtl( $period );

        if ( $forceRefresh ) {
            Cache::forget( $cacheKey );
        }

        $charts = Cache::remember( $cacheKey, $ttl, function () use ($userId, $period) {
            return $this->metricsService->getChartData( $userId, $period );
        } );

        return response()->json( [
            'success' => true,
            'data'    => [
                'charts'       => $charts,
                'period'       => $period,
                'timestamp'    => now()->toISOString(),
                'cached_until' => now()->addSeconds( $ttl )->toISOString()
            ]
        ] );
    }

    /**
     * Obtém métricas em tempo real (sem cache)
     */
    public function realtime( Request $request ): JsonResponse
    {
        $request->validate( [
            'period' => 'string|in:today,week,month,year'
        ] );

        $userId = auth()->id();
        $period = $request->get( 'period', 'month' );

        $metrics = $this->metricsService->getMetrics( $userId, $period, true );

        return response()->json( [
            'success' => true,
            'data'    => [
                'metrics'   => $metrics,
                'period'    => $period,
                'timestamp' => now()->toISOString(),
                'realtime'  => true
            ]
        ] );
    }

    /**
     * Define TTL do cache baseado no período
     */
    private function getCacheTtl( string $period ): int
    {
        return match ( $period ) {
            'today' => 60,      // 1 minuto
            'week'  => 300,      // 5 minutos
            'month' => 900,     // 15 minutos
            'year'  => 3600,     // 1 hora
            default => 900      // 15 minutos
        };
    }

}
