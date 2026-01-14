<?php

declare(strict_types=1);

namespace App\DTOs\Payment;

use App\DTOs\AbstractDTO;

readonly class PaymentConfirmDTO extends AbstractDTO
{
    public function __construct(
        public int $payment_id,
        public ?string $transaction_id = null,
        public ?array $gateway_response = null,
        public ?string $notes = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            payment_id: (int) ($data['payment_id'] ?? $data['id']),
            transaction_id: $data['transaction_id'] ?? null,
            gateway_response: $data['gateway_response'] ?? null,
            notes: $data['notes'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'transaction_id' => $this->transaction_id,
            'gateway_response' => $this->gateway_response,
            'notes' => $this->notes,
        ];
    }
}
