<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OperationStatus;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

/**
 * Serviço utilitário para operações de cache no sistema Easy Budget.
 *
 * Este service migra funcionalidades do CacheService legacy para usar
 * Laravel Cache facade, mantendo compatibilidade com a API existente
 * enquanto oferece recursos modernos como tags, múltiplos stores e
 * invalidação inteligente.
 *
 * Funcionalidades principais:
 * - Cache de dados com TTL configurável
 * - Invalidação individual e em lote
 * - Suporte a múltiplos tenants com isolamento
 * - Compatibilidade com API legacy
 * - Operações assíncronas para performance
 *
 * @author IA - Kilo Code
 * @version 1.0.0
 */
class CacheService
{
    /**
     * Prefixo padrão para chaves de cache.
     */
    private const DEFAULT_PREFIX = 'easy_budget:';

    /**
     * TTL padrão em segundos (1 hora).
     */
    private const DEFAULT_TTL = 3600;

    /**
     * Cache de tags para operações em lote.
     */
    private array $tagCache = [];

    /**
     * Recupera um item do cache ou executa callback se não existir.
     *
     * Método compatível com API legacy. Equivalente ao remember() do Laravel.
     *
     * @param string $key Chave única do cache
     * @param int $ttl Tempo de vida em segundos (padrão: 1 hora)
     * @param callable $callback Função para gerar o valor se não existir em cache
     * @return mixed Valor do cache ou resultado do callback
     */
    public function remember( string $key, int $ttl, callable $callback ): mixed
    {
        try {
            $prefixedKey = $this->getPrefixedKey( $key );

            return Cache::remember( $prefixedKey, $ttl, $callback );
        } catch ( Exception $e ) {
            Log::error( 'Erro no cache remember', [ 
                'key'   => $key,
                'ttl'   => $ttl,
                'error' => $e->getMessage()
            ] );

            // Fallback: executar callback sem cache
            return $callback();
        }
    }

    /**
     * Recupera um item do cache.
     *
     * Método compatível com API legacy.
     *
     * @param string $key Chave do cache
     * @return mixed Dados do cache ou null se não encontrado/expirado
     */
    public function get( string $key ): mixed
    {
        try {
            $prefixedKey = $this->getPrefixedKey( $key );

            return Cache::get( $prefixedKey );
        } catch ( Exception $e ) {
            Log::error( 'Erro ao buscar do cache', [ 
                'key'   => $key,
                'error' => $e->getMessage()
            ] );

            return null;
        }
    }

    /**
     * Armazena um item no cache.
     *
     * Método compatível com API legacy.
     *
     * @param string $key Chave do cache
     * @param mixed $value Valor a ser armazenado
     * @param int $ttl Tempo de vida em segundos (padrão: 1 hora)
     * @return bool Sucesso da operação
     */
    public function put( string $key, mixed $value, int $ttl = self::DEFAULT_TTL ): bool
    {
        try {
            $prefixedKey = $this->getPrefixedKey( $key );

            Cache::put( $prefixedKey, $value, $ttl );

            return true;
        } catch ( Exception $e ) {
            Log::error( 'Erro ao armazenar no cache', [ 
                'key'   => $key,
                'ttl'   => $ttl,
                'error' => $e->getMessage()
            ] );

            return false;
        }
    }

    /**
     * Recupera um item do cache ou executa callback (versão com ServiceResult).
     *
     * Versão moderna do remember() com ServiceResult para padronização.
     *
     * @param string $key Chave única do cache
     * @param int $ttl Tempo de vida em segundos
     * @param callable|null $callback Função para gerar o valor se não existir
     * @param string|null $tenantId ID do tenant para isolamento (opcional)
     * @return ServiceResult Resultado da operação
     */
    public function rememberWithResult(
        string $key,
        int $ttl,
        ?callable $callback = null,
        ?string $tenantId = null,
    ): ServiceResult {
        try {
            $cacheKey = $this->buildTenantKey( $key, $tenantId );

            if ( Cache::has( $cacheKey ) ) {
                $cached = Cache::get( $cacheKey );
                return ServiceResult::success( $cached, 'Valor recuperado do cache.' );
            }

            if ( $callback !== null ) {
                $result = $callback();
                $this->put( $cacheKey, $result, $ttl );
                return ServiceResult::success( $result, 'Valor gerado e armazenado em cache.' );
            }

            return ServiceResult::error(
                OperationStatus::NOT_FOUND,
                'Valor não encontrado no cache e nenhum callback fornecido.',
            );
        } catch ( Exception $e ) {
            Log::error( 'Erro no cache rememberWithResult', [ 
                'key'       => $key,
                'tenant_id' => $tenantId,
                'error'     => $e->getMessage()
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao recuperar do cache: ' . $e->getMessage()
            );
        }
    }

    /**
     * Busca um item de cache pelo ID (compatibilidade com API legacy).
     *
     * @param int $id ID do cache (será convertido para string)
     * @param string|null $tenantId ID do tenant para isolamento
     * @return ServiceResult Resultado da operação
     */
    public function getById( int $id, ?string $tenantId = null ): ServiceResult
    {
        try {
            $key      = (string) $id;
            $cacheKey = $this->buildTenantKey( $key, $tenantId );
            $cached   = Cache::get( $cacheKey );

            if ( $cached !== null ) {
                return ServiceResult::success( $cached, 'Item de cache encontrado.' );
            }

            return ServiceResult::error(
                OperationStatus::NOT_FOUND,
                'Item de cache não encontrado.',
            );
        } catch ( Exception $e ) {
            Log::error( 'Erro ao buscar item de cache por ID', [ 
                'id'        => $id,
                'tenant_id' => $tenantId,
                'error'     => $e->getMessage()
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao buscar item de cache: ' . $e->getMessage()
            );
        }
    }

    /**
     * Remove um item específico do cache.
     *
     * @param string $key Chave do cache
     * @param string|null $tenantId ID do tenant para isolamento
     * @return bool Sucesso da operação
     */
    public function forget( string $key, ?string $tenantId = null ): bool
    {
        try {
            $cacheKey = $this->buildTenantKey( $key, $tenantId );

            Cache::forget( $cacheKey );

            return true;
        } catch ( Exception $e ) {
            Log::error( 'Erro ao remover do cache', [ 
                'key'       => $key,
                'tenant_id' => $tenantId,
                'error'     => $e->getMessage()
            ] );

            return false;
        }
    }

    /**
     * Remove um item de cache (compatibilidade com API legacy).
     *
     * @param int $id ID do cache
     * @param string|null $tenantId ID do tenant para isolamento
     * @return ServiceResult Resultado da operação
     */
    public function delete( int $id, ?string $tenantId = null ): ServiceResult
    {
        try {
            $key      = (string) $id;
            $cacheKey = $this->buildTenantKey( $key, $tenantId );

            if ( Cache::has( $cacheKey ) ) {
                Cache::forget( $cacheKey );
                return ServiceResult::success( null, 'Item de cache removido com sucesso.' );
            }

            return ServiceResult::error(
                OperationStatus::NOT_FOUND,
                'Item de cache não encontrado.',
            );
        } catch ( Exception $e ) {
            Log::error( 'Erro ao excluir item de cache', [ 
                'id'        => $id,
                'tenant_id' => $tenantId,
                'error'     => $e->getMessage()
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao excluir item de cache: ' . $e->getMessage()
            );
        }
    }

    /**
     * Remove múltiplos itens do cache usando tags.
     *
     * @param array $tags Tags para identificar os itens
     * @return bool Sucesso da operação
     */
    public function flushByTags( array $tags ): bool
    {
        try {
            Cache::tags( $tags )->flush();

            return true;
        } catch ( Exception $e ) {
            Log::error( 'Erro ao limpar cache por tags', [ 
                'tags'  => $tags,
                'error' => $e->getMessage()
            ] );

            return false;
        }
    }

    /**
     * Remove todos os itens de cache de um tenant.
     *
     * @param string $tenantId ID do tenant
     * @return bool Sucesso da operação
     */
    public function flushByTenant( string $tenantId ): bool
    {
        try {
            $pattern = $this->getTenantPrefix( $tenantId ) . '*';

            // Para Redis, usar pattern delete se disponível
            if ( Config::get( 'cache.default' ) === 'redis' ) {
                $redis = Cache::store( 'redis' )->getRedis();
                $keys  = $redis->keys( $pattern );

                if ( !empty( $keys ) ) {
                    $redis->del( $keys );
                }
            } else {
                // Para outros stores, usar flush geral (limitação do Laravel)
                Cache::flush();
            }

            return true;
        } catch ( Exception $e ) {
            Log::error( 'Erro ao limpar cache do tenant', [ 
                'tenant_id' => $tenantId,
                'error'     => $e->getMessage()
            ] );

            return false;
        }
    }

    /**
     * Armazena um item no cache com tags para invalidação em grupo.
     *
     * @param string $key Chave do cache
     * @param mixed $value Valor a ser armazenado
     * @param int $ttl Tempo de vida em segundos
     * @param array $tags Tags para categorização
     * @param string|null $tenantId ID do tenant para isolamento
     * @return bool Sucesso da operação
     */
    public function putWithTags(
        string $key,
        mixed $value,
        int $ttl,
        array $tags = [],
        ?string $tenantId = null,
    ): bool {
        try {
            $cacheKey = $this->buildTenantKey( $key, $tenantId );

            if ( !empty( $tags ) ) {
                Cache::tags( $tags )->put( $cacheKey, $value, $ttl );
            } else {
                Cache::put( $cacheKey, $value, $ttl );
            }

            return true;
        } catch ( Exception $e ) {
            Log::error( 'Erro ao armazenar no cache com tags', [ 
                'key'       => $key,
                'tags'      => $tags,
                'tenant_id' => $tenantId,
                'error'     => $e->getMessage()
            ] );

            return false;
        }
    }

    /**
     * Recupera um item do cache com tags ou executa callback.
     *
     * @param string $key Chave do cache
     * @param int $ttl Tempo de vida em segundos
     * @param callable $callback Função para gerar o valor
     * @param array $tags Tags para categorização
     * @param string|null $tenantId ID do tenant para isolamento
     * @return mixed Valor do cache ou resultado do callback
     */
    public function rememberWithTags(
        string $key,
        int $ttl,
        callable $callback,
        array $tags = [],
        ?string $tenantId = null,
    ): mixed {
        try {
            $cacheKey = $this->buildTenantKey( $key, $tenantId );

            if ( !empty( $tags ) ) {
                return Cache::tags( $tags )->remember( $cacheKey, $ttl, $callback );
            }

            return Cache::remember( $cacheKey, $ttl, $callback );
        } catch ( Exception $e ) {
            Log::error( 'Erro no cache rememberWithTags', [ 
                'key'       => $key,
                'tags'      => $tags,
                'tenant_id' => $tenantId,
                'error'     => $e->getMessage()
            ] );

            return $callback();
        }
    }

    /**
     * Verifica se um item existe no cache.
     *
     * @param string $key Chave do cache
     * @param string|null $tenantId ID do tenant para isolamento
     * @return bool Verdadeiro se existe
     */
    public function has( string $key, ?string $tenantId = null ): bool
    {
        try {
            $cacheKey = $this->buildTenantKey( $key, $tenantId );

            return Cache::has( $cacheKey );
        } catch ( Exception $e ) {
            Log::error( 'Erro ao verificar existência no cache', [ 
                'key'       => $key,
                'tenant_id' => $tenantId,
                'error'     => $e->getMessage()
            ] );

            return false;
        }
    }

    /**
     * Obtém informações sobre o cache (debugging).
     *
     * @param string|null $tenantId ID do tenant para filtro
     * @return array Informações do cache
     */
    public function getCacheInfo( ?string $tenantId = null ): array
    {
        try {
            $info = [ 
                'driver'    => Config::get( 'cache.default' ),
                'prefix'    => Config::get( 'cache.prefix' ),
                'tenant_id' => $tenantId,
                'timestamp' => now()->toISOString(),
            ];

            // Para Redis, obter estatísticas adicionais
            if ( Config::get( 'cache.default' ) === 'redis' ) {
                $redis                = Cache::store( 'redis' )->getRedis();
                $info[ 'redis_info' ] = [ 
                    'connected' => $redis->isConnected(),
                    'db_size'   => $redis->dbSize(),
                ];
            }

            return $info;
        } catch ( Exception $e ) {
            Log::error( 'Erro ao obter informações do cache', [ 
                'tenant_id' => $tenantId,
                'error'     => $e->getMessage()
            ] );

            return [ 'error' => $e->getMessage() ];
        }
    }

    /**
     * Constrói chave de cache com prefixo de tenant.
     *
     * @param string $key Chave original
     * @param string|null $tenantId ID do tenant
     * @return string Chave com prefixo
     */
    private function buildTenantKey( string $key, ?string $tenantId = null ): string
    {
        $prefixedKey = $this->getPrefixedKey( $key );

        if ( $tenantId ) {
            return $this->getTenantPrefix( $tenantId ) . $prefixedKey;
        }

        return $prefixedKey;
    }

    /**
     * Adiciona prefixo padrão à chave.
     *
     * @param string $key Chave original
     * @return string Chave com prefixo
     */
    private function getPrefixedKey( string $key ): string
    {
        return self::DEFAULT_PREFIX . $key;
    }

    /**
     * Gera prefixo para tenant.
     *
     * @param string $tenantId ID do tenant
     * @return string Prefixo do tenant
     */
    private function getTenantPrefix( string $tenantId ): string
    {
        return "tenant:{$tenantId}:";
    }

    /**
     * Limpa todo o cache do sistema (use com cuidado).
     *
     * @return bool Sucesso da operação
     */
    public function flushAll(): bool
    {
        try {
            Cache::flush();

            Log::info( 'Cache global limpo com sucesso' );

            return true;
        } catch ( Exception $e ) {
            Log::error( 'Erro ao limpar cache global', [ 
                'error' => $e->getMessage()
            ] );

            return false;
        }
    }

    /**
     * Armazena um item no cache para sempre.
     *
     * @param string $key Chave do cache
     * @param mixed $value Valor a ser armazenado
     * @param string|null $tenantId ID do tenant para isolamento
     * @return bool Sucesso da operação
     */
    public function putForever( string $key, mixed $value, ?string $tenantId = null ): bool
    {
        try {
            $cacheKey = $this->buildTenantKey( $key, $tenantId );

            Cache::forever( $cacheKey, $value );

            return true;
        } catch ( Exception $e ) {
            Log::error( 'Erro ao armazenar permanentemente no cache', [ 
                'key'       => $key,
                'tenant_id' => $tenantId,
                'error'     => $e->getMessage()
            ] );

            return false;
        }
    }

    /**
     * Recupera um item do cache ou executa callback (cache eterno).
     *
     * @param string $key Chave do cache
     * @param callable $callback Função para gerar o valor
     * @param string|null $tenantId ID do tenant para isolamento
     * @return mixed Valor do cache ou resultado do callback
     */
    public function rememberForever( string $key, callable $callback, ?string $tenantId = null ): mixed
    {
        try {
            $cacheKey = $this->buildTenantKey( $key, $tenantId );

            return Cache::rememberForever( $cacheKey, $callback );
        } catch ( Exception $e ) {
            Log::error( 'Erro no cache rememberForever', [ 
                'key'       => $key,
                'tenant_id' => $tenantId,
                'error'     => $e->getMessage()
            ] );

            return $callback();
        }
    }

}
