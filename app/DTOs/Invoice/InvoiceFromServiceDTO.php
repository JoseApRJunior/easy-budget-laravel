<?php

declare(strict_types=1);

namespace App\DTOs\Invoice;

use App\DTOs\AbstractDTO;
use App\Enums\InvoiceStatus;
use Carbon\Carbon;

readonly class InvoiceFromServiceDTO extends AbstractDTO
{
    public function __construct(
        public string $service_code,
        public ?Carbon $issue_date = null,
        public ?Carbon $due_date = null,
        public ?string $notes = null,
        public ?array $items = null,
        public bool $is_automatic = true,
        public ?InvoiceStatus $status = null,
        public ?float $discount = null,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            service_code: $data['service_code'] ?? '',
            issue_date: isset($data['issue_date']) ? Carbon::parse($data['issue_date']) : null,
            due_date: isset($data['due_date']) ? Carbon::parse($data['due_date']) : null,
            notes: $data['notes'] ?? null,
            items: $data['items'] ?? null,
            is_automatic: (bool) ($data['is_automatic'] ?? true),
            status: isset($data['status']) ? InvoiceStatus::from($data['status']) : null,
            discount: isset($data['discount']) ? (float) $data['discount'] : null,
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'service_code' => $this->service_code,
            'issue_date' => $this->issue_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'notes' => $this->notes,
            'items' => $this->items,
            'is_automatic' => $this->is_automatic,
            'status' => $this->status?->value,
            'discount' => $this->discount,
            'tenant_id' => $this->tenant_id,
        ];
    }
}
