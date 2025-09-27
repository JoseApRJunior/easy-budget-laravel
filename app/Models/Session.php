<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Model para gerenciamento de sessões de usuário.
 * Implementa funcionalidades de sessão customizada para o sistema.
 */
class Session extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sessions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'session_token',
        'ip_address',
        'user_agent',
        'last_activity',
        'expires_at',
        'is_active',
        'session_data',
        'pay_load',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_id'       => 'integer',
        'ip_address'    => 'string',
        'user_agent'    => 'string',
        'session_token' => 'string',
        'is_active'     => 'boolean',
        'session_data'  => 'array',
        'pay_load'      => 'array',
        'created_at'    => 'immutable_datetime',
        'last_activity' => 'immutable_datetime',
        'expires_at'    => 'immutable_datetime',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Regras de validação para o modelo Session.
     */
    public static function businessRules(): array
    {
        return [
            'user_id'       => 'required|integer|exists:users,id',
            'session_token' => 'required|string|max:255|unique:sessions,session_token',
            'ip_address'    => 'nullable|string|max:45',
            'user_agent'    => 'nullable|string',
            'last_activity' => 'required|date',
            'expires_at'    => 'required|date|after:last_activity',
            'is_active'     => 'boolean',
            'session_data'  => 'nullable|array',
            'pay_load'      => 'nullable|array',
        ];
    }

    /**
     * Get the user that owns the Session.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo( User::class);
    }

    /**
     * Scope para sessões ativas.
     */
    public function scopeActive( $query )
    {
        return $query->where( 'is_active', true )
            ->where( 'expires_at', '>', now() );
    }

    /**
     * Scope para sessões expiradas.
     */
    public function scopeExpired( $query )
    {
        return $query->where( 'expires_at', '<=', now() );
    }

    /**
     * Scope para sessões inativas.
     */
    public function scopeInactive( $query )
    {
        return $query->where( 'is_active', false );
    }

    /**
     * Scope para sessões por usuário.
     */
    public function scopeByUser( $query, int $userId )
    {
        return $query->where( 'user_id', $userId );
    }

    /**
     * Scope para sessões por token.
     */
    public function scopeByToken( $query, string $token )
    {
        return $query->where( 'session_token', $token );
    }

    /**
     * Scope para sessões por endereço IP.
     */
    public function scopeByIpAddress( $query, string $ipAddress )
    {
        return $query->where( 'ip_address', $ipAddress );
    }

    /**
     * Verifica se a sessão está ativa e válida.
     */
    public function isValid(): bool
    {
        return $this->is_active &&
            $this->expires_at->isFuture() &&
            $this->last_activity->diffInMinutes( now() ) < 120; // 2 horas de inatividade
    }

    /**
     * Verifica se a sessão está expirada.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Estende a validade da sessão.
     */
    public function extend( int $minutes = 60 ): void
    {
        $this->update( [
            'expires_at'    => Carbon::now()->addMinutes( $minutes ),
            'last_activity' => Carbon::now(),
        ] );
    }

    /**
     * Desativa a sessão.
     */
    public function deactivate(): void
    {
        $this->update( [
            'is_active'  => false,
            'expires_at' => now(),
        ] );
    }

    /**
     * Atualiza a última atividade da sessão.
     */
    public function updateLastActivity(): void
    {
        $this->update( [
            'last_activity' => now(),
        ] );
    }

    /**
     * Atualiza os dados da sessão.
     */
    public function updateSessionData( array $data ): void
    {
        $this->update( [
            'session_data'  => array_merge( $this->session_data ?? [], $data ),
            'last_activity' => now(),
        ] );
    }

    /**
     * Obtém dados específicos da sessão.
     */
    public function getSessionData( string $key = null )
    {
        if ( $key ) {
            return $this->session_data[ $key ] ?? null;
        }

        return $this->session_data;
    }

    /**
     * Define dados específicos da sessão.
     */
    public function setSessionData( string $key, $value ): void
    {
        $sessionData         = $this->session_data ?? [];
        $sessionData[ $key ] = $value;

        $this->update( [
            'session_data'  => $sessionData,
            'last_activity' => now(),
        ] );
    }

    /**
     * Remove dados específicos da sessão.
     */
    public function removeSessionData( string $key ): void
    {
        if ( isset( $this->session_data[ $key ] ) ) {
            unset( $this->session_data[ $key ] );

            $this->update( [
                'session_data'  => $this->session_data,
                'last_activity' => now(),
            ] );
        }
    }

    /**
     * Obtém estatísticas de sessões por período.
     */
    public static function getSessionStats( Carbon $startDate, Carbon $endDate ): array
    {
        return static::whereBetween( 'created_at', [ $startDate, $endDate ] )
            ->selectRaw( '
                COUNT(*) as total_sessions,
                COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_sessions,
                COUNT(CASE WHEN is_active = 0 THEN 1 END) as inactive_sessions,
                COUNT(CASE WHEN expires_at <= NOW() THEN 1 END) as expired_sessions,
                AVG(TIMESTAMPDIFF(MINUTE, created_at, expires_at)) as avg_duration_minutes,
                COUNT(DISTINCT user_id) as unique_users,
                COUNT(DISTINCT ip_address) as unique_ips
            ' )
            ->first()
            ->toArray();
    }

    /**
     * Obtém sessões mais ativas por usuário.
     */
    public static function getMostActiveUsers( Carbon $startDate, Carbon $endDate, int $limit = 10 ): array
    {
        return static::whereBetween( 'created_at', [ $startDate, $endDate ] )
            ->selectRaw( 'user_id, COUNT(*) as session_count, MAX(last_activity) as last_activity' )
            ->groupBy( 'user_id' )
            ->orderBy( 'session_count', 'desc' )
            ->limit( $limit )
            ->get()
            ->toArray();
    }

    /**
     * Obtém sessões por endereço IP (para análise de segurança).
     */
    public static function getSessionsByIp( string $ipAddress, Carbon $startDate, Carbon $endDate, int $limit = 50 ): array
    {
        return static::where( 'ip_address', $ipAddress )
            ->whereBetween( 'created_at', [ $startDate, $endDate ] )
            ->orderBy( 'created_at', 'desc' )
            ->limit( $limit )
            ->get()
            ->toArray();
    }

    /**
     * Limpa sessões expiradas.
     */
    public static function cleanupExpiredSessions(): int
    {
        return static::expired()
            ->where( 'expires_at', '<', Carbon::now()->subDays( 7 ) ) // Remove sessões expiradas há mais de 7 dias
            ->delete();
    }

    /**
     * Obtém tempo de vida restante da sessão em minutos.
     */
    public function getRemainingLifetime(): int
    {
        if ( $this->isExpired() ) {
            return 0;
        }

        return (int) $this->expires_at->diffInMinutes( now(), false );
    }

    /**
     * Verifica se a sessão precisa ser renovada (expira em menos de 30 minutos).
     */
    public function needsRenewal(): bool
    {
        return $this->getRemainingLifetime() < 30;
    }

    /**
     * Renova a sessão por um período específico.
     */
    public function renew( int $minutes = 120 ): void
    {
        $this->update( [
            'expires_at'    => now()->addMinutes( $minutes ),
            'last_activity' => now(),
            'is_active'     => true,
        ] );
    }

    /**
     * Obtém uma descrição formatada da sessão.
     */
    public function getFormattedDescription(): string
    {
        return sprintf(
            'Sessão %s - Usuário ID: %d - IP: %s - %s - Expira: %s',
            $this->session_token,
            $this->user_id,
            $this->ip_address ?? 'N/A',
            $this->is_active ? 'ATIVA' : 'INATIVA',
            $this->expires_at->format( 'Y-m-d H:i:s' ),
        );
    }

}
