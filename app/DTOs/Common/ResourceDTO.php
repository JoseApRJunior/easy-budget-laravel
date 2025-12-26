<?php

declare(strict_types=1);

namespace App\DTOs\Common;

use App\DTOs\AbstractDTO;

readonly class ResourceDTO extends AbstractDTO
{
    public function __construct(
        public string $name,
        public string $slug,
        public bool $in_dev = false,
        public string $status = 'active',
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            slug: $data['slug'],
            in_dev: isset($data['in_dev']) ? (bool) $data['in_dev'] : false,
            status: $data['status'] ?? 'active',
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'in_dev' => $this->in_dev,
            'status' => $this->status,
            'tenant_id' => $this->tenant_id,
        ];
    }
}
