<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Services\ChartService;
use App\Services\MetricsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private MetricsService $metricsService,
        private ChartService $chartService,
    ) {}

    /**
     * Dashboard principal
     */
    public function index( Request $request ): View
    {
        $userId = auth()->id();
        $period = $request->get( 'period', 'month' );

        // Obter dados iniciais com cache curto para boa UX
        $cacheKey = "dashboard.initial.{$userId}.{$period}";
        $ttl      = 60; // 1 minuto para dados iniciais

        $dashboardData = Cache::remember( $cacheKey, $ttl, function () use ($userId, $period) {
            return [
                'metrics'             => $this->metricsService->getMetrics( $userId, $period ),
                'charts'              => $this->chartService->getInitialChartData( $userId, $period ),
                'recent_transactions' => $this->getRecentTransactions( $userId ),
                'quick_actions'       => $this->getQuickActions()
            ];
        } );

        return view( 'dashboard.index', [
            'metrics'            => $dashboardData[ 'metrics' ],
            'charts'             => $dashboardData[ 'charts' ],
            'recentTransactions' => $dashboardData[ 'recent_transactions' ],
            'quickActions'       => $dashboardData[ 'quick_actions' ],
            'currentPeriod'      => $period,
            'lastUpdated'        => now()->toISOString()
        ] );
    }

    /**
     * Obtém transações recentes para o dashboard
     */
    private function getRecentTransactions( int $userId, int $limit = 10 ): array
    {
        // Por enquanto retorna dados mock até implementar o modelo Transaction
        return [
            [
                'id'          => 1,
                'description' => 'Pagamento recebido - Cliente ABC',
                'amount'      => 1500.00,
                'type'        => 'receita',
                'date'        => now()->subDays( 1 )->toDateString(),
                'category'    => 'Vendas',
                'icon'        => 'plus-circle',
                'color'       => 'green'
            ],
            [
                'id'          => 2,
                'description' => 'Compra de material escritório',
                'amount'      => 250.00,
                'type'        => 'despesa',
                'date'        => now()->subDays( 2 )->toDateString(),
                'category'    => 'Escritório',
                'icon'        => 'dash-circle',
                'color'       => 'red'
            ],
            [
                'id'          => 3,
                'description' => 'Serviço de manutenção',
                'amount'      => 800.00,
                'type'        => 'despesa',
                'date'        => now()->subDays( 3 )->toDateString(),
                'category'    => 'Manutenção',
                'icon'        => 'dash-circle',
                'color'       => 'red'
            ]
        ];
    }

    /**
     * Obtém ações rápidas para o dashboard
     */
    private function getQuickActions(): array
    {
        return [
            [
                'id'          => 'nova_receita',
                'title'       => 'Nova Receita',
                'description' => 'Registrar nova entrada financeira',
                'icon'        => 'plus-circle',
                'color'       => 'green',
                'route'       => 'transactions.create',
                'params'      => [ 'type' => 'receita' ]
            ],
            [
                'id'          => 'nova_despesa',
                'title'       => 'Nova Despesa',
                'description' => 'Registrar nova saída financeira',
                'icon'        => 'dash-circle',
                'color'       => 'red',
                'route'       => 'transactions.create',
                'params'      => [ 'type' => 'despesa' ]
            ],
            [
                'id'          => 'ver_relatorios',
                'title'       => 'Relatórios',
                'description' => 'Ver relatórios detalhados',
                'icon'        => 'bar-chart',
                'color'       => 'blue',
                'route'       => 'reports.index'
            ],
            [
                'id'          => 'configuracoes',
                'title'       => 'Configurações',
                'description' => 'Ajustar preferências do sistema',
                'icon'        => 'gear',
                'color'       => 'gray',
                'route'       => 'settings.index'
            ]
        ];
    }

}
