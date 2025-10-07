<?php

declare(strict_types=1);

namespace core\middlewares;

use app\database\servicesORM\AuthenticationService;
use app\database\servicesORM\SessionService;
use core\services\AlertService;
use Doctrine\ORM\EntityManagerInterface;
use http\Redirect;

/**
 * Middleware para monitoramento automático de alertas
 *
 * Coleta métricas em tempo real e dispara alertas
 * quando thresholds são ultrapassados
 */
class AlertMiddleware extends AbstractORMMiddleware
{
    private AlertService $alertService;
    private float        $startTime;
    private int          $startMemory;

    public function __construct()
    {
        $this->alertService = new AlertService();
    }

    /**
     * Executa o middleware de alertas
     */
    public function execute(): \http\Redirect|null
    {
        // AlertMiddleware não bloqueia execução, apenas monitora
        return null;
    }

    /**
     * Executa antes da requisição
     */
    public function handle( Request $request, callable $next )
    {
        $this->startTime   = microtime( true );
        $this->startMemory = memory_get_usage( true );

        // Executar próximo middleware
        $response = $next( $request );

        // Coletar métricas após execução
        $this->collectMetrics( $request, $response );

        return $response;
    }

    /**
     * Coleta métricas da requisição
     */
    private function collectMetrics( Request $request, $response ): void
    {
        $executionTime = ( microtime( true ) - $this->startTime ) * 1000; // ms
        $memoryUsage   = memory_get_usage( true ) - $this->startMemory;
        $statusCode    = $this->getStatusCode( $response );

        // Verificar thresholds críticos
        $this->checkCriticalThresholds( $executionTime, $memoryUsage, $statusCode );

        // Salvar métricas no banco
        $this->saveMetrics( [ 
            'middleware_name' => 'AlertMiddleware',
            'route'           => $request->getUri(),
            'method'          => $request->getMethod(),
            'response_time'   => $executionTime,
            'memory_usage'    => $memoryUsage,
            'status_code'     => $statusCode,
            'created_at'      => date( 'Y-m-d H:i:s' )
        ] );
    }

    /**
     * Executa verificação de alertas
     */
    protected function performCheck(): ?Redirect
    {
        // Middleware de monitoramento não bloqueia requisições
        $this->checkCriticalThresholds();
        return null;
    }

    /**
     * Retorna chave da sessão
     */
    protected function getSessionKey(): string
    {
        return 'alert_monitoring';
    }

    /**
     * Verifica thresholds críticos
     */
    private function saveMetrics( array $metrics ): void
    {
        try {
            $pdo = new \PDO( 'mysql:host=localhost;dbname=easybudget;charset=utf8mb4', 'root', '' );
            $pdo->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );

            $sql = "INSERT INTO middleware_metrics_history
                    (middleware_name, route, method, response_time, memory_usage, status_code, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $pdo->prepare( $sql );
            $stmt->execute( [ 
                $metrics[ 'middleware_name' ],
                $metrics[ 'route' ],
                $metrics[ 'method' ],
                $metrics[ 'response_time' ],
                $metrics[ 'memory_usage' ],
                $metrics[ 'status_code' ],
                $metrics[ 'created_at' ]
            ] );
        } catch ( \Exception $e ) {
            error_log( "Erro ao salvar métricas: " . $e->getMessage() );
        }
    }

    /**
     * Extrai status code da resposta
     */
    private function getStatusCode( $response ): int
    {
        if ( is_object( $response ) && method_exists( $response, 'getStatusCode' ) ) {
            return $response->getStatusCode();
        }

        return http_response_code() ?: 200;
    }

}
