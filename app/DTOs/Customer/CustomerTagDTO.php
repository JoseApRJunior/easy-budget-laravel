<?php

declare(strict_types=1);

namespace App\DTOs\Customer;

use App\DTOs\AbstractDTO;

readonly class CustomerTagDTO extends AbstractDTO
{
    public function __construct(
        public int $customer_id,
        public string $name,
        public string $slug,
        public ?string $color = null,
        public ?string $description = null,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            customer_id: (int) $data['customer_id'],
            name: $data['name'],
            slug: $data['slug'],
            color: $data['color'] ?? null,
            description: $data['description'] ?? null,
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'customer_id' => $this->customer_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'color' => $this->color,
            'description' => $this->description,
            'tenant_id' => $this->tenant_id,
        ];
    }
}
