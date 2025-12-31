<?php

declare(strict_types=1);

namespace App\DTOs\Report;

use App\DTOs\AbstractDTO;
use App\Helpers\DateHelper;
use Carbon\Carbon;

readonly class ReportExecutionDTO extends AbstractDTO
{
    public function __construct(
        public int $report_definition_id,
        public int $user_id,
        public string $status,
        public ?Carbon $started_at = null,
        public ?Carbon $completed_at = null,
        public array $parameters = [],
        public ?string $result_path = null,
        public ?int $result_size = null,
        public ?int $execution_time_ms = null,
        public ?string $error_message = null,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            report_definition_id: (int) $data['report_definition_id'],
            user_id: (int) $data['user_id'],
            status: $data['status'],
            started_at: DateHelper::toCarbon($data['started_at'] ?? null),
            completed_at: DateHelper::toCarbon($data['completed_at'] ?? null),
            parameters: is_string($data['parameters'] ?? []) ? json_decode($data['parameters'], true) : ($data['parameters'] ?? []),
            result_path: $data['result_path'] ?? null,
            result_size: isset($data['result_size']) ? (int) $data['result_size'] : null,
            execution_time_ms: isset($data['execution_time_ms']) ? (int) $data['execution_time_ms'] : null,
            error_message: $data['error_message'] ?? null,
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'report_definition_id' => $this->report_definition_id,
            'user_id' => $this->user_id,
            'status' => $this->status,
            'started_at' => $this->started_at?->toDateTimeString(),
            'completed_at' => $this->completed_at?->toDateTimeString(),
            'parameters' => $this->parameters,
            'result_path' => $this->result_path,
            'result_size' => $this->result_size,
            'execution_time_ms' => $this->execution_time_ms,
            'error_message' => $this->error_message,
            'tenant_id' => $this->tenant_id,
        ];
    }
}
