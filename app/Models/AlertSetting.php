<?php

namespace App\Models;

use App\Enums\AlertSeverityEnum;
use App\Enums\AlertTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlertSetting extends Model
{
    use HasFactory;

    protected $table = 'alert_settings';

    protected $fillable = [
        'tenant_id',
        'alert_type',
        'metric_name',
        'severity',
        'threshold_value',
        'evaluation_window_minutes',
        'cooldown_minutes',
        'is_active',
        'notification_channels',
        'notification_emails',
        'slack_webhook_url',
        'custom_message',
    ];

    protected $casts = [
        'threshold_value' => 'decimal:3',
        'evaluation_window_minutes' => 'integer',
        'cooldown_minutes' => 'integer',
        'is_active' => 'boolean',
        'notification_channels' => 'array',
        'notification_emails' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function monitoringAlertsHistory(): HasMany
    {
        return $this->hasMany(MonitoringAlertsHistory::class, 'alert_setting_id');
    }

    public function getAlertTypeEnum(): AlertTypeEnum
    {
        return AlertTypeEnum::from($this->alert_type);
    }

    public function getSeverityEnum(): AlertSeverityEnum
    {
        return AlertSeverityEnum::from($this->severity);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, AlertTypeEnum $type)
    {
        return $query->where('alert_type', $type->value);
    }

    public function scopeBySeverity($query, AlertSeverityEnum $severity)
    {
        return $query->where('severity', $severity->value);
    }

    public function shouldNotify(): bool
    {
        return $this->getSeverityEnum()->shouldNotify();
    }

    public function getNotificationDelay(): int
    {
        return $this->getSeverityEnum()->notificationDelay();
    }

    public function isInCooldown(): bool
    {
        $lastAlert = $this->monitoringAlertsHistory()
            ->where('created_at', '>=', now()->subMinutes($this->cooldown_minutes))
            ->exists();

        return $lastAlert;
    }
}
