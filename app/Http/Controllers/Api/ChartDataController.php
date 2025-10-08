<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ChartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ChartDataController extends Controller
{
    public function __construct(
        private ChartService $chartService,
    ) {}

    /**
     * Dados para gráfico de receitas vs despesas
     */
    public function receitaDespesa( Request $request ): JsonResponse
    {
        $request->validate( [
            'period'  => 'string|in:today,week,month,year',
            'days'    => 'integer|min:7|max:90',
            'refresh' => 'boolean'
        ] );

        $userId       = auth()->id();
        $period       = $request->get( 'period', 'month' );
        $days         = $request->get( 'days', 30 );
        $forceRefresh = $request->boolean( 'refresh', false );

        $cacheKey = "chart.receita_despesa.{$userId}.{$period}.{$days}";

        if ( $forceRefresh ) {
            Cache::forget( $cacheKey );
        }

        $data = Cache::remember( $cacheKey, 300, function () use ($userId, $period, $days) {
            return $this->chartService->getReceitaDespesaData( $userId, $period, $days );
        } );

        return response()->json( [
            'success' => true,
            'data'    => [
                'chart'     => $data,
                'type'      => 'line',
                'period'    => $period,
                'days'      => $days,
                'timestamp' => now()->toISOString()
            ]
        ] );
    }

    /**
     * Dados para gráfico de distribuição por categoria
     */
    public function categorias( Request $request ): JsonResponse
    {
        $request->validate( [
            'period'  => 'string|in:today,week,month,year',
            'limit'   => 'integer|min:5|max:20',
            'refresh' => 'boolean'
        ] );

        $userId       = auth()->id();
        $period       = $request->get( 'period', 'month' );
        $limit        = $request->get( 'limit', 10 );
        $forceRefresh = $request->boolean( 'refresh', false );

        $cacheKey = "chart.categorias.{$userId}.{$period}.{$limit}";

        if ( $forceRefresh ) {
            Cache::forget( $cacheKey );
        }

        $data = Cache::remember( $cacheKey, 600, function () use ($userId, $period, $limit) {
            return $this->chartService->getCategoriasData( $userId, $period, $limit );
        } );

        return response()->json( [
            'success' => true,
            'data'    => [
                'chart'     => $data,
                'type'      => 'doughnut',
                'period'    => $period,
                'limit'     => $limit,
                'timestamp' => now()->toISOString()
            ]
        ] );
    }

    /**
     * Dados para gráfico comparativo mensal
     */
    public function mensal( Request $request ): JsonResponse
    {
        $request->validate( [
            'months'  => 'integer|min:2|max:12',
            'refresh' => 'boolean'
        ] );

        $userId       = auth()->id();
        $months       = $request->get( 'months', 6 );
        $forceRefresh = $request->boolean( 'refresh', false );

        $cacheKey = "chart.mensal.{$userId}.{$months}";

        if ( $forceRefresh ) {
            Cache::forget( $cacheKey );
        }

        $data = Cache::remember( $cacheKey, 1800, function () use ($userId, $months) {
            return $this->chartService->getMensalData( $userId, $months );
        } );

        return response()->json( [
            'success' => true,
            'data'    => [
                'chart'     => $data,
                'type'      => 'bar',
                'months'    => $months,
                'timestamp' => now()->toISOString()
            ]
        ] );
    }

    /**
     * Dados para gráfico de tendências
     */
    public function tendencias( Request $request ): JsonResponse
    {
        $request->validate( [
            'period'  => 'string|in:week,month,year',
            'metric'  => 'string|in:receita,despesa,saldo,transacoes',
            'refresh' => 'boolean'
        ] );

        $userId       = auth()->id();
        $period       = $request->get( 'period', 'month' );
        $metric       = $request->get( 'metric', 'receita' );
        $forceRefresh = $request->boolean( 'refresh', false );

        $cacheKey = "chart.tendencias.{$userId}.{$period}.{$metric}";

        if ( $forceRefresh ) {
            Cache::forget( $cacheKey );
        }

        $data = Cache::remember( $cacheKey, 300, function () use ($userId, $period, $metric) {
            return $this->chartService->getTendenciasData( $userId, $period, $metric );
        } );

        return response()->json( [
            'success' => true,
            'data'    => [
                'chart'     => $data,
                'type'      => 'line',
                'period'    => $period,
                'metric'    => $metric,
                'timestamp' => now()->toISOString()
            ]
        ] );
    }

}
