<?php

declare(strict_types=1);

namespace App\DTOs\Budget;

use App\DTOs\AbstractDTO;
use App\Enums\BudgetStatus;
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
        public array $items = [],
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            customer_id: (int) $data['customer_id'],
            status: isset($data['status']) ? BudgetStatus::from($data['status']) : BudgetStatus::DRAFT,
            code: $data['code'] ?? null,
            due_date: isset($data['due_date']) ? Carbon::parse($data['due_date']) : null,
            discount: (float) ($data['discount'] ?? 0.0),
            total: (float) ($data['total'] ?? 0.0),
            description: $data['description'] ?? null,
            payment_terms: $data['payment_terms'] ?? null,
            items: array_map(fn($item) => BudgetItemDTO::fromRequest($item), $data['items'] ?? []),
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }
}
