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
        'status',
        'acknowledged_by',
        'acknowledged_at',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
        'occurrence_count',
        'first_occurrence',
        'last_occurrence',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'current_value'    => 'float',
        'threshold_value'  => 'float',
        'metadata'         => 'array',
        'acknowledged_at'  => 'datetime',
        'resolved_at'      => 'datetime',
        'first_occurrence' => 'datetime',
        'last_occurrence'  => 'datetime',
        'tenant_id'        => 'integer',
        'created_at'       => 'immutable_datetime',
        'updated_at'       => 'immutable_datetime',
    ];

    /**
     * Get the tenant that owns the MonitoringAlertHistory.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the user who acknowledged the alert.
     */
    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo( User::class, 'acknowledged_by' );
    }

    /**
     * Get the user who resolved the alert.
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo( User::class, 'resolved_by' );
    }

}
