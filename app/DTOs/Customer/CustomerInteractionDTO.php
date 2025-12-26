<?php

declare(strict_types=1);

namespace App\DTOs\Customer;

use App\DTOs\AbstractDTO;
use Carbon\Carbon;

readonly class CustomerInteractionDTO extends AbstractDTO
{
    public function __construct(
        public int $customer_id,
        public int $user_id,
        public string $type,
        public string $title,
        public string $direction,
        public Carbon $interaction_date,
        public ?string $description = null,
        public ?int $duration_minutes = null,
        public ?string $outcome = null,
        public ?string $next_action = null,
        public ?Carbon $next_action_date = null,
        public array $attachments = [],
        public array $metadata = [],
        public bool $notify_customer = false,
        public bool $is_active = true
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            customer_id: (int) $data['customer_id'],
            user_id: (int) $data['user_id'],
            type: $data['type'],
            title: $data['title'],
            direction: $data['direction'],
            interaction_date: isset($data['interaction_date']) ? Carbon::parse($data['interaction_date']) : now(),
            description: $data['description'] ?? null,
            duration_minutes: isset($data['duration_minutes']) ? (int) $data['duration_minutes'] : null,
            outcome: $data['outcome'] ?? null,
            next_action: $data['next_action'] ?? null,
            next_action_date: isset($data['next_action_date']) ? Carbon::parse($data['next_action_date']) : null,
            attachments: is_string($data['attachments'] ?? []) ? json_decode($data['attachments'], true) : ($data['attachments'] ?? []),
            metadata: is_string($data['metadata'] ?? []) ? json_decode($data['metadata'], true) : ($data['metadata'] ?? []),
            notify_customer: isset($data['notify_customer']) ? (bool) $data['notify_customer'] : false,
            is_active: isset($data['is_active']) ? (bool) $data['is_active'] : true
        );
    }

    public function toArray(): array
    {
        return [
            'customer_id' => $this->customer_id,
            'user_id' => $this->user_id,
            'type' => $this->type,
            'title' => $this->title,
            'direction' => $this->direction,
            'interaction_date' => $this->interaction_date->toDateTimeString(),
            'description' => $this->description,
            'duration_minutes' => $this->duration_minutes,
            'outcome' => $this->outcome,
            'next_action' => $this->next_action,
            'next_action_date' => $this->next_action_date?->toDateTimeString(),
            'attachments' => $this->attachments,
            'metadata' => $this->metadata,
            'notify_customer' => $this->notify_customer,
            'is_active' => $this->is_active,
        ];
    }
}
