<?php

declare(strict_types=1);

namespace App\DTOs\Product;

use App\DTOs\AbstractDTO;
use Illuminate\Http\UploadedFile;

/**
 * DTO para transferência de dados de Produto.
 */
readonly class ProductDTO extends AbstractDTO
{
    public function __construct(
        public string $name,
        public float $price,
        public ?int $category_id = null,
        public ?string $description = null,
        public ?string $sku = null,
        public string $unit = 'un',
        public bool $is_active = true,
        public string|UploadedFile|null $image = null,
    ) {}

    /**
     * Cria uma instância de ProductDTO a partir de um array de dados validados.
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            name: mb_convert_case((string) $data['name'], MB_CASE_TITLE, 'UTF-8'),
            price: (float) $data['price'],
            category_id: isset($data['category_id']) ? (int) $data['category_id'] : null,
            description: $data['description'] ?? null,
            sku: $data['sku'] ?? null,
            unit: $data['unit'] ?? 'un',
            is_active: (bool) ($data['active'] ?? $data['is_active'] ?? true),
            image: $data['image'] ?? null,
        );
    }

    /**
     * Sobrescreve toArray para mapear is_active para active caso o banco ainda use active.
     * Isso permite que o Service/Repository receba o nome esperado pelo Model.
     */
    public function toDatabaseArray(): array
    {
        $data = $this->toArray();

        // Mapeamento temporário para compatibilidade com a model Product que usa 'active'
        if (array_key_exists('is_active', $data)) {
            $data['active'] = $data['is_active'];
            unset($data['is_active']);
        }

        return $data;
    }
}
