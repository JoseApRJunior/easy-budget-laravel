<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Model para histórico de alertas de monitoramento, scoped por tenant.
 */
class MonitoringAlertHistory extends Model
{
    use HasFactory, TenantScoped;

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        static::bootTenantScoped();
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'monitoring_alerts_history';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'alert_type',
        'severity',
        'middleware_name',
        'endpoint',
        'metric_name',
        'metric_value',
        'threshold_value',
        'message',
        'additional_data',
        'is_resolved',
        'resolved_at',
        'resolved_by',
        'resolution_notes',
        'notification_sent',
        'notification_sent_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metric_value'         => 'decimal:3',
        'threshold_value'      => 'decimal:3',
        'additional_data'      => 'array',
        'is_resolved'          => 'boolean',
        'tenant_id'            => 'integer',
        'resolved_by'          => 'integer',
        'notification_sent'    => 'boolean',
        'resolved_at'          => 'immutable_datetime',
        'notification_sent_at' => 'immutable_datetime',
        'created_at'           => 'immutable_datetime',
        'updated_at'           => 'datetime',
    ];

    /**
     * Regras de validação para o modelo MonitoringAlertHistory.
     */
    public static function businessRules(): array
    {
        return [
            'tenant_id'            => 'required|integer|exists:tenants,id',
            'alert_type'           => 'required|in:performance,error,security,availability,resource',
            'severity'             => 'required|in:low,medium,high,critical',
            'middleware_name'      => 'required|string|max:100',
            'endpoint'             => 'nullable|string|max:255',
            'metric_name'          => 'required|string|max:100',
            'metric_value'         => 'required|numeric|min:0',
            'threshold_value'      => 'required|numeric|min:0',
            'message'              => 'required|string',
            'additional_data'      => 'nullable|array',
            'is_resolved'          => 'boolean',
            'resolved_at'          => 'nullable|date',
            'resolved_by'          => 'nullable|integer|exists:users,id',
            'resolution_notes'     => 'nullable|string',
            'notification_sent'    => 'boolean',
            'notification_sent_at' => 'nullable|date',
        ];
    }

    /**
     * Get the tenant that owns the MonitoringAlertHistory.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the user who resolved the alert.
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo( User::class, 'resolved_by' );
    }

    /**
     * Scope para alertas não resolvidos.
     */
    public function scopeUnresolved( $query )
    {
        return $query->where( 'is_resolved', false );
    }

    /**
     * Scope para alertas resolvidos.
     */
    public function scopeResolved( $query )
    {
        return $query->where( 'is_resolved', true );
    }

    /**
     * Scope para alertas por tipo.
     */
    public function scopeByType( $query, string $type )
    {
        return $query->where( 'alert_type', $type );
    }

    /**
     * Scope para alertas por severidade.
     */
    public function scopeBySeverity( $query, string $severity )
    {
        return $query->where( 'severity', $severity );
    }

    /**
     * Scope para alertas críticos e high.
     */
    public function scopeCritical( $query )
    {
        return $query->whereIn( 'severity', [ 'high', 'critical' ] );
    }

    /**
     * Scope para alertas por período.
     */
    public function scopePeriod( $query, Carbon $startDate, Carbon $endDate )
    {
        return $query->whereBetween( 'created_at', [ $startDate, $endDate ] );
    }

    /**
     * Scope para alertas por middleware.
     */
    public function scopeByMiddleware( $query, string $middlewareName )
    {
        return $query->where( 'middleware_name', $middlewareName );
    }

    /**
     * Scope para alertas por endpoint.
     */
    public function scopeByEndpoint( $query, string $endpoint )
    {
        return $query->where( 'endpoint', $endpoint );
    }

    /**
     * Marca o alerta como resolvido.
     */
    public function markAsResolved( User $user, string $notes = null ): void
    {
        $this->update( [
            'is_resolved'      => true,
            'resolved_at'      => now(),
            'resolved_by'      => $user->id,
            'resolution_notes' => $notes,
        ] );
    }

    /**
     * Verifica se o alerta está resolvido.
     */
    public function isResolved(): bool
    {
        return $this->is_resolved;
    }

    /**
     * Obtém estatísticas de alertas por período.
     */
    public static function getAlertStats( int $tenantId, Carbon $startDate, Carbon $endDate ): array
    {
        return static::where( 'tenant_id', $tenantId )
            ->period( $startDate, $endDate )
            ->selectRaw( '
                COUNT(*) as total_alerts,
                COUNT(CASE WHEN is_resolved = 1 THEN 1 END) as resolved_alerts,
                COUNT(CASE WHEN is_resolved = 0 THEN 1 END) as unresolved_alerts,
                COUNT(CASE WHEN severity = "critical" THEN 1 END) as critical_alerts,
                COUNT(CASE WHEN severity = "high" THEN 1 END) as high_alerts,
                COUNT(CASE WHEN severity = "medium" THEN 1 END) as medium_alerts,
                COUNT(CASE WHEN severity = "low" THEN 1 END) as low_alerts,
                COUNT(CASE WHEN alert_type = "performance" THEN 1 END) as performance_alerts,
                COUNT(CASE WHEN alert_type = "error" THEN 1 END) as error_alerts,
                COUNT(CASE WHEN alert_type = "security" THEN 1 END) as security_alerts,
                COUNT(CASE WHEN alert_type = "availability" THEN 1 END) as availability_alerts,
                COUNT(CASE WHEN alert_type = "resource" THEN 1 END) as resource_alerts
            ' )
            ->first()
            ->toArray();
    }

    /**
     * Obtém alertas mais frequentes por middleware.
     */
    public static function getMostFrequentMiddlewares( int $tenantId, Carbon $startDate, Carbon $endDate, int $limit = 10 ): array
    {
        return static::where( 'tenant_id', $tenantId )
            ->period( $startDate, $endDate )
            ->selectRaw( 'middleware_name, COUNT(*) as alert_count, MAX(severity) as max_severity' )
            ->groupBy( 'middleware_name' )
            ->orderBy( 'alert_count', 'desc' )
            ->limit( $limit )
            ->get()
            ->toArray();
    }

    /**
     * Obtém tempo médio de resolução de alertas.
     */
    public static function getAverageResolutionTime( int $tenantId, Carbon $startDate, Carbon $endDate ): float
    {
        $result = static::where( 'tenant_id', $tenantId )
            ->period( $startDate, $endDate )
            ->where( 'is_resolved', true )
            ->whereNotNull( 'resolved_at' )
            ->selectRaw( 'AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_resolution_time' )
            ->first();

        return round( $result->avg_resolution_time ?? 0, 2 );
    }

    /**
     * Verifica se o alerta requer notificação.
     */
    public function requiresNotification(): bool
    {
        return !$this->notification_sent && in_array( $this->severity, [ 'high', 'critical' ] );
    }

    /**
     * Marca notificação como enviada.
     */
    public function markNotificationAsSent(): void
    {
        $this->update( [
            'notification_sent'    => true,
            'notification_sent_at' => now(),
        ] );
    }

    /**
     * Obtém uma descrição formatada do alerta.
     */
    public function getFormattedDescription(): string
    {
        return sprintf(
            '[%s] %s - %s: %.3f (threshold: %.3f) - %s',
            strtoupper( $this->severity ),
            $this->alert_type,
            $this->metric_name,
            $this->metric_value,
            $this->threshold_value,
            $this->is_resolved ? 'RESOLVIDO' : 'PENDENTE'
        );
    }

}
