<?php

declare(strict_types=1);

namespace App\DTOs\Invoice;

use App\DTOs\AbstractDTO;
use App\Enums\InvoiceStatus;
use App\Helpers\DateHelper;
use Carbon\Carbon;

readonly class InvoiceUpdateDTO extends AbstractDTO
{
    public function __construct(
        public ?int $customer_id = null,
        public ?InvoiceStatus $status = null,
        public ?float $subtotal = null,
        public ?float $total = null,
        public ?Carbon $due_date = null,
        public ?float $discount = null,
        public ?string $payment_method = null,
        public ?string $payment_id = null,
        public ?float $transaction_amount = null,
        public ?Carbon $transaction_date = null,
        public ?string $notes = null,
        public ?array $items = null,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            customer_id: isset($data['customer_id']) ? (int) $data['customer_id'] : null,
            status: isset($data['status']) ? InvoiceStatus::from($data['status']) : null,
            subtotal: isset($data['subtotal']) ? (float) $data['subtotal'] : null,
            total: isset($data['total']) ? (float) $data['total'] : null,
            due_date: DateHelper::toCarbon($data['due_date'] ?? null),
            discount: isset($data['discount']) ? (float) $data['discount'] : null,
            payment_method: $data['payment_method'] ?? null,
            payment_id: $data['payment_id'] ?? null,
            transaction_amount: isset($data['transaction_amount']) ? (float) $data['transaction_amount'] : null,
            transaction_date: DateHelper::toCarbon($data['transaction_date'] ?? null),
            notes: $data['notes'] ?? null,
            items: isset($data['items']) ? array_map(fn ($item) => InvoiceItemDTO::fromRequest($item), $data['items']) : null,
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        $data = [];

        if ($this->customer_id !== null) {
            $data['customer_id'] = $this->customer_id;
        }
        if ($this->status !== null) {
            $data['status'] = $this->status->value;
        }
        if ($this->subtotal !== null) {
            $data['subtotal'] = $this->subtotal;
        }
        if ($this->total !== null) {
            $data['total'] = $this->total;
        }
        if ($this->due_date !== null) {
            $data['due_date'] = $this->due_date->toDateString();
        }
        if ($this->discount !== null) {
            $data['discount'] = $this->discount;
        }
        if ($this->payment_method !== null) {
            $data['payment_method'] = $this->payment_method;
        }
        if ($this->payment_id !== null) {
            $data['payment_id'] = $this->payment_id;
        }
        if ($this->transaction_amount !== null) {
            $data['transaction_amount'] = $this->transaction_amount;
        }
        if ($this->transaction_date !== null) {
            $data['transaction_date'] = $this->transaction_date->toDateTimeString();
        }
        if ($this->notes !== null) {
            $data['notes'] = $this->notes;
        }
        if ($this->tenant_id !== null) {
            $data['tenant_id'] = $this->tenant_id;
        }

        return $data;
    }

    public function toDatabaseArray(): array
    {
        return [
            'customer_id' => $this->customer_id,
            'status' => $this->status?->value,
            'subtotal' => $this->subtotal,
            'total' => $this->total,
            'due_date' => $this->due_date?->toDateString(),
            'discount' => $this->discount,
            'payment_method' => $this->payment_method,
            'payment_id' => $this->payment_id,
            'transaction_amount' => $this->transaction_amount,
            'transaction_date' => $this->transaction_date?->toDateTimeString(),
            'notes' => $this->notes,
            'tenant_id' => $this->tenant_id,
        ];
    }
}
