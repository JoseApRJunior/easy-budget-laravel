<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Services\Infrastructure\QueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Controller para gerenciamento e monitoramento de filas de e-mail.
 *
 * Funcionalidades:
 * - Dashboard de monitoramento de filas
 * - Estatísticas avançadas de performance
 * - Limpeza de jobs antigos
 * - Retry de jobs falhados
 * - Controle de saúde das filas
 */
class QueueManagementController extends Controller
{
    private QueueService $queueService;

    public function __construct(QueueService $queueService)
    {
        $this->queueService = $queueService;
    }

    /**
     * Dashboard principal de monitoramento de filas.
     */
    public function index(): View
    {
        $stats = $this->queueService->getAdvancedQueueStats();
        $health = $this->queueService->getHealthStatus();
        $config = $this->queueService->getConfiguration();

        return view('queues.index', [
            'stats' => $stats,
            'health' => $health,
            'config' => $config,
        ]);
    }

    /**
     * API para obter estatísticas das filas.
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->queueService->getAdvancedQueueStats();

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao obter estatísticas das filas', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao obter estatísticas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Status de saúde das filas.
     */
    public function health(): JsonResponse
    {
        try {
            $health = $this->queueService->getHealthStatus();

            $statusCode = match ($health['status']) {
                'critical' => 503,
                'warning' => 200,
                'healthy' => 200,
                default => 200,
            };

            return response()->json([
                'success' => true,
                'data' => $health,
            ], $statusCode);

        } catch (\Exception $e) {
            Log::error('Erro ao obter status de saúde das filas', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao obter status de saúde',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Limpeza de jobs antigos.
     */
    public function cleanup(Request $request): JsonResponse
    {
        try {
            $daysOld = $request->get('days', 7);
            $result = $this->queueService->cleanupOldJobs((int) $daysOld);

            if ($result->isSuccess()) {
                return response()->json([
                    'success' => true,
                    'message' => $result->getMessage(),
                    'data' => $result->getData(),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result->getMessage(),
            ], 400);

        } catch (\Exception $e) {
            Log::error('Erro na limpeza de jobs', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro na limpeza de jobs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retry de jobs falhados.
     */
    public function retry(Request $request): JsonResponse
    {
        try {
            $queue = $request->get('queue');
            $result = $this->queueService->retryFailedJobs($queue);

            if ($result->isSuccess()) {
                return response()->json([
                    'success' => true,
                    'message' => $result->getMessage(),
                    'data' => $result->getData(),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result->getMessage(),
            ], 400);

        } catch (\Exception $e) {
            Log::error('Erro no retry de jobs', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro no retry de jobs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Executa worker de filas.
     */
    public function work(Request $request): JsonResponse
    {
        try {
            $queue = $request->get('queue', 'emails');
            $timeout = $request->get('timeout', 60);
            $maxJobs = $request->get('max_jobs', 1000);

            // Em produção, isso seria executado em background
            // Por ora, apenas loga a intenção
            Log::info('Worker de filas solicitado', [
                'queue' => $queue,
                'timeout' => $timeout,
                'max_jobs' => $maxJobs,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Worker iniciado (simulado)',
                'data' => [
                    'queue' => $queue,
                    'timeout' => $timeout,
                    'max_jobs' => $maxJobs,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao iniciar worker', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao iniciar worker',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Para todos os workers.
     */
    public function stop(): JsonResponse
    {
        try {
            // Em produção, isso seria implementado com sinais ou supervisor
            Log::info('Parada de workers solicitada');

            return response()->json([
                'success' => true,
                'message' => 'Workers parados (simulado)',
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao parar workers', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao parar workers',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Testa envio de e-mail via fila.
     */
    public function testEmail(Request $request): JsonResponse
    {
        try {
            $email = $request->get('email', 'test@example.com');

            // Usar o SendEmailJob diretamente
            $emailData = [
                'to' => $email,
                'subject' => 'Teste de Fila - Easy Budget',
                'body' => '<h1>Teste de E-mail via Fila</h1><p>Este é um e-mail de teste do sistema de filas.</p>',
            ];

            dispatch(new \App\Jobs\SendEmailJob($emailData));

            return response()->json([
                'success' => true,
                'message' => 'E-mail de teste enfileirado com sucesso',
                'data' => [
                    'recipient' => $email,
                    'queued_at' => now()->toDateTimeString(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Erro no teste de e-mail', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro no teste de e-mail',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
