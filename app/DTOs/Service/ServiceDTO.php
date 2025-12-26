<?php

declare(strict_types=1);

namespace App\DTOs\Service;

use App\DTOs\AbstractDTO;
use App\Enums\ServiceStatus;
use Carbon\Carbon;

readonly class ServiceDTO extends AbstractDTO
{
    public function __construct(
        public int $budget_id,
        public int $category_id,
        public ServiceStatus $status,
        public ?string $code = null,
        public ?string $description = null,
        public float $discount = 0.0,
        public float $total = 0.0,
        public ?Carbon $due_date = null,
        public ?string $reason = null,
        public array $items = [],
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            budget_id: (int) $data['budget_id'],
            category_id: (int) $data['category_id'],
            status: isset($data['status']) ? ServiceStatus::from($data['status']) : ServiceStatus::PENDING,
            code: $data['code'] ?? null,
            description: $data['description'] ?? null,
            discount: (float) ($data['discount'] ?? 0.0),
            total: (float) ($data['total'] ?? 0.0),
            due_date: isset($data['due_date']) ? Carbon::parse($data['due_date']) : null,
            reason: $data['reason'] ?? null,
            items: array_map(fn ($item) => ServiceItemDTO::fromRequest($item), $data['items'] ?? []),
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'budget_id' => $this->budget_id,
            'category_id' => $this->category_id,
            'status' => $this->status->value,
            'code' => $this->code,
            'description' => $this->description,
            'discount' => $this->discount,
            'total' => $this->total,
            'due_date' => $this->due_date?->toDateString(),
            'reason' => $this->reason,
            'tenant_id' => $this->tenant_id,
        ];
    }
}
