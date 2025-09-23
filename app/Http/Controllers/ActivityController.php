<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exports\ActivityReportExport;
use App\Services\ActivityService;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Controlador para visualização de logs de atividades.
 * Implementa exibição tenant-aware de logs com filtros e paginação.
 * Migração do sistema legacy app/controllers/ActivityController.php.
 *
 * @package App\Http\Controllers
 * @author IA
 */
class ActivityController extends BaseController
{
    /**
     * @var ActivityService|null
     */
    protected ?ActivityService $activityService = null;

    /**
     * Construtor da classe ActivityController.
     *
     * @param ActivityService $activityService
     */
    public function __construct( ActivityService $activityService )
    {
        parent::__construct();
        $this->activityService = $activityService;
    }

    /**
     * Exibe listagem de atividades do tenant atual com filtros.
     *
     * @param Request $request
     * @return View
     */
    public function index( Request $request ): View
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $this->logActivity(
            action: 'view_activities',
            entity: 'activities',
            metadata: [ 'tenant_id' => $tenantId ],
        );

        $filters = $this->getActivityFilters( $request );

        $activities = $this->activityService->getActivitiesByTenant(
            tenantId: $tenantId,
            filters: $filters,
            perPage: 50,
            orderBy: $request->get( 'order_by', 'created_at' ),
            orderDirection: $request->get( 'order_direction', 'desc' ),
        );

        $users    = $this->activityService->getUsersForFilter( $tenantId );
        $entities = $this->activityService->getEntityTypes( $tenantId );
        $actions  = $this->activityService->getActionTypes( $tenantId );

        return $this->renderView( 'activities.index', [ 
            'activities' => $activities,
            'filters'    => $filters,
            'users'      => $users,
            'entities'   => $entities,
            'actions'    => $actions,
            'tenantId'   => $tenantId,
            'stats'      => $this->activityService->getActivityStats( $tenantId )
        ] );
    }

    /**
     * Processa filtros para consulta de atividades.
     *
     * @param Request $request
     * @return array
     */
    private function getActivityFilters( Request $request ): array
    {
        return [ 
            'date_from'           => $request->get( 'date_from' ),
            'date_to'             => $request->get( 'date_to' ),
            'user_id'             => $request->get( 'user_id' ),
            'entity_type'         => $request->get( 'entity_type' ),
            'action_type'         => $request->get( 'action_type' ),
            'ip_address'          => $request->get( 'ip_address' ),
            'user_agent_contains' => $request->get( 'user_agent_contains' ),
            'success_only'        => $request->boolean( 'success_only', false ),
            'error_only'          => $request->boolean( 'error_only', false )
        ];
    }

    /**
     * Busca atividades em tempo real via AJAX.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search( Request $request ): JsonResponse
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->jsonError( 'Tenant não encontrado.', statusCode: 403 );
        }

        $request->validate( [ 
            'query'     => 'required|string|max:100',
            'limit'     => 'nullable|integer|min:1|max:1000',
            'date_from' => 'nullable|date',
            'date_to'   => 'nullable|date'
        ] );

        $filters                   = $this->getActivityFilters( $request );
        $filters[ 'search_query' ] = $request->query;

        $activities = $this->activityService->searchActivities(
            tenantId: $tenantId,
            searchQuery: $request->query,
            filters: $filters,
            limit: $request->get( 'limit', 20 ),
        );

        return $this->jsonSuccess(
            data: $activities,
            message: 'Atividades encontradas com sucesso.',
        );
    }

    /**
     * Exporta log de atividades para PDF.
     *
     * @param Request $request
     * @return RedirectResponse|\Illuminate\Http\Response
     */
    public function exportPdf( Request $request ): RedirectResponse|\Illuminate\Http\Response
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $request->validate( [ 
            'date_from' => 'required|date',
            'date_to'   => 'required|date|after_or_equal:date_from',
            'format'    => 'nullable|in:pdf,excel'
        ] );

        $this->logActivity(
            action: 'export_activity_pdf',
            entity: 'activities',
            metadata: [ 
                'tenant_id' => $tenantId,
                'date_from' => $request->date_from,
                'date_to'   => $request->date_to
            ],
        );

        $filters    = $this->getActivityFilters( $request );
        $activities = $this->activityService->getActivitiesByTenant(
            tenantId: $tenantId,
            filters: $filters,
            perPage: null // Todas as atividades no período
        );

        $reportData = [ 
            'tenantId'    => $tenantId,
            'activities'  => $activities,
            'filters'     => $filters,
            'generatedAt' => now()
        ];

        $pdf      = PDF::loadView( 'reports.activities', $reportData );
        $filename = 'log_atividades_' . date( 'Y-m-d_H-i-s' ) . '.pdf';

        return $pdf->download( $filename );
    }

    /**
     * Exporta log de atividades para Excel.
     *
     * @param Request $request
     * @return RedirectResponse|\Illuminate\Http\Response
     */
    public function exportExcel( Request $request ): RedirectResponse|\Illuminate\Http\Response
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $request->validate( [ 
            'date_from' => 'required|date',
            'date_to'   => 'required|date|after_or_equal:date_from'
        ] );

        $this->logActivity(
            action: 'export_activity_excel',
            entity: 'activities',
            metadata: [ 
                'tenant_id' => $tenantId,
                'date_from' => $request->date_from,
                'date_to'   => $request->date_to
            ],
        );

        $filters    = $this->getActivityFilters( $request );
        $activities = $this->activityService->getActivitiesByTenant(
            tenantId: $tenantId,
            filters: $filters,
            perPage: null // Todas as atividades no período
        );

        return Excel::download(
            new ActivityReportExport( $activities ),
            'log_atividades_' . date( 'Y-m-d' ) . '.xlsx'
        );
    }

    /**
     * Exibe estatísticas e métricas de atividades.
     *
     * @return View
     */
    public function stats(): View
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $this->logActivity(
            action: 'view_activity_stats',
            entity: 'activities',
            metadata: [ 'tenant_id' => $tenantId ],
        );

        $stats       = $this->activityService->getActivityStats( $tenantId );
        $trends      = $this->activityService->getActivityTrends( $tenantId, 30 );
        $topUsers    = $this->activityService->getTopActiveUsers( $tenantId, 10 );
        $topEntities = $this->activityService->getMostActiveEntities( $tenantId, 10 );

        return $this->renderView( 'activities.stats', [ 
            'stats'       => $stats,
            'trends'      => $trends,
            'topUsers'    => $topUsers,
            'topEntities' => $topEntities,
            'tenantId'    => $tenantId
        ] );
    }

    /**
     * Limpa logs antigos baseado nas configurações de retenção.
     *
     * @return RedirectResponse
     */
    public function cleanup(): RedirectResponse
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $settings      = $this->settingsService->getActivityLogSettings( $tenantId );
        $retentionDays = $settings->retention_days ?? 90;

        $deletedCount = $this->activityService->cleanupOldActivities( $tenantId, $retentionDays );

        $this->logActivity(
            action: 'cleanup_activity_logs',
            entity: 'activities',
            metadata: [ 
                'tenant_id'      => $tenantId,
                'retention_days' => $retentionDays,
                'deleted_count'  => $deletedCount
            ],
        );

        return $this->successRedirect(
            message: "Limpeza concluída. {$deletedCount} atividades antigas foram removidas.",
            route: 'activities.index',
        );
    }

    /**
     * API endpoint para dados de atividades em tempo real.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function apiActivityData( Request $request ): JsonResponse
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->jsonError( 'Tenant não encontrado.', statusCode: 403 );
        }

        $request->validate( [ 
            'type'  => 'required|string|in:recent,stats,trends,users,entities',
            'limit' => 'nullable|integer|min:1|max:100'
        ] );

        $data = $this->activityService->getActivityDataApi(
            tenantId: $tenantId,
            type: $request->type,
            limit: $request->get( 'limit', 20 ),
        );

        return $this->jsonSuccess(
            data: $data,
            message: 'Dados de atividades carregados com sucesso.',
        );
    }

}
