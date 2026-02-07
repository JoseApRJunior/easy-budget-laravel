<?php

declare(strict_types=1);

namespace App\DTOs\Payment;

use App\DTOs\AbstractDTO;

readonly class MerchantOrderDTO extends AbstractDTO
{
    public function __construct(
        public string $merchant_order_id,
        public int $provider_id,
        public int $plan_subscription_id,
        public string $status,
        public string $order_status,
        public float $total_amount,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            merchant_order_id: (string) $data['merchant_order_id'],
            provider_id: (int) $data['provider_id'],
            plan_subscription_id: (int) $data['plan_subscription_id'],
            status: $data['status'],
            order_status: $data['order_status'],
            total_amount: (float) $data['total_amount'],
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'merchant_order_id' => $this->merchant_order_id,
            'provider_id' => $this->provider_id,
            'plan_subscription_id' => $this->plan_subscription_id,
            'status' => $this->status,
            'order_status' => $this->order_status,
            'total_amount' => $this->total_amount,
            'tenant_id' => $this->tenant_id,
        ];
    }
}
