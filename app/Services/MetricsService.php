<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Serviço para métricas e analytics do sistema.
 *
 * Este serviço fornece funcionalidades para cálculo de métricas financeiras,
 * seguindo a arquitetura estabelecida no sistema.
 */
class MetricsService
{
    /**
     * Obtém métricas principais do dashboard
     */
    public function getMetrics( int $userId, string $period, bool $forceRefresh = false ): array
    {
        $dateRange = $this->getDateRange( $period );

        return [
            'receita_total'        => $this->calculateReceitaTotal( $userId, $dateRange ),
            'despesas_totais'      => $this->calculateDespesasTotais( $userId, $dateRange ),
            'saldo_atual'          => $this->calculateSaldoAtual( $userId, $dateRange ),
            'transacoes_hoje'      => $this->countTransacoesHoje( $userId ),
            'comparativo_anterior' => $this->getComparativoPeriodoAnterior( $userId, $period ),
            'metas_alcancadas'     => $this->calculateMetasAlcancadas( $userId, $period ),
            'period'               => $period,
            'date_range'           => $dateRange
        ];
    }

    /**
     * Calcula receita total do período
     */
    private function calculateReceitaTotal( int $userId, array $dateRange ): array
    {
        $tenantId = $this->getTenantId( $userId );
        $receita  = Invoice::where( 'tenant_id', $tenantId )
            ->where( 'transaction_amount', '>', 0 )
            ->whereBetween( 'transaction_date', [ $dateRange[ 'start' ], $dateRange[ 'end' ] ] )
            ->sum( 'transaction_amount' );

        $receitaAnterior = $this->getReceitaPeriodoAnterior( $userId, $dateRange );

        return [
            'valor'     => $receita,
            'formatado' => 'R$ ' . number_format( $receita, 2, ',', '.' ),
            'variacao'  => $this->calculateVariacao( $receita, $receitaAnterior ),
            'tendencia' => $receita > $receitaAnterior ? 'up' : ( $receita < $receitaAnterior ? 'down' : 'stable' )
        ];
    }

    /**
     * Calcula despesas totais do período
     */
    private function calculateDespesasTotais( int $userId, array $dateRange ): array
    {
        $tenantId = $this->getTenantId( $userId );
        $despesas = abs( Invoice::where( 'tenant_id', $tenantId )
            ->where( 'transaction_amount', '<', 0 )
            ->whereBetween( 'transaction_date', [ $dateRange[ 'start' ], $dateRange[ 'end' ] ] )
            ->sum( 'transaction_amount' ) );

        $despesasAnteriores = $this->getDespesasPeriodoAnterior( $userId, $dateRange );

        return [
            'valor'     => $despesas,
            'formatado' => 'R$ ' . number_format( $despesas, 2, ',', '.' ),
            'variacao'  => $this->calculateVariacao( $despesas, $despesasAnteriores ),
            'tendencia' => $despesas > $despesasAnteriores ? 'up' : ( $despesas < $despesasAnteriores ? 'down' : 'stable' )
        ];
    }

    /**
     * Calcula saldo atual (receitas - despesas)
     */
    private function calculateSaldoAtual( int $userId, array $dateRange ): array
    {
        $receita  = $this->calculateReceitaTotal( $userId, $dateRange );
        $despesas = $this->calculateDespesasTotais( $userId, $dateRange );

        $saldo = $receita[ 'valor' ] - $despesas[ 'valor' ];

        return [
            'valor'               => $saldo,
            'formatado'           => 'R$ ' . number_format( $saldo, 2, ',', '.' ),
            'positivo'            => $saldo >= 0,
            'porcentagem_receita' => $receita[ 'valor' ] > 0 ? round( ( $saldo / $receita[ 'valor' ] ) * 100, 1 ) : 0
        ];
    }

    /**
     * Conta transações do dia atual
     */
    private function countTransacoesHoje( int $userId ): array
    {
        $hoje   = Carbon::today();
        $amanha = Carbon::tomorrow();

        $tenantId = $this->getTenantId( $userId );
        $count    = Invoice::where( 'tenant_id', $tenantId )
            ->whereBetween( 'transaction_date', [ $hoje, $amanha ] )
            ->count();

        return [
            'quantidade' => $count,
            'formatado'  => number_format( $count )
        ];
    }

    /**
     * Obtém comparativo com período anterior
     */
    private function getComparativoPeriodoAnterior( int $userId, string $period ): array
    {
        $currentRange  = $this->getDateRange( $period );
        $previousRange = $this->getPreviousPeriodRange( $period );

        $receitaAtual    = $this->calculateReceitaTotal( $userId, $currentRange );
        $receitaAnterior = $this->calculateReceitaTotal( $userId, $previousRange );

        $despesasAtual      = $this->calculateDespesasTotais( $userId, $currentRange );
        $despesasAnteriores = $this->calculateDespesasTotais( $userId, $previousRange );

        return [
            'receita'  => [
                'atual'    => $receitaAtual[ 'valor' ],
                'anterior' => $receitaAnterior[ 'valor' ],
                'variacao' => $receitaAtual[ 'variacao' ]
            ],
            'despesas' => [
                'atual'    => $despesasAtual[ 'valor' ],
                'anterior' => $despesasAnteriores[ 'valor' ],
                'variacao' => $despesasAtual[ 'variacao' ]
            ]
        ];
    }

    /**
     * Calcula percentual de metas alcançadas
     */
    private function calculateMetasAlcancadas( int $userId, string $period ): array
    {
        // Por enquanto retorna dados mock - implementar lógica de metas posteriormente
        return [
            'receita'  => 85.5,
            'despesas' => 92.3,
            'saldo'    => 78.1
        ];
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
     * Obtém range do período anterior
     */
    private function getPreviousPeriodRange( string $period ): array
    {
        $now = Carbon::now();

        return match ( $period ) {
            'today' => [
                'start' => $now->subDay()->startOfDay(),
                'end'   => $now->subDay()->endOfDay()
            ],
            'week'  => [
                'start'  => $now->subWeek()->startOfWeek(),
                'end'    => $now->subWeek()->endOfWeek()
            ],
            'month' => [
                'start' => $now->subMonth()->startOfMonth(),
                'end'   => $now->subMonth()->endOfMonth()
            ],
            'year'  => [
                'start'  => $now->subYear()->startOfYear(),
                'end'    => $now->subYear()->endOfYear()
            ],
            default => [
                'start' => $now->subMonth()->startOfMonth(),
                'end'   => $now->subMonth()->endOfMonth()
            ]
        };
    }

    /**
     * Calcula variação percentual entre dois valores
     */
    private function calculateVariacao( float $atual, float $anterior ): float
    {
        if ( $anterior == 0 ) {
            return $atual > 0 ? 100.0 : 0.0;
        }

        return round( ( ( $atual - $anterior ) / $anterior ) * 100, 2 );
    }

    /**
     * Obtém receita do período anterior
     */
    private function getReceitaPeriodoAnterior( int $userId, array $dateRange ): float
    {
        // Implementar lógica específica se necessário
        return 0.0;
    }

    /**
     * Obtém despesas do período anterior
     */
    private function getDespesasPeriodoAnterior( int $userId, array $dateRange ): float
    {
        // Implementar lógica específica se necessário
        return 0.0;
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
