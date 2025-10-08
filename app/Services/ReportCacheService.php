<?php

namespace App\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

/**
 * Serviço de cache inteligente para relatórios
 * Gerencia cache com invalidação automática e estratégias avançadas
 */
class ReportCacheService
{
    private const DEFAULT_TTL = 3600; // 1 hora
    private const LONG_TTL    = 7200;   // 2 horas
    private const SHORT_TTL   = 1800;  // 30 minutos

    private array $cacheTags         = [ 'reports' ];
    private array $invalidationRules = [];

    public function __construct()
    {
        $this->initializeInvalidationRules();
    }

    /**
     * Obtém dados do relatório com cache inteligente
     */
    public function getReportData( string $cacheKey, callable $dataGenerator, ?int $ttl = null ): Collection
    {
        $ttl = $ttl ?? $this->determineOptimalTtl( $cacheKey );

        return Cache::remember( $cacheKey, $ttl, function () use ($dataGenerator, $cacheKey) {
            try {
                $data = $dataGenerator();

                // Registrar estatísticas de cache hit
                $this->recordCacheHit( $cacheKey, true );

                return collect( $data );
            } catch ( Exception $e ) {
                // Em caso de erro, tentar novamente sem cache
                $this->recordCacheHit( $cacheKey, false );
                throw $e;
            }
        } );
    }

    /**
     * Armazena dados no cache com configurações avançadas
     */
    public function putReportData( string $cacheKey, Collection $data, ?int $ttl = null ): bool
    {
        $ttl = $ttl ?? $this->determineOptimalTtl( $cacheKey );

        try {
            $success = Cache::put( $cacheKey, $data, $ttl );

            if ( $success ) {
                $this->recordCacheStorage( $cacheKey, $data->count() );
            }

            return $success;
        } catch ( Exception $e ) {
            // Log error mas não interromper execução
            logger()->error( 'Erro ao armazenar dados no cache', [
                'cache_key' => $cacheKey,
                'error'     => $e->getMessage()
            ] );
            return false;
        }
    }

    /**
     * Invalida cache de relatório específico
     */
    public function invalidateReportCache( string $reportId, ?string $tenantId = null ): void
    {
        $pattern = $this->buildCachePattern( $reportId, $tenantId );

        if ( Cache::getStore() instanceof \Illuminate\Cache\RedisStore ) {
            $this->invalidateRedisPattern( $pattern );
        } else {
            $this->invalidateTagBased( $reportId );
        }

        $this->recordInvalidation( $reportId, 'manual' );
    }

    /**
     * Invalida todos os caches de relatórios de um tenant
     */
    public function invalidateAllReports( ?string $tenantId = null ): void
    {
        $pattern = $tenantId ? "report_{$tenantId}_*" : "report_*";

        if ( Cache::getStore() instanceof \Illuminate\Cache\RedisStore ) {
            $this->invalidateRedisPattern( $pattern );
        } else {
            Cache::tags( $this->cacheTags )->flush();
        }

        $this->recordInvalidation( 'all', 'bulk' );
    }

    /**
     * Invalidação automática baseada em eventos
     */
    public function invalidateByEvent( string $event, array $data = [] ): void
    {
        $rules = $this->invalidationRules[ $event ] ?? [];

        foreach ( $rules as $rule ) {
            if ( $this->shouldInvalidate( $rule, $data ) ) {
                $pattern = $rule[ 'pattern' ];
                $this->invalidateRedisPattern( $pattern );
                $this->recordInvalidation( $event, 'event' );
            }
        }
    }

    /**
     * Obtém estatísticas de cache
     */
    public function getCacheStats(): array
    {
        if ( Cache::getStore() instanceof \Illuminate\Cache\RedisStore ) {
            return $this->getRedisStats();
        }

        return [
            'driver'       => 'file',
            'hit_ratio'    => 0,
            'total_keys'   => 0,
            'memory_usage' => 'N/A'
        ];
    }

    /**
     * Limpa cache antigo automaticamente
     */
    public function cleanupExpiredCache(): int
    {
        $cleaned = 0;

        if ( Cache::getStore() instanceof \Illuminate\Cache\RedisStore ) {
            $cleaned = $this->cleanupRedisCache();
        }

        return $cleaned;
    }

    /**
     * Define configuração de cache para relatório específico
     */
    public function setReportCacheConfig( string $reportId, array $config ): void
    {
        $cacheKey = "report_config_{$reportId}";

        Cache::put( $cacheKey, $config, self::LONG_TTL );
    }

    /**
     * Obtém configuração de cache para relatório
     */
    public function getReportCacheConfig( string $reportId ): ?array
    {
        $cacheKey = "report_config_{$reportId}";

        return Cache::get( $cacheKey );
    }

    /**
     * Gera chave de cache inteligente
     */
    public function generateCacheKey( string $reportId, array $filters = [] ): string
    {
        $tenantId   = auth()->user()->tenant_id ?? 'global';
        $filterHash = $this->generateFilterHash( $filters );

        return "report_{$tenantId}_{$reportId}_{$filterHash}";
    }

    /**
     * Verifica se dados estão no cache
     */
    public function hasCache( string $cacheKey ): bool
    {
        return Cache::has( $cacheKey );
    }

    /**
     * Obtém dados do cache sem gerar novos
     */
    public function getCachedData( string $cacheKey ): ?Collection
    {
        $data = Cache::get( $cacheKey );

        if ( $data ) {
            $this->recordCacheHit( $cacheKey, true );
            return collect( $data );
        }

        $this->recordCacheHit( $cacheKey, false );
        return null;
    }

    /**
     * Remove dados do cache
     */
    public function forgetCache( string $cacheKey ): bool
    {
        return Cache::forget( $cacheKey );
    }

    /**
     * Determina TTL ótimo baseado no tipo de relatório
     */
    private function determineOptimalTtl( string $cacheKey ): int
    {
        // Relatórios financeiros: cache mais curto
        if ( str_contains( $cacheKey, 'financial' ) || str_contains( $cacheKey, 'revenue' ) ) {
            return self::SHORT_TTL;
        }

        // Relatórios executivos: cache médio
        if ( str_contains( $cacheKey, 'executive' ) || str_contains( $cacheKey, 'kpi' ) ) {
            return self::DEFAULT_TTL;
        }

        // Relatórios históricos: cache longo
        if ( str_contains( $cacheKey, 'history' ) || str_contains( $cacheKey, 'archive' ) ) {
            return self::LONG_TTL;
        }

        // Padrão
        return self::DEFAULT_TTL;
    }

    /**
     * Constrói padrão de cache para invalidação
     */
    private function buildCachePattern( string $reportId, ?string $tenantId = null ): string
    {
        $tenantPart = $tenantId ? "_{$tenantId}_" : '_*_';
        return "report{$tenantPart}{$reportId}_*";
    }

    /**
     * Invalida cache usando Redis pattern
     */
    private function invalidateRedisPattern( string $pattern ): void
    {
        try {
            $redis = Cache::getRedis();
            $keys  = $redis->keys( $pattern );

            if ( !empty( $keys ) ) {
                $redis->del( $keys );
            }
        } catch ( Exception $e ) {
            logger()->error( 'Erro ao invalidar cache Redis', [
                'pattern' => $pattern,
                'error'   => $e->getMessage()
            ] );
        }
    }

    /**
     * Invalidação baseada em tags
     */
    private function invalidateTagBased( string $reportId ): void
    {
        try {
            Cache::tags( array_merge( $this->cacheTags, [ $reportId ] ) )->flush();
        } catch ( Exception $e ) {
            logger()->error( 'Erro ao invalidar cache por tags', [
                'report_id' => $reportId,
                'error'     => $e->getMessage()
            ] );
        }
    }

    /**
     * Verifica se deve invalidar baseado na regra
     */
    private function shouldInvalidate( array $rule, array $data ): bool
    {
        if ( isset( $rule[ 'conditions' ] ) ) {
            foreach ( $rule[ 'conditions' ] as $condition ) {
                $field    = $condition[ 'field' ];
                $operator = $condition[ 'operator' ];
                $expected = $condition[ 'value' ];

                $actual = data_get( $data, $field );

                if ( !$this->evaluateCondition( $actual, $operator, $expected ) ) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Avalia condição de invalidação
     */
    private function evaluateCondition( $actual, string $operator, $expected ): bool
    {
        return match ( $operator ) {
            'equals'       => $actual == $expected,
            'not_equals'   => $actual != $expected,
            'in'           => in_array( $actual, (array) $expected ),
            'not_in'       => !in_array( $actual, (array) $expected ),
            'greater_than' => $actual > $expected,
            'less_than'    => $actual < $expected,
            'contains'     => str_contains( (string) $actual, (string) $expected ),
            default        => true
        };
    }

    /**
     * Inicializa regras de invalidação automática
     */
    private function initializeInvalidationRules(): void
    {
        $this->invalidationRules = [
            'budget.created'   => [
                [
                    'pattern'    => 'report_*_financial_*',
                    'conditions' => [ [ 'field' => 'model', 'operator' => 'equals', 'value' => 'Budget' ] ]
                ],
                [
                    'pattern'    => 'report_*_budget_*',
                    'conditions' => [ [ 'field' => 'model', 'operator' => 'equals', 'value' => 'Budget' ] ]
                ]
            ],
            'budget.updated'   => [
                [
                    'pattern'    => 'report_*_financial_*',
                    'conditions' => [ [ 'field' => 'model', 'operator' => 'equals', 'value' => 'Budget' ] ]
                ]
            ],
            'customer.created' => [
                [
                    'pattern'    => 'report_*_customer_*',
                    'conditions' => [ [ 'field' => 'model', 'operator' => 'equals', 'value' => 'Customer' ] ]
                ]
            ],
            'customer.updated' => [
                [
                    'pattern'    => 'report_*_customer_*',
                    'conditions' => [ [ 'field' => 'model', 'operator' => 'equals', 'value' => 'Customer' ] ]
                ]
            ]
        ];
    }

    /**
     * Gera hash dos filtros para cache
     */
    private function generateFilterHash( array $filters ): string
    {
        // Ordenar filtros para consistência
        ksort( $filters );

        // Remover valores vazios
        $filtered = array_filter( $filters, fn( $value ) => !empty( $value ) );

        return md5( serialize( $filtered ) );
    }

    /**
     * Registra hit de cache
     */
    private function recordCacheHit( string $cacheKey, bool $hit ): void
    {
        try {
            $statsKey = 'cache_stats_' . date( 'Y-m-d' );

            $currentStats = Cache::get( $statsKey, [
                'total_requests' => 0,
                'hits'           => 0,
                'misses'         => 0
            ] );

            $currentStats[ 'total_requests' ]++;

            if ( $hit ) {
                $currentStats[ 'hits' ]++;
            } else {
                $currentStats[ 'misses' ]++;
            }

            Cache::put( $statsKey, $currentStats, 86400 ); // 24 horas
        } catch ( Exception $e ) {
            // Não deixar erro de estatísticas interromper o fluxo
        }
    }

    /**
     * Registra armazenamento no cache
     */
    private function recordCacheStorage( string $cacheKey, int $dataCount ): void
    {
        try {
            $storageKey = 'cache_storage_' . date( 'Y-m-d' );

            $currentStorage = Cache::get( $storageKey, [
                'total_storages' => 0,
                'total_records'  => 0
            ] );

            $currentStorage[ 'total_storages' ]++;
            $currentStorage[ 'total_records' ] += $dataCount;

            Cache::put( $storageKey, $currentStorage, 86400 );
        } catch ( Exception $e ) {
            // Não deixar erro de estatísticas interromper o fluxo
        }
    }

    /**
     * Registra invalidação de cache
     */
    private function recordInvalidation( string $target, string $type ): void
    {
        try {
            $invalidationKey = 'cache_invalidation_' . date( 'Y-m-d' );

            $currentInvalidations = Cache::get( $invalidationKey, [
                'total_invalidations' => 0,
                'by_target'           => [],
                'by_type'             => []
            ] );

            $currentInvalidations[ 'total_invalidations' ]++;

            if ( !isset( $currentInvalidations[ 'by_target' ][ $target ] ) ) {
                $currentInvalidations[ 'by_target' ][ $target ] = 0;
            }
            $currentInvalidations[ 'by_target' ][ $target ]++;

            if ( !isset( $currentInvalidations[ 'by_type' ][ $type ] ) ) {
                $currentInvalidations[ 'by_type' ][ $type ] = 0;
            }
            $currentInvalidations[ 'by_type' ][ $type ]++;

            Cache::put( $invalidationKey, $currentInvalidations, 86400 );
        } catch ( Exception $e ) {
            // Não deixar erro de estatísticas interromper o fluxo
        }
    }

    /**
     * Obtém estatísticas do Redis
     */
    private function getRedisStats(): array
    {
        try {
            $redis = Cache::getRedis();
            $info  = $redis->info();

            return [
                'driver'            => 'redis',
                'hit_ratio'         => $this->calculateHitRatio(),
                'total_keys'        => $info[ 'db0' ][ 'keys' ] ?? 0,
                'memory_usage'      => $info[ 'memory' ][ 'used_memory_human' ] ?? 'N/A',
                'connected_clients' => $info[ 'clients' ][ 'connected_clients' ] ?? 0
            ];
        } catch ( Exception $e ) {
            return [
                'driver' => 'redis',
                'error'  => $e->getMessage()
            ];
        }
    }

    /**
     * Calcula taxa de acerto do cache
     */
    private function calculateHitRatio(): float
    {
        $statsKey = 'cache_stats_' . date( 'Y-m-d' );
        $stats    = Cache::get( $statsKey, [ 'total_requests' => 0, 'hits' => 0 ] );

        if ( $stats[ 'total_requests' ] === 0 ) {
            return 0.0;
        }

        return round( ( $stats[ 'hits' ] / $stats[ 'total_requests' ] ) * 100, 2 );
    }

    /**
     * Limpa cache antigo do Redis
     */
    private function cleanupRedisCache(): int
    {
        try {
            $redis   = Cache::getRedis();
            $pattern = 'report_*';
            $keys    = $redis->keys( $pattern );
            $cleaned = 0;

            foreach ( $keys as $key ) {
                $ttl = $redis->ttl( $key );

                // Se TTL for -1 (sem expiração) ou muito antigo, remove
                if ( $ttl === -1 || $ttl > 86400 ) { // 24 horas
                    $redis->del( $key );
                    $cleaned++;
                }
            }

            return $cleaned;
        } catch ( Exception $e ) {
            logger()->error( 'Erro ao limpar cache Redis', [
                'error' => $e->getMessage()
            ] );
            return 0;
        }
    }

}
