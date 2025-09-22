<?php

declare(strict_types=1);

namespace core\services;

use core\support\Logger;

/**
 * Serviço de Monitoramento de Middlewares
 *
 * Responsável por monitorar a execução, performance e segurança dos middlewares
 * do sistema Easy Budget, fornecendo métricas detalhadas e alertas.
 */
class MiddlewareMonitoringService
{
    private array                     $config;
    private Logger                    $logger;

    private array                     $executionMetrics = [];

    private SecurityMonitoringService $securityService;

    public function __construct()
    {
        $this->config = require BASE_PATH . '/config/security_monitoring.php';
        $this->logger = new Logger();
        $this->securityService = new SecurityMonitoringService();
        $this->initializeMetrics();
    }

    /**
     * Inicializa as métricas de monitoramento
     *
     * @return void
     */
    private function initializeMetrics(): void
    {
        $this->executionMetrics = [ 
            'total_executions'       => 0,
            'successful_executions'  => 0,
            'failed_executions'      => 0,
            'average_execution_time' => 0.0,
            'peak_memory_usage'      => 0,
            'middlewares'            => []
        ];
    }

    /**
     * Inicia o monitoramento de execução de um middleware
     *
     * @param string $middlewareName Nome do middleware
     * @return void
     */
    public function startExecution(string $middlewareName): void
    {
        if (!isset($this->executionMetrics['middlewares'][$middlewareName])) {
            $this->executionMetrics['middlewares'][$middlewareName] = [
                'executions' => 0,
                'successes' => 0,
                'failures' => 0,
                'total_time' => 0.0,
                'avg_time' => 0.0,
                'max_memory' => 0
            ];
        }
    }

    /**
     * Registra a execução de um middleware
     *
     * @param array $metrics Métricas da execução
     * @return void
     */
    public function recordExecution(array $metrics): void
    {
        $middlewareName = $metrics['middleware'];
        $executionTime = $metrics['execution_time'];
        $memoryUsage = $metrics['memory_usage'];
        $success = $metrics['success'];
        $errorMessage = $metrics['error_message'] ?? null;

        // Atualiza métricas globais
        $this->executionMetrics['total_executions']++;
        
        if ($success) {
            $this->executionMetrics['successful_executions']++;
        } else {
            $this->executionMetrics['failed_executions']++;
        }

        // Atualiza métricas do middleware específico
        if (!isset($this->executionMetrics['middlewares'][$middlewareName])) {
            $this->startExecution($middlewareName);
        }

        $middlewareMetrics = &$this->executionMetrics['middlewares'][$middlewareName];
        $middlewareMetrics['executions']++;
        $middlewareMetrics['total_time'] += $executionTime;
        $middlewareMetrics['avg_time'] = $middlewareMetrics['total_time'] / $middlewareMetrics['executions'];
        $middlewareMetrics['max_memory'] = max($middlewareMetrics['max_memory'], $memoryUsage);

        if ($success) {
            $middlewareMetrics['successes']++;
        } else {
            $middlewareMetrics['failures']++;
        }

        // Atualiza tempo médio global
        $this->executionMetrics['average_execution_time'] = 
            ($this->executionMetrics['average_execution_time'] * ($this->executionMetrics['total_executions'] - 1) + $executionTime) 
            / $this->executionMetrics['total_executions'];

        // Atualiza pico de memória
        $this->executionMetrics['peak_memory_usage'] = max(
            $this->executionMetrics['peak_memory_usage'], 
            $metrics['peak_memory'] ?? 0
        );

        // Log da execução
        $logData = [
            'middleware' => $middlewareName,
            'execution_time_ms' => $executionTime,
            'memory_usage_bytes' => $memoryUsage,
            'success' => $success,
            'timestamp' => $metrics['timestamp']
        ];

        if ($errorMessage) {
            $logData['error'] = $errorMessage;
        }

        if ($success) {
            $this->logger->info("Middleware '{$middlewareName}' executado com sucesso", $logData);
        } else {
            $this->logger->error("Middleware '{$middlewareName}' falhou na execução", $logData);
        }
    }

    /**
     * Registra um erro durante a execução do middleware
     *
     * @param array $errorData Dados do erro
     * @return void
     */
    public function recordError(array $errorData): void
    {
        $this->logger->error('Erro no middleware: ' . $errorData['middleware'], [
            'middleware' => $errorData['middleware'],
            'error_type' => $errorData['error_type'],
            'error_message' => $errorData['error_message'],
            'error_file' => $errorData['error_file'],
            'error_line' => $errorData['error_line'],
            'timestamp' => $errorData['timestamp']
        ]);
    }

    /**
     * Monitora a execução de um middleware
     *
     * @param string $middlewareName Nome do middleware
     * @param callable $execution Função de execução do middleware
     * @param array $context Contexto adicional
     * @return mixed Resultado da execução
     * @throws \Exception
     */
    public function monitorExecution( string $middlewareName, callable $execution, array $context = [] )
    {
        $startTime    = microtime( true );
        $startMemory  = memory_get_usage( true );
        $success      = false;
        $result       = null;
        $errorMessage = null;

        try {
            // Executa o middleware
            $result  = $execution();
            $success = true;

            // Log de sucesso
            $this->logger->info( "Middleware '{$middlewareName}' executado com sucesso", [ 
                'middleware' => $middlewareName,
                'context'    => $context
            ] );

        } catch ( \Exception $e ) {
            $errorMessage = $e->getMessage();
            $success      = false;

            // Log de erro
            $this->logger->error( "Erro na execução do middleware '{$middlewareName}': {$errorMessage}", [ 
                'middleware'  => $middlewareName,
                'error'       => $errorMessage,
                'context'     => $context,
                'stack_trace' => $e->getTraceAsString()
            ] );

            throw $e;
        } finally {
            // Calcula métricas de performance
            $executionTime = microtime( true ) - $startTime;
            $memoryUsed    = memory_get_usage( true ) - $startMemory;
            $peakMemory    = memory_get_peak_usage( true );

            // Registra métricas
            $this->recordMetrics( $middlewareName, $executionTime, $memoryUsed, $peakMemory, $success, $errorMessage );

            // Log no sistema de segurança
            $this->securityService->logMiddlewareExecution(
                $middlewareName,
                $executionTime,
                $success,
                array_merge( $context, [ 
                    'memory_used'   => $memoryUsed,
                    'peak_memory'   => $peakMemory,
                    'error_message' => $errorMessage
                ] ),
            );
        }

        return $result;
    }

    /**
     * Registra métricas de execução
     *
     * @param string $middlewareName Nome do middleware
     * @param float $executionTime Tempo de execução
     * @param int $memoryUsed Memória utilizada
     * @param int $peakMemory Pico de memória
     * @param bool $success Se a execução foi bem-sucedida
     * @param string|null $errorMessage Mensagem de erro (se houver)
     * @return void
     */
    private function recordMetrics(
        string $middlewareName,
        float $executionTime,
        int $memoryUsed,
        int $peakMemory,
        bool $success,
        ?string $errorMessage = null,
    ): void {
        // Atualiza métricas globais
        $this->executionMetrics[ 'total_executions' ]++;

        if ( $success ) {
            $this->executionMetrics[ 'successful_executions' ]++;
        } else {
            $this->executionMetrics[ 'failed_executions' ]++;
        }

        // Atualiza tempo médio de execução
        $totalTime                                        = $this->executionMetrics[ 'average_execution_time' ] * ( $this->executionMetrics[ 'total_executions' ] - 1 );
        $this->executionMetrics[ 'average_execution_time' ] = ( $totalTime + $executionTime ) / $this->executionMetrics[ 'total_executions' ];

        // Atualiza pico de memória
        if ( $peakMemory > $this->executionMetrics[ 'peak_memory_usage' ] ) {
            $this->executionMetrics[ 'peak_memory_usage' ] = $peakMemory;
        }

        // Inicializa métricas do middleware se não existir
        if ( !isset( $this->executionMetrics[ 'middlewares' ][ $middlewareName ] ) ) {
            $this->executionMetrics[ 'middlewares' ][ $middlewareName ] = [ 
                'executions'     => 0,
                'successes'      => 0,
                'failures'       => 0,
                'total_time'     => 0.0,
                'average_time'   => 0.0,
                'min_time'       => PHP_FLOAT_MAX,
                'max_time'       => 0.0,
                'total_memory'   => 0,
                'average_memory' => 0,
                'peak_memory'    => 0,
                'last_execution' => null,
                'last_error'     => null
            ];
        }

        $middlewareMetrics = &$this->executionMetrics[ 'middlewares' ][ $middlewareName ];

        // Atualiza métricas específicas do middleware
        $middlewareMetrics[ 'executions' ]++;
        $middlewareMetrics[ 'total_time' ] += $executionTime;
        $middlewareMetrics[ 'average_time' ]   = $middlewareMetrics[ 'total_time' ] / $middlewareMetrics[ 'executions' ];
        $middlewareMetrics[ 'total_memory' ] += $memoryUsed;
        $middlewareMetrics[ 'average_memory' ] = $middlewareMetrics[ 'total_memory' ] / $middlewareMetrics[ 'executions' ];
        $middlewareMetrics[ 'last_execution' ] = date( 'Y-m-d H:i:s' );

        if ( $success ) {
            $middlewareMetrics[ 'successes' ]++;
        } else {
            $middlewareMetrics[ 'failures' ]++;
            $middlewareMetrics[ 'last_error' ] = $errorMessage;
        }

        // Atualiza tempos mínimo e máximo
        if ( $executionTime < $middlewareMetrics[ 'min_time' ] ) {
            $middlewareMetrics[ 'min_time' ] = $executionTime;
        }
        if ( $executionTime > $middlewareMetrics[ 'max_time' ] ) {
            $middlewareMetrics[ 'max_time' ] = $executionTime;
        }

        // Atualiza pico de memória do middleware
        if ( $peakMemory > $middlewareMetrics[ 'peak_memory' ] ) {
            $middlewareMetrics[ 'peak_memory' ] = $peakMemory;
        }

        // Verifica alertas de performance
        $this->checkPerformanceAlerts( $middlewareName, $executionTime, $memoryUsed, !$success );
    }

    /**
     * Verifica alertas de performance
     *
     * @param string $middlewareName Nome do middleware
     * @param float $executionTime Tempo de execução
     * @param int $memoryUsed Memória utilizada
     * @param bool $hasError Se houve erro na execução
     * @return void
     */
    private function checkPerformanceAlerts( string $middlewareName, float $executionTime, int $memoryUsed, bool $hasError = false ): void
    {
        // Alerta de tempo de execução lento
        if ( $executionTime > $this->config[ 'middleware' ][ 'performance_threshold' ] ) {
            $this->logger->warning( "Middleware '{$middlewareName}' executando lentamente", [ 
                'middleware'     => $middlewareName,
                'execution_time' => $executionTime,
                'threshold'      => $this->config[ 'middleware' ][ 'performance_threshold' ]
            ] );
        }

        // Alerta de uso excessivo de memória
        if ( $memoryUsed > $this->config[ 'performance' ][ 'memory_threshold' ] ) {
            $this->logger->warning( "Middleware '{$middlewareName}' usando muita memória", [ 
                'middleware'  => $middlewareName,
                'memory_used' => $memoryUsed,
                'threshold'   => $this->config[ 'performance' ][ 'memory_threshold' ]
            ] );
        }

        // Alertas automáticos desabilitados - método não existe no AlertService
    }

    /**
     * Retorna métricas de execução
     *
     * @return array Métricas de execução
     */
    public function getExecutionMetrics(): array
    {
        return $this->executionMetrics;
    }

    /**
     * Retorna métricas de um middleware específico
     *
     * @param string $middlewareName Nome do middleware
     * @return array|null Métricas do middleware ou null se não encontrado
     */
    public function getMiddlewareMetrics( string $middlewareName ): ?array
    {
        return $this->executionMetrics[ 'middlewares' ][ $middlewareName ] ?? null;
    }

    /**
     * Gera relatório de performance
     *
     * @return array Relatório de performance
     */
    public function generatePerformanceReport(): array
    {
        $report = [ 
            'timestamp'   => date( 'Y-m-d H:i:s' ),
            'summary'     => [ 
                'total_executions'       => $this->executionMetrics[ 'total_executions' ],
                'success_rate'           => $this->calculateSuccessRate(),
                'average_execution_time' => round( $this->executionMetrics[ 'average_execution_time' ], 4 ),
                'peak_memory_usage'      => $this->formatBytes( $this->executionMetrics[ 'peak_memory_usage' ] )
            ],
            'middlewares' => []
        ];

        foreach ( $this->executionMetrics[ 'middlewares' ] as $name => $metrics ) {
            $report[ 'middlewares' ][ $name ] = [ 
                'executions'     => $metrics[ 'executions' ],
                'success_rate'   => $metrics[ 'executions' ] > 0 ? round( ( $metrics[ 'successes' ] / $metrics[ 'executions' ] ) * 100, 2 ) : 0,
                'average_time'   => round( $metrics[ 'average_time' ] ?? 0, 4 ),
                'min_time'       => round( $metrics[ 'min_time' ] ?? 0, 4 ),
                'max_time'       => round( $metrics[ 'max_time' ] ?? 0, 4 ),
                'average_memory' => $this->formatBytes( $metrics[ 'average_memory' ] ?? 0 ),
                'peak_memory'    => $this->formatBytes( $metrics[ 'peak_memory' ] ?? 0 ),
                'last_execution' => $metrics[ 'last_execution' ] ?? null,
                'last_error'     => $metrics[ 'last_error' ] ?? null
            ];
        }

        return $report;
    }

    /**
     * Calcula taxa de sucesso
     *
     * @return float Taxa de sucesso em porcentagem
     */
    private function calculateSuccessRate(): float
    {
        if ( $this->executionMetrics[ 'total_executions' ] === 0 ) {
            return 0.0;
        }

        return round( ( $this->executionMetrics[ 'successful_executions' ] / $this->executionMetrics[ 'total_executions' ] ) * 100, 2 );
    }

    /**
     * Formata bytes em formato legível
     *
     * @param int $bytes Bytes
     * @return string Formato legível
     */
    private function formatBytes( int $bytes ): string
    {
        $units  = [ 'B', 'KB', 'MB', 'GB' ];
        $factor = floor( ( strlen( (string) $bytes ) - 1 ) / 3 );

        return sprintf( "%.2f %s", $bytes / pow( 1024, $factor ), $units[ $factor ] );
    }

    /**
     * Reseta métricas de execução
     *
     * @return void
     */
    public function resetMetrics(): void
    {
        $this->initializeMetrics();
        $this->logger->info( 'Métricas de monitoramento de middlewares resetadas' );
    }

    /**
     * Salva relatório de performance em arquivo
     *
     * @param string $filePath Caminho do arquivo
     * @return bool Sucesso da operação
     */
    public function savePerformanceReport( string $filePath ): bool
    {
        try {
            $report     = $this->generatePerformanceReport();
            $jsonReport = json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );

            $result = file_put_contents( $filePath, $jsonReport );

            if ( $result !== false ) {
                $this->logger->info( "Relatório de performance salvo em: {$filePath}" );
                return true;
            }

            return false;
        } catch ( \Exception $e ) {
            $this->logger->error( "Erro ao salvar relatório de performance: {$e->getMessage()}" );
            return false;
        }
    }

}
