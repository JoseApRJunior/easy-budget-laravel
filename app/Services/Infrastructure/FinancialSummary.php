<?php

declare(strict_types=1);

namespace App\Services\Infrastructure;

use App\Enums\OperationStatus;
use App\Models\Budget;
use App\Models\BudgetStatus;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Serviço para geração de resumos financeiros e relatórios.
 *
 * Migra lógica legacy: cálculos financeiros, análises de receita/despesa,
 * projeções mensais. Usa Eloquent para queries complexas de análise financeira.
 * Mantém compatibilidade com API legacy através de métodos específicos.
 *
 * Este service é responsável por:
 * - Cálculos de receita mensal por tenant
 * - Análise de orçamentos pendentes e atrasados
 * - Projeções financeiras para próximos períodos
 * - Relatórios consolidados de performance financeira
 * - Análise de tendências e padrões de receita
 */
class FinancialSummary
{
    /**
     * Status de orçamentos considerados como receita (faturamento).
     */
    private const REVENUE_STATUSES = [ 'IN_PROGRESS', 'COMPLETED' ];

    /**
     * Status de orçamentos pendentes para análise.
     */
    private const PENDING_STATUSES = [ 'DRAFT', 'PENDING' ];

    /**
     * Status de orçamentos para projeção futura.
     */
    private const PROJECTION_STATUSES = [ 'DRAFT', 'PENDING', 'APPROVED', 'IN_PROGRESS' ];

    /**
     * Obtém o resumo financeiro mensal por tenant.
     *
     * @param int $tenantId ID do tenant
     * @return ServiceResult Resumo financeiro mensal
     */
    public function getMonthlySummary( int $tenantId ): ServiceResult
    {
        try {
            $currentMonth = Carbon::now()->format( 'Y-m' );

            // Faturamento Mensal (Orçamentos em Andamento e Concluídos)
            $monthlyRevenue = $this->calculateMonthlyRevenue( $tenantId, $currentMonth );

            // Orçamentos Pendentes
            $pendingBudgets = $this->calculatePendingBudgets( $tenantId );

            // Pagamentos Atrasados
            $overduePayments = $this->calculateOverduePayments( $tenantId );

            // Projeção para o próximo mês
            $nextMonthProjection = $this->calculateNextMonthProjection( $tenantId, $currentMonth );

            // Análise de tendências (nova funcionalidade)
            $trends = $this->calculateTrends( $tenantId );

            $summary = [
                'monthly_revenue'       => $monthlyRevenue,
                'pending_budgets'       => $pendingBudgets,
                'overdue_payments'      => $overduePayments,
                'next_month_projection' => $nextMonthProjection,
                'trends'                => $trends,
                'generated_at'          => Carbon::now()->toISOString(),
                'period'                => $currentMonth,
            ];

            return ServiceResult::success(
                $summary,
                'Resumo financeiro obtido com sucesso.',
            );
        } catch ( Exception $e ) {
            Log::error( 'FinancialSummary: Erro ao obter resumo financeiro', [
                'tenant_id' => $tenantId,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao obter resumo financeiro: ' . $e->getMessage()
            );
        }
    }

    /**
     * Obtém resumo financeiro consolidado para todos os tenants.
     *
     * Método específico para relatórios administrativos globais.
     * Implementa tenant isolation através de agrupamento por tenant.
     *
     * @param array $filters Filtros opcionais (period, status, etc.)
     * @return ServiceResult Resumo consolidado
     */
    public function getConsolidatedSummary( array $filters = [] ): ServiceResult
    {
        try {
            $period       = $filters[ 'period' ] ?? Carbon::now()->format( 'Y-m' );
            $statusFilter = $filters[ 'status' ] ?? null;

            $consolidatedData = Budget::query()
                ->select( [
                    'tenant_id',
                    DB::raw( 'COUNT(*) as total_budgets' ),
                    DB::raw( 'SUM(total) as total_value' ),
                    DB::raw( 'AVG(total) as average_value' ),
                    DB::raw( 'COUNT(CASE WHEN due_date < CURDATE() THEN 1 END) as overdue_count' ),
                    DB::raw( 'SUM(CASE WHEN due_date < CURDATE() THEN total ELSE 0 END) as overdue_value' ),
                ] )
                ->when( $period, function ( $query, $period ) {
                    return $query->whereRaw( 'DATE_FORMAT(created_at, "%Y-%m") = ?', [ $period ] );
                } )
                ->when( $statusFilter, function ( $query, $statusFilter ) {
                    return $query->whereHas( 'budgetStatus', function ( $q ) use ( $statusFilter ) {
                        $q->where( 'slug', $statusFilter );
                    } );
                } )
                ->groupBy( 'tenant_id' )
                ->orderBy( 'total_value', 'desc' )
                ->get();

            $summary = [
                'consolidated' => $consolidatedData,
                'totals'       => [
                    'total_budgets' => $consolidatedData->sum( 'total_budgets' ),
                    'total_value'   => $consolidatedData->sum( 'total_value' ),
                    'average_value' => $consolidatedData->avg( 'average_value' ),
                    'overdue_count' => $consolidatedData->sum( 'overdue_count' ),
                    'overdue_value' => $consolidatedData->sum( 'overdue_value' ),
                ],
                'generated_at' => Carbon::now()->toISOString(),
                'period'       => $period,
            ];

            Log::info( 'FinancialSummary: Resumo consolidado gerado', [
                'period'        => $period,
                'tenants_count' => $consolidatedData->count(),
            ] );

            return ServiceResult::success(
                $summary,
                'Resumo financeiro consolidado obtido com sucesso.',
            );
        } catch ( Exception $e ) {
            Log::error( 'FinancialSummary: Erro ao obter resumo consolidado', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao obter resumo consolidado: ' . $e->getMessage()
            );
        }
    }

    /**
     * Obtém análise de performance financeira por período.
     *
     * Implementa análise de tendências e padrões de receita.
     * Compatibilidade com API legacy mantida através de estrutura de dados similar.
     *
     * @param int $tenantId ID do tenant
     * @param string $period Período para análise (Y-m)
     * @param int $months Número de meses para análise (padrão: 6)
     * @return ServiceResult Análise de performance
     */
    public function getPerformanceAnalysis( int $tenantId, string $period, int $months = 6 ): ServiceResult
    {
        try {
            $startDate = Carbon::now()->parse( $period . '-01' )->subMonths( $months - 1 );
            $endDate   = Carbon::now()->parse( $period . '-01' )->endOfMonth();

            $performanceData = Budget::query()
                ->select( [
                    DB::raw( 'DATE_FORMAT(created_at, "%Y-%m") as period' ),
                    DB::raw( 'COUNT(*) as total_budgets' ),
                    DB::raw( 'SUM(total) as total_revenue' ),
                    DB::raw( 'AVG(total) as average_budget' ),
                    DB::raw( 'COUNT(CASE WHEN budget_statuses.slug IN ("COMPLETED", "IN_PROGRESS") THEN 1 END) as completed_budgets' ),
                ] )
                ->join( 'budget_statuses', 'budgets.budget_statuses_id', '=', 'budget_statuses.id' )
                ->where( 'tenant_id', $tenantId )
                ->whereBetween( 'created_at', [ $startDate, $endDate ] )
                ->groupBy( 'period' )
                ->orderBy( 'period' )
                ->get();

            // Calcular tendências
            $trends = $this->calculatePerformanceTrends( $performanceData );

            $analysis = [
                'performance_data' => $performanceData,
                'trends'           => $trends,
                'summary'          => [
                    'total_periods'           => $performanceData->count(),
                    'total_revenue'           => $performanceData->sum( 'total_revenue' ),
                    'average_monthly_revenue' => $performanceData->avg( 'total_revenue' ),
                    'best_month'              => $performanceData->sortByDesc( 'total_revenue' )->first(),
                    'growth_rate'             => $trends[ 'growth_rate' ] ?? 0,
                ],
                'generated_at'     => Carbon::now()->toISOString(),
                'analysis_period'  => [
                    'start'  => $startDate->format( 'Y-m' ),
                    'end'    => $endDate->format( 'Y-m' ),
                    'months' => $months,
                ],
            ];

            Log::info( 'FinancialSummary: Análise de performance gerada', [
                'tenant_id' => $tenantId,
                'period'    => $period,
                'months'    => $months,
            ] );

            return ServiceResult::success(
                $analysis,
                'Análise de performance financeira obtida com sucesso.',
            );
        } catch ( Exception $e ) {
            Log::error( 'FinancialSummary: Erro ao obter análise de performance', [
                'tenant_id' => $tenantId,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao obter análise de performance: ' . $e->getMessage()
            );
        }
    }

    /**
     * Método de compatibilidade com API legacy.
     *
     * Mantém a mesma interface do FinancialSummary legacy para
     * garantir compatibilidade com código existente.
     *
     * @param int $tenantId ID do tenant
     * @return array<string, mixed> Dados no formato legacy
     */
    public function getLegacyMonthlySummary( int $tenantId ): array
    {
        $result = $this->getMonthlySummary( $tenantId );

        if ( !$result->isSuccess() ) {
            return [
                'monthly_revenue'       => 0,
                'pending_budgets'       => [ 'total' => 0, 'count' => 0 ],
                'overdue_payments'      => [ 'total' => 0, 'count' => 0 ],
                'next_month_projection' => 0,
            ];
        }

        $data = $result->getData();

        return [
            'monthly_revenue'       => $data[ 'monthly_revenue' ],
            'pending_budgets'       => $data[ 'pending_budgets' ],
            'overdue_payments'      => $data[ 'overdue_payments' ],
            'next_month_projection' => $data[ 'next_month_projection' ],
        ];
    }

    /**
     * Calcula receita mensal por tenant.
     *
     * @param int $tenantId ID do tenant
     * @param string $period Período (Y-m)
     * @return float Receita mensal
     */
    private function calculateMonthlyRevenue( int $tenantId, string $period ): float
    {
        return Budget::query()
            ->join( 'budget_statuses', 'budgets.budget_statuses_id', '=', 'budget_statuses.id' )
            ->where( 'tenant_id', $tenantId )
            ->whereIn( 'budget_statuses.slug', self::REVENUE_STATUSES )
            ->whereRaw( 'DATE_FORMAT(budgets.updated_at, "%Y-%m") = ?', [ $period ] )
            ->sum( 'total' );
    }

    /**
     * Calcula orçamentos pendentes por tenant.
     *
     * @param int $tenantId ID do tenant
     * @return array{total: float, count: int}
     */
    private function calculatePendingBudgets( int $tenantId ): array
    {
        $result = Budget::query()
            ->join( 'budget_statuses', 'budgets.budget_statuses_id', '=', 'budget_statuses.id' )
            ->where( 'tenant_id', $tenantId )
            ->whereIn( 'budget_statuses.slug', self::PENDING_STATUSES )
            ->selectRaw( 'COALESCE(SUM(total), 0) as total, COUNT(*) as count' )
            ->first();

        return [
            'total' => (float) ( $result->total ?? 0 ),
            'count' => (int) ( $result->count ?? 0 ),
        ];
    }

    /**
     * Calcula pagamentos atrasados por tenant.
     *
     * @param int $tenantId ID do tenant
     * @return array{total: float, count: int}
     */
    private function calculateOverduePayments( int $tenantId ): array
    {
        $result = Budget::query()
            ->join( 'budget_statuses', 'budgets.budget_statuses_id', '=', 'budget_statuses.id' )
            ->where( 'tenant_id', $tenantId )
            ->where( 'budget_statuses.slug', 'PENDING' )
            ->where( 'due_date', '<', Carbon::now() )
            ->selectRaw( 'COALESCE(SUM(total), 0) as total, COUNT(*) as count' )
            ->first();

        return [
            'total' => (float) ( $result->total ?? 0 ),
            'count' => (int) ( $result->count ?? 0 ),
        ];
    }

    /**
     * Calcula projeção para o próximo mês.
     *
     * @param int $tenantId ID do tenant
     * @param string $currentPeriod Período atual (Y-m)
     * @return float Projeção para próximo mês
     */
    private function calculateNextMonthProjection( int $tenantId, string $currentPeriod ): float
    {
        $nextMonth = Carbon::now()->parse( $currentPeriod . '-01' )->addMonth()->format( 'Y-m' );

        return Budget::query()
            ->join( 'budget_statuses', 'budgets.budget_statuses_id', '=', 'budget_statuses.id' )
            ->where( 'tenant_id', $tenantId )
            ->whereIn( 'budget_statuses.slug', self::PROJECTION_STATUSES )
            ->whereRaw( 'DATE_FORMAT(due_date, "%Y-%m") = ?', [ $nextMonth ] )
            ->sum( 'total' );
    }

    /**
     * Calcula tendências de performance financeira.
     *
     * @param int $tenantId ID do tenant
     * @return array Tendências calculadas
     */
    private function calculateTrends( int $tenantId ): array
    {
        $last3Months = Budget::query()
            ->select( [
                DB::raw( 'DATE_FORMAT(budgets.created_at, "%Y-%m") as period' ),
                DB::raw( 'SUM(total) as revenue' ),
            ] )
            ->join( 'budget_statuses', 'budgets.budget_statuses_id', '=', 'budget_statuses.id' )
            ->where( 'tenant_id', $tenantId )
            ->whereIn( 'budget_statuses.slug', self::REVENUE_STATUSES )
            ->where( 'budgets.created_at', '>=', Carbon::now()->subMonths( 3 ) )
            ->groupBy( 'period' )
            ->orderBy( 'period' )
            ->get();

        if ( $last3Months->count() < 2 ) {
            return [ 'trend' => 'insufficient_data' ];
        }

        $revenues   = $last3Months->pluck( 'revenue' )->toArray();
        $growthRate = $this->calculateGrowthRate( $revenues );

        return [
            'trend'            => $growthRate > 0 ? 'growing' : ( $growthRate < 0 ? 'declining' : 'stable' ),
            'growth_rate'      => round( $growthRate, 2 ),
            'periods_analyzed' => $last3Months->count(),
        ];
    }

    /**
     * Calcula tendências de performance a partir de dados históricos.
     *
     * @param \Illuminate\Support\Collection $performanceData Dados de performance
     * @return array Tendências calculadas
     */
    private function calculatePerformanceTrends( $performanceData ): array
    {
        if ( $performanceData->count() < 2 ) {
            return [ 'trend' => 'insufficient_data' ];
        }

        $revenues   = $performanceData->pluck( 'total_revenue' )->toArray();
        $growthRate = $this->calculateGrowthRate( $revenues );

        return [
            'trend'            => $growthRate > 0 ? 'growing' : ( $growthRate < 0 ? 'declining' : 'stable' ),
            'growth_rate'      => round( $growthRate, 2 ),
            'periods_analyzed' => $performanceData->count(),
        ];
    }

    /**
     * Calcula taxa de crescimento entre períodos.
     *
     * @param array $values Array de valores para calcular crescimento
     * @return float Taxa de crescimento em percentual
     */
    private function calculateGrowthRate( array $values ): float
    {
        if ( count( $values ) < 2 ) {
            return 0;
        }

        $firstValue = (float) $values[ 0 ];
        $lastValue  = (float) end( $values );

        if ( $firstValue == 0 ) {
            return $lastValue > 0 ? 100 : 0;
        }

        return ( ( $lastValue - $firstValue ) / $firstValue ) * 100;
    }

}
