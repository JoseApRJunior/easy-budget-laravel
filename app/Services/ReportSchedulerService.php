<?php

namespace App\Services;

use App\Jobs\ProcessScheduledReport;
use App\Models\ReportDefinition;
use App\Models\ReportExecution;
use App\Models\ReportSchedule;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

/**
 * Serviço de agendamento de relatórios
 * Gerencia execução automática de relatórios com filas Laravel
 */
class ReportSchedulerService
{
    private ReportGenerationService $reportGenerationService;
    private ReportCacheService      $cacheService;
    private ExportService           $exportService;

    public function __construct(
        ReportGenerationService $reportGenerationService,
        ReportCacheService $cacheService,
        ExportService $exportService,
    ) {
        $this->reportGenerationService = $reportGenerationService;
        $this->cacheService            = $cacheService;
        $this->exportService           = $exportService;
    }

    /**
     * Processa todos os relatórios agendados que estão vencidos
     */
    public function processScheduledReports(): array
    {
        $results = [
            'processed' => 0,
            'failed'    => 0,
            'errors'    => []
        ];

        try {
            // Buscar todos os agendamentos ativos que estão vencidos
            $dueSchedules = ReportSchedule::active()
                ->due()
                ->with( [ 'definition', 'user' ] )
                ->get();

            foreach ( $dueSchedules as $schedule ) {
                try {
                    $this->processSchedule( $schedule );
                    $results[ 'processed' ]++;
                } catch ( Exception $e ) {
                    $results[ 'failed' ]++;
                    $results[ 'errors' ][] = [
                        'schedule_id' => $schedule->id,
                        'error'       => $e->getMessage()
                    ];

                    Log::error( 'Erro ao processar relatório agendado', [
                        'schedule_id' => $schedule->id,
                        'error'       => $e->getMessage(),
                        'trace'       => $e->getTraceAsString()
                    ] );
                }
            }

        } catch ( Exception $e ) {
            $results[ 'errors' ][] = [
                'error' => 'Erro geral no processamento: ' . $e->getMessage()
            ];

            Log::error( 'Erro geral no processamento de relatórios agendados', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ] );
        }

        return $results;
    }

    /**
     * Processa um agendamento específico
     */
    public function processSchedule( ReportSchedule $schedule ): void
    {
        // Verificar se já não foi processado hoje
        if ( $this->wasProcessedToday( $schedule ) ) {
            Log::info( 'Relatório já processado hoje', [ 'schedule_id' => $schedule->id ] );
            return;
        }

        // Despachar job para processamento em fila
        ProcessScheduledReport::dispatch( $schedule );

        Log::info( 'Relatório agendado enviado para processamento', [
            'schedule_id' => $schedule->id,
            'next_run'    => $schedule->next_run_at
        ] );
    }

    /**
     * Cria novo agendamento de relatório
     */
    public function createSchedule( array $data ): ReportSchedule
    {
        try {
            $schedule = ReportSchedule::create( [
                'tenant_id'       => auth()->user()->tenant_id,
                'definition_id'   => $data[ 'definition_id' ],
                'user_id'         => auth()->id(),
                'name'            => $data[ 'name' ],
                'description'     => $data[ 'description' ] ?? null,
                'is_active'       => $data[ 'is_active' ] ?? true,
                'frequency_type'  => $data[ 'frequency_type' ],
                'frequency_value' => $data[ 'frequency_value' ] ?? null,
                'day_of_week'     => $data[ 'day_of_week' ] ?? null,
                'day_of_month'    => $data[ 'day_of_month' ] ?? null,
                'time_to_run'     => $data[ 'time_to_run' ],
                'timezone'        => $data[ 'timezone' ] ?? config( 'app.timezone' ),
                'recipients'      => $data[ 'recipients' ],
                'email_subject'   => $data[ 'email_subject' ],
                'email_body'      => $data[ 'email_body' ] ?? null,
                'format'          => $data[ 'format' ],
                'parameters'      => $data[ 'parameters' ] ?? [],
                'filters'         => $data[ 'filters' ] ?? [],
                'created_by'      => auth()->id()
            ] );

            Log::info( 'Novo agendamento de relatório criado', [
                'schedule_id' => $schedule->id,
                'frequency'   => $schedule->frequency_type
            ] );

            return $schedule;

        } catch ( Exception $e ) {
            Log::error( 'Erro ao criar agendamento de relatório', [
                'error' => $e->getMessage(),
                'data'  => $data
            ] );
            throw $e;
        }
    }

    /**
     * Atualiza agendamento existente
     */
    public function updateSchedule( ReportSchedule $schedule, array $data ): ReportSchedule
    {
        try {
            $schedule->update( [
                'name'            => $data[ 'name' ] ?? $schedule->name,
                'description'     => $data[ 'description' ] ?? $schedule->description,
                'is_active'       => $data[ 'is_active' ] ?? $schedule->is_active,
                'frequency_type'  => $data[ 'frequency_type' ] ?? $schedule->frequency_type,
                'frequency_value' => $data[ 'frequency_value' ] ?? $schedule->frequency_value,
                'day_of_week'     => $data[ 'day_of_week' ] ?? $schedule->day_of_week,
                'day_of_month'    => $data[ 'day_of_month' ] ?? $schedule->day_of_month,
                'time_to_run'     => $data[ 'time_to_run' ] ?? $schedule->time_to_run,
                'timezone'        => $data[ 'timezone' ] ?? $schedule->timezone,
                'recipients'      => $data[ 'recipients' ] ?? $schedule->recipients,
                'email_subject'   => $data[ 'email_subject' ] ?? $schedule->email_subject,
                'email_body'      => $data[ 'email_body' ] ?? $schedule->email_body,
                'format'          => $data[ 'format' ] ?? $schedule->format,
                'parameters'      => $data[ 'parameters' ] ?? $schedule->parameters,
                'filters'         => $data[ 'filters' ] ?? $schedule->filters,
                'updated_by'      => auth()->id()
            ] );

            Log::info( 'Agendamento de relatório atualizado', [
                'schedule_id' => $schedule->id
            ] );

            return $schedule;

        } catch ( Exception $e ) {
            Log::error( 'Erro ao atualizar agendamento de relatório', [
                'schedule_id' => $schedule->id,
                'error'       => $e->getMessage()
            ] );
            throw $e;
        }
    }

    /**
     * Remove agendamento
     */
    public function deleteSchedule( ReportSchedule $schedule ): bool
    {
        try {
            $scheduleId = $schedule->id;
            $schedule->delete();

            Log::info( 'Agendamento de relatório removido', [
                'schedule_id' => $scheduleId
            ] );

            return true;

        } catch ( Exception $e ) {
            Log::error( 'Erro ao remover agendamento de relatório', [
                'schedule_id' => $schedule->id,
                'error'       => $e->getMessage()
            ] );
            throw $e;
        }
    }

    /**
     * Pausa agendamento
     */
    public function pauseSchedule( ReportSchedule $schedule ): ReportSchedule
    {
        $schedule->update( [
            'is_active'  => false,
            'updated_by' => auth()->id()
        ] );

        Log::info( 'Agendamento de relatório pausado', [
            'schedule_id' => $schedule->id
        ] );

        return $schedule;
    }

    /**
     * Retoma agendamento
     */
    public function resumeSchedule( ReportSchedule $schedule ): ReportSchedule
    {
        $schedule->update( [
            'is_active'  => true,
            'updated_by' => auth()->id()
        ] );

        Log::info( 'Agendamento de relatório retomado', [
            'schedule_id' => $schedule->id
        ] );

        return $schedule;
    }

    /**
     * Obtém próximos agendamentos
     */
    public function getUpcomingSchedules( int $limit = 10 ): \Illuminate\Database\Eloquent\Collection
    {
        return ReportSchedule::active()
            ->where( 'next_run_at', '>', now() )
            ->orderBy( 'next_run_at', 'ASC' )
            ->limit( $limit )
            ->get();
    }

    /**
     * Obtém histórico de execuções de um agendamento
     */
    public function getScheduleHistory( ReportSchedule $schedule, int $limit = 50 ): \Illuminate\Database\Eloquent\Collection
    {
        return ReportExecution::where( 'tenant_id', $schedule->tenant_id )
            ->whereHas( 'definition', function ( $query ) use ( $schedule ) {
                $query->where( 'id', $schedule->definition_id );
            } )
            ->orderBy( 'created_at', 'DESC' )
            ->limit( $limit )
            ->get();
    }

    /**
     * Verifica se agendamento já foi processado hoje
     */
    private function wasProcessedToday( ReportSchedule $schedule ): bool
    {
        return ReportExecution::where( 'tenant_id', $schedule->tenant_id )
            ->whereHas( 'definition', function ( $query ) use ( $schedule ) {
                $query->where( 'id', $schedule->definition_id );
            } )
            ->whereDate( 'created_at', today() )
            ->exists();
    }

    /**
     * Calcula estatísticas de agendamentos
     */
    public function getSchedulerStats(): array
    {
        $totalSchedules  = ReportSchedule::count();
        $activeSchedules = ReportSchedule::active()->count();
        $dueSchedules    = ReportSchedule::active()->due()->count();

        $todayExecutions = ReportExecution::whereDate( 'created_at', today() )->count();
        $weekExecutions  = ReportExecution::whereBetween( 'created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ] )->count();

        return [
            'schedules'  => [
                'total'    => $totalSchedules,
                'active'   => $activeSchedules,
                'inactive' => $totalSchedules - $activeSchedules,
                'due'      => $dueSchedules
            ],
            'executions' => [
                'today'     => $todayExecutions,
                'this_week' => $weekExecutions
            ],
            'queue'      => [
                'pending' => Queue::size( 'reports' ),
                'failed'  => Queue::size( 'failed_reports' )
            ]
        ];
    }

    /**
     * Executa relatório agendado manualmente
     */
    public function executeScheduleManually( ReportSchedule $schedule ): array
    {
        try {
            // Gerar relatório
            $reportData = $this->reportGenerationService->generateReport(
                $schedule->definition,
                $schedule->filters,
                $schedule->parameters,
            );

            // Exportar no formato especificado
            $exportResult = $this->exportService->export(
                collect( $reportData[ 'data' ] ),
                $schedule->format,
                [
                    'title'       => $schedule->name,
                    'orientation' => 'portrait',
                    'page_size'   => 'a4'
                ],
            );

            if ( !$exportResult[ 'success' ] ) {
                throw new Exception( 'Erro na exportação: ' . $exportResult[ 'error' ] );
            }

            // Registrar execução
            $execution = ReportExecution::create( [
                'tenant_id'       => $schedule->tenant_id,
                'definition_id'   => $schedule->definition_id,
                'user_id'         => auth()->id(),
                'execution_id'    => $reportData[ 'execution_id' ],
                'status'          => 'completed',
                'parameters'      => $schedule->parameters,
                'filters_applied' => $schedule->filters,
                'data_count'      => $reportData[ 'metadata' ][ 'total_records' ],
                'file_path'       => $exportResult[ 'path' ],
                'file_size'       => $exportResult[ 'size' ],
                'executed_at'     => now(),
                'completed_at'    => now()
            ] );

            // Atualizar último processamento do agendamento
            $schedule->markAsRun();

            // TODO: Implementar envio por email se necessário

            return [
                'success'      => true,
                'execution_id' => $execution->execution_id,
                'file_path'    => $exportResult[ 'path' ],
                'download_url' => $exportResult[ 'download_url' ]
            ];

        } catch ( Exception $e ) {
            Log::error( 'Erro na execução manual de relatório agendado', [
                'schedule_id' => $schedule->id,
                'error'       => $e->getMessage()
            ] );

            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Valida configuração de agendamento
     */
    public function validateScheduleConfig( array $data ): array
    {
        $errors = [];

        // Validar tipo de frequência
        if ( !isset( $data[ 'frequency_type' ] ) || !in_array( $data[ 'frequency_type' ], array_keys( ReportSchedule::FREQUENCY_TYPES ) ) ) {
            $errors[] = 'Tipo de frequência inválido';
        }

        // Validar horário
        if ( !isset( $data[ 'time_to_run' ] ) || !preg_match( '/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $data[ 'time_to_run' ] ) ) {
            $errors[] = 'Horário deve estar no formato HH:MM';
        }

        // Validar dia da semana para frequência semanal
        if ( ( $data[ 'frequency_type' ] ?? null ) === 'weekly' && empty( $data[ 'day_of_week' ] ) ) {
            $errors[] = 'Dia da semana é obrigatório para frequência semanal';
        }

        // Validar dia do mês para frequência mensal
        if ( ( $data[ 'frequency_type' ] ?? null ) === 'monthly' && empty( $data[ 'day_of_month' ] ) ) {
            $errors[] = 'Dia do mês é obrigatório para frequência mensal';
        }

        // Validar recipients
        if ( empty( $data[ 'recipients' ] ) ) {
            $errors[] = 'Pelo menos um destinatário deve ser informado';
        } else {
            foreach ( $data[ 'recipients' ] as $recipient ) {
                if ( !filter_var( $recipient, FILTER_VALIDATE_EMAIL ) ) {
                    $errors[] = "Email inválido: {$recipient}";
                }
            }
        }

        // Validar formato
        if ( !isset( $data[ 'format' ] ) || !in_array( $data[ 'format' ], array_keys( ReportSchedule::FORMATS ) ) ) {
            $errors[] = 'Formato inválido';
        }

        return $errors;
    }

    /**
     * Obtém agendamentos por definição
     */
    public function getSchedulesByDefinition( ReportDefinition $definition ): \Illuminate\Database\Eloquent\Collection
    {
        return $definition->schedules()->active()->get();
    }

    /**
     * Remove agendamentos antigos
     */
    public function cleanupOldSchedules( int $daysToKeep = 90 ): int
    {
        try {
            $cutoffDate = now()->subDays( $daysToKeep );

            $deletedCount = ReportSchedule::where( 'created_at', '<', $cutoffDate )
                ->delete();

            Log::info( "Removidos {$deletedCount} agendamentos antigos" );

            return $deletedCount;

        } catch ( Exception $e ) {
            Log::error( 'Erro ao limpar agendamentos antigos', [
                'error' => $e->getMessage()
            ] );
            return 0;
        }
    }

    /**
     * Testa agendamento sem executar
     */
    public function testSchedule( ReportSchedule $schedule ): array
    {
        try {
            // Simular geração de dados
            $testData = $this->generateTestData( $schedule->definition );

            // Testar exportação
            $exportResult = $this->exportService->export(
                collect( $testData ),
                $schedule->format,
                [
                    'title'       => $schedule->name . ' (TESTE)',
                    'orientation' => 'portrait',
                    'page_size'   => 'a4'
                ],
            );

            if ( !$exportResult[ 'success' ] ) {
                return [
                    'success' => false,
                    'error'   => 'Erro no teste de exportação: ' . $exportResult[ 'error' ]
                ];
            }

            // Limpar arquivo de teste
            if ( file_exists( $exportResult[ 'full_path' ] ) ) {
                unlink( $exportResult[ 'full_path' ] );
            }

            return [
                'success'         => true,
                'message'         => 'Teste realizado com sucesso',
                'test_data_count' => count( $testData ),
                'estimated_size'  => $exportResult[ 'size' ]
            ];

        } catch ( Exception $e ) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Gera dados de teste para validação
     */
    private function generateTestData( ReportDefinition $definition ): array
    {
        // Gerar dados fictícios baseados na configuração
        $testRecords = [];

        for ( $i = 1; $i <= 5; $i++ ) {
            $record = [
                'id'         => $i,
                'name'       => "Registro de Teste {$i}",
                'created_at' => now()->subDays( rand( 1, 30 ) )->toDateString(),
                'value'      => rand( 100, 10000 ),
                'status'     => [ 'ativo', 'inativo', 'pendente' ][ rand( 0, 2 ) ]
            ];

            $testRecords[] = $record;
        }

        return $testRecords;
    }

}
