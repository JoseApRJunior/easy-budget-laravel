<?php

declare(strict_types=1);

namespace App\DTOs\Category;

use App\DTOs\AbstractDTO;

/**
 * DTO para filtragem de Categorias.
 * Agrupa critérios de busca e parâmetros de paginação.
 */
readonly class CategoryFilterDTO extends AbstractDTO
{
    public function __construct(
        public ?string $search = null,
        public ?bool $is_active = null,
        public ?string $deleted = null,
        public bool $all = false,
        public int $per_page = 10,
    ) {}

    /**
     * Cria uma instância de CategoryFilterDTO a partir de um array de filtros.
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            search: $data['search'] ?? null,
            is_active: isset($data['active']) ? filter_var($data['active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null,
            deleted: $data['deleted'] ?? null,
            all: filter_var($data['all'] ?? false, FILTER_VALIDATE_BOOLEAN),
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

        if ($this->is_active !== null) {
            $filters['active'] = $this->is_active ? '1' : '0';
        }

        if ($this->deleted !== null) {
            $filters['deleted'] = $this->deleted;
        }

        if ($this->all) {
            $filters['all'] = '1';
        }

        return $filters;
    }
}
