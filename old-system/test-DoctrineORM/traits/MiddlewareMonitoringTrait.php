<?php

declare(strict_types=1);

namespace core\traits;

use core\services\MiddlewareMonitoringService;
use Exception;

/**
 * Trait para Monitoramento de Middlewares
 *
 * Fornece funcionalidades de coleta automática de métricas
 * para middlewares do sistema Easy Budget.
 */
trait MiddlewareMonitoringTrait
{
    private ?MiddlewareMonitoringService $monitoringService = null;
    private float $startTime = 0.0;
    private int $startMemory = 0;

    /**
     * Inicializa o monitoramento da execução do middleware
     *
     * @return void
     */
    protected function startMonitoring(): void
    {
        if (!$this->isMonitoringEnabled()) {
            return;
        }

        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage();
        
        $this->getMonitoringService()->startExecution(static::class);
    }

    /**
     * Finaliza o monitoramento e registra as métricas
     *
     * @param bool $success Indica se a execução foi bem-sucedida
     * @param string|null $errorMessage Mensagem de erro, se houver
     * @return void
     */
    protected function endMonitoring(bool $success = true, ?string $errorMessage = null): void
    {
        if (!$this->isMonitoringEnabled()) {
            return;
        }

        $executionTime = (microtime(true) - $this->startTime) * 1000; // em milissegundos
        $memoryUsage = memory_get_usage() - $this->startMemory;

        $metrics = [
            'middleware' => static::class,
            'execution_time' => $executionTime,
            'memory_usage' => $memoryUsage,
            'success' => $success,
            'error_message' => $errorMessage,
            'timestamp' => time(),
            'peak_memory' => memory_get_peak_usage()
        ];

        $this->getMonitoringService()->recordExecution($metrics);
    }

    /**
     * Registra um erro durante a execução do middleware
     *
     * @param Exception $exception Exceção capturada
     * @return void
     */
    protected function recordError(Exception $exception): void
    {
        if (!$this->isMonitoringEnabled()) {
            return;
        }

        $this->getMonitoringService()->recordError([
            'middleware' => static::class,
            'error_type' => get_class($exception),
            'error_message' => $exception->getMessage(),
            'error_file' => $exception->getFile(),
            'error_line' => $exception->getLine(),
            'timestamp' => time()
        ]);
    }

    /**
     * Verifica se o monitoramento está habilitado
     *
     * @return bool
     */
    private function isMonitoringEnabled(): bool
    {
        $config = require BASE_PATH . '/config/security_monitoring.php';
        return $config['middleware']['log_execution'] ?? false;
    }

    /**
     * Obtém a instância do serviço de monitoramento
     *
     * @return MiddlewareMonitoringService
     */
    private function getMonitoringService(): MiddlewareMonitoringService
    {
        if ($this->monitoringService === null) {
            $this->monitoringService = new MiddlewareMonitoringService();
        }

        return $this->monitoringService;
    }

    /**
     * Executa uma função com monitoramento automático
     *
     * @param callable $callback Função a ser executada
     * @return mixed Resultado da execução
     */
    protected function executeWithMonitoring(callable $callback): mixed
    {
        $this->startMonitoring();
        
        try {
            $result = $callback();
            $this->endMonitoring(true);
            return $result;
        } catch (Exception $exception) {
            $this->recordError($exception);
            $this->endMonitoring(false, $exception->getMessage());
            throw $exception;
        }
    }
}