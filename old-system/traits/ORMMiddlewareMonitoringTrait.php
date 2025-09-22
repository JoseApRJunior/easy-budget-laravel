<?php

declare(strict_types=1);

namespace core\traits;

use core\services\MiddlewareMonitoringService;
use Exception;
use http\Redirect;

/**
 * Trait para monitoramento automático de middlewares ORM.
 *
 * Este trait fornece funcionalidades de monitoramento específicas para middlewares
 * que estendem AbstractORMMiddleware, integrando-se com o sistema de monitoramento
 * existente e coletando métricas de performance, execução e erros.
 *
 * Funcionalidades:
 * - Coleta automática de métricas de tempo de execução
 * - Monitoramento de uso de memória
 * - Registro de sucessos e falhas
 * - Logging detalhado de erros
 * - Integração com MiddlewareMonitoringService
 */
trait ORMMiddlewareMonitoringTrait
{
    private ?MiddlewareMonitoringService $monitoringService    = null;
    private ?float                       $executionStartTime   = null;
    private ?int                         $executionStartMemory = null;

    /**
     * Verifica se o monitoramento está habilitado.
     *
     * @return bool True se o monitoramento estiver ativo
     */
    private function isMonitoringEnabled(): bool
    {
        return $this->getMonitoringService() !== null;
    }

    /**
     * Obtém a instância do serviço de monitoramento.
     *
     * @return MiddlewareMonitoringService|null
     */
    private function getMonitoringService(): ?MiddlewareMonitoringService
    {
        if ( $this->monitoringService === null ) {
            try {
                // Cria uma nova instância do serviço
                $this->monitoringService = new MiddlewareMonitoringService();
            } catch ( Exception $e ) {
                // Se não conseguir obter o serviço, desabilita o monitoramento
                error_log( "[ORMMiddlewareMonitoring] Falha ao obter MiddlewareMonitoringService: " . $e->getMessage() );
                return null;
            }
        }

        return $this->monitoringService;
    }

    /**
     * Inicia o monitoramento da execução do middleware.
     *
     * @param string $middlewareName Nome do middleware sendo executado
     */
    private function startMonitoring( string $middlewareName ): void
    {
        if ( !$this->isMonitoringEnabled() ) {
            return;
        }

        $this->executionStartTime   = microtime( true );
        $this->executionStartMemory = memory_get_usage( true );

        $this->getMonitoringService()->startExecution( $middlewareName );
    }

    /**
     * Finaliza o monitoramento da execução do middleware.
     *
     * @param string $middlewareName Nome do middleware executado
     * @param bool $success Se a execução foi bem-sucedida
     * @param string|null $errorMessage Mensagem de erro, se houver
     */
    private function endMonitoring( string $middlewareName, bool $success = true, ?string $errorMessage = null ): void
    {
        if ( !$this->isMonitoringEnabled() || $this->executionStartTime === null ) {
            return;
        }

        $executionTime = microtime( true ) - $this->executionStartTime;
        $memoryUsage   = memory_get_usage( true ) - ( $this->executionStartMemory ?? 0 );

        $metrics = [ 
            'middleware'     => $middlewareName,
            'execution_time' => $executionTime,
            'memory_usage'   => $memoryUsage,
            'success'        => $success,
            'error_message'  => $errorMessage,
            'timestamp'      => time(),
            'peak_memory'    => memory_get_peak_usage( true )
        ];

        $this->getMonitoringService()->recordExecution( $metrics );

        // Reset das variáveis de monitoramento
        $this->executionStartTime   = null;
        $this->executionStartMemory = null;
    }

    /**
     * Registra um erro específico do middleware.
     *
     * @param string $middlewareName Nome do middleware
     * @param Exception $exception Exceção capturada
     */
    private function recordError( string $middlewareName, Exception $exception ): void
    {
        if ( !$this->isMonitoringEnabled() ) {
            return;
        }

        $this->getMonitoringService()->recordError( [ 
            'middleware'    => $middlewareName,
            'error_type'    => get_class( $exception ),
            'error_message' => $exception->getMessage(),
            'error_file'    => $exception->getFile(),
            'error_line'    => $exception->getLine(),
            'timestamp'     => date( 'Y-m-d H:i:s' ),
            'trace'         => $exception->getTraceAsString(),
            'user_id'       => $_SESSION[ 'user_id' ] ?? 'anonymous',
            'session_token' => substr( $_SESSION[ 'session_token' ] ?? 'no-token', 0, 8 ),
            'request_uri'   => $_SERVER[ 'REQUEST_URI' ] ?? 'unknown',
            'user_agent'    => $_SERVER[ 'HTTP_USER_AGENT' ] ?? 'unknown',
            'ip_address'    => $_SERVER[ 'REMOTE_ADDR' ] ?? 'unknown'
        ] );
    }

    /**
     * Executa um callback com monitoramento automático.
     *
     * Este método encapsula a execução de uma função com monitoramento
     * automático de início, fim e tratamento de erros.
     *
     * @param string $middlewareName Nome do middleware
     * @param callable $callback Função a ser executada
     * @return mixed Resultado da execução do callback
     * @throws Exception Re-lança exceções após registrá-las
     */
    protected function executeWithORMMonitoring( string $middlewareName, callable $callback )
    {
        $this->startMonitoring( $middlewareName );

        try {
            $result = $callback();
            $this->endMonitoring( $middlewareName, true );
            return $result;
        } catch ( Exception $e ) {
            $this->recordError( $middlewareName, $e );
            $this->endMonitoring( $middlewareName, false, $e->getMessage() );
            throw $e;
        }
    }

    /**
     * Sobrescreve o método execute do AbstractORMMiddleware para incluir monitoramento.
     *
     * Este método deve ser chamado pelos middlewares ORM que utilizam este trait.
     *
     * @return Redirect|null
     */
    public function executeWithMonitoring(): Redirect|null
    {
        $middlewareName = $this->getSessionKey();

        return $this->executeWithORMMonitoring( $middlewareName, function () {
            // Chama o método execute original do AbstractORMMiddleware
            return $this->executeOriginal();
        } );
    }

    /**
     * Método que deve ser implementado pelos middlewares para chamar
     * o execute original do AbstractORMMiddleware.
     *
     * @return Redirect|null
     */
    abstract protected function executeOriginal(): Redirect|null;
}