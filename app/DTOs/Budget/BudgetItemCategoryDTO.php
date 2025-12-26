<?php

declare(strict_types=1);

namespace App\DTOs\Budget;

use App\DTOs\AbstractDTO;

readonly class BudgetItemCategoryDTO extends AbstractDTO
{
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $description = null,
        public ?string $color = null,
        public ?string $icon = null,
        public bool $is_active = true,
        public ?int $parent_id = null,
        public int $order = 0,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            slug: $data['slug'],
            description: $data['description'] ?? null,
            color: $data['color'] ?? null,
            icon: $data['icon'] ?? null,
            is_active: isset($data['is_active']) ? (bool) $data['is_active'] : true,
            parent_id: isset($data['parent_id']) ? (int) $data['parent_id'] : null,
            order: isset($data['order']) ? (int) $data['order'] : 0,
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'color' => $this->color,
            'icon' => $this->icon,
            'is_active' => $this->is_active,
            'parent_id' => $this->parent_id,
            'order' => $this->order,
            'tenant_id' => $this->tenant_id,
        ];
    }
}
