<?php

declare(strict_types=1);

namespace App\DTOs\Product;

use App\DTOs\AbstractDTO;

/**
 * DTO para filtragem de Produtos.
 * Agrupa critérios de busca e parâmetros de paginação.
 */
readonly class ProductFilterDTO extends AbstractDTO
{
    public function __construct(
        public ?string $search = null,
        public ?bool $is_active = null,
        public ?string $deleted = null,
        public ?int $category_id = null,
        public ?float $min_price = null,
        public ?float $max_price = null,
        public bool $all = false,
        public int $per_page = 15,
    ) {}

    /**
     * Cria uma instância de ProductFilterDTO a partir de um array de filtros.
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            search: $data['search'] ?? null,
            is_active: isset($data['active']) ? filter_var($data['active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null,
            deleted: $data['deleted'] ?? null,
            category_id: isset($data['category_id']) ? (int) $data['category_id'] : null,
            min_price: isset($data['min_price']) ? (float) $data['min_price'] : null,
            max_price: isset($data['max_price']) ? (float) $data['max_price'] : null,
            all: filter_var($data['all'] ?? false, FILTER_VALIDATE_BOOLEAN),
            per_page: (int) ($data['per_page'] ?? 15),
        );
    }

    /**
     * Converte o DTO para o formato de filtros esperado pelo Service/Repository.
     */
    public function toFilterArray(): array
    {
        $filters = [];

        if ($this->search !== null) {
            $filters['search'] = $this->search;
        }

        if ($this->is_active !== null) {
            $filters['active'] = $this->is_active ? '1' : '0';
        }

        if ($this->deleted !== null) {
            $filters['deleted'] = $this->deleted;
        }

        if ($this->category_id !== null) {
            $filters['category_id'] = $this->category_id;
        }

        if ($this->min_price !== null) {
            $filters['min_price'] = $this->min_price;
        }

        if ($this->max_price !== null) {
            $filters['max_price'] = $this->max_price;
        }

        if ($this->all) {
            $filters['all'] = '1';
        }

        return $filters;
    }
}
