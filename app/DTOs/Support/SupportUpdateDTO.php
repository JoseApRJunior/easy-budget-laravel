<?php

declare(strict_types=1);

namespace App\DTOs\Support;

use App\DTOs\AbstractDTO;

readonly class SupportUpdateDTO extends AbstractDTO
{
    public function __construct(
        public ?string $status = null,
        public ?string $priority = null,
        public ?string $category = null,
        public ?int $assigned_to = null,
        public ?string $last_response = null,
        public ?string $resolved_at = null,
        public ?string $closed_at = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            status: $data['status'] ?? null,
            priority: $data['priority'] ?? null,
            category: $data['category'] ?? null,
            assigned_to: isset($data['assigned_to']) ? (int) $data['assigned_to'] : null,
            last_response: $data['last_response'] ?? null,
            resolved_at: $data['resolved_at'] ?? null,
            closed_at: $data['closed_at'] ?? null
        );
    }
}
