<?php

declare(strict_types=1);

namespace App\DTOs\Customer;

use App\DTOs\AbstractDTO;
use App\Helpers\DateHelper;

/**
 * DTO para filtragem de Clientes.
 * Agrupa critérios de busca e parâmetros de paginação.
 */
readonly class CustomerFilterDTO extends AbstractDTO
{
    public function __construct(
        public ?string $search = null,
        public ?string $active = null,
        public ?string $type = null,
        public ?string $area_of_activity = null,
        public ?string $deleted = null,
        public ?string $cep = null,
        public ?string $cpf = null,
        public ?string $cnpj = null,
        public ?string $phone = null,
        public ?string $start_date = null,
        public ?string $end_date = null,
        public bool $all = false,
        public int $per_page = 10,
    ) {}

    /**
     * Cria uma instância de CustomerFilterDTO a partir de um array de filtros.
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            search: $data['search'] ?? null,
            active: $data['active'] ?? $data['status'] ?? null, // Aceita 'active' da view ou 'status' (legado/padrão)
            type: $data['type'] ?? null,
            area_of_activity: $data['area_of_activity'] ?? null,
            deleted: $data['deleted'] ?? null,
            cep: $data['cep'] ?? null,
            cpf: $data['cpf'] ?? null,
            cnpj: $data['cnpj'] ?? null,
            phone: $data['phone'] ?? null,
            start_date: DateHelper::parseDate($data['start_date'] ?? null),
            end_date: DateHelper::parseDate($data['end_date'] ?? null),
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

        // Mapeamento de 'active' da view para 'status' do banco
        if ($this->active !== null && $this->active !== 'all') {
            if ($this->active === '1' || $this->active === 'active') {
                $filters['status'] = 'active';
            } elseif ($this->active === '0' || $this->active === 'inactive') {
                $filters['status'] = 'inactive';
            }
        }

        if ($this->type !== null) {
            $filters['type'] = $this->type;
        }

        if ($this->area_of_activity !== null) {
            $filters['area_of_activity'] = $this->area_of_activity;
        }

        if ($this->deleted !== null) {
            // Se for 'current', não passamos o filtro para o repository usar o comportamento padrão (sem trashed)
            // Se for 'only', passamos 'only' para o repository
            // Se for 'all', passamos 'all' para o repository
            if ($this->deleted === 'only') {
                $filters['deleted'] = 'only';
            } elseif ($this->deleted === 'all') {
                $filters['deleted'] = 'all';
            }
        }

        if ($this->cep !== null) {
            $filters['cep'] = $this->cep;
        }

        if ($this->cpf !== null) {
            $filters['cpf'] = $this->cpf;
        }

        if ($this->cnpj !== null) {
            $filters['cnpj'] = $this->cnpj;
        }

        if ($this->phone !== null) {
            $filters['phone'] = $this->phone;
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
     */
    public function toViewArray(): array
    {
        return [
            'search' => $this->search,
            'active' => $this->active,
            'type' => $this->type,
            'area_of_activity' => $this->area_of_activity,
            'deleted' => $this->deleted,
            'cep' => $this->cep,
            'cpf' => $this->cpf,
            'cnpj' => $this->cnpj,
            'phone' => $this->phone,
            'start_date' => $this->start_date ? \Carbon\Carbon::parse($this->start_date)->format('d/m/Y') : null,
            'end_date' => $this->end_date ? \Carbon\Carbon::parse($this->end_date)->format('d/m/Y') : null,
            'all' => $this->all,
            'per_page' => $this->per_page,
        ];
    }
}
