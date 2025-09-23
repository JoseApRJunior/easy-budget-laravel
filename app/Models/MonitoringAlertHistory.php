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
        'resolved_at'          => 'datetime',
        'notification_sent'    => 'boolean',
        'notification_sent_at' => 'datetime',
        'tenant_id'            => 'integer',
        'resolved_by'          => 'integer',
        'created_at'           => 'immutable_datetime',
        'updated_at'           => 'immutable_datetime',
    ];

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