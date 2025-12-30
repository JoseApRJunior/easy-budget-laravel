<?php

declare(strict_types=1);

namespace App\DTOs\Inventory;

use App\DTOs\AbstractDTO;
use App\Helpers\DateHelper;

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
        public ?string $start_date = null,
        public ?string $end_date = null,
        public int $per_page = 10,
    ) {}

    /**
     * Cria uma instância de InventoryFilterDTO a partir de um array de filtros.
     */
    public static function fromRequest(array $data): self
    {
        $startDate = null;
        if (!empty($data['start_date'])) {
            $startDate = DateHelper::parseBirthDate($data['start_date']) ?? $data['start_date'];
            // Garantir formato Y-m-d se vier com /
            if (str_contains($startDate, '/')) {
                $parts = explode('/', $startDate);
                if (count($parts) === 3) {
                    $startDate = "{$parts[2]}-{$parts[1]}-{$parts[0]}";
                }
            }
        }

        $endDate = null;
        if (!empty($data['end_date'])) {
            $endDate = DateHelper::parseBirthDate($data['end_date']) ?? $data['end_date'];
            // Garantir formato Y-m-d se vier com /
            if (str_contains($endDate, '/')) {
                $parts = explode('/', $endDate);
                if (count($parts) === 3) {
                    $endDate = "{$parts[2]}-{$parts[1]}-{$parts[0]}";
                }
            }
        }

        return new self(
            search: $data['search'] ?? null,
            category: $data['category'] ?? null,
            status: $data['status'] ?? null,
            start_date: $startDate,
            end_date: $endDate,
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

        if ($this->start_date !== null) {
            $filters['start_date'] = $this->start_date;
        }

        if ($this->end_date !== null) {
            $filters['end_date'] = $this->end_date;
        }

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
