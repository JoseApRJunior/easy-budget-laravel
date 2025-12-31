<?php

declare(strict_types=1);

namespace App\DTOs\Budget;

use App\DTOs\AbstractDTO;
use App\Enums\BudgetStatus;
use App\Helpers\DateHelper;
use Carbon\Carbon;

readonly class BudgetDTO extends AbstractDTO
{
    public function __construct(
        public int $customer_id,
        public BudgetStatus $status,
        public ?string $code = null,
        public ?Carbon $due_date = null,
        public float $discount = 0.0,
        public float $total = 0.0,
        public ?string $description = null,
        public ?string $payment_terms = null,
        public ?array $services = null,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        $servicesData = $data['services'] ?? $data['items'] ?? null;
        $services = is_array($servicesData) 
            ? array_map(fn ($service) => \App\DTOs\Service\ServiceDTO::fromRequest($service), $servicesData)
            : null;

        return new self(
            customer_id: (int) $data['customer_id'],
            status: isset($data['status']) ? BudgetStatus::from($data['status']) : BudgetStatus::DRAFT,
            code: $data['code'] ?? null,
            due_date: DateHelper::toCarbon($data['due_date'] ?? null),
            discount: (float) ($data['discount'] ?? 0.0),
            total: (float) ($data['total'] ?? 0.0),
            description: $data['description'] ?? null,
            payment_terms: $data['payment_terms'] ?? null,
            services: $services,
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toDatabaseArray(): array
    {
        return [
            'customer_id' => $this->customer_id,
            'status' => $this->status->value,
            'code' => $this->code,
            'due_date' => $this->due_date?->toDateString(),
            'discount' => $this->discount,
            'total' => $this->total,
            'description' => $this->description,
            'payment_terms' => $this->payment_terms,
            'tenant_id' => $this->tenant_id,
        ];
    }
}
