<?php

declare(strict_types=1);

namespace App\DTOs\Common;

use App\DTOs\AbstractDTO;

readonly class AreaOfActivityDTO extends AbstractDTO
{
    public function __construct(
        public string $slug,
        public string $name,
        public bool $is_active = true
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            slug: $data['slug'],
            name: $data['name'],
            is_active: (bool) ($data['is_active'] ?? true)
        );
    }

    public function toArray(): array
    {
        return [
            'slug'      => $this->slug,
            'name'      => $this->name,
            'is_active' => $this->is_active,
        ];
    }
}
