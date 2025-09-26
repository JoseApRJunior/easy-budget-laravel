<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'title',
        'description',
        'component',
        'endpoint',
        'method',
        'current_value',
        'threshold_value',
        'unit',
        'metadata',
        'message',
        'status',
        'acknowledged_by',
        'acknowledged_at',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
        'occurrence_count',
        'first_occurrence',
        'last_occurrence',
        'resolved',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'current_value'    => 'decimal:3',
        'threshold_value'  => 'decimal:3',
        'metadata'         => 'array',
        'resolved'         => 'boolean',
        'tenant_id'        => 'integer',
        'resolved_by'      => 'integer',
        'acknowledged_by'  => 'integer',
        'acknowledged_at'  => 'immutable_datetime',
        'resolved_at'      => 'immutable_datetime',
        'occurrence_count' => 'integer',
        'first_occurrence' => 'immutable_datetime',
        'last_occurrence'  => 'immutable_datetime',
        'unit'             => 'string',
        'status'           => 'string',
        'created_at'       => 'immutable_datetime',
        'updated_at'       => 'immutable_datetime',
    ];


        /**
     * Regras de validação para o modelo Plan.
     */
    public static function businessRules(): array
    {
        return [

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

}
