<?php

declare(strict_types=1);

namespace App\DTOs\Invoice;

use App\DTOs\AbstractDTO;

readonly class InvoiceItemDTO extends AbstractDTO
{
    public function __construct(
        public int $product_id,
        public int $quantity,
        public float $unit_price,
        public float $total,
        public ?string $description = null,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            product_id: (int) $data['product_id'],
            quantity: (int) $data['quantity'],
            unit_price: (float) $data['unit_price'],
            total: (float) $data['total'],
            description: $data['description'] ?? null,
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'product_id' => $this->product_id,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'total' => $this->total,
            'description' => $this->description,
            'tenant_id' => $this->tenant_id,
        ];
    }
}
