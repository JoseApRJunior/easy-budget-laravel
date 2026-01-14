<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

use App\DTOs\AbstractDTO;
use App\Helpers\DateHelper;
use Carbon\Carbon;

readonly class PlanSubscriptionDTO extends AbstractDTO
{
    public function __construct(
        public int $provider_id,
        public int $plan_id,
        public string $status,
        public float $transaction_amount,
        public Carbon $start_date,
        public ?Carbon $end_date = null,
        public ?Carbon $transaction_date = null,
        public ?string $payment_method = null,
        public ?string $payment_id = null,
        public ?string $public_hash = null,
        public ?Carbon $last_payment_date = null,
        public ?Carbon $next_payment_date = null,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            provider_id: (int) $data['provider_id'],
            plan_id: (int) $data['plan_id'],
            status: $data['status'] ?? 'pending',
            transaction_amount: (float) $data['transaction_amount'],
            start_date: DateHelper::toCarbon($data['start_date'] ?? null) ?? now(),
            end_date: DateHelper::toCarbon($data['end_date'] ?? null),
            transaction_date: DateHelper::toCarbon($data['transaction_date'] ?? null),
            payment_method: $data['payment_method'] ?? null,
            payment_id: $data['payment_id'] ?? null,
            public_hash: $data['public_hash'] ?? null,
            last_payment_date: DateHelper::toCarbon($data['last_payment_date'] ?? null),
            next_payment_date: DateHelper::toCarbon($data['next_payment_date'] ?? null),
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'provider_id' => $this->provider_id,
            'plan_id' => $this->plan_id,
            'status' => $this->status,
            'transaction_amount' => $this->transaction_amount,
            'start_date' => $this->start_date->toDateTimeString(),
            'end_date' => $this->end_date?->toDateTimeString(),
            'transaction_date' => $this->transaction_date?->toDateTimeString(),
            'payment_method' => $this->payment_method,
            'payment_id' => $this->payment_id,
            'public_hash' => $this->public_hash,
            'last_payment_date' => $this->last_payment_date?->toDateTimeString(),
            'next_payment_date' => $this->next_payment_date?->toDateTimeString(),
            'tenant_id' => $this->tenant_id,
        ];
    }
}
