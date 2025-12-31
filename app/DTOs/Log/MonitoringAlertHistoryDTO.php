<?php

declare(strict_types=1);

namespace App\DTOs\Log;

use App\DTOs\AbstractDTO;
use App\Helpers\DateHelper;
use Carbon\Carbon;

readonly class MonitoringAlertHistoryDTO extends AbstractDTO
{
    public function __construct(
        public string $alert_type,
        public string $severity,
        public string $middleware_name,
        public string $metric_name,
        public float $metric_value,
        public float $threshold_value,
        public string $message,
        public ?string $endpoint = null,
        public ?array $additional_data = null,
        public bool $is_resolved = false,
        public ?Carbon $resolved_at = null,
        public ?int $resolved_by = null,
        public ?string $resolution_notes = null,
        public bool $notification_sent = false,
        public ?Carbon $notification_sent_at = null,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            alert_type: $data['alert_type'],
            severity: $data['severity'],
            middleware_name: $data['middleware_name'],
            metric_name: $data['metric_name'],
            metric_value: (float) $data['metric_value'],
            threshold_value: (float) $data['threshold_value'],
            message: $data['message'],
            endpoint: $data['endpoint'] ?? null,
            additional_data: is_string($data['additional_data'] ?? []) ? json_decode($data['additional_data'], true) : ($data['additional_data'] ?? []),
            is_resolved: isset($data['is_resolved']) ? (bool) $data['is_resolved'] : false,
            resolved_at: DateHelper::toCarbon($data['resolved_at'] ?? null),
            resolved_by: isset($data['resolved_by']) ? (int) $data['resolved_by'] : null,
            resolution_notes: $data['resolution_notes'] ?? null,
            notification_sent: isset($data['notification_sent']) ? (bool) $data['notification_sent'] : false,
            notification_sent_at: DateHelper::toCarbon($data['notification_sent_at'] ?? null),
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'alert_type' => $this->alert_type,
            'severity' => $this->severity,
            'middleware_name' => $this->middleware_name,
            'metric_name' => $this->metric_name,
            'metric_value' => $this->metric_value,
            'threshold_value' => $this->threshold_value,
            'message' => $this->message,
            'endpoint' => $this->endpoint,
            'additional_data' => $this->additional_data,
            'is_resolved' => $this->is_resolved,
            'resolved_at' => $this->resolved_at?->toDateTimeString(),
            'resolved_by' => $this->resolved_by,
            'resolution_notes' => $this->resolution_notes,
            'notification_sent' => $this->notification_sent,
            'notification_sent_at' => $this->notification_sent_at?->toDateTimeString(),
            'tenant_id' => $this->tenant_id,
        ];
    }
}
