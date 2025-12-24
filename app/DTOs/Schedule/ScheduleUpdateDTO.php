<?php

declare(strict_types=1);

namespace App\DTOs\Schedule;

use App\DTOs\AbstractDTO;

readonly class ScheduleUpdateDTO extends AbstractDTO
{
    public function __construct(
        public ?int $service_id = null,
        public ?string $start_date_time = null,
        public ?string $end_date_time = null,
        public ?string $location = null,
        public ?string $status = null,
        public ?string $cancellation_reason = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            service_id: isset($data['service_id']) ? (int) $data['service_id'] : null,
            start_date_time: $data['start_date_time'] ?? null,
            end_date_time: $data['end_date_time'] ?? null,
            location: $data['location'] ?? null,
            status: $data['status'] ?? null,
            cancellation_reason: $data['cancellation_reason'] ?? null
        );
    }
}
