<?php

declare(strict_types=1);

namespace App\DTOs\Product;

use App\DTOs\AbstractDTO;
use App\Helpers\CurrencyHelper;

use App\Helpers\DateHelper;

/**
 * DTO para filtragem de Produtos.
 * Agrupa critérios de busca e parâmetros de paginação.
 */
readonly class ProductFilterDTO extends AbstractDTO
{
    public function __construct(
        public ?string $search = null,
        public ?string $is_active = null,
        public ?string $deleted = null,
        public ?string $category = null,
        public ?float $min_price = null,
        public ?float $max_price = null,
        public ?string $start_date = null,
        public ?string $end_date = null,
        public bool $all = false,
        public int $per_page = 10,
    ) {}

    /**
     * Cria uma instância de ProductFilterDTO a partir de um array de filtros.
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            search: $data['search'] ?? null,
            is_active: $data['active'] ?? null,
            deleted: $data['deleted'] ?? null,
            category: $data['category'] ?? null,
            min_price: isset($data['min_price']) && $data['min_price'] !== '' ? CurrencyHelper::unformat($data['min_price']) : null,
            max_price: isset($data['max_price']) && $data['max_price'] !== '' ? CurrencyHelper::unformat($data['max_price']) : null,
            start_date: DateHelper::parseDate($data['start_date'] ?? null),
            end_date: DateHelper::parseDate($data['end_date'] ?? null),
            all: filter_var($data['all'] ?? false, FILTER_VALIDATE_BOOLEAN),
            per_page: (int) ($data['per_page'] ?? 10),
        );
    }

    /**
     * Converte o DTO para o formato de filtros esperado pelo Service/Repository.
     * Retorna valores puros (floats) para consultas no banco.
     */
    public function toFilterArray(): array
    {
        $filters = [];

        if ($this->search !== null) {
            $filters['search'] = $this->search;
        }

        if ($this->is_active !== null) {
            $filters['active'] = $this->is_active;
        }

        if ($this->deleted !== null) {
            $filters['deleted'] = $this->deleted;
        }

        if ($this->category !== null) {
            $filters['category'] = $this->category;
        }

        if ($this->min_price !== null) {
            $filters['min_price'] = $this->min_price;
        }

        if ($this->max_price !== null) {
            $filters['max_price'] = $this->max_price;
        }

        if ($this->start_date !== null) {
            $filters['start_date'] = $this->start_date;
        }

        if ($this->end_date !== null) {
            $filters['end_date'] = $this->end_date;
        }

        if ($this->all) {
            $filters['all'] = '1';
        }

        if ($this->per_page !== 10) {
            $filters['per_page'] = $this->per_page;
        }

        return $filters;
    }

    /**
     * Converte o DTO para o formato de exibição na View.
     * Formata preços para o padrão brasileiro (sem R$).
     */
    public function toDisplayArray(): array
    {
        $display = $this->toFilterArray();

        if ($this->min_price !== null) {
            $display['min_price'] = CurrencyHelper::format($this->min_price, 2, false);
        }

        if ($this->max_price !== null) {
            $display['max_price'] = CurrencyHelper::format($this->max_price, 2, false);
        }

        return $display;
    }
}
