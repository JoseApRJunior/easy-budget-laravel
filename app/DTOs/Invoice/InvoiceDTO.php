<?php

declare(strict_types=1);

namespace App\DTOs\Invoice;

use App\DTOs\AbstractDTO;
use App\Enums\InvoiceStatus;
use Carbon\Carbon;

readonly class InvoiceDTO extends AbstractDTO
{
    public function __construct(
        public int $service_id,
        public int $customer_id,
        public InvoiceStatus $status,
        public float $subtotal,
        public float $total,
        public Carbon $due_date,
        public ?string $code = null,
        public float $discount = 0.0,
        public ?string $payment_method = null,
        public ?string $payment_id = null,
        public ?float $transaction_amount = null,
        public ?Carbon $transaction_date = null,
        public ?string $notes = null,
        public bool $is_automatic = false,
        public array $items = [],
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            service_id: (int) $data['service_id'],
            customer_id: (int) $data['customer_id'],
            status: isset($data['status']) ? InvoiceStatus::from($data['status']) : InvoiceStatus::PENDING,
            subtotal: (float) $data['subtotal'],
            total: (float) $data['total'],
            due_date: Carbon::parse($data['due_date']),
            code: $data['code'] ?? null,
            discount: (float) ($data['discount'] ?? 0.0),
            payment_method: $data['payment_method'] ?? null,
            payment_id: $data['payment_id'] ?? null,
            transaction_amount: isset($data['transaction_amount']) ? (float) $data['transaction_amount'] : null,
            transaction_date: isset($data['transaction_date']) ? Carbon::parse($data['transaction_date']) : null,
            notes: $data['notes'] ?? null,
            is_automatic: (bool) ($data['is_automatic'] ?? false),
            items: array_map(fn($item) => InvoiceItemDTO::fromRequest($item), $data['items'] ?? []),
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'service_id'         => $this->service_id,
            'customer_id'        => $this->customer_id,
            'status'             => $this->status->value,
            'subtotal'           => $this->subtotal,
            'total'              => $this->total,
            'due_date'           => $this->due_date->toDateString(),
            'code'               => $this->code,
            'discount'           => $this->discount,
            'payment_method'     => $this->payment_method,
            'payment_id'         => $this->payment_id,
            'transaction_amount' => $this->transaction_amount,
            'transaction_date'   => $this->transaction_date?->toDateTimeString(),
            'notes'              => $this->notes,
            'is_automatic'       => $this->is_automatic,
            'tenant_id'          => $this->tenant_id,
        ];
    }
}
