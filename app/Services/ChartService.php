<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Serviço para geração de dados para gráficos e visualizações.
 *
 * Este serviço fornece funcionalidades para preparar dados para gráficos,
 * seguindo a arquitetura estabelecida no sistema.
 */
class ChartService
{
    /**
     * Obtém dados para gráfico de receitas por mês
     */
    public function getMonthlyRevenueChart( int $userId, int $year ): array
    {
        $tenantId = $this->getTenantId( $userId );
        $data     = [];

        for ( $month = 1; $month <= 12; $month++ ) {
            $startDate = Carbon::create( $year, $month, 1 )->startOfMonth();
            $endDate   = Carbon::create( $year, $month, 1 )->endOfMonth();

            $revenue = Invoice::where( 'tenant_id', $tenantId )
                ->where( 'transaction_amount', '>', 0 )
                ->whereBetween( 'transaction_date', [ $startDate, $endDate ] )
                ->sum( 'transaction_amount' );

            $data[] = [
                'month'     => Carbon::create( $year, $month, 1 )->format( 'M' ),
                'revenue'   => $revenue,
                'formatted' => 'R$ ' . number_format( $revenue, 2, ',', '.' )
            ];
        }

        return $data;
    }

    /**
     * Obtém dados para gráfico de despesas por categoria
     */
    public function getExpensesByCategoryChart( int $userId, string $period ): array
    {
        $dateRange = $this->getDateRange( $period );
        $tenantId  = $this->getTenantId( $userId );

        // Por enquanto retorna dados mock - implementar lógica real posteriormente
        return [
            [
                'category'   => 'Operacionais',
                'amount'     => 1500.00,
                'percentage' => 45.5
            ],
            [
                'category'   => 'Marketing',
                'amount'     => 800.00,
                'percentage' => 24.2
            ],
            [
                'category'   => 'Administrativas',
                'amount'     => 650.00,
                'percentage' => 19.7
            ],
            [
                'category'   => 'Outras',
                'amount'     => 350.00,
                'percentage' => 10.6
            ]
        ];
    }

    /**
     * Obtém dados para gráfico de evolução mensal
     */
    public function getMonthlyEvolutionChart( int $userId, int $months = 6 ): array
    {
        $data = [];
        $now  = Carbon::now();

        for ( $i = $months - 1; $i >= 0; $i-- ) {
            $date      = $now->copy()->subMonths( $i );
            $startDate = $date->copy()->startOfMonth();
            $endDate   = $date->copy()->endOfMonth();

            $tenantId = $this->getTenantId( $userId );
            $revenue  = Invoice::where( 'tenant_id', $tenantId )
                ->where( 'transaction_amount', '>', 0 )
                ->whereBetween( 'transaction_date', [ $startDate, $endDate ] )
                ->sum( 'transaction_amount' );

            $expenses = abs( Invoice::where( 'tenant_id', $tenantId )
                ->where( 'transaction_amount', '<', 0 )
                ->whereBetween( 'transaction_date', [ $startDate, $endDate ] )
                ->sum( 'transaction_amount' ) );

            $data[] = [
                'month'    => $date->format( 'M/Y' ),
                'revenue'  => $revenue,
                'expenses' => $expenses,
                'profit'   => $revenue - $expenses
            ];
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
     * Obtém ID do tenant do usuário
     */
    private function getTenantId( int $userId ): int
    {
        // Por enquanto retorna 1 - implementar lógica de tenant posteriormente
        return 1;
    }

}
