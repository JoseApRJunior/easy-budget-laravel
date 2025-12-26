<?php

declare(strict_types=1);

namespace App\DTOs\Payment;

use App\DTOs\AbstractDTO;
use App\Enums\PaymentStatus;
use Carbon\Carbon;

readonly class PaymentDTO extends AbstractDTO
{
    public function __construct(
        public int $invoice_id,
        public int $customer_id,
        public PaymentStatus $status,
        public string $method,
        public float $amount,
        public ?string $gateway_transaction_id = null,
        public ?array $gateway_response = null,
        public ?Carbon $processed_at = null,
        public ?Carbon $confirmed_at = null,
        public ?string $notes = null,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            invoice_id: (int) $data['invoice_id'],
            customer_id: (int) $data['customer_id'],
            status: isset($data['status']) ? PaymentStatus::from($data['status']) : PaymentStatus::PENDING,
            method: $data['method'],
            amount: (float) $data['amount'],
            gateway_transaction_id: $data['gateway_transaction_id'] ?? null,
            gateway_response: $data['gateway_response'] ?? null,
            processed_at: isset($data['processed_at']) ? Carbon::parse($data['processed_at']) : null,
            confirmed_at: isset($data['confirmed_at']) ? Carbon::parse($data['confirmed_at']) : null,
            notes: $data['notes'] ?? null,
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'invoice_id' => $this->invoice_id,
            'customer_id' => $this->customer_id,
            'status' => $this->status->value,
            'method' => $this->method,
            'amount' => $this->amount,
            'gateway_transaction_id' => $this->gateway_transaction_id,
            'gateway_response' => $this->gateway_response,
            'processed_at' => $this->processed_at?->toDateTimeString(),
            'confirmed_at' => $this->confirmed_at?->toDateTimeString(),
            'notes' => $this->notes,
            'tenant_id' => $this->tenant_id,
        ];
    }
}
