<?php

declare(strict_types=1);

namespace App\DTOs\Inventory;

use App\DTOs\AbstractDTO;

readonly class InventoryMovementDTO extends AbstractDTO
{
    public function __construct(
        public int $product_id,
        public string $type,
        public int $quantity,
        public ?int $previous_quantity = null,
        public ?int $new_quantity = null,
        public ?string $reason = null,
        public ?int $reference_id = null,
        public ?string $reference_type = null,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            product_id: (int) $data['product_id'],
            type: $data['type'],
            quantity: (int) $data['quantity'],
            previous_quantity: isset($data['previous_quantity']) ? (int) $data['previous_quantity'] : null,
            new_quantity: isset($data['new_quantity']) ? (int) $data['new_quantity'] : null,
            reason: $data['reason'] ?? null,
            reference_id: isset($data['reference_id']) ? (int) $data['reference_id'] : null,
            reference_type: $data['reference_type'] ?? null,
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'product_id' => $this->product_id,
            'type' => $this->type,
            'quantity' => $this->quantity,
            'previous_quantity' => $this->previous_quantity,
            'new_quantity' => $this->new_quantity,
            'reason' => $this->reason,
            'reference_id' => $this->reference_id,
            'reference_type' => $this->reference_type,
            'tenant_id' => $this->tenant_id,
        ];
    }
}
