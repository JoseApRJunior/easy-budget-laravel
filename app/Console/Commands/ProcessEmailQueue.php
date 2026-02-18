<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Facades\Log;

/**
 * Comando personalizado para processar fila de emails.
 *
 * Este comando é específico para processamento de emails com configurações
 * otimizadas para este tipo de job (timeout, retry, logging detalhado).
 */
class ProcessEmailQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:work
                            {--max-jobs=1000 : Número máximo de jobs a processar}
                            {--timeout=60 : Timeout em segundos para cada job}
                            {--sleep=3 : Tempo de espera entre jobs em segundos}
                            {--tries=3 : Número máximo de tentativas para cada job}
                            {--force : Força o worker a continuar mesmo com problemas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process the email queue with optimized settings for email jobs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Iniciando worker de fila de emails...');
        $this->info('📧 Configurações:');
        $this->line('   - Máximo de jobs: '.$this->option('max-jobs'));
        $this->line('   - Timeout: '.$this->option('timeout').'s');
        $this->line('   - Sleep: '.$this->option('sleep').'s');
        $this->line('   - Tentativas: '.$this->option('tries'));

        Log::info('Email queue worker iniciado', [
            'max_jobs' => $this->option('max-jobs'),
            'timeout' => $this->option('timeout'),
            'sleep' => $this->option('sleep'),
            'tries' => $this->option('tries'),
            'pid' => getmypid(),
        ]);

        try {
            $worker = app('queue.worker');
            $worker->setCache(app('cache.store'));

            $options = new WorkerOptions;
            $options->maxTries = (int) $this->option('tries');
            $options->timeout = (int) $this->option('timeout');
            $options->sleep = (int) $this->option('sleep');
            $options->rest = 0;
            $options->maxJobs = (int) $this->option('max-jobs');
            $options->force = $this->option('force');
            $options->stopWhenEmpty = false;
            $options->memory = 128; // MB

            // Configura listeners para logging
            $this->laravel['events']->listen(\Illuminate\Queue\Events\JobProcessing::class, function ($event) {
                $this->logJobProcessing($event->job);
            });

            $this->laravel['events']->listen(\Illuminate\Queue\Events\JobProcessed::class, function ($event) {
                $this->info('✅ Email enviado com sucesso - Job ID: '.$event->job->getJobId());
                Log::info('Email job processado com sucesso', [
                    'job_id' => $event->job->getJobId(),
                    'queue' => $event->job->getQueue(),
                    'attempts' => $event->job->attempts(),
                ]);
            });

            $this->laravel['events']->listen(\Illuminate\Queue\Events\JobExceptionOccurred::class, function ($event) {
                $this->handleJobFailure($event->job, $event->exception);
                
                Log::error('Falha no processamento de email job', [
                    'job_id' => $event->job->getJobId(),
                    'queue' => $event->job->getQueue(),
                    'attempts' => $event->job->attempts(),
                    'error' => $event->exception->getMessage(),
                    'trace' => $event->exception->getTraceAsString(),
                ]);

                $this->error('❌ Falha no email - Job ID: '.$event->job->getJobId().' - '.$event->exception->getMessage());
            });

            // Processa apenas a fila de emails
            $worker->daemon('database', 'emails', $options);

        } catch (\Throwable $e) {
            Log::critical('Worker de email parado devido a erro crítico', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'pid' => getmypid(),
            ]);

            $this->error('💥 Erro crítico no worker: '.$e->getMessage());

            return 1;
        }

        $this->info('🏁 Worker de fila de emails finalizado.');
        Log::info('Email queue worker finalizado', ['pid' => getmypid()]);

        return 0;
    }

    /**
     * Log detalhado do início do processamento de um job.
     */
    private function logJobProcessing($job): void
    {
        $this->line('🔄 Processando job: '.$job->getJobId().' (tentativa '.$job->attempts().')');

        Log::info('Iniciando processamento de email job', [
            'job_id' => $job->getJobId(),
            'queue' => $job->getQueue(),
            'attempts' => $job->attempts(),
            'max_tries' => $job->maxTries(),
            'payload_size' => strlen($job->getRawBody()),
        ]);
    }

    /**
     * Trata falhas no processamento de jobs.
     */
    private function handleJobFailure($job, \Throwable $e): void
    {
        $this->error('Job falhou: '.$job->getJobId());

        // Se ainda há tentativas restantes, loga tentativa de retry
        if ($job->attempts() < $job->maxTries()) {
            $remaining = $job->maxTries() - $job->attempts();

            Log::warning('Email job será retryado', [
                'job_id' => $job->getJobId(),
                'remaining_attempts' => $remaining,
                'next_retry_in' => $job->getRetryUntil()?->diffInSeconds(now()).'s',
            ]);

            $this->warn('🔄 Tentativa de retry em alguns segundos... ('.$remaining.' restantes)');
        } else {
            Log::error('Email job esgotou todas as tentativas', [
                'job_id' => $job->getJobId(),
                'total_attempts' => $job->attempts(),
                'error_summary' => substr($e->getMessage(), 0, 255),
            ]);

            $this->error('💀 Job descartado após múltiplas falhas');
        }
    }
}
