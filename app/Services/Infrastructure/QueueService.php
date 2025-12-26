<?php

declare(strict_types=1);

namespace App\Services\Infrastructure;

use App\Enums\OperationStatus;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Serviço avançado para gerenciamento de filas de e-mail no sistema Easy Budget.
 *
 * Funcionalidades principais:
 * - Gerenciamento inteligente de filas de e-mail
 * - Sistema de priorização de e-mails críticos
 * - Monitoramento avançado de performance
 * - Limpeza automática de jobs antigos
 * - Sistema de retry inteligente com backoff exponencial
 * - Métricas detalhadas de processamento
 *
 * Este service complementa o MailerService existente, fornecendo
 * funcionalidades avançadas de gerenciamento de filas.
 */
class QueueService
{
    /**
     * Configurações padrão para diferentes tipos de e-mail.
     */
    private array $emailTypeConfigs = [
        'critical' => [
            'queue' => 'emails-critical',
            'timeout' => 120,
            'tries' => 5,
            'retry_after' => 30,
            'priority' => 1,
        ],
        'high' => [
            'queue' => 'emails-high',
            'timeout' => 90,
            'tries' => 4,
            'retry_after' => 60,
            'priority' => 2,
        ],
        'normal' => [
            'queue' => 'emails',
            'timeout' => 60,
            'tries' => 3,
            'retry_after' => 90,
            'priority' => 3,
        ],
        'low' => [
            'queue' => 'emails-low',
            'timeout' => 30,
            'tries' => 2,
            'retry_after' => 180,
            'priority' => 4,
        ],
    ];

    /**
     * Enfileira e-mail com configurações específicas baseadas no tipo.
     *
     * @param  string  $type  Tipo do e-mail (critical, high, normal, low)
     * @param  callable  $job  Closure que retorna o Mailable
     * @param  string|null  $recipient  E-mail do destinatário para logging
     * @return ServiceResult Resultado da operação
     */
    public function queueEmail(
        string $type,
        callable $job,
        ?string $recipient = null,
    ): ServiceResult {
        try {
            // Validar tipo de e-mail
            if (! isset($this->emailTypeConfigs[$type])) {
                return ServiceResult::error(
                    OperationStatus::INVALID_DATA,
                    "Tipo de e-mail inválido: {$type}. Use: critical, high, normal, low",
                );
            }

            $config = $this->emailTypeConfigs[$type];

            // Executar o job com configurações específicas
            $mailable = $job();

            // Adicionar configurações de fila ao mailable
            $mailable->onQueue($config['queue'])
                ->delay(now()->addSeconds($this->calculateDelay($type)));

            // Disparar job para fila específica
            dispatch($mailable)->onQueue($config['queue']);

            // Log detalhado
            Log::info('E-mail enfileirado com configurações específicas', [
                'type' => $type,
                'recipient' => $recipient,
                'queue' => $config['queue'],
                'timeout' => $config['timeout'],
                'tries' => $config['tries'],
                'priority' => $config['priority'],
            ]);

            return ServiceResult::success([
                'type' => $type,
                'queue' => $config['queue'],
                'recipient' => $recipient,
                'queued_at' => now()->toDateTimeString(),
            ], "E-mail enfileirado com sucesso na fila {$config['queue']}");

        } catch (Exception $e) {
            Log::error('Erro ao enfileirar e-mail', [
                'type' => $type,
                'recipient' => $recipient,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao enfileirar e-mail: '.$e->getMessage()
            );
        }
    }

    /**
     * Calcula delay baseado no tipo de e-mail para distribuição de carga.
     */
    private function calculateDelay(string $type): int
    {
        $baseDelays = [
            'critical' => 0,
            'high' => 5,
            'normal' => 10,
            'low' => 30,
        ];

        return $baseDelays[$type] ?? 10;
    }

    /**
     * Obtém estatísticas avançadas de todas as filas de e-mail.
     */
    public function getAdvancedQueueStats(): array
    {
        try {
            $jobsTable = config('queue.connections.database.table', 'jobs');
            $failedTable = config('queue.failed.table', 'failed_jobs');

            $stats = [];

            // Estatísticas por tipo de fila
            foreach (array_keys($this->emailTypeConfigs) as $type) {
                $queueName = $this->emailTypeConfigs[$type]['queue'];

                $queued = DB::table($jobsTable)
                    ->where('queue', $queueName)
                    ->whereNull('reserved_at')
                    ->count();

                $processing = DB::table($jobsTable)
                    ->where('queue', $queueName)
                    ->whereNotNull('reserved_at')
                    ->count();

                $failed = DB::table($failedTable)
                    ->where('queue', $queueName)
                    ->count();

                $recentJobs = DB::table($jobsTable)
                    ->where('queue', $queueName)
                    ->where('created_at', '>=', now()->subHour())
                    ->selectRaw('COUNT(*) as total, AVG(TIMESTAMPDIFF(SECOND, created_at, COALESCE(reserved_at, NOW()))) as avg_wait_time')
                    ->first();

                $stats[$type] = [
                    'queue_name' => $queueName,
                    'queued_emails' => $queued,
                    'processing_emails' => $processing,
                    'failed_emails' => $failed,
                    'total_jobs_hour' => $recentJobs->total ?? 0,
                    'avg_wait_time_sec' => round($recentJobs->avg_wait_time ?? 0, 2),
                    'status' => $this->getQueueStatus($queued, $processing, $failed),
                    'config' => $this->emailTypeConfigs[$type],
                ];
            }

            // Estatísticas gerais
            $totalStats = [
                'total_queued' => array_sum(array_column($stats, 'queued_emails')),
                'total_processing' => array_sum(array_column($stats, 'processing_emails')),
                'total_failed' => array_sum(array_column($stats, 'failed_emails')),
                'total_jobs_hour' => array_sum(array_column($stats, 'total_jobs_hour')),
                'overall_status' => $this->getOverallQueueStatus($stats),
                'timestamp' => now()->toDateTimeString(),
            ];

            Log::info('Estatísticas avançadas de filas obtidas', $stats);

            return [
                'queues' => $stats,
                'totals' => $totalStats,
                'performance' => $this->getPerformanceMetrics(),
            ];

        } catch (Exception $e) {
            Log::error('Erro ao obter estatísticas avançadas de filas', [
                'error' => $e->getMessage(),
            ]);

            return [
                'error' => 'Erro ao obter estatísticas: '.$e->getMessage(),
                'timestamp' => now()->toDateTimeString(),
            ];
        }
    }

    /**
     * Determina status de uma fila específica.
     */
    private function getQueueStatus(int $queued, int $processing, int $failed): string
    {
        if ($failed > 5) {
            return 'critical';
        }

        if ($failed > 2 || $queued > 50) {
            return 'warning';
        }

        if ($processing > 0 || $queued > 0) {
            return 'active';
        }

        return 'idle';
    }

    /**
     * Determina status geral de todas as filas.
     */
    private function getOverallQueueStatus(array $stats): string
    {
        $criticalQueues = collect($stats)->where('status', 'critical')->count();
        $warningQueues = collect($stats)->where('status', 'warning')->count();

        if ($criticalQueues > 0) {
            return 'critical';
        }

        if ($warningQueues > 0) {
            return 'warning';
        }

        if (collect($stats)->where('status', 'active')->count() > 0) {
            return 'active';
        }

        return 'idle';
    }

    /**
     * Obtém métricas de performance detalhadas.
     */
    private function getPerformanceMetrics(): array
    {
        try {
            $jobsTable = config('queue.connections.database.table', 'jobs');

            // Métricas da última hora
            $lastHour = DB::table($jobsTable)
                ->where('created_at', '>=', now()->subHour())
                ->selectRaw('
                    COUNT(*) as total_jobs,
                    AVG(TIMESTAMPDIFF(SECOND, created_at, COALESCE(reserved_at, NOW()))) as avg_wait_time,
                    AVG(TIMESTAMPDIFF(SECOND, reserved_at, NOW())) as avg_processing_time,
                    MAX(TIMESTAMPDIFF(SECOND, created_at, NOW())) as max_wait_time
                ')
                ->first();

            // Métricas das últimas 24 horas
            $last24Hours = DB::table($jobsTable)
                ->where('created_at', '>=', now()->subDay())
                ->selectRaw('COUNT(*) as total_jobs_24h')
                ->first();

            return [
                'last_hour' => [
                    'total_jobs' => $lastHour->total_jobs ?? 0,
                    'avg_wait_time_sec' => round($lastHour->avg_wait_time ?? 0, 2),
                    'avg_processing_sec' => round($lastHour->avg_processing_time ?? 0, 2),
                    'max_wait_time_sec' => round($lastHour->max_wait_time ?? 0, 2),
                ],
                'last_24_hours' => [
                    'total_jobs' => $last24Hours->total_jobs_24h ?? 0,
                ],
                'throughput' => [
                    'jobs_per_minute' => round(($lastHour->total_jobs ?? 0) / 60, 2),
                    'jobs_per_hour' => $lastHour->total_jobs ?? 0,
                    'jobs_per_day' => round(($last24Hours->total_jobs_24h ?? 0) / 24, 2),
                ],
            ];

        } catch (Exception $e) {
            return [
                'error' => 'Erro ao calcular métricas: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Limpa jobs antigos e falhados baseado em critérios.
     */
    public function cleanupOldJobs(int $daysOld = 7): ServiceResult
    {
        try {
            $jobsTable = config('queue.connections.database.table', 'jobs');
            $failedTable = config('queue.failed.table', 'failed_jobs');

            $cutoffDate = now()->subDays($daysOld);

            // Remover jobs antigos bem-sucedidos
            $deletedJobs = DB::table($jobsTable)
                ->where('created_at', '<', $cutoffDate)
                ->whereNotNull('reserved_at')
                ->delete();

            // Remover jobs falhados antigos
            $deletedFailed = DB::table($failedTable)
                ->where('failed_at', '<', $cutoffDate)
                ->delete();

            Log::info('Limpeza de jobs antigos executada', [
                'deleted_jobs' => $deletedJobs,
                'deleted_failed' => $deletedFailed,
                'days_old' => $daysOld,
                'cutoff_date' => $cutoffDate->toDateTimeString(),
            ]);

            return ServiceResult::success([
                'deleted_jobs' => $deletedJobs,
                'deleted_failed' => $deletedFailed,
                'days_old' => $daysOld,
            ], "Limpeza executada com sucesso. Removidos {$deletedJobs} jobs e {$deletedFailed} jobs falhados.");

        } catch (Exception $e) {
            Log::error('Erro na limpeza de jobs antigos', [
                'error' => $e->getMessage(),
                'days_old' => $daysOld,
            ]);

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro na limpeza: '.$e->getMessage()
            );
        }
    }

    /**
     * Retenta jobs falhados que podem ser processados novamente.
     */
    public function retryFailedJobs(?string $queue = null): ServiceResult
    {
        try {
            $failedTable = config('queue.failed.table', 'failed_jobs');
            $jobsTable = config('queue.connections.database.table', 'jobs');

            $query = DB::table($failedTable);

            if ($queue) {
                $query->where('queue', $queue);
            }

            $failedJobs = $query->get();

            $retried = 0;
            $errors = [];

            foreach ($failedJobs as $failedJob) {
                try {
                    // Verificar se é erro retryável
                    $exception = json_decode($failedJob->exception, true);
                    $errorMessage = $exception['message'] ?? '';

                    if (! $this->isRetryableError($errorMessage)) {
                        continue;
                    }

                    // Recriar job na fila
                    DB::table($jobsTable)->insert([
                        'queue' => $failedJob->queue,
                        'payload' => $failedJob->payload,
                        'attempts' => 0,
                        'reserved_at' => null,
                        'available_at' => now()->timestamp,
                        'created_at' => now(),
                    ]);

                    $retried++;

                } catch (Exception $e) {
                    $errors[] = "Erro ao retentar job {$failedJob->id}: ".$e->getMessage();
                }
            }

            // Remover jobs falhados que foram retryados
            if ($retried > 0) {
                $query = DB::table($failedTable);
                if ($queue) {
                    $query->where('queue', $queue);
                }
                $query->delete();
            }

            Log::info('Retry de jobs falhados executado', [
                'total_failed' => count($failedJobs),
                'retried' => $retried,
                'errors' => $errors,
            ]);

            return ServiceResult::success([
                'total_failed' => count($failedJobs),
                'retried' => $retried,
                'errors' => $errors,
            ], "{$retried} jobs falhados foram retryados com sucesso.");

        } catch (Exception $e) {
            Log::error('Erro no retry de jobs falhados', [
                'error' => $e->getMessage(),
            ]);

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro no retry: '.$e->getMessage()
            );
        }
    }

    /**
     * Verifica se erro é retryável baseado no tipo de erro.
     */
    private function isRetryableError(string $errorMessage): bool
    {
        $retryableErrors = [
            'Connection timeout',
            'SMTP connect failed',
            'Temporary failure',
            'Service unavailable',
            'Rate limit exceeded',
            'Network is unreachable',
            'Connection refused',
        ];

        $errorMessage = strtolower($errorMessage);

        foreach ($retryableErrors as $retryableError) {
            if (str_contains($errorMessage, strtolower($retryableError))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtém configurações atuais do serviço de filas.
     */
    public function getConfiguration(): array
    {
        return [
            'default_connection' => config('queue.default'),
            'email_type_configs' => $this->emailTypeConfigs,
            'failed_jobs_driver' => config('queue.failed.driver'),
            'retry_after' => config('queue.connections.database.retry_after'),
            'timeout' => config('queue.connections.database.timeout', 60),
        ];
    }

    /**
     * Monitora saúde das filas e retorna alertas se necessário.
     */
    public function getHealthStatus(): array
    {
        $stats = $this->getAdvancedQueueStats();

        if (isset($stats['error'])) {
            return [
                'status' => 'error',
                'message' => 'Erro ao obter status das filas',
                'details' => $stats['error'],
            ];
        }

        $alerts = [];
        $warnings = [];

        foreach ($stats['queues'] as $type => $queueStats) {
            // Alertas críticos
            if ($queueStats['failed_emails'] > 10) {
                $alerts[] = "Fila {$type} tem {$queueStats['failed_emails']} jobs falhados";
            }

            if ($queueStats['queued_emails'] > 100) {
                $alerts[] = "Fila {$type} tem {$queueStats['queued_emails']} jobs na fila";
            }

            // Avisos
            if ($queueStats['failed_emails'] > 3) {
                $warnings[] = "Fila {$type} tem {$queueStats['failed_emails']} jobs falhados";
            }

            if ($queueStats['avg_wait_time_sec'] > 300) {
                $warnings[] = "Fila {$type} tem tempo médio de espera de {$queueStats['avg_wait_time_sec']}s";
            }
        }

        $status = 'healthy';
        if (! empty($alerts)) {
            $status = 'critical';
        } elseif (! empty($warnings)) {
            $status = 'warning';
        }

        return [
            'status' => $status,
            'alerts' => $alerts,
            'warnings' => $warnings,
            'queues' => $stats['queues'],
            'timestamp' => now()->toDateTimeString(),
        ];
    }
}
