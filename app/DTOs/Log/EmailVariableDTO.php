<?php

declare(strict_types=1);

namespace App\DTOs\Log;

use App\DTOs\AbstractDTO;

readonly class EmailVariableDTO extends AbstractDTO
{
    public function __construct(
        public string $name,
        public string $slug,
        public string $description,
        public string $category,
        public string $data_type,
        public ?string $default_value = null,
        public ?array $validation_rules = null,
        public bool $is_system = false,
        public bool $is_active = true,
        public int $sort_order = 0,
        public ?array $metadata = null,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            slug: $data['slug'],
            description: $data['description'],
            category: $data['category'],
            data_type: $data['data_type'],
            default_value: $data['default_value'] ?? null,
            validation_rules: is_string($data['validation_rules'] ?? []) ? json_decode($data['validation_rules'], true) : ($data['validation_rules'] ?? []),
            is_system: isset($data['is_system']) ? (bool) $data['is_system'] : false,
            is_active: isset($data['is_active']) ? (bool) $data['is_active'] : true,
            sort_order: isset($data['sort_order']) ? (int) $data['sort_order'] : 0,
            metadata: is_string($data['metadata'] ?? []) ? json_decode($data['metadata'], true) : ($data['metadata'] ?? []),
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'name'             => $this->name,
            'slug'             => $this->slug,
            'description'      => $this->description,
            'category'         => $this->category,
            'data_type'        => $this->data_type,
            'default_value'    => $this->default_value,
            'validation_rules' => $this->validation_rules,
            'is_system'        => $this->is_system,
            'is_active'        => $this->is_active,
            'sort_order'       => $this->sort_order,
            'metadata'         => $this->metadata,
            'tenant_id'        => $this->tenant_id,
        ];
    }
}
