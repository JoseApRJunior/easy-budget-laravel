<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ChartService
{
    /**
     * Obtém dados iniciais para gráficos do dashboard
     */
    public function getInitialChartData( int $userId, string $period ): array
    {
        return [
            'receita_despesa' => $this->getReceitaDespesaData( $userId, $period, 30 ),
            'categorias'      => $this->getCategoriasData( $userId, $period, 10 ),
            'mensal'          => $this->getMensalData( $userId, 6 ),
            'tendencias'      => $this->getTendenciasData( $userId, $period, 'receita' )
        ];
    }

    /**
     * Dados para gráfico de receitas vs despesas (últimos N dias)
     */
    public function getReceitaDespesaData( int $userId, string $period, int $days ): array
    {
        $data     = [];
        $labels   = [];
        $receitas = [];
        $despesas = [];

        $startDate = Carbon::now()->subDays( $days );
        $endDate   = Carbon::now();

        // Gerar dados diários
        for ( $i = $days; $i >= 0; $i-- ) {
            $date     = Carbon::now()->subDays( $i );
            $dayStart = $date->format( 'Y-m-d 00:00:00' );
            $dayEnd   = $date->format( 'Y-m-d 23:59:59' );

            $receitaDia = Invoice::where( 'tenant_id', $this->getTenantId( $userId ) )
                ->where( 'transaction_amount', '>', 0 )
                ->whereBetween( 'transaction_date', [ $dayStart, $dayEnd ] )
                ->sum( 'transaction_amount' );

            $despesaDia = abs( Invoice::where( 'tenant_id', $this->getTenantId( $userId ) )
                ->where( 'transaction_amount', '<', 0 )
                ->whereBetween( 'transaction_date', [ $dayStart, $dayEnd ] )
                ->sum( 'transaction_amount' ) );

            $labels[]   = $date->format( 'd/m' );
            $receitas[] = round( $receitaDia, 2 );
            $despesas[] = round( $despesaDia, 2 );
        }

        return [
            'labels'   => $labels,
            'datasets' => [
                [
                    'label'           => 'Receitas',
                    'data'            => $receitas,
                    'borderColor'     => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'tension'         => 0.4,
                    'fill'            => false
                ],
                [
                    'label'           => 'Despesas',
                    'data'            => $despesas,
                    'borderColor'     => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'tension'         => 0.4,
                    'fill'            => false
                ]
            ]
        ];
    }

    /**
     * Dados para gráfico de distribuição por categoria
     */
    public function getCategoriasData( int $userId, string $period, int $limit ): array
    {
        $dateRange       = $this->getDateRange( $period );
        $labels          = [];
        $data            = [];
        $backgroundColor = [
            'rgb(59, 130, 246)',   // blue
            'rgb(16, 185, 129)',   // green
            'rgb(245, 158, 11)',   // yellow
            'rgb(239, 68, 68)',    // red
            'rgb(139, 92, 246)',   // purple
            'rgb(236, 72, 153)',   // pink
            'rgb(6, 182, 212)',    // cyan
            'rgb(245, 101, 101)'   // rose
        ];

        // Por enquanto dados mock - implementar categorização posteriormente
        $categorias = [
            [ 'nome' => 'Alimentação', 'valor' => 3500.00 ],
            [ 'nome' => 'Transporte', 'valor' => 2500.00 ],
            [ 'nome' => 'Lazer', 'valor' => 2000.00 ],
            [ 'nome' => 'Saúde', 'valor' => 1500.00 ],
            [ 'nome' => 'Educação', 'valor' => 1200.00 ],
            [ 'nome' => 'Outros', 'valor' => 800.00 ]
        ];

        $total = array_sum( array_column( $categorias, 'valor' ) );

        foreach ( $categorias as $index => $categoria ) {
            $labels[] = $categoria[ 'nome' ];
            $data[]   = round( ( $categoria[ 'valor' ] / $total ) * 100, 1 );
        }

        return [
            'labels'   => $labels,
            'datasets' => [
                [
                    'data'            => $data,
                    'backgroundColor' => $backgroundColor,
                    'borderWidth'     => 2,
                    'borderColor'     => '#ffffff'
                ]
            ]
        ];
    }

    /**
     * Dados para gráfico comparativo mensal
     */
    public function getMensalData( int $userId, int $months ): array
    {
        $labels   = [];
        $receitas = [];
        $despesas = [];

        for ( $i = $months - 1; $i >= 0; $i-- ) {
            $date       = Carbon::now()->subMonths( $i );
            $monthStart = $date->format( 'Y-m-01 00:00:00' );
            $monthEnd   = $date->format( 'Y-m-t 23:59:59' );

            $receitaMes = Invoice::where( 'tenant_id', $this->getTenantId( $userId ) )
                ->where( 'transaction_amount', '>', 0 )
                ->whereBetween( 'transaction_date', [ $monthStart, $monthEnd ] )
                ->sum( 'transaction_amount' );

            $despesaMes = abs( Invoice::where( 'tenant_id', $this->getTenantId( $userId ) )
                ->where( 'transaction_amount', '<', 0 )
                ->whereBetween( 'transaction_date', [ $monthStart, $monthEnd ] )
                ->sum( 'transaction_amount' ) );

            $labels[]   = $date->format( 'M/Y' );
            $receitas[] = round( $receitaMes, 2 );
            $despesas[] = round( $despesaMes, 2 );
        }

        return [
            'labels'   => $labels,
            'datasets' => [
                [
                    'label'           => 'Receitas',
                    'data'            => $receitas,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.8)',
                    'borderColor'     => 'rgb(34, 197, 94)',
                    'borderWidth'     => 1
                ],
                [
                    'label'           => 'Despesas',
                    'data'            => $despesas,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.8)',
                    'borderColor'     => 'rgb(239, 68, 68)',
                    'borderWidth'     => 1
                ]
            ]
        ];
    }

    /**
     * Dados para gráfico de tendências
     */
    public function getTendenciasData( int $userId, string $period, string $metric ): array
    {
        $data   = [];
        $labels = [];

        switch ( $metric ) {
            case 'receita':
                $data = $this->getReceitaTendenciaData( $userId, $period );
                break;
            case 'despesa':
                $data = $this->getDespesaTendenciaData( $userId, $period );
                break;
            case 'saldo':
                $data = $this->getSaldoTendenciaData( $userId, $period );
                break;
            case 'transacoes':
                $data = $this->getTransacoesTendenciaData( $userId, $period );
                break;
            default:
                $data = $this->getReceitaTendenciaData( $userId, $period );
        }

        return [
            'labels'   => $labels,
            'datasets' => [
                [
                    'label'           => ucfirst( $metric ),
                    'data'            => $data,
                    'borderColor'     => $this->getMetricColor( $metric ),
                    'backgroundColor' => $this->getMetricBackgroundColor( $metric ),
                    'tension'         => 0.4,
                    'fill'            => false
                ]
            ]
        ];
    }

    /**
     * Dados de tendência de receitas
     */
    private function getReceitaTendenciaData( int $userId, string $period ): array
    {
        $days = $period === 'week' ? 7 : ( $period === 'month' ? 30 : 365 );
        $data = [];

        for ( $i = $days; $i >= 0; $i-- ) {
            $date     = Carbon::now()->subDays( $i );
            $dayStart = $date->format( 'Y-m-d 00:00:00' );
            $dayEnd   = $date->format( 'Y-m-d 23:59:59' );

            $receita = Invoice::where( 'tenant_id', $this->getTenantId( $userId ) )
                ->where( 'transaction_amount', '>', 0 )
                ->whereBetween( 'transaction_date', [ $dayStart, $dayEnd ] )
                ->sum( 'transaction_amount' );

            $data[] = round( $receita, 2 );
        }

        return $data;
    }

    /**
     * Dados de tendência de despesas
     */
    private function getDespesaTendenciaData( int $userId, string $period ): array
    {
        $days = $period === 'week' ? 7 : ( $period === 'month' ? 30 : 365 );
        $data = [];

        for ( $i = $days; $i >= 0; $i-- ) {
            $date     = Carbon::now()->subDays( $i );
            $dayStart = $date->format( 'Y-m-d 00:00:00' );
            $dayEnd   = $date->format( 'Y-m-d 23:59:59' );

            $despesas = abs( Invoice::where( 'tenant_id', $this->getTenantId( $userId ) )
                ->where( 'transaction_amount', '<', 0 )
                ->whereBetween( 'transaction_date', [ $dayStart, $dayEnd ] )
                ->sum( 'transaction_amount' ) );

            $data[] = round( $despesas, 2 );
        }

        return $data;
    }

    /**
     * Dados de tendência de saldo
     */
    private function getSaldoTendenciaData( int $userId, string $period ): array
    {
        $days = $period === 'week' ? 7 : ( $period === 'month' ? 30 : 365 );
        $data = [];

        for ( $i = $days; $i >= 0; $i-- ) {
            $date     = Carbon::now()->subDays( $i );
            $dayStart = $date->format( 'Y-m-d 00:00:00' );
            $dayEnd   = $date->format( 'Y-m-d 23:59:59' );

            $receita = Invoice::where( 'tenant_id', $this->getTenantId( $userId ) )
                ->where( 'transaction_amount', '>', 0 )
                ->whereBetween( 'transaction_date', [ $dayStart, $dayEnd ] )
                ->sum( 'transaction_amount' );

            $despesas = abs( Invoice::where( 'tenant_id', $this->getTenantId( $userId ) )
                ->where( 'transaction_amount', '<', 0 )
                ->whereBetween( 'transaction_date', [ $dayStart, $dayEnd ] )
                ->sum( 'transaction_amount' ) );

            $data[] = round( $receita - $despesas, 2 );
        }

        return $data;
    }

    /**
     * Dados de tendência de transações
     */
    private function getTransacoesTendenciaData( int $userId, string $period ): array
    {
        $days = $period === 'week' ? 7 : ( $period === 'month' ? 30 : 365 );
        $data = [];

        for ( $i = $days; $i >= 0; $i-- ) {
            $date     = Carbon::now()->subDays( $i );
            $dayStart = $date->format( 'Y-m-d 00:00:00' );
            $dayEnd   = $date->format( 'Y-m-d 23:59:59' );

            $count = Invoice::where( 'tenant_id', $this->getTenantId( $userId ) )
                ->whereBetween( 'transaction_date', [ $dayStart, $dayEnd ] )
                ->count();

            $data[] = $count;
        }

        return $data;
    }

    /**
     * Define range de datas baseado no período
     */
    private function getDateRange( string $period ): array
    {
        $now = Carbon::now();

        return match ( $period ) {
            'today' => [
                'start' => $now->startOfDay(),
                'end'   => $now->endOfDay()
            ],
            'week'  => [
                'start'  => $now->startOfWeek(),
                'end'    => $now->endOfWeek()
            ],
            'month' => [
                'start' => $now->startOfMonth(),
                'end'   => $now->endOfMonth()
            ],
            'year'  => [
                'start'  => $now->startOfYear(),
                'end'    => $now->endOfYear()
            ],
            default => [
                'start' => $now->startOfMonth(),
                'end'   => $now->endOfMonth()
            ]
        };
    }

    /**
     * Obtém cor para a métrica específica
     */
    private function getMetricColor( string $metric ): string
    {
        return match ( $metric ) {
            'receita'    => 'rgb(34, 197, 94)',
            'despesa'    => 'rgb(239, 68, 68)',
            'saldo'      => 'rgb(59, 130, 246)',
            'transacoes' => 'rgb(139, 92, 246)',
            default      => 'rgb(59, 130, 246)'
        };
    }

    /**
     * Obtém cor de fundo para a métrica específica
     */
    private function getMetricBackgroundColor( string $metric ): string
    {
        return match ( $metric ) {
            'receita'    => 'rgba(34, 197, 94, 0.1)',
            'despesa'    => 'rgba(239, 68, 68, 0.1)',
            'saldo'      => 'rgba(59, 130, 246, 0.1)',
            'transacoes' => 'rgba(139, 92, 246, 0.1)',
            default      => 'rgba(59, 130, 246, 0.1)'
        };
    }

    /**
     * Obtém ID do tenant do usuário
     */
    private function getTenantId( int $userId ): int
    {
        // Por enquanto retorna 1 - implementar lógica de tenant posteriormente
        return 1;
    }

}
