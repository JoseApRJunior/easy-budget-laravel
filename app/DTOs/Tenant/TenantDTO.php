<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

use App\DTOs\AbstractDTO;

readonly class TenantDTO extends AbstractDTO
{
    public function __construct(
        public string $name,
        public bool $is_active = true
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            is_active: (bool) ($data['is_active'] ?? true)
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'is_active' => $this->is_active,
        ];
    }
}
