<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Infrastructure\MailerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Comando para gerenciamento avançado da fila de emails.
 *
 * Permite visualizar estatísticas, limpar filas, reenfileirar jobs falhos,
 * e outras operações administrativas específicas para emails.
 */
class EmailQueueManage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:manage
                            {action : Ação a executar (stats, clear, retry, flush, monitor)}
                            {--queue=emails : Nome da fila específica}
                            {--hours=24 : Horas para análise (usado em stats)}
                            {--force : Força execução sem confirmação}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage email queue with advanced operations (stats, clear, retry, flush, monitor)';

    /**
     * Mailer service instance.
     */
    private MailerService $mailerService;

    /**
     * Create a new command instance.
     */
    public function __construct( MailerService $mailerService )
    {
        parent::__construct();
        $this->mailerService = $mailerService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument( 'action' );
        $queue  = $this->option( 'queue' );

        $this->info( "🚀 Gerenciando fila de emails: {$queue}" );
        $this->info( "📋 Ação solicitada: {$action}" );

        try {
            switch ( $action ) {
                case 'stats':
                    return $this->showQueueStats( $queue );

                case 'clear':
                    return $this->clearQueue( $queue );

                case 'retry':
                    return $this->retryFailedJobs( $queue );

                case 'flush':
                    return $this->flushQueue( $queue );

                case 'monitor':
                    return $this->monitorQueue( $queue );

                default:
                    $this->error( "❌ Ação '{$action}' não reconhecida." );
                    $this->showAvailableActions();
                    return 1;
            }
        } catch ( \Throwable $e ) {
            Log::error( 'Erro no gerenciamento da fila de emails', [
                'action' => $action,
                'queue'  => $queue,
                'error'  => $e->getMessage(),
                'trace'  => $e->getTraceAsString()
            ] );

            $this->error( '💥 Erro: ' . $e->getMessage() );
            return 1;
        }
    }

    /**
     * Exibe estatísticas detalhadas da fila.
     */
    private function showQueueStats( string $queue ): int
    {
        $this->info( '📊 Obtendo estatísticas da fila...' );

        $stats = $this->mailerService->getEmailQueueStats();

        if ( isset( $stats[ 'error' ] ) ) {
            $this->error( '❌ ' . $stats[ 'error' ] );
            return 1;
        }

        $this->info( '📈 Estatísticas da fila de emails:' );
        $this->table(
            [ 'Métrica', 'Valor' ],
            [
                [ 'Emails enfileirados', $stats[ 'queued_emails' ] ],
                [ 'Emails processando', $stats[ 'processing_emails' ] ],
                [ 'Emails com falha', $stats[ 'failed_emails' ] ],
                [ 'Jobs última hora', $stats[ 'total_jobs_last_hour' ] ],
                [ 'Tempo médio de espera', $stats[ 'avg_wait_time_seconds' ] . 's' ],
                [ 'Status da fila', $this->getStatusBadge( $stats[ 'queue_status' ] ) ],
                [ 'Última atualização', $stats[ 'timestamp' ] ],
            ],
        );

        // Análise adicional
        $this->analyzeQueueHealth( $stats );

        return 0;
    }

    /**
     * Limpa fila de emails (remove jobs pendentes).
     */
    private function clearQueue( string $queue ): int
    {
        if ( !$this->option( 'force' ) && !$this->confirm( 'Tem certeza que deseja limpar a fila de emails?', false ) ) {
            $this->info( 'Operação cancelada.' );
            return 0;
        }

        $this->info( '🧹 Limpando fila de emails...' );

        $jobsTable = config( 'queue.connections.database.table', 'jobs' );
        $deleted   = DB::table( $jobsTable )
            ->where( 'queue', $queue )
            ->whereNull( 'reserved_at' )
            ->delete();

        $this->info( "✅ {$deleted} jobs removidos da fila '{$queue}'." );

        Log::warning( 'Fila de emails limpa manualmente', [
            'queue'        => $queue,
            'jobs_removed' => $deleted,
            'user'         => auth()->id() ?? 'system'
        ] );

        return 0;
    }

    /**
     * Tenta reenfileirar jobs falhos.
     */
    private function retryFailedJobs( string $queue ): int
    {
        $this->info( '🔄 Tentando reenfileirar jobs falhos...' );

        $failedTable = config( 'queue.failed.table', 'failed_jobs' );
        $failedJobs  = DB::table( $failedTable )
            ->where( 'queue', $queue )
            ->get();

        if ( $failedJobs->isEmpty() ) {
            $this->info( '✅ Nenhum job falho encontrado na fila.' );
            return 0;
        }

        $this->info( "📋 Encontrados {$failedJobs->count()} jobs falhos." );

        if ( !$this->option( 'force' ) && !$this->confirm( 'Deseja tentar reenfileirar esses jobs?', false ) ) {
            $this->info( 'Operação cancelada.' );
            return 0;
        }

        $retried = 0;
        foreach ( $failedJobs as $job ) {
            try {
                $this->retrySingleJob( $job );
                $retried++;
            } catch ( \Throwable $e ) {
                $this->error( "❌ Falha ao reenfileirar job {$job->id}: " . $e->getMessage() );
            }
        }

        $this->info( "✅ {$retried} jobs reenfileirados com sucesso." );
        return 0;
    }

    /**
     * Esvazia completamente a fila (remove todos os jobs).
     */
    private function flushQueue( string $queue ): int
    {
        $this->warn( '⚠️  Esta operação irá remover TODOS os jobs da fila!' );

        if ( !$this->option( 'force' ) && !$this->confirm( 'Tem certeza absoluta? Esta ação não pode ser desfeita!', false ) ) {
            $this->info( 'Operação cancelada.' );
            return 0;
        }

        $this->info( '💥 Esvaziando fila de emails...' );

        $jobsTable = config( 'queue.connections.database.table', 'jobs' );
        $deleted   = DB::table( $jobsTable )
            ->where( 'queue', $queue )
            ->delete();

        $this->info( "✅ {$deleted} jobs removidos completamente da fila '{$queue}'." );

        Log::critical( 'Fila de emails esvaziada completamente', [
            'queue'        => $queue,
            'jobs_removed' => $deleted,
            'user'         => auth()->id() ?? 'system'
        ] );

        return 0;
    }

    /**
     * Monitora a fila em tempo real.
     */
    private function monitorQueue( string $queue ): int
    {
        $this->info( '👁️  Iniciando monitoramento da fila de emails...' );
        $this->info( 'Pressione Ctrl+C para parar.' );

        $iterations = 0;
        while ( true ) {
            $stats = $this->mailerService->getEmailQueueStats();

            $this->showLiveStats( $stats, ++$iterations );

            sleep( 5 ); // Atualiza a cada 5 segundos
        }

        return 0;
    }

    /**
     * Exibe estatísticas em tempo real.
     */
    private function showLiveStats( array $stats, int $iteration ): void
    {
        $this->newLine();
        $this->line( "🔄 Monitoramento - Iteração #{$iteration} - " . now()->format( 'H:i:s' ) );

        if ( isset( $stats[ 'error' ] ) ) {
            $this->error( '❌ Erro: ' . $stats[ 'error' ] );
            return;
        }

        $this->line( "📊 Enfileirados: {$stats[ 'queued_emails' ]} | Processando: {$stats[ 'processing_emails' ]} | Falhos: {$stats[ 'failed_emails' ]}" );
        $this->line( "⏱️  Tempo médio de espera: {$stats[ 'avg_wait_time_seconds' ]}s" );
        $this->line( "📈 Status: " . $this->getStatusBadge( $stats[ 'queue_status' ] ) );
    }

    /**
     * Tenta reenfileirar um job individual.
     */
    private function retrySingleJob( object $job ): void
    {
        $jobsTable = config( 'queue.connections.database.table', 'jobs' );

        DB::table( $jobsTable )->insert( [
            'queue'        => $job->queue,
            'payload'      => $job->payload,
            'attempts'     => 0,
            'reserved_at'  => null,
            'available_at' => now()->timestamp,
            'created_at'   => now()->timestamp,
        ] );

        // Remove da tabela de falhas
        $failedTable = config( 'queue.failed.table', 'failed_jobs' );
        DB::table( $failedTable )->where( 'id', $job->id )->delete();

        Log::info( 'Job de email reenfileirado', [
            'failed_job_id' => $job->id,
            'queue'         => $job->queue
        ] );
    }

    /**
     * Analisa a saúde da fila e dá recomendações.
     */
    private function analyzeQueueHealth( array $stats ): void
    {
        $this->newLine();
        $this->info( '🔍 Análise de saúde da fila:' );

        if ( $stats[ 'failed_emails' ] > 10 ) {
            $this->warn( '⚠️  Muitos jobs falhos detectados! Considere verificar logs de erro.' );
        }

        if ( $stats[ 'queued_emails' ] > 50 ) {
            $this->warn( '⚠️  Muitos emails enfileirados. Considere iniciar mais workers.' );
        }

        if ( $stats[ 'avg_wait_time_seconds' ] > 30 ) {
            $this->warn( '⚠️  Tempo de espera alto. Workers podem estar sobrecarregados.' );
        }

        if ( $stats[ 'queue_status' ] === 'idle' && $stats[ 'queued_emails' ] === 0 ) {
            $this->info( '✅ Fila saudável e sem pendências.' );
        }
    }

    /**
     * Retorna badge colorido para status.
     */
    private function getStatusBadge( string $status ): string
    {
        return match ( $status ) {
            'active'   => '<fg=green>● ATIVO</>',
            'idle'     => '<fg=blue>● OCIOSO</>',
            'warning'  => '<fg=yellow>● ATENÇÃO</>',
            'critical' => '<fg=red>● CRÍTICO</>',
            default    => '<fg=gray>● DESCONHECIDO</>',
        };
    }

    /**
     * Exibe ações disponíveis.
     */
    private function showAvailableActions(): void
    {
        $this->info( 'Ações disponíveis:' );
        $this->line( '  <fg=green>stats</>   - Exibir estatísticas da fila' );
        $this->line( '  <fg=yellow>clear</>   - Limpar jobs pendentes' );
        $this->line( '  <fg=blue>retry</>   - Reenfileirar jobs falhos' );
        $this->line( '  <fg=red>flush</>   - Esvaziar fila completamente' );
        $this->line( '  <fg=cyan>monitor</> - Monitorar fila em tempo real' );
    }

}
