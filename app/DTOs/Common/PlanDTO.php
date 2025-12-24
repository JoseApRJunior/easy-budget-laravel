<?php

declare(strict_types=1);

namespace App\DTOs\Common;

use App\DTOs\AbstractDTO;

readonly class PlanDTO extends AbstractDTO
{
    public function __construct(
        public string $name,
        public string $slug,
        public float $price,
        public int $max_budgets,
        public int $max_clients,
        public ?string $description = null,
        public bool $status = true,
        public ?array $features = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            slug: $data['slug'],
            price: (float) $data['price'],
            max_budgets: (int) $data['max_budgets'],
            max_clients: (int) $data['max_clients'],
            description: $data['description'] ?? null,
            status: (bool) ($data['status'] ?? true),
            features: $data['features'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'name'        => $this->name,
            'slug'        => $this->slug,
            'price'       => $this->price,
            'max_budgets' => $this->max_budgets,
            'max_clients' => $this->max_clients,
            'description' => $this->description,
            'status'      => $this->status,
            'features'    => $this->features,
        ];
    }
}
