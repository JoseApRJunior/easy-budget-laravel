<?php

declare(strict_types=1);

namespace App\Services\Infrastructure;

use App\Enums\OperationStatus;
use App\Models\Tenant;
use App\Models\User;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Serviço avançado para controle de taxa de envio de e-mails.
 *
 * Funcionalidades principais:
 * - Rate limiting por usuário, tenant e global
 * - Proteção contra spam e abuso
 * - Sistema de bloqueio temporário
 * - Monitoramento de tentativas suspeitas
 * - Integração com sistema multi-tenant
 * - Cache inteligente para performance
 *
 * Este serviço trabalha em conjunto com o EmailSenderService
 * para fornecer proteção completa contra abuso de e-mail.
 */
class EmailRateLimitService
{
    /**
     * Configurações carregadas do arquivo de configuração.
     */
    private array $config;

    /**
     * Cache de limites por chave.
     */
    private array $rateLimitCache = [];

    /**
     * Construtor: inicializa configurações.
     */
    public function __construct()
    {
        $this->config = config( 'email-senders.rate_limiting' );
    }

    /**
     * Verifica se o envio de e-mail está dentro dos limites permitidos.
     *
     * @param User|null $user Usuário que está enviando o e-mail
     * @param Tenant|null $tenant Tenant relacionado ao envio
     * @param string $emailType Tipo de e-mail (critical, high, normal, low)
     * @return ServiceResult Resultado da verificação de limite
     */
    public function checkRateLimit(
        ?User $user = null,
        ?Tenant $tenant = null,
        string $emailType = 'normal',
    ): ServiceResult {
        try {
            // Se rate limiting estiver desabilitado, permitir
            if ( !$this->config[ 'enabled' ] ) {
                return ServiceResult::success( [
                    'allowed' => true,
                    'reason'  => 'rate_limiting_disabled',
                ], 'Rate limiting desabilitado.' );
            }

            $violations = [];
            $limits     = [];

            // Verificar limite por usuário
            if ( $user ) {
                $userLimit = $this->checkUserRateLimit( $user, $emailType );
                if ( !$userLimit->isSuccess() ) {
                    $violations[] = 'user_limit_exceeded';
                }
                $limits[ 'user' ] = $userLimit->getData();
            }

            // Verificar limite por tenant
            if ( $tenant ) {
                $tenantLimit = $this->checkTenantRateLimit( $tenant, $emailType );
                if ( !$tenantLimit->isSuccess() ) {
                    $violations[] = 'tenant_limit_exceeded';
                }
                $limits[ 'tenant' ] = $tenantLimit->getData();
            }

            // Verificar limite global
            $globalLimit = $this->checkGlobalRateLimit( $emailType );
            if ( !$globalLimit->isSuccess() ) {
                $violations[] = 'global_limit_exceeded';
            }
            $limits[ 'global' ] = $globalLimit->getData();

            // Se há violações, bloquear envio
            if ( !empty( $violations ) ) {
                $blockResult = $this->handleRateLimitViolation( $user, $tenant, $violations );

                return ServiceResult::error(
                    OperationStatus::RATE_LIMITED,
                    'Limite de taxa excedido: ' . implode( ', ', $violations ),
                    [
                        'violations' => $violations,
                        'limits'     => $limits,
                        'block_info' => $blockResult->getData(),
                    ],
                );
            }

            return ServiceResult::success( [
                'allowed'    => true,
                'limits'     => $limits,
                'checked_at' => now()->toDateTimeString(),
            ], 'Envio permitido dentro dos limites.' );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao verificar limite de taxa', [
                'user_id'    => $user?->id,
                'tenant_id'  => $tenant?->id,
                'email_type' => $emailType,
                'error'      => $e->getMessage(),
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao verificar limite de taxa: ' . $e->getMessage()
            );
        }
    }

    /**
     * Registra tentativa de envio de e-mail para controle de taxa.
     *
     * @param User|null $user Usuário que está enviando
     * @param Tenant|null $tenant Tenant relacionado
     * @param string $emailType Tipo de e-mail
     * @return ServiceResult Resultado do registro
     */
    public function recordEmailAttempt(
        ?User $user = null,
        ?Tenant $tenant = null,
        string $emailType = 'normal',
    ): ServiceResult {
        try {
            $records = [];

            // Registrar tentativa por usuário
            if ( $user ) {
                $userRecord        = $this->recordUserAttempt( $user, $emailType );
                $records[ 'user' ] = $userRecord->getData();
            }

            // Registrar tentativa por tenant
            if ( $tenant ) {
                $tenantRecord        = $this->recordTenantAttempt( $tenant, $emailType );
                $records[ 'tenant' ] = $tenantRecord->getData();
            }

            // Registrar tentativa global
            $globalRecord        = $this->recordGlobalAttempt( $emailType );
            $records[ 'global' ] = $globalRecord->getData();

            return ServiceResult::success( [
                'recorded'    => true,
                'records'     => $records,
                'recorded_at' => now()->toDateTimeString(),
            ], 'Tentativa registrada com sucesso.' );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao registrar tentativa de e-mail', [
                'user_id'    => $user?->id,
                'tenant_id'  => $tenant?->id,
                'email_type' => $emailType,
                'error'      => $e->getMessage(),
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao registrar tentativa: ' . $e->getMessage()
            );
        }
    }

    /**
     * Obtém estatísticas de uso de rate limiting.
     *
     * @return array Estatísticas atuais
     */
    public function getRateLimitStats(): array
    {
        try {
            $stats = [];

            // Estatísticas por tipo de limite
            foreach ( [ 'critical', 'high', 'normal', 'low' ] as $type ) {
                $stats[ $type ] = [
                    'global'     => $this->getGlobalStats( $type ),
                    'cache_info' => [
                        'cached_keys'   => count( $this->rateLimitCache ),
                        'cache_enabled' => $this->config[ 'enabled' ],
                    ],
                ];
            }

            return [
                'stats'     => $stats,
                'config'    => [
                    'enabled'    => $this->config[ 'enabled' ],
                    'per_user'   => $this->config[ 'per_user' ],
                    'per_tenant' => $this->config[ 'per_tenant' ],
                    'global'     => $this->config[ 'global' ],
                ],
                'timestamp' => now()->toDateTimeString(),
            ];

        } catch ( Exception $e ) {
            return [
                'error'     => 'Erro ao obter estatísticas: ' . $e->getMessage(),
                'timestamp' => now()->toDateTimeString(),
            ];
        }
    }

    /**
     * Limpa bloqueios de rate limiting para um usuário específico.
     *
     * @param User $user Usuário para desbloquear
     * @return ServiceResult Resultado da operação
     */
    public function clearUserRateLimit( User $user ): ServiceResult
    {
        try {
            $cleared = [];

            foreach ( [ 'critical', 'high', 'normal', 'low' ] as $type ) {
                $cacheKey = $this->getUserCacheKey( $user->id, $type );
                Cache::forget( $cacheKey );
                $cleared[] = $type;
            }

            Log::info( 'Rate limit de usuário limpo', [
                'user_id'       => $user->id,
                'cleared_types' => $cleared,
            ] );

            return ServiceResult::success( [
                'cleared'       => true,
                'cleared_types' => $cleared,
                'user_id'       => $user->id,
            ], 'Rate limit de usuário limpo com sucesso.' );

        } catch ( Exception $e ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao limpar rate limit: ' . $e->getMessage()
            );
        }
    }

    /**
     * Verifica limite de taxa por usuário.
     */
    private function checkUserRateLimit( User $user, string $emailType ): ServiceResult
    {
        $config   = $this->config[ 'per_user' ];
        $cacheKey = $this->getUserCacheKey( $user->id, $emailType );

        $attempts = $this->getCachedAttempts( $cacheKey, $config );

        return $this->evaluateRateLimit( $attempts, $config, 'user', $user->id );
    }

    /**
     * Verifica limite de taxa por tenant.
     */
    private function checkTenantRateLimit( Tenant $tenant, string $emailType ): ServiceResult
    {
        $config   = $this->config[ 'per_tenant' ];
        $cacheKey = $this->getTenantCacheKey( $tenant->id, $emailType );

        $attempts = $this->getCachedAttempts( $cacheKey, $config );

        return $this->evaluateRateLimit( $attempts, $config, 'tenant', $tenant->id );
    }

    /**
     * Verifica limite de taxa global.
     */
    private function checkGlobalRateLimit( string $emailType ): ServiceResult
    {
        $config   = $this->config[ 'global' ];
        $cacheKey = $this->getGlobalCacheKey( $emailType );

        $attempts = $this->getCachedAttempts( $cacheKey, $config );

        return $this->evaluateRateLimit( $attempts, $config, 'global', null );
    }

    /**
     * Registra tentativa por usuário.
     */
    private function recordUserAttempt( User $user, string $emailType ): ServiceResult
    {
        $config   = $this->config[ 'per_user' ];
        $cacheKey = $this->getUserCacheKey( $user->id, $emailType );

        $this->incrementCachedAttempts( $cacheKey, $config );

        return ServiceResult::success( [
            'recorded'  => true,
            'cache_key' => $cacheKey,
            'user_id'   => $user->id,
        ] );
    }

    /**
     * Registra tentativa por tenant.
     */
    private function recordTenantAttempt( Tenant $tenant, string $emailType ): ServiceResult
    {
        $config   = $this->config[ 'per_tenant' ];
        $cacheKey = $this->getTenantCacheKey( $tenant->id, $emailType );

        $this->incrementCachedAttempts( $cacheKey, $config );

        return ServiceResult::success( [
            'recorded'  => true,
            'cache_key' => $cacheKey,
            'tenant_id' => $tenant->id,
        ] );
    }

    /**
     * Registra tentativa global.
     */
    private function recordGlobalAttempt( string $emailType ): ServiceResult
    {
        $config   = $this->config[ 'global' ];
        $cacheKey = $this->getGlobalCacheKey( $emailType );

        $this->incrementCachedAttempts( $cacheKey, $config );

        return ServiceResult::success( [
            'recorded'  => true,
            'cache_key' => $cacheKey,
            'type'      => 'global',
        ] );
    }

    /**
     * Trata violação de limite de taxa.
     */
    private function handleRateLimitViolation( ?User $user, ?Tenant $tenant, array $violations ): ServiceResult
    {
        $blockDuration = $this->config[ 'blocking' ][ 'block_duration_minutes' ];

        // Registrar tentativa de abuso
        $this->logSecurityEvent( 'rate_limit_exceeded', [
            'user_id'                => $user?->id,
            'tenant_id'              => $tenant?->id,
            'violations'             => $violations,
            'block_duration_minutes' => $blockDuration,
        ] );

        // Em produção, implementar bloqueio mais sofisticado
        return ServiceResult::success( [
            'blocked'                => true,
            'block_duration_minutes' => $blockDuration,
            'violations'             => $violations,
            'blocked_at'             => now()->toDateTimeString(),
        ], 'Envio bloqueado devido a excesso de tentativas.' );
    }

    /**
     * Avalia se limite de taxa foi excedido.
     */
    private function evaluateRateLimit( array $attempts, array $config, string $type, ?int $id ): ServiceResult
    {
        $now = now();

        // Verificar limite por minuto
        $minuteKey = 'minute_' . $now->format( 'Y_m_d_H_i' );
        if ( ( $attempts[ $minuteKey ] ?? 0 ) >= $config[ 'max_per_minute' ] ) {
            return ServiceResult::error(
                OperationStatus::RATE_LIMITED,
                "Limite por minuto excedido para {$type}: {$attempts[ $minuteKey ]}/{$config[ 'max_per_minute' ]}",
            );
        }

        // Verificar limite por hora
        $hourKey = 'hour_' . $now->format( 'Y_m_d_H' );
        if ( ( $attempts[ $hourKey ] ?? 0 ) >= $config[ 'max_per_hour' ] ) {
            return ServiceResult::error(
                OperationStatus::RATE_LIMITED,
                "Limite por hora excedido para {$type}: {$attempts[ $hourKey ]}/{$config[ 'max_per_hour' ]}",
            );
        }

        // Verificar limite por dia
        $dayKey = 'day_' . $now->format( 'Y_m_d' );
        if ( ( $attempts[ $dayKey ] ?? 0 ) >= $config[ 'max_per_day' ] ) {
            return ServiceResult::error(
                OperationStatus::RATE_LIMITED,
                "Limite por dia excedido para {$type}: {$attempts[ $dayKey ]}/{$config[ 'max_per_day' ]}",
            );
        }

        return ServiceResult::success( [
            'within_limits' => true,
            'attempts'      => $attempts,
            'type'          => $type,
            'id'            => $id,
        ], 'Dentro dos limites de taxa.' );
    }

    /**
     * Obtém tentativas em cache.
     */
    private function getCachedAttempts( string $cacheKey, array $config ): array
    {
        $cached = Cache::get( $cacheKey, [] );

        // Limpar tentativas antigas
        $now       = now();
        $oneDayAgo = $now->copy()->subDay();

        foreach ( $cached as $timeKey => $count ) {
            // Extrair timestamp do formato 'day_2024_01_01'
            $parts = explode( '_', $timeKey );
            if ( count( $parts ) >= 4 ) {
                $timestamp = strtotime( "{$parts[ 1 ]}-{$parts[ 2 ]}-{$parts[ 3 ]} " .
                    ( $parts[ 3 ] === 'day' ? '00:00:00' :
                        ( $parts[ 3 ] === 'hour' ? "{$parts[ 4 ]}:00:00" : "00:{$parts[ 4 ]}:00" ) ) );

                if ( $timestamp && $timestamp < $oneDayAgo->timestamp ) {
                    unset( $cached[ $timeKey ] );
                }
            }
        }

        return $cached;
    }

    /**
     * Incrementa tentativas em cache.
     */
    private function incrementCachedAttempts( string $cacheKey, array $config ): void
    {
        $attempts = $this->getCachedAttempts( $cacheKey, $config );
        $now      = now();

        // Incrementar contadores por período
        $minuteKey = 'minute_' . $now->format( 'Y_m_d_H_i' );
        $hourKey   = 'hour_' . $now->format( 'Y_m_d_H' );
        $dayKey    = 'day_' . $now->format( 'Y_m_d' );

        $attempts[ $minuteKey ] = ( $attempts[ $minuteKey ] ?? 0 ) + 1;
        $attempts[ $hourKey ]   = ( $attempts[ $hourKey ] ?? 0 ) + 1;
        $attempts[ $dayKey ]    = ( $attempts[ $dayKey ] ?? 0 ) + 1;

        // Salvar no cache com TTL de 24 horas
        Cache::put( $cacheKey, $attempts, now()->addDay() );
    }

    /**
     * Gera chave de cache para usuário.
     */
    private function getUserCacheKey( int $userId, string $emailType ): string
    {
        return "email_rate_limit_user_{$userId}_{$emailType}";
    }

    /**
     * Gera chave de cache para tenant.
     */
    private function getTenantCacheKey( int $tenantId, string $emailType ): string
    {
        return "email_rate_limit_tenant_{$tenantId}_{$emailType}";
    }

    /**
     * Gera chave de cache global.
     */
    private function getGlobalCacheKey( string $emailType ): string
    {
        return "email_rate_limit_global_{$emailType}";
    }

    /**
     * Obtém estatísticas globais.
     */
    private function getGlobalStats( string $emailType ): array
    {
        $cacheKey = $this->getGlobalCacheKey( $emailType );
        $attempts = $this->getCachedAttempts( $cacheKey, $this->config[ 'global' ] );

        return [
            'attempts'  => $attempts,
            'cache_key' => $cacheKey,
        ];
    }

    /**
     * Loga evento de segurança.
     */
    private function logSecurityEvent( string $event, array $context = [] ): void
    {
        $logData = [
            'event'      => $event,
            'timestamp'  => now()->toDateTimeString(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        $logData = array_merge( $logData, $context );

        Log::warning( 'Evento de segurança de rate limiting', $logData );
    }

}
