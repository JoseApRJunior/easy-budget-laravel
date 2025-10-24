<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Services\ChartService;
use App\Services\Domain\ActivityService;
use App\Services\MetricsService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private ChartService $chartService,
        private MetricsService $metricsService,
        private ActivityService $activityService,
    ) {}

    /**
     * Dashboard principal
     */
    public function index( Request $request ): View
    {
        $userId = Auth::id();
        $period = $request->get( 'period', 'month' );

        // Obter dados iniciais com cache curto para boa UX
        $cacheKey = "dashboard.initial.{$userId}.{$period}";
        $ttl      = 60; // 1 minuto para dados iniciais

        $dashboardData = Cache::remember( $cacheKey, $ttl, function () use ($userId, $period) {
            return [
                'metrics'           => $this->metricsService->getMetrics( $userId, $period ),
                'charts'            => $this->chartService->getInitialChartData( $userId, $period ),
                'recent_activities' => $this->getRecentActivities( $userId ),
                'quick_actions'     => $this->getQuickActions()
            ];
        } );

        return view( 'dashboard.index', [
            'metrics'            => $dashboardData[ 'metrics' ],
            'charts'             => $dashboardData[ 'charts' ],
            'recentTransactions' => $dashboardData[ 'recent_activities' ],
            'quickActions'       => $dashboardData[ 'quick_actions' ],
            'currentPeriod'      => $period,
            'lastUpdated'        => Carbon::now()->toDateTimeString()
        ] );
    }

    /**
     * Obtém atividades recentes para o dashboard
     */
    private function getRecentActivities( int $userId, int $limit = 10 ): array
    {
        $user = Auth::user();
        if ( !$user ) {
            return [];
        }

        $activitiesResult = $this->activityService->getActivitiesByUser( $userId, [
            'limit'           => $limit,
            'order_by'        => 'created_at',
            'order_direction' => 'desc'
        ] );

        if ( !$activitiesResult->isSuccess() ) {
            return [];
        }

        $activities = $activitiesResult->getData();

        // Transform activities to include user_name for the component
        return $activities->map( function ( $activity ) {
            return (object) [
                'id'          => $activity->id,
                'action_type' => $activity->action_type,
                'description' => $activity->description,
                'created_at'  => $activity->created_at,
                'user_name'   => $activity->user->name ?? 'Sistema',
                'entity_type' => $activity->entity_type,
                'entity_id'   => $activity->entity_id,
                'metadata'    => $activity->metadata,
            ];
        } )->toArray();
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
