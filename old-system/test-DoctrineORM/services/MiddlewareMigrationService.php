<?php

declare(strict_types=1);

namespace core\services;

use core\enums\RouteMiddlewares;

/**
 * Serviço para migração gradual de middlewares
 */
class MiddlewareMigrationService
{
    private array $config;
    private string $currentRoute;

    public function __construct()
    {
        $configPath = BASE_PATH . '/config/middleware_migration.php';
        $config     = require $configPath;

        // Garantir que $config seja um array
        if ( !is_array( $config ) ) {
            throw new \InvalidArgumentException( 'Arquivo de configuração deve retornar um array' );
        }
        $this->config       = $config;
        $this->currentRoute = $_SERVER[ 'REQUEST_URI' ] ?? '/';
    }

    /**
     * Determina qual classe de middleware usar baseado na configuração
     *
     * @param string $middlewareName Nome do middleware (auth, admin, etc.)
     * @return string Classe do middleware a ser utilizada
     */
    public function getMiddlewareClass( string $middlewareName ): string
    {
        // Verifica se deve usar ORM para este middleware
        if ( $this->shouldUseORM( $middlewareName ) ) {
            $ormMiddleware = $this->getORMMiddleware( $middlewareName );
            if ( $ormMiddleware ) {
                $this->logMiddlewareUsage( $middlewareName, 'ORM', $ormMiddleware );
                return $ormMiddleware;
            }
        }

        // Fallback para middleware legado
        $legacyMiddleware = $this->getLegacyMiddleware( $middlewareName );
        if ( $legacyMiddleware ) {
            $this->logMiddlewareUsage( $middlewareName, 'Legacy', $legacyMiddleware );
            return $legacyMiddleware;
        }

        throw new \Exception( "Middleware '$middlewareName' não encontrado em nenhum enum." );
    }

    /**
     * Verifica se deve usar middleware ORM para o middleware específico
     *
     * @param string $middlewareName Nome do middleware
     * @return bool
     */
    private function shouldUseORM( string $middlewareName ): bool
    {
        // Verifica configuração global
        if ( !$this->config[ 'global' ][ 'enable_orm_middlewares' ] ) {
            return false;
        }

        // Verifica se a rota está na lista de rotas legadas
        if ( $this->isLegacyRoute() ) {
            return false;
        }

        // Verifica configuração específica do middleware
        $middlewareConfig = $this->config[ 'middlewares' ][ $middlewareName ] ?? [];

        // Verifica configuração global do middleware
        if ( isset( $middlewareConfig[ 'use_orm' ] ) && $middlewareConfig[ 'use_orm' ] ) {
            return true;
        }

        // Verifica configuração por rota específica
        if ( isset( $middlewareConfig[ 'routes' ] ) ) {
            foreach ( $middlewareConfig[ 'routes' ] as $route => $useOrm ) {
                if ( $this->matchRoute( $route ) && $useOrm ) {
                    return true;
                }
            }
        }

        // Verifica se é uma rota crítica
        if ( $this->isCriticalRoute() ) {
            return true;
        }
        return false;
    }

    /**
     * Reverte um middleware para legacy
     */
    private function getORMMiddleware( string $middlewareName ): ?string
    {
        // Usar RouteMiddlewares diretamente (já migrado para ORM)
        return $this->getLegacyMiddleware( $middlewareName );
    }

    /**
     * Obtém a classe do middleware legado
     *
     * @param string $middlewareName Nome do middleware
     * @return string|null
     */
    private function getLegacyMiddleware( string $middlewareName ): ?string
    {
        $legacyMiddlewares = RouteMiddlewares::cases();
        foreach ( $legacyMiddlewares as $middleware ) {
            if ( $middleware->name === $middlewareName ) {
                return $middleware->value;
            }
        }
        return null;
    }

    /**
     * Verifica se a rota atual corresponde ao padrão
     *
     * @param string $pattern Padrão da rota (suporta wildcards com *)
     * @return bool
     */
    private function matchRoute( string $pattern ): bool
    {
        // Remove query string da rota atual
        $currentRoute = parse_url( $this->currentRoute, PHP_URL_PATH ) ?? '/';

        // Converte padrão com wildcard para regex
        $regex = str_replace( '*', '.*', preg_quote( $pattern, '/' ) );

        return preg_match( "/^{$regex}$/", $currentRoute ) === 1;
    }

    /**
     * Verifica se a rota atual é uma rota crítica
     *
     * @return bool
     */
    private function isCriticalRoute(): bool
    {
        $currentRoute = parse_url( $this->currentRoute, PHP_URL_PATH ) ?? '/';
        return in_array( $currentRoute, $this->config[ 'critical_routes' ] );
    }

    /**
     * Verifica se a rota atual deve usar middleware legado
     *
     * @return bool
     */
    private function isLegacyRoute(): bool
    {
        foreach ( $this->config[ 'legacy_routes' ] as $legacyRoute ) {
            if ( $this->matchRoute( $legacyRoute ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Registra o uso do middleware para monitoramento
     *
     * @param string $middlewareName Nome do middleware
     * @param string $type Tipo (ORM ou Legacy)
     * @param string $class Classe utilizada
     * @return void
     */
    private function logMiddlewareUsage( string $middlewareName, string $type, string $class ): void
    {
        if ( !$this->config[ 'global' ][ 'log_middleware_usage' ] ) {
            return;
        }

        $logData = [
            'timestamp'  => date( 'Y-m-d H:i:s' ),
            'route'      => $this->currentRoute,
            'middleware' => $middlewareName,
            'type'       => $type,
            'class'      => $class,
            'user_agent' => $_SERVER[ 'HTTP_USER_AGENT' ] ?? 'Unknown',
            'ip'         => $_SERVER[ 'REMOTE_ADDR' ] ?? 'Unknown',
        ];

        // Log para arquivo ou sistema de logging
        error_log(
            "[MIDDLEWARE_MIGRATION] " . json_encode( $logData ),
            3,
            __DIR__ . '/../../storage/logs/middleware_migration.log'
        );
    }

    /**
     * Verifica se deve registrar logs de uso dos middlewares
     *
     * @return bool
     */
    public function shouldLogUsage(): bool
    {
        return $this->config[ 'global' ][ 'log_middleware_usage' ] ?? false;
    }

    /**
     * Obtém a classe do middleware (ORM ou Legacy)
     */
    public function getUsageStats(): array
    {
        $logFile = __DIR__ . '/../../storage/logs/middleware_migration.log';

        if ( !file_exists( $logFile ) ) {
            return [ 'total' => 0, 'orm' => 0, 'legacy' => 0, 'by_middleware' => [] ];
        }

        $lines = file( $logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
        $stats = [ 'total' => 0, 'orm' => 0, 'legacy' => 0, 'by_middleware' => [] ];

        foreach ( $lines as $line ) {
            if ( strpos( $line, '[MIDDLEWARE_MIGRATION]' ) !== false ) {
                $jsonPart = substr( $line, strpos( $line, '{' ) );
                $data     = json_decode( $jsonPart, true );

                if ( $data ) {
                    $stats[ 'total' ]++;
                    $stats[ strtolower( $data[ 'type' ] ) ]++;

                    if ( !isset( $stats[ 'by_middleware' ][ $data[ 'middleware' ] ] ) ) {
                        $stats[ 'by_middleware' ][ $data[ 'middleware' ] ] = [ 'orm' => 0, 'legacy' => 0 ];
                    }
                    $stats[ 'by_middleware' ][ $data[ 'middleware' ] ][ strtolower( $data[ 'type' ] ) ]++;
                }
            }
        }

        return $stats;
    }

}
