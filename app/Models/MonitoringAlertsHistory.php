<?php

namespace App\Models;

use App\Enums\AlertSeverityEnum;
use App\Enums\AlertTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringAlertsHistory extends Model
{
    use HasFactory;

    protected $table = 'monitoring_alerts_history';

    protected $fillable = [
        'tenant_id',
        'alert_setting_id',
        'resolved_by',
        'alert_type',
        'severity',
        'metric_name',
        'metric_value',
        'threshold_value',
        'message',
        'additional_data',
        'is_resolved',
        'resolved_at',
        'resolution_notes',
        'notification_sent',
        'notification_sent_at',
    ];

    protected $casts = [
        'metric_value' => 'decimal:3',
        'threshold_value' => 'decimal:3',
        'additional_data' => 'array',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'notification_sent' => 'boolean',
        'notification_sent_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function alertSetting(): BelongsTo
    {
        return $this->belongsTo(AlertSetting::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function getAlertTypeEnum(): AlertTypeEnum
    {
        return AlertTypeEnum::from($this->alert_type);
    }

    public function getSeverityEnum(): AlertSeverityEnum
    {
        return AlertSeverityEnum::from($this->severity);
    }

    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeResolved($query)
    {
        return $query->where('is_resolved', true);
    }

    public function scopeByType($query, AlertTypeEnum $type)
    {
        return $query->where('alert_type', $type->value);
    }

    public function scopeBySeverity($query, AlertSeverityEnum $severity)
    {
        return $query->where('severity', $severity->value);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', AlertSeverityEnum::CRITICAL->value);
    }

    public function scopeRecent($query, int $minutes = 60)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }

    public function markAsResolved(int $resolvedBy, ?string $resolutionNotes = null): bool
    {
        return $this->update([
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolved_by' => $resolvedBy,
            'resolution_notes' => $resolutionNotes,
        ]);
    }

    public function markAsNotified(): bool
    {
        return $this->update([
            'notification_sent' => true,
            'notification_sent_at' => now(),
        ]);
    }

    public function getSeverityColor(): string
    {
        return $this->getSeverityEnum()->color();
    }

    public function getSeverityLabel(): string
    {
        return $this->getSeverityEnum()->label();
    }

    public function getAlertTypeColor(): string
    {
        return $this->getAlertTypeEnum()->color();
    }

    public function getAlertTypeLabel(): string
    {
        return $this->getAlertTypeEnum()->label();
    }

    public function isCritical(): bool
    {
        return $this->severity === AlertSeverityEnum::CRITICAL->value;
    }

    public function isHighPriority(): bool
    {
        return in_array($this->severity, [
            AlertSeverityEnum::ERROR->value,
            AlertSeverityEnum::CRITICAL->value,
        ]);
    }
}
