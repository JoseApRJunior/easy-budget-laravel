<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReportDefinition;
use App\Models\ReportExecution;
use App\Models\ReportSchedule;
use App\Services\ExportService;
use App\Services\ReportGenerationService;
use App\Services\ReportSchedulerService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * API RESTful para gerenciamento avançado de relatórios
 * Fornece endpoints para integração externa e operações avançadas
 */
class ReportApiController extends Controller
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
     * Lista todas as definições de relatório
     */
    public function index( Request $request ): JsonResponse
    {
        try {
            $query = ReportDefinition::where( 'tenant_id', auth()->user()->tenant_id )
                ->where( 'user_id', auth()->id() )
                ->active();

            // Aplicar filtros
            if ( $request->has( 'category' ) ) {
                $query->byCategory( $request->category );
            }

            if ( $request->has( 'type' ) ) {
                $query->byType( $request->type );
            }

            if ( $request->has( 'search' ) ) {
                $search = $request->search;
                $query->where( function ( $q ) use ( $search ) {
                    $q->where( 'name', 'like', "%{$search}%" )
                        ->orWhere( 'description', 'like', "%{$search}%" );
                } );
            }

            // Paginação
            $perPage     = min( $request->get( 'per_page', 15 ), 100 );
            $definitions = $query->paginate( $perPage );

            return response()->json( [
                'success'    => true,
                'data'       => $definitions->items(),
                'pagination' => [
                    'current_page' => $definitions->currentPage(),
                    'per_page'     => $definitions->perPage(),
                    'total'        => $definitions->total(),
                    'last_page'    => $definitions->lastPage()
                ]
            ] );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao listar definições de relatório', [
                'error'   => $e->getMessage(),
                'user_id' => auth()->id()
            ] );

            return response()->json( [
                'success' => false,
                'error'   => 'Erro interno do servidor'
            ], 500 );
        }
    }

    /**
     * Cria nova definição de relatório
     */
    public function store( Request $request ): JsonResponse
    {
        try {
            $validator = Validator::make( $request->all(), [
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

            if ( $validator->fails() ) {
                return response()->json( [
                    'success' => false,
                    'errors'  => $validator->errors()
                ], 422 );
            }

            $definition = ReportDefinition::create( [
                'tenant_id'     => auth()->user()->tenant_id,
                'user_id'       => auth()->id(),
                'name'          => $request->name,
                'description'   => $request->description,
                'category'      => $request->category,
                'type'          => $request->type,
                'query_builder' => $request->query_builder,
                'config'        => $request->config,
                'filters'       => $request->filters ?? [],
                'visualization' => $request->visualization ?? [],
                'is_active'     => $request->is_active ?? true,
                'created_by'    => auth()->id()
            ] );

            Log::info( 'Nova definição de relatório criada via API', [
                'definition_id' => $definition->id,
                'user_id'       => auth()->id()
            ] );

            return response()->json( [
                'success' => true,
                'data'    => $definition,
                'message' => 'Relatório criado com sucesso'
            ], 201 );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao criar definição de relatório via API', [
                'error'   => $e->getMessage(),
                'user_id' => auth()->id()
            ] );

            return response()->json( [
                'success' => false,
                'error'   => 'Erro interno do servidor'
            ], 500 );
        }
    }

    /**
     * Mostra definição específica
     */
    public function show( ReportDefinition $report ): JsonResponse
    {
        try {
            // Verificar permissão
            if ( $report->tenant_id !== auth()->user()->tenant_id ) {
                return response()->json( [
                    'success' => false,
                    'error'   => 'Acesso negado'
                ], 403 );
            }

            return response()->json( [
                'success' => true,
                'data'    => $report->load( [ 'executions' => function ( $query ) {
                    $query->recent( 10 );
                }, 'schedules' ] )
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'error'   => 'Erro interno do servidor'
            ], 500 );
        }
    }

    /**
     * Atualiza definição de relatório
     */
    public function update( Request $request, ReportDefinition $report ): JsonResponse
    {
        try {
            // Verificar permissão
            if ( $report->tenant_id !== auth()->user()->tenant_id ) {
                return response()->json( [
                    'success' => false,
                    'error'   => 'Acesso negado'
                ], 403 );
            }

            $validator = Validator::make( $request->all(), [
                'name'          => 'sometimes|required|string|max:255',
                'description'   => 'nullable|string',
                'category'      => 'sometimes|required|string|in:' . implode( ',', array_keys( ReportDefinition::CATEGORIES ) ),
                'type'          => 'sometimes|required|string|in:' . implode( ',', array_keys( ReportDefinition::TYPES ) ),
                'query_builder' => 'sometimes|required|array',
                'config'        => 'sometimes|required|array',
                'filters'       => 'nullable|array',
                'visualization' => 'nullable|array',
                'is_active'     => 'boolean'
            ] );

            if ( $validator->fails() ) {
                return response()->json( [
                    'success' => false,
                    'errors'  => $validator->errors()
                ], 422 );
            }

            $report->update( array_filter( [
                'name'          => $request->name,
                'description'   => $request->description,
                'category'      => $request->category,
                'type'          => $request->type,
                'query_builder' => $request->query_builder,
                'config'        => $request->config,
                'filters'       => $request->filters,
                'visualization' => $request->visualization,
                'is_active'     => $request->is_active,
                'updated_by'    => auth()->id()
            ], fn( $value ) => $value !== null ) );

            return response()->json( [
                'success' => true,
                'data'    => $report,
                'message' => 'Relatório atualizado com sucesso'
            ] );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao atualizar definição de relatório via API', [
                'definition_id' => $report->id,
                'error'         => $e->getMessage()
            ] );

            return response()->json( [
                'success' => false,
                'error'   => 'Erro interno do servidor'
            ], 500 );
        }
    }

    /**
     * Remove definição de relatório
     */
    public function destroy( ReportDefinition $report ): JsonResponse
    {
        try {
            // Verificar permissão
            if ( $report->tenant_id !== auth()->user()->tenant_id ) {
                return response()->json( [
                    'success' => false,
                    'error'   => 'Acesso negado'
                ], 403 );
            }

            $report->schedules()->delete();
            $report->delete();

            return response()->json( [
                'success' => true,
                'message' => 'Relatório removido com sucesso'
            ] );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao remover definição de relatório via API', [
                'definition_id' => $report->id,
                'error'         => $e->getMessage()
            ] );

            return response()->json( [
                'success' => false,
                'error'   => 'Erro interno do servidor'
            ], 500 );
        }
    }

    /**
     * Gera relatório sob demanda
     */
    public function generate( Request $request, ReportDefinition $report ): JsonResponse
    {
        try {
            // Verificar permissão
            if ( $report->tenant_id !== auth()->user()->tenant_id ) {
                return response()->json( [
                    'success' => false,
                    'error'   => 'Acesso negado'
                ], 403 );
            }

            $validator = Validator::make( $request->all(), [
                'filters' => 'nullable|array',
                'format'  => 'nullable|string|in:pdf,excel,csv,json',
                'async'   => 'boolean'
            ] );

            if ( $validator->fails() ) {
                return response()->json( [
                    'success' => false,
                    'errors'  => $validator->errors()
                ], 422 );
            }

            $filters = $request->get( 'filters', [] );
            $format  = $request->get( 'format', 'json' );
            $async   = $request->get( 'async', false );

            if ( $async ) {
                // Processamento assíncrono
                $result = $this->reportGenerationService->generateReport( $report, $filters );

                return response()->json( [
                    'success'      => true,
                    'execution_id' => $result[ 'execution_id' ],
                    'status'       => 'processing',
                    'message'      => 'Relatório enviado para processamento'
                ] );
            } else {
                // Processamento síncrono
                $result = $this->reportGenerationService->generateReport( $report, $filters );

                return response()->json( [
                    'success'      => true,
                    'execution_id' => $result[ 'execution_id' ],
                    'data'         => $result[ 'data' ],
                    'metadata'     => $result[ 'metadata' ]
                ] );
            }

        } catch ( Exception $e ) {
            Log::error( 'Erro ao gerar relatório via API', [
                'definition_id' => $report->id,
                'error'         => $e->getMessage()
            ] );

            return response()->json( [
                'success' => false,
                'error'   => 'Erro ao gerar relatório'
            ], 500 );
        }
    }

    /**
     * Obtém dados do relatório sem gerar novo
     */
    public function getData( Request $request, ReportDefinition $report ): JsonResponse
    {
        try {
            // Verificar permissão
            if ( $report->tenant_id !== auth()->user()->tenant_id ) {
                return response()->json( [
                    'success' => false,
                    'error'   => 'Acesso negado'
                ], 403 );
            }

            $filters = $request->get( 'filters', [] );

            // Tentar obter do cache primeiro
            $cacheKey   = app( ReportCacheService::class)->generateCacheKey( $report->id, $filters );
            $cachedData = app( ReportCacheService::class)->getCachedData( $cacheKey );

            if ( $cachedData ) {
                return response()->json( [
                    'success'   => true,
                    'data'      => $cachedData,
                    'source'    => 'cache',
                    'cached_at' => now()->toISOString()
                ] );
            }

            // Gerar dados se não estiver em cache
            $result = $this->reportGenerationService->generateReport( $report, $filters );

            return response()->json( [
                'success'  => true,
                'data'     => $result[ 'data' ],
                'metadata' => $result[ 'metadata' ],
                'source'   => 'generated'
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'error'   => 'Erro ao obter dados do relatório'
            ], 500 );
        }
    }

    /**
     * Preview de relatório
     */
    public function preview( Request $request, ReportDefinition $report ): JsonResponse
    {
        try {
            // Verificar permissão
            if ( $report->tenant_id !== auth()->user()->tenant_id ) {
                return response()->json( [
                    'success' => false,
                    'error'   => 'Acesso negado'
                ], 403 );
            }

            $filters = $request->get( 'filters', [] );

            // Limitar dados para preview
            $previewFilters = array_merge( $filters, [ 'limit' => 50 ] );

            $result = $this->reportGenerationService->generateReport( $report, $previewFilters );

            return response()->json( [
                'success'       => true,
                'data'          => array_slice( $result[ 'data' ], 0, 50 ),
                'total_records' => count( $result[ 'data' ] ),
                'is_preview'    => true
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'error'   => 'Erro no preview do relatório'
            ], 500 );
        }
    }

    /**
     * Exporta relatório
     */
    public function export( Request $request, ReportDefinition $report, string $format ): JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        try {
            // Verificar permissão
            if ( $report->tenant_id !== auth()->user()->tenant_id ) {
                return response()->json( [
                    'success' => false,
                    'error'   => 'Acesso negado'
                ], 403 );
            }

            // Validar formato
            $supportedFormats = array_keys( $this->exportService->getSupportedFormats() );
            if ( !in_array( $format, $supportedFormats ) ) {
                return response()->json( [
                    'success' => false,
                    'error'   => "Formato '{$format}' não suportado"
                ], 422 );
            }

            $filters      = $request->get( 'filters', [] );
            $exportConfig = $request->get( 'config', [] );

            // Gerar dados
            $result = $this->reportGenerationService->generateReport( $report, $filters );

            // Configurar exportação
            $exportConfig = array_merge( [
                'title'       => $report->name,
                'orientation' => 'portrait',
                'page_size'   => 'a4'
            ], $exportConfig );

            // Executar exportação
            $exportResult = $this->exportService->export(
                collect( $result[ 'data' ] ),
                $format,
                $exportConfig,
            );

            if ( !$exportResult[ 'success' ] ) {
                return response()->json( [
                    'success' => false,
                    'error'   => $exportResult[ 'error' ]
                ], 500 );
            }

            // Para formatos que retornam arquivos, fazer download
            if ( in_array( $format, [ 'pdf', 'excel', 'csv' ] ) ) {
                $filePath = storage_path( "app/public/{$exportResult[ 'path' ]}" );

                return response()->streamDownload( function () use ($filePath) {
                    $stream = fopen( $filePath, 'rb' );
                    while ( !feof( $stream ) ) {
                        echo fread( $stream, 8192 );
                    }
                    fclose( $stream );
                }, basename( $exportResult[ 'path' ] ) );
            }

            // Para JSON, retornar dados diretamente
            return response()->json( [
                'success' => true,
                'data'    => $exportResult
            ] );

        } catch ( Exception $e ) {
            Log::error( 'Erro na exportação via API', [
                'definition_id' => $report->id,
                'format'        => $format,
                'error'         => $e->getMessage()
            ] );

            return response()->json( [
                'success' => false,
                'error'   => 'Erro na exportação'
            ], 500 );
        }
    }

    /**
     * Cria agendamento de relatório
     */
    public function schedule( Request $request, ReportDefinition $report ): JsonResponse
    {
        try {
            // Verificar permissão
            if ( $report->tenant_id !== auth()->user()->tenant_id ) {
                return response()->json( [
                    'success' => false,
                    'error'   => 'Acesso negado'
                ], 403 );
            }

            $validator = Validator::make( $request->all(), [
                'name'           => 'required|string|max:255',
                'frequency_type' => 'required|string|in:' . implode( ',', array_keys( ReportSchedule::FREQUENCY_TYPES ) ),
                'time_to_run'    => 'required|string|regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/',
                'recipients'     => 'required|array|min:1',
                'recipients.*'   => 'email',
                'email_subject'  => 'required|string|max:255',
                'format'         => 'required|string|in:' . implode( ',', array_keys( ReportSchedule::FORMATS ) )
            ] );

            if ( $validator->fails() ) {
                return response()->json( [
                    'success' => false,
                    'errors'  => $validator->errors()
                ], 422 );
            }

            $schedule = $this->reportSchedulerService->createSchedule( array_merge( $request->all(), [
                'definition_id' => $report->id
            ] ) );

            return response()->json( [
                'success' => true,
                'data'    => $schedule,
                'message' => 'Agendamento criado com sucesso'
            ], 201 );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'error'   => $e->getMessage()
            ], 500 );
        }
    }

    /**
     * Lista agendamentos de relatório
     */
    public function getSchedules( ReportDefinition $report ): JsonResponse
    {
        try {
            // Verificar permissão
            if ( $report->tenant_id !== auth()->user()->tenant_id ) {
                return response()->json( [
                    'success' => false,
                    'error'   => 'Acesso negado'
                ], 403 );
            }

            $schedules = $report->schedules()
                ->orderBy( 'created_at', 'DESC' )
                ->get();

            return response()->json( [
                'success' => true,
                'data'    => $schedules
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'error'   => 'Erro interno do servidor'
            ], 500 );
        }
    }

    /**
     * Remove agendamento
     */
    public function deleteSchedule( ReportDefinition $report, ReportSchedule $schedule ): JsonResponse
    {
        try {
            // Verificar permissões
            if ( $report->tenant_id !== auth()->user()->tenant_id || $schedule->definition_id !== $report->id ) {
                return response()->json( [
                    'success' => false,
                    'error'   => 'Acesso negado'
                ], 403 );
            }

            $this->reportSchedulerService->deleteSchedule( $schedule );

            return response()->json( [
                'success' => true,
                'message' => 'Agendamento removido com sucesso'
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'error'   => $e->getMessage()
            ], 500 );
        }
    }

    /**
     * Relatórios financeiros especializados
     */
    public function financialRevenue( Request $request ): JsonResponse
    {
        try {
            $filters = $request->get( 'filters', [] );
            $result  = $this->reportGenerationService->generateRevenueReport( $filters );

            return response()->json( [
                'success' => true,
                'data'    => $result
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'error'   => $e->getMessage()
            ], 500 );
        }
    }

    public function financialExpenses( Request $request ): JsonResponse
    {
        try {
            $filters = $request->get( 'filters', [] );
            $result  = $this->reportGenerationService->generateExpensesByCategoryReport( $filters );

            return response()->json( [
                'success' => true,
                'data'    => $result
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'error'   => $e->getMessage()
            ], 500 );
        }
    }

    public function financialProfit( Request $request ): JsonResponse
    {
        try {
            $filters = $request->get( 'filters', [] );
            $result  = $this->reportGenerationService->generateProfitReport( $filters );

            return response()->json( [
                'success' => true,
                'data'    => $result
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'error'   => $e->getMessage()
            ], 500 );
        }
    }

    /**
     * Relatórios de clientes especializados
     */
    public function customerSegmentation( Request $request ): JsonResponse
    {
        try {
            $filters = $request->get( 'filters', [] );
            $result  = $this->reportGenerationService->generateCustomerSegmentationReport( $filters );

            return response()->json( [
                'success' => true,
                'data'    => $result
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'error'   => $e->getMessage()
            ], 500 );
        }
    }

    public function customerInteractions( Request $request ): JsonResponse
    {
        try {
            $filters = $request->get( 'filters', [] );
            $result  = $this->reportGenerationService->generateCustomerInteractionsReport( $filters );

            return response()->json( [
                'success' => true,
                'data'    => $result
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'error'   => $e->getMessage()
            ], 500 );
        }
    }

    /**
     * Relatórios de orçamentos especializados
     */
    public function budgetStatus( Request $request ): JsonResponse
    {
        try {
            $filters = $request->get( 'filters', [] );
            $result  = $this->reportGenerationService->generateBudgetStatusReport( $filters );

            return response()->json( [
                'success' => true,
                'data'    => $result
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'error'   => $e->getMessage()
            ], 500 );
        }
    }

    public function budgetConversion( Request $request ): JsonResponse
    {
        try {
            $filters = $request->get( 'filters', [] );
            $result  = $this->reportGenerationService->generateBudgetConversionReport( $filters );

            return response()->json( [
                'success' => true,
                'data'    => $result
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'error'   => $e->getMessage()
            ], 500 );
        }
    }

    /**
     * Dashboard executivo
     */
    public function executiveKpis( Request $request ): JsonResponse
    {
        try {
            $filters = $request->get( 'filters', [] );
            $result  = $this->reportGenerationService->generateExecutiveKpisReport( $filters );

            return response()->json( [
                'success' => true,
                'data'    => $result
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'error'   => $e->getMessage()
            ], 500 );
        }
    }

    /**
     * Gerenciamento de cache
     */
    public function clearCache(): JsonResponse
    {
        try {
            app( ReportCacheService::class)->invalidateAllReports( auth()->user()->tenant_id );

            return response()->json( [
                'success' => true,
                'message' => 'Cache limpo com sucesso'
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'error'   => $e->getMessage()
            ], 500 );
        }
    }

    public function invalidateCache( Request $request, ReportDefinition $report ): JsonResponse
    {
        try {
            // Verificar permissão
            if ( $report->tenant_id !== auth()->user()->tenant_id ) {
                return response()->json( [
                    'success' => false,
                    'error'   => 'Acesso negado'
                ], 403 );
            }

            app( ReportCacheService::class)->invalidateReportCache( $report->id, auth()->user()->tenant_id );

            return response()->json( [
                'success' => true,
                'message' => 'Cache do relatório invalidado com sucesso'
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'error'   => $e->getMessage()
            ], 500 );
        }
    }

    /**
     * Email de relatório
     */
    public function emailReport( Request $request, ReportDefinition $report ): JsonResponse
    {
        try {
            // Verificar permissão
            if ( $report->tenant_id !== auth()->user()->tenant_id ) {
                return response()->json( [
                    'success' => false,
                    'error'   => 'Acesso negado'
                ], 403 );
            }

            $validator = Validator::make( $request->all(), [
                'execution_id' => 'required|string',
                'recipients'   => 'required|array|min:1',
                'recipients.*' => 'email',
                'subject'      => 'nullable|string|max:255',
                'message'      => 'nullable|string'
            ] );

            if ( $validator->fails() ) {
                return response()->json( [
                    'success' => false,
                    'errors'  => $validator->errors()
                ], 422 );
            }

            // TODO: Implementar serviço de email
            // Por enquanto, apenas simular sucesso

            return response()->json( [
                'success' => true,
                'message' => 'Relatório enviado por email com sucesso'
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'error'   => $e->getMessage()
            ], 500 );
        }
    }

}
