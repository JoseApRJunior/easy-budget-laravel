<?php

declare(strict_types=1);

namespace App\DTOs\Service;

use App\DTOs\AbstractDTO;

readonly class ServiceItemDTO extends AbstractDTO
{
    public function __construct(
        public int $product_id,
        public float $unit_value,
        public int $quantity,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            product_id: (int) $data['product_id'],
            unit_value: (float) $data['unit_value'],
            quantity: (int) $data['quantity'],
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'product_id' => $this->product_id,
            'unit_value' => $this->unit_value,
            'quantity' => $this->quantity,
            'tenant_id' => $this->tenant_id,
        ];
    }
}
