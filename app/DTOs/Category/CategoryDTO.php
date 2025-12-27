<?php

declare(strict_types=1);

namespace App\DTOs\Category;

use App\DTOs\AbstractDTO;

/**
 * DTO para transferência de dados de Categoria.
 * Garante tipagem e integridade dos dados entre Controller e Service.
 */
readonly class CategoryDTO extends AbstractDTO
{
    public function __construct(
        public string $name,
        public ?string $slug = null,
        public ?int $parent_id = null,
        public bool $is_active = true,
    ) {}

    /**
     * Cria uma instância de CategoryDTO a partir de um array de dados validados.
     * Geralmente usado no Controller após $request->validated().
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            name: self::formatTitle((string) $data['name']),
            slug: $data['slug'] ?? null,
            parent_id: isset($data['parent_id']) ? (int) $data['parent_id'] : null,
            is_active: self::mapBoolean($data['is_active'] ?? true),
        );
    }
}
