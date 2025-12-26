<?php

declare(strict_types=1);

namespace App\DTOs\Auth;

use App\DTOs\AbstractDTO;

readonly class PermissionDTO extends AbstractDTO
{
    public function __construct(
        public string $name,
        public ?string $description = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            description: $data['description'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}
