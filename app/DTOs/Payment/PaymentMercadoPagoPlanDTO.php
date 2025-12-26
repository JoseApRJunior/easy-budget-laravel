<?php

declare(strict_types=1);

namespace App\DTOs\Payment;

use App\DTOs\AbstractDTO;

readonly class PaymentMercadoPagoPlanDTO extends AbstractDTO
{
    public function __construct(
        public int $payment_id,
        public int $plan_subscription_id,
        public string $mercado_pago_payment_id,
        public string $status,
        public string $status_detail,
        public float $transaction_amount,
        public float $net_received_amount,
        public ?string $payment_method_id = null,
        public ?string $payment_type_id = null,
        public ?array $metadata = null,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            payment_id: (int) $data['payment_id'],
            plan_subscription_id: (int) $data['plan_subscription_id'],
            mercado_pago_payment_id: (string) $data['mercado_pago_payment_id'],
            status: $data['status'],
            status_detail: $data['status_detail'],
            transaction_amount: (float) $data['transaction_amount'],
            net_received_amount: (float) $data['net_received_amount'],
            payment_method_id: $data['payment_method_id'] ?? null,
            payment_type_id: $data['payment_type_id'] ?? null,
            metadata: is_string($data['metadata'] ?? []) ? json_decode($data['metadata'], true) : ($data['metadata'] ?? []),
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'payment_id'              => $this->payment_id,
            'plan_subscription_id'    => $this->plan_subscription_id,
            'mercado_pago_payment_id' => $this->mercado_pago_payment_id,
            'status'                  => $this->status,
            'status_detail'           => $this->status_detail,
            'transaction_amount'      => $this->transaction_amount,
            'net_received_amount'     => $this->net_received_amount,
            'payment_method_id'       => $this->payment_method_id,
            'payment_type_id'         => $this->payment_type_id,
            'metadata'                => $this->metadata,
            'tenant_id'               => $this->tenant_id,
        ];
    }
}
