<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Modelo para logs de auditoria do sistema
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $user_id
 * @property string $action
 * @property string $model_type
 * @property int|null $model_id
 * @property array|null $old_values
 * @property array|null $new_values
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array|null $metadata
 * @property string|null $description
 * @property string $severity
 * @property string|null $category
 * @property bool $is_system_action
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AuditLog extends Model
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
     */
    protected $table = 'audit_logs';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'metadata',
        'description',
        'severity',
        'category',
        'is_system_action',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'tenant_id'        => 'integer',
        'user_id'          => 'integer',
        'model_id'         => 'integer',
        'old_values'       => 'array',
        'new_values'       => 'array',
        'metadata'         => 'array',
        'is_system_action' => 'boolean',
        'created_at'       => 'immutable_datetime',
        'updated_at'       => 'datetime',
    ];

    /**
     * Default values for attributes.
     */
    protected $attributes = [
        'severity'         => 'info',
        'is_system_action' => false,
    ];

    /**
     * Available severity levels.
     */
    public const SEVERITY_LEVELS = [
        'low',
        'info',
        'warning',
        'high',
        'critical',
    ];

    /**
     * Available action categories.
     */
    public const ACTION_CATEGORIES = [
        'authentication',
        'authorization',
        'data_modification',
        'data_access',
        'system',
        'security',
        'settings',
        'user_management',
        'file_operations',
        'backup_restore',
    ];

    /**
     * Common audit actions.
     */
    public const COMMON_ACTIONS = [
        // Authentication
        'login'               => 'authentication',
        'logout'              => 'authentication',
        'login_failed'        => 'security',
        'password_changed'    => 'security',
        'password_reset'      => 'security',

        // Data operations
        'created'             => 'data_modification',
        'updated'             => 'data_modification',
        'deleted'             => 'data_modification',
        'restored'            => 'data_modification',
        'archived'            => 'data_modification',

        // Settings
        'settings_updated'    => 'settings',
        'settings_restored'   => 'settings',
        'profile_updated'     => 'settings',

        // Security
        'two_factor_enabled'  => 'security',
        'two_factor_disabled' => 'security',
        'session_terminated'  => 'security',
        'permission_granted'  => 'authorization',
        'permission_revoked'  => 'authorization',

        // File operations
        'file_uploaded'       => 'file_operations',
        'file_deleted'        => 'file_operations',
        'avatar_updated'      => 'file_operations',

        // Backup operations
        'backup_created'      => 'backup_restore',
        'backup_restored'     => 'backup_restore',
        'backup_deleted'      => 'backup_restore',

        // System
        'system_maintenance'  => 'system',
        'cache_cleared'       => 'system',
        'migration_ran'       => 'system',
    ];

    /**
     * Regras de validação para o modelo.
     */
    public static function businessRules(): array
    {
        return [
            'tenant_id'        => 'required|integer|exists:tenants,id',
            'user_id'          => 'required|integer|exists:users,id',
            'action'           => 'required|string|max:100',
            'model_type'       => 'nullable|string|max:255',
            'model_id'         => 'nullable|integer',
            'old_values'       => 'nullable|array',
            'new_values'       => 'nullable|array',
            'ip_address'       => 'nullable|ip|max:45',
            'user_agent'       => 'nullable|string|max:500',
            'metadata'         => 'nullable|array',
            'description'      => 'nullable|string|max:1000',
            'severity'         => 'required|in:' . implode( ',', self::SEVERITY_LEVELS ),
            'category'         => 'nullable|in:' . implode( ',', self::ACTION_CATEGORIES ),
            'is_system_action' => 'boolean',
        ];
    }

    /**
     * Get the user that performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo( User::class);
    }

    /**
     * Get the tenant that owns the audit log.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the auditable model.
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get formatted IP address with location if available.
     */
    public function getFormattedIpAddressAttribute(): string
    {
        if ( !$this->ip_address ) {
            return 'N/A';
        }

        // Se temos metadata com localização, inclui
        if ( $this->metadata && isset( $this->metadata[ 'location' ] ) ) {
            return $this->ip_address . ' (' . $this->metadata[ 'location' ] . ')';
        }

        return $this->ip_address;
    }

    /**
     * Get user agent information.
     */
    public function getUserAgentInfoAttribute(): array
    {
        if ( !$this->user_agent ) {
            return [];
        }

        // Parse básico do user agent
        return [
            'browser' => $this->extractBrowser( $this->user_agent ),
            'os'      => $this->extractOS( $this->user_agent ),
            'device'  => $this->extractDevice( $this->user_agent ),
            'raw'     => $this->user_agent,
        ];
    }

    /**
     * Get changes summary.
     */
    public function getChangesSummaryAttribute(): array
    {
        $changes = [];

        if ( !$this->old_values || !$this->new_values ) {
            return $changes;
        }

        foreach ( $this->new_values as $key => $newValue ) {
            $oldValue = $this->old_values[ $key ] ?? null;

            if ( $oldValue !== $newValue ) {
                $changes[ $key ] = [
                    'old'  => $oldValue,
                    'new'  => $newValue,
                    'type' => $this->determineChangeType( $oldValue, $newValue ),
                ];
            }
        }

        return $changes;
    }

    /**
     * Get severity badge color.
     */
    public function getSeverityColorAttribute(): string
    {
        return match ( $this->severity ) {
            'low'      => 'gray',
            'info'     => 'blue',
            'warning'  => 'yellow',
            'high'     => 'red',
            'critical' => 'red',
            default    => 'gray',
        };
    }

    /**
     * Get category icon.
     */
    public function getCategoryIconAttribute(): string
    {
        return match ( $this->category ) {
            'authentication'    => 'bi-shield-check',
            'authorization'     => 'bi-key',
            'data_modification' => 'bi-pencil-square',
            'data_access'       => 'bi-eye',
            'system'            => 'bi-gear',
            'security'          => 'bi-shield-exclamation',
            'settings'          => 'bi-gear',
            'user_management'   => 'bi-people',
            'file_operations'   => 'bi-file-earmark',
            'backup_restore'    => 'bi-archive',
            default             => 'bi-info-circle',
        };
    }

    /**
     * Check if action is security related.
     */
    public function isSecurityRelated(): bool
    {
        $securityActions = [
            'login_failed',
            'password_changed',
            'password_reset',
            'two_factor_enabled',
            'two_factor_disabled',
            'session_terminated',
            'permission_granted',
            'permission_revoked',
        ];

        return in_array( $this->action, $securityActions ) ||
            $this->category === 'security' ||
            $this->severity === 'critical' ||
            $this->severity === 'high';
    }

    /**
     * Check if action is data modification.
     */
    public function isDataModification(): bool
    {
        $dataActions = [
            'created',
            'updated',
            'deleted',
            'restored',
            'archived',
        ];

        return in_array( $this->action, $dataActions ) ||
            $this->category === 'data_modification';
    }

    /**
     * Create audit log entry.
     */
    public static function log(
        string $action,
        ?Model $model = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null,
    ): static {
        $user = auth()->user();

        if ( !$user ) {
            return new static();
        }

        $category = self::COMMON_ACTIONS[ $action ] ?? 'system';

        return static::create( [
            'tenant_id'  => $user->tenant_id,
            'user_id'    => $user->id,
            'action'     => $action,
            'model_type' => $model ? get_class( $model ) : null,
            'model_id'   => $model ? $model->getKey() : null,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata'   => $metadata,
            'category'   => $category,
            'severity'   => static::determineSeverity( $action ),
        ] );
    }

    /**
     * Determine severity level for action.
     */
    protected static function determineSeverity( string $action ): string
    {
        $criticalActions = [
            'login_failed',
            'password_changed',
            'two_factor_disabled',
            'permission_granted',
            'permission_revoked',
        ];

        $highActions = [
            'deleted',
            'session_terminated',
            'backup_restored',
        ];

        $warningActions = [
            'created',
            'updated',
            'two_factor_enabled',
        ];

        if ( in_array( $action, $criticalActions ) ) {
            return 'critical';
        }

        if ( in_array( $action, $highActions ) ) {
            return 'high';
        }

        if ( in_array( $action, $warningActions ) ) {
            return 'warning';
        }

        return 'info';
    }

    /**
     * Extract browser from user agent.
     */
    private function extractBrowser( string $userAgent ): string
    {
        if ( stripos( $userAgent, 'Chrome' ) !== false ) {
            return 'Chrome';
        }
        if ( stripos( $userAgent, 'Firefox' ) !== false ) {
            return 'Firefox';
        }
        if ( stripos( $userAgent, 'Safari' ) !== false ) {
            return 'Safari';
        }
        if ( stripos( $userAgent, 'Edge' ) !== false ) {
            return 'Edge';
        }

        return 'Desconhecido';
    }

    /**
     * Extract OS from user agent.
     */
    private function extractOS( string $userAgent ): string
    {
        if ( stripos( $userAgent, 'Windows' ) !== false ) {
            return 'Windows';
        }
        if ( stripos( $userAgent, 'Mac' ) !== false ) {
            return 'macOS';
        }
        if ( stripos( $userAgent, 'Linux' ) !== false ) {
            return 'Linux';
        }
        if ( stripos( $userAgent, 'Android' ) !== false ) {
            return 'Android';
        }
        if ( stripos( $userAgent, 'iOS' ) !== false ) {
            return 'iOS';
        }

        return 'Desconhecido';
    }

    /**
     * Extract device from user agent.
     */
    private function extractDevice( string $userAgent ): string
    {
        if ( stripos( $userAgent, 'Mobile' ) !== false ) {
            return 'Mobile';
        }
        if ( stripos( $userAgent, 'Tablet' ) !== false ) {
            return 'Tablet';
        }

        return 'Desktop';
    }

    /**
     * Determine change type.
     */
    private function determineChangeType( $oldValue, $newValue ): string
    {
        if ( $oldValue === null && $newValue !== null ) {
            return 'created';
        }

        if ( $oldValue !== null && $newValue === null ) {
            return 'deleted';
        }

        return 'modified';
    }

    /**
     * Scope for security related logs.
     */
    public function scopeSecurity( $query )
    {
        return $query->where( function ( $q ) {
            $q->whereIn( 'action', [ 'login_failed', 'password_changed', 'two_factor_enabled', 'two_factor_disabled' ] )
                ->orWhere( 'category', 'security' )
                ->orWhereIn( 'severity', [ 'high', 'critical' ] );
        } );
    }

    /**
     * Scope for data modification logs.
     */
    public function scopeDataModifications( $query )
    {
        return $query->where( function ( $q ) {
            $q->whereIn( 'action', [ 'created', 'updated', 'deleted', 'restored' ] )
                ->orWhere( 'category', 'data_modification' );
        } );
    }

    /**
     * Scope for recent logs.
     */
    public function scopeRecent( $query, int $days = 7 )
    {
        return $query->where( 'created_at', '>=', now()->subDays( $days ) );
    }

}
