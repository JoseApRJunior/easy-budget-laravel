<?php

declare(strict_types=1);

namespace App\DTOs\Inventory;

use App\DTOs\AbstractDTO;

readonly class ProductInventoryDTO extends AbstractDTO
{
    public function __construct(
        public int $product_id,
        public int $quantity,
        public ?int $min_quantity = 0,
        public ?int $max_quantity = null,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            product_id: (int) $data['product_id'],
            quantity: (int) ($data['quantity'] ?? 0),
            min_quantity: isset($data['min_quantity']) ? (int) $data['min_quantity'] : 0,
            max_quantity: isset($data['max_quantity']) ? (int) $data['max_quantity'] : null,
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'product_id'   => $this->product_id,
            'quantity'     => $this->quantity,
            'min_quantity' => $this->min_quantity,
            'max_quantity' => $this->max_quantity,
            'tenant_id'    => $this->tenant_id,
        ];
    }
}
