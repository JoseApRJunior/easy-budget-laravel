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
        public ?float $cost_price = 0,
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
            name: self::formatTitle((string) $data['name']),
            price: (float) $data['price'],
            cost_price: isset($data['cost_price']) ? (float) $data['cost_price'] : 0,
            category_id: isset($data['category_id']) ? (int) $data['category_id'] : null,
            description: $data['description'] ?? null,
            sku: $data['sku'] ?? null,
            unit: $data['unit'] ?? 'un',
            is_active: self::mapBoolean($data['active'] ?? $data['is_active'] ?? true),
            image: $data['image'] ?? null,
        );
    }

    /**
     * Converte o DTO para um array formatado para persistência no banco de dados.
     * 
     * @param bool $includeNulls Se deve incluir campos nulos (útil para create, perigoso para update)
     */
    public function toDatabaseArray(bool $includeNulls = true): array
    {
        $data = $includeNulls ? $this->toArray() : $this->toArrayWithoutNulls();

        // Mapeamento temporário para compatibilidade com a model Product que usa 'active'
        if (array_key_exists('is_active', $data)) {
            $data['active'] = $data['is_active'];
            unset($data['is_active']);
        }

        return $data;
    }
}
