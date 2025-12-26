<?php

declare(strict_types=1);

namespace App\DTOs\Settings;

use App\DTOs\AbstractDTO;

readonly class AlertSettingDTO extends AbstractDTO
{
    public function __construct(
        public string $alert_type,
        public string $metric_name,
        public string $severity,
        public float $threshold_value,
        public int $evaluation_window_minutes,
        public int $cooldown_minutes,
        public bool $is_active = true,
        public array $notification_channels = [],
        public array $notification_emails = [],
        public ?string $slack_webhook_url = null,
        public ?string $custom_message = null,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            alert_type: $data['alert_type'],
            metric_name: $data['metric_name'],
            severity: $data['severity'],
            threshold_value: (float) $data['threshold_value'],
            evaluation_window_minutes: (int) $data['evaluation_window_minutes'],
            cooldown_minutes: (int) $data['cooldown_minutes'],
            is_active: isset($data['is_active']) ? (bool) $data['is_active'] : true,
            notification_channels: is_string($data['notification_channels'] ?? []) ? json_decode($data['notification_channels'], true) : ($data['notification_channels'] ?? []),
            notification_emails: is_string($data['notification_emails'] ?? []) ? json_decode($data['notification_emails'], true) : ($data['notification_emails'] ?? []),
            slack_webhook_url: $data['slack_webhook_url'] ?? null,
            custom_message: $data['custom_message'] ?? null,
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'alert_type'                => $this->alert_type,
            'metric_name'               => $this->metric_name,
            'severity'                  => $this->severity,
            'threshold_value'           => $this->threshold_value,
            'evaluation_window_minutes' => $this->evaluation_window_minutes,
            'cooldown_minutes'          => $this->cooldown_minutes,
            'is_active'                 => $this->is_active,
            'notification_channels'     => $this->notification_channels,
            'notification_emails'       => $this->notification_emails,
            'slack_webhook_url'         => $this->slack_webhook_url,
            'custom_message'            => $this->custom_message,
            'tenant_id'                 => $this->tenant_id,
        ];
    }
}
