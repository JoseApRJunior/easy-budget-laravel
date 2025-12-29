<?php

declare(strict_types=1);

namespace App\DTOs\Inventory;

use App\DTOs\AbstractDTO;

/**
 * DTO para filtragem de Inventário.
 * Agrupa critérios de busca e parâmetros de paginação.
 */
readonly class InventoryFilterDTO extends AbstractDTO
{
    public function __construct(
        public ?string $search = null,
        public ?string $category = null,
        public ?string $status = null,
        public int $per_page = 10,
    ) {}

    /**
     * Cria uma instância de InventoryFilterDTO a partir de um array de filtros.
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            search: $data['search'] ?? null,
            category: $data['category'] ?? null,
            status: $data['status'] ?? null,
            per_page: (int) ($data['per_page'] ?? 10),
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

        if ($this->category !== null) {
            $filters['category'] = $this->category;
        }

        if ($this->status !== null) {
            $filters['status'] = $this->status;
        }

        $filters['per_page'] = $this->per_page;

        return $filters;
    }

    /**
     * Converte o DTO para o formato de exibição na View.
     */
    public function toDisplayArray(): array
    {
        return $this->toFilterArray();
    }
}
