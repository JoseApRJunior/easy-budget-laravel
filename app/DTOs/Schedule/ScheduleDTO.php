<?php

declare(strict_types=1);

namespace App\DTOs\Schedule;

use App\DTOs\AbstractDTO;
use Carbon\Carbon;

readonly class ScheduleDTO extends AbstractDTO
{
    public function __construct(
        public int $service_id,
        public string $start_date_time,
        public string $end_date_time,
        public ?int $user_confirmation_token_id = null,
        public ?string $location = null,
        public ?string $status = 'pending',
        public ?string $cancellation_reason = null,
        public ?string $confirmed_at = null,
        public ?string $completed_at = null,
        public ?string $no_show_at = null,
        public ?string $cancelled_at = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            service_id: (int) ($data['service_id'] ?? 0),
            start_date_time: $data['start_date_time'] ?? '',
            end_date_time: $data['end_date_time'] ?? '',
            user_confirmation_token_id: isset($data['user_confirmation_token_id']) ? (int) $data['user_confirmation_token_id'] : null,
            location: $data['location'] ?? null,
            status: $data['status'] ?? 'pending',
            cancellation_reason: $data['cancellation_reason'] ?? null,
            confirmed_at: $data['confirmed_at'] ?? null,
            completed_at: $data['completed_at'] ?? null,
            no_show_at: $data['no_show_at'] ?? null,
            cancelled_at: $data['cancelled_at'] ?? null
        );
    }
}
