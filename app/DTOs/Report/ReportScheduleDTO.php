<?php

declare(strict_types=1);

namespace App\DTOs\Report;

use App\DTOs\AbstractDTO;
use App\Helpers\DateHelper;
use Carbon\Carbon;

readonly class ReportScheduleDTO extends AbstractDTO
{
    public function __construct(
        public int $report_definition_id,
        public int $user_id,
        public string $frequency,
        public string $cron_expression,
        public array $parameters = [],
        public array $recipients = [],
        public bool $is_active = true,
        public ?Carbon $last_run_at = null,
        public ?Carbon $next_run_at = null,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            report_definition_id: (int) $data['report_definition_id'],
            user_id: (int) $data['user_id'],
            frequency: $data['frequency'],
            cron_expression: $data['cron_expression'],
            parameters: is_string($data['parameters'] ?? []) ? json_decode($data['parameters'], true) : ($data['parameters'] ?? []),
            recipients: is_string($data['recipients'] ?? []) ? json_decode($data['recipients'], true) : ($data['recipients'] ?? []),
            is_active: isset($data['is_active']) ? (bool) $data['is_active'] : true,
            last_run_at: DateHelper::toCarbon($data['last_run_at'] ?? null),
            next_run_at: DateHelper::toCarbon($data['next_run_at'] ?? null),
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'report_definition_id' => $this->report_definition_id,
            'user_id' => $this->user_id,
            'frequency' => $this->frequency,
            'cron_expression' => $this->cron_expression,
            'parameters' => $this->parameters,
            'recipients' => $this->recipients,
            'is_active' => $this->is_active,
            'last_run_at' => $this->last_run_at?->toDateTimeString(),
            'next_run_at' => $this->next_run_at?->toDateTimeString(),
            'tenant_id' => $this->tenant_id,
        ];
    }
}
