<?php

declare(strict_types=1);

namespace App\DTOs\Invoice;

use App\DTOs\AbstractDTO;
use App\Enums\InvoiceStatus;
use Carbon\Carbon;

readonly class InvoiceFromBudgetDTO extends AbstractDTO
{
    public function __construct(
        public int $service_id,
        public array $items,
        public ?Carbon $due_date = null,
        public float $discount = 0.0,
        public ?InvoiceStatus $status = null,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            service_id: (int) $data['service_id'],
            items: $data['items'] ?? [],
            due_date: isset($data['due_date']) ? Carbon::parse($data['due_date']) : null,
            discount: (float) ($data['discount'] ?? 0.0),
            status: isset($data['status']) ? InvoiceStatus::from($data['status']) : null,
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'service_id' => $this->service_id,
            'items'      => $this->items,
            'due_date'   => $this->due_date?->toDateString(),
            'discount'   => $this->discount,
            'status'     => $this->status?->value,
            'tenant_id'  => $this->tenant_id,
        ];
    }
}
