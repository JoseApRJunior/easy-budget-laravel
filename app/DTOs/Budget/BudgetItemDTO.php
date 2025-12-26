<?php

declare(strict_types=1);

namespace App\DTOs\Budget;

use App\DTOs\AbstractDTO;

readonly class BudgetItemDTO extends AbstractDTO
{
    public function __construct(
        public string $title,
        public float $quantity,
        public string $unit,
        public float $unit_price,
        public ?int $budget_item_category_id = null,
        public ?string $description = null,
        public float $discount_percentage = 0.0,
        public float $tax_percentage = 0.0,
        public float $total_price = 0.0,
        public float $net_total = 0.0,
        public int $order_index = 0,
        public ?array $metadata = null,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            title: $data['title'],
            quantity: (float) $data['quantity'],
            unit: $data['unit'],
            unit_price: (float) $data['unit_price'],
            budget_item_category_id: isset($data['budget_item_category_id']) ? (int) $data['budget_item_category_id'] : null,
            description: $data['description'] ?? null,
            discount_percentage: (float) ($data['discount_percentage'] ?? 0.0),
            tax_percentage: (float) ($data['tax_percentage'] ?? 0.0),
            total_price: (float) ($data['total_price'] ?? 0.0),
            net_total: (float) ($data['net_total'] ?? 0.0),
            order_index: (int) ($data['order_index'] ?? 0),
            metadata: $data['metadata'] ?? null,
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }
}
