<?php

namespace App\Http\Controllers;

use App\Models\ReportDefinition;
use App\Models\ReportExecution;
use App\Models\ReportSchedule;
use App\Services\ExportService;
use App\Services\ReportGenerationService;
use App\Services\ReportSchedulerService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Controlador principal para gerenciamento de relatórios
 * Gerencia interface web e operações básicas
 */
class ReportController extends Controller
{
    private ReportGenerationService $reportGenerationService;
    private ReportSchedulerService  $reportSchedulerService;
    private ExportService           $exportService;

    public function __construct(
        ReportGenerationService $reportGenerationService,
        ReportSchedulerService $reportSchedulerService,
        ExportService $exportService,
    ) {
        $this->reportGenerationService = $reportGenerationService;
        $this->reportSchedulerService  = $reportSchedulerService;
        $this->exportService           = $exportService;
    }

    /**
     * Dashboard principal de relatórios
     */
    public function index(): View
    {
        $user = auth()->user();

        // Obter definições de relatório do usuário
        $reportDefinitions = ReportDefinition::where( 'tenant_id', $user->tenant_id )
            ->where( 'user_id', $user->id )
            ->active()
            ->orderBy( 'name' )
            ->get();

        // Obter execuções recentes
        $recentExecutions = ReportExecution::where( 'tenant_id', $user->tenant_id )
            ->where( 'user_id', $user->id )
            ->recent( 7 )
            ->with( [ 'definition' ] )
            ->orderBy( 'created_at', 'DESC' )
            ->limit( 10 )
            ->get();

        // Obter agendamentos ativos
        $activeSchedules = ReportSchedule::where( 'tenant_id', $user->tenant_id )
            ->where( 'user_id', $user->id )
            ->active()
            ->with( [ 'definition' ] )
            ->orderBy( 'next_run_at' )
            ->limit( 5 )
            ->get();

        // Estatísticas rápidas
        $stats = [
            'total_definitions' => $reportDefinitions->count(),
            'total_executions'  => ReportExecution::where( 'tenant_id', $user->tenant_id )
                ->where( 'user_id', $user->id )
                ->recent( 30 )
                ->count(),
            'active_schedules'  => $activeSchedules->count(),
            'failed_executions' => ReportExecution::where( 'tenant_id', $user->tenant_id )
                ->where( 'user_id', $user->id )
                ->failed()
                ->recent( 7 )
                ->count()
        ];

        return view( 'reports.index', compact(
            'reportDefinitions',
            'recentExecutions',
            'activeSchedules',
            'stats',
        ) );
    }

    /**
     * Mostra formulário de criação de relatório
     */
    public function create(): View
    {
        return view( 'reports.create' );
    }

    /**
     * Salva nova definição de relatório
     */
    public function store( Request $request ): RedirectResponse
    {
        try {
            $validated = $request->validate( [
                'name'          => 'required|string|max:255',
                'description'   => 'nullable|string',
                'category'      => 'required|string|in:' . implode( ',', array_keys( ReportDefinition::CATEGORIES ) ),
                'type'          => 'required|string|in:' . implode( ',', array_keys( ReportDefinition::TYPES ) ),
                'query_builder' => 'required|array',
                'config'        => 'required|array',
                'filters'       => 'nullable|array',
                'visualization' => 'nullable|array',
                'is_active'     => 'boolean'
            ] );

            $definition = ReportDefinition::create( [
                'tenant_id'     => auth()->user()->tenant_id,
                'user_id'       => auth()->id(),
                'name'          => $validated[ 'name' ],
                'description'   => $validated[ 'description' ] ?? null,
                'category'      => $validated[ 'category' ],
                'type'          => $validated[ 'type' ],
                'query_builder' => $validated[ 'query_builder' ],
                'config'        => $validated[ 'config' ],
                'filters'       => $validated[ 'filters' ] ?? [],
                'visualization' => $validated[ 'visualization' ] ?? [],
                'is_active'     => $validated[ 'is_active' ] ?? true,
                'created_by'    => auth()->id()
            ] );

            Log::info( 'Nova definição de relatório criada', [
                'definition_id' => $definition->id,
                'user_id'       => auth()->id()
            ] );

            return redirect()->route( 'reports.show', $definition )
                ->with( 'success', 'Relatório criado com sucesso!' );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao criar definição de relatório', [
                'error'   => $e->getMessage(),
                'user_id' => auth()->id()
            ] );

            return back()->withInput()
                ->with( 'error', 'Erro ao criar relatório: ' . $e->getMessage() );
        }
    }

    /**
     * Mostra detalhes de um relatório
     */
    public function show( ReportDefinition $report ): View
    {
        // Verificar permissão
        if ( $report->tenant_id !== auth()->user()->tenant_id ) {
            abort( 403 );
        }

        // Obter execuções recentes
        $recentExecutions = $report->executions()
            ->recent( 30 )
            ->orderBy( 'created_at', 'DESC' )
            ->limit( 10 )
            ->get();

        // Obter agendamentos
        $schedules = $report->schedules()
            ->orderBy( 'created_at', 'DESC' )
            ->get();

        // Dados para gráficos
        $chartData = $this->prepareChartData( $recentExecutions );

        return view( 'reports.show', compact(
            'report',
            'recentExecutions',
            'schedules',
            'chartData',
        ) );
    }

    /**
     * Mostra formulário de edição
     */
    public function edit( ReportDefinition $report ): View
    {
        // Verificar permissão
        if ( $report->tenant_id !== auth()->user()->tenant_id ) {
            abort( 403 );
        }

        return view( 'reports.edit', compact( 'report' ) );
    }

    /**
     * Atualiza definição de relatório
     */
    public function update( Request $request, ReportDefinition $report ): RedirectResponse
    {
        // Verificar permissão
        if ( $report->tenant_id !== auth()->user()->tenant_id ) {
            abort( 403 );
        }

        try {
            $validated = $request->validate( [
                'name'          => 'required|string|max:255',
                'description'   => 'nullable|string',
                'category'      => 'required|string|in:' . implode( ',', array_keys( ReportDefinition::CATEGORIES ) ),
                'type'          => 'required|string|in:' . implode( ',', array_keys( ReportDefinition::TYPES ) ),
                'query_builder' => 'required|array',
                'config'        => 'required|array',
                'filters'       => 'nullable|array',
                'visualization' => 'nullable|array',
                'is_active'     => 'boolean'
            ] );

            $report->update( [
                'name'          => $validated[ 'name' ],
                'description'   => $validated[ 'description' ] ?? null,
                'category'      => $validated[ 'category' ],
                'type'          => $validated[ 'type' ],
                'query_builder' => $validated[ 'query_builder' ],
                'config'        => $validated[ 'config' ],
                'filters'       => $validated[ 'filters' ] ?? [],
                'visualization' => $validated[ 'visualization' ] ?? [],
                'is_active'     => $validated[ 'is_active' ] ?? true,
                'updated_by'    => auth()->id()
            ] );

            Log::info( 'Definição de relatório atualizada', [
                'definition_id' => $report->id,
                'user_id'       => auth()->id()
            ] );

            return redirect()->route( 'reports.show', $report )
                ->with( 'success', 'Relatório atualizado com sucesso!' );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao atualizar definição de relatório', [
                'definition_id' => $report->id,
                'error'         => $e->getMessage()
            ] );

            return back()->withInput()
                ->with( 'error', 'Erro ao atualizar relatório: ' . $e->getMessage() );
        }
    }

    /**
     * Remove definição de relatório
     */
    public function destroy( ReportDefinition $report ): RedirectResponse
    {
        // Verificar permissão
        if ( $report->tenant_id !== auth()->user()->tenant_id ) {
            abort( 403 );
        }

        try {
            $reportId = $report->id;

            // Remover agendamentos relacionados
            $report->schedules()->delete();

            // Remover definição
            $report->delete();

            Log::info( 'Definição de relatório removida', [
                'definition_id' => $reportId,
                'user_id'       => auth()->id()
            ] );

            return redirect()->route( 'reports.index' )
                ->with( 'success', 'Relatório removido com sucesso!' );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao remover definição de relatório', [
                'definition_id' => $report->id,
                'error'         => $e->getMessage()
            ] );

            return back()->with( 'error', 'Erro ao remover relatório: ' . $e->getMessage() );
        }
    }

    /**
     * Gera relatório sob demanda
     */
    public function generate( Request $request, ReportDefinition $report ): RedirectResponse
    {
        // Verificar permissão
        if ( $report->tenant_id !== auth()->user()->tenant_id ) {
            abort( 403 );
        }

        try {
            $filters = $request->get( 'filters', [] );
            $format  = $request->get( 'format', 'screen' );

            // Gerar relatório
            $result = $this->reportGenerationService->generateReport( $report, $filters );

            if ( $format === 'screen' ) {
                return redirect()->route( 'reports.show', $report )
                    ->with( 'success', 'Relatório gerado com sucesso!' )
                    ->with( 'execution_id', $result[ 'execution_id' ] );
            } else {
                // Para outros formatos, redirecionar para download
                return redirect()->route( 'reports.download', $result[ 'execution_id' ] );
            }

        } catch ( Exception $e ) {
            Log::error( 'Erro ao gerar relatório', [
                'definition_id' => $report->id,
                'error'         => $e->getMessage()
            ] );

            return back()->with( 'error', 'Erro ao gerar relatório: ' . $e->getMessage() );
        }
    }

    /**
     * Mostra construtor visual de relatórios
     */
    public function builder(): View
    {
        $availableMetrics = $this->reportGenerationService->getAvailableMetrics();

        return view( 'reports.builder', compact( 'availableMetrics' ) );
    }

    /**
     * Preview de relatório
     */
    public function preview( Request $request ): JsonResponse
    {
        try {
            $config = $request->validate( [
                'name'          => 'required|string|max:255',
                'category'      => 'required|string',
                'type'          => 'required|string',
                'query_builder' => 'required|array',
                'filters'       => 'nullable|array'
            ] );

            // Gerar dados de preview
            $queryBuilder = app( AdvancedQueryBuilder::class);
            $queryBuilder->from( $config[ 'query_builder' ][ 'table' ] ?? 'budgets' );

            // Aplicar configuração básica
            if ( isset( $config[ 'query_builder' ][ 'selects' ] ) ) {
                foreach ( $config[ 'query_builder' ][ 'selects' ] as $select ) {
                    $queryBuilder->select( $select[ 'field' ], $select[ 'alias' ] ?? null );
                }
            }

            // Aplicar filtros
            if ( isset( $config[ 'filters' ] ) ) {
                foreach ( $config[ 'filters' ] as $filter ) {
                    if ( !empty( $filter[ 'value' ] ) ) {
                        $queryBuilder->where( $filter[ 'column' ], $filter[ 'operator' ], $filter[ 'value' ] );
                    }
                }
            }

            // Limitar para preview
            $queryBuilder->limit( 50 );

            $previewData = $queryBuilder->get();

            return response()->json( [
                'success'       => true,
                'data'          => $previewData,
                'columns'       => $this->extractColumns( $previewData ),
                'total_records' => $previewData->count()
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'error'   => $e->getMessage()
            ], 500 );
        }
    }

    /**
     * Gerenciamento de agendamentos
     */
    public function schedules( ReportDefinition $report ): View
    {
        // Verificar permissão
        if ( $report->tenant_id !== auth()->user()->tenant_id ) {
            abort( 403 );
        }

        $schedules = $report->schedules()
            ->orderBy( 'created_at', 'DESC' )
            ->get();

        return view( 'reports.schedules', compact( 'report', 'schedules' ) );
    }

    /**
     * Histórico de execuções
     */
    public function history( ReportDefinition $report ): View
    {
        // Verificar permissão
        if ( $report->tenant_id !== auth()->user()->tenant_id ) {
            abort( 403 );
        }

        $executions = $report->executions()
            ->orderBy( 'created_at', 'DESC' )
            ->paginate( 20 );

        return view( 'reports.history', compact( 'report', 'executions' ) );
    }

    /**
     * Download de relatório gerado
     */
    public function download( string $executionId ): \Symfony\Component\HttpFoundation\BinaryFileResponse|RedirectResponse
    {
        try {
            $execution = ReportExecution::where( 'execution_id', $executionId )
                ->where( 'tenant_id', auth()->user()->tenant_id )
                ->first();

            if ( !$execution ) {
                abort( 404, 'Relatório não encontrado' );
            }

            if ( !$execution->isCompleted() ) {
                return back()->with( 'error', 'Relatório ainda não foi concluído' );
            }

            $filePath = storage_path( "app/public/{$execution->file_path}" );

            if ( !file_exists( $filePath ) ) {
                abort( 404, 'Arquivo do relatório não encontrado' );
            }

            return response()->download( $filePath, basename( $execution->file_path ) );

        } catch ( Exception $e ) {
            Log::error( 'Erro no download de relatório', [
                'execution_id' => $executionId,
                'error'        => $e->getMessage()
            ] );

            return back()->with( 'error', 'Erro ao baixar relatório' );
        }
    }

    /**
     * Estatísticas do sistema de relatórios
     */
    public function stats(): JsonResponse
    {
        try {
            $user = auth()->user();

            $stats = [
                'definitions'       => ReportDefinition::where( 'tenant_id', $user->tenant_id )
                    ->where( 'user_id', $user->id )
                    ->count(),
                'executions_today'  => ReportExecution::where( 'tenant_id', $user->tenant_id )
                    ->where( 'user_id', $user->id )
                    ->whereDate( 'created_at', today() )
                    ->count(),
                'executions_month'  => ReportExecution::where( 'tenant_id', $user->tenant_id )
                    ->where( 'user_id', $user->id )
                    ->whereMonth( 'created_at', now()->month )
                    ->whereYear( 'created_at', now()->year )
                    ->count(),
                'active_schedules'  => ReportSchedule::where( 'tenant_id', $user->tenant_id )
                    ->where( 'user_id', $user->id )
                    ->active()
                    ->count(),
                'failed_executions' => ReportExecution::where( 'tenant_id', $user->tenant_id )
                    ->where( 'user_id', $user->id )
                    ->failed()
                    ->recent( 7 )
                    ->count()
            ];

            return response()->json( [
                'success' => true,
                'stats'   => $stats
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'error'   => $e->getMessage()
            ], 500 );
        }
    }

    /**
     * Prepara dados para gráficos
     */
    private function prepareChartData( $executions ): array
    {
        $chartData = [
            'execution_trend'     => [],
            'status_distribution' => [],
            'performance_metrics' => []
        ];

        // Tendência de execuções (últimos 7 dias)
        $last7Days = collect();
        for ( $i = 6; $i >= 0; $i-- ) {
            $date  = now()->subDays( $i )->toDateString();
            $count = $executions->where( 'created_at', 'like', $date . '%' )->count();

            $last7Days->push( [
                'date'  => $date,
                'count' => $count
            ] );
        }

        $chartData[ 'execution_trend' ] = $last7Days;

        // Distribuição por status
        $statusCounts                     = $executions->groupBy( 'status' )->map->count();
        $chartData[ 'status_distribution' ] = [
            'completed' => $statusCounts->get( 'completed', 0 ),
            'failed'    => $statusCounts->get( 'failed', 0 ),
            'running'   => $statusCounts->get( 'running', 0 )
        ];

        return $chartData;
    }

    /**
     * Extrai colunas dos dados
     */
    private function extractColumns( $data ): array
    {
        if ( $data->isEmpty() ) {
            return [];
        }

        $firstRow = $data->first();
        return is_array( $firstRow ) ? array_keys( $firstRow ) : array_keys( (array) $firstRow );
    }

}
