<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\Support\SupportDTO;
use App\DTOs\Support\SupportUpdateDTO;
use App\Models\Support;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Model;

/**
 * Repositório para operações de suporte tenant-aware
 *
 * Implementa métodos específicos para gerenciamento de tickets de suporte
 * com isolamento automático por tenant_id
 */
class SupportRepository extends AbstractTenantRepository
{
    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new Support;
    }

    /**
     * Busca tickets de suporte com filtros.
     */
    public function getFiltered(array $filters = [], int $perPage = 50): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        return $this->applyFilters($query, $filters)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Cria um novo ticket de suporte a partir de um DTO.
     */
    public function createFromDTO(SupportDTO $dto): Model
    {
        return $this->create($dto->toArrayWithoutNulls());
    }

    /**
     * Atualiza um ticket de suporte a partir de um DTO.
     */
    public function updateFromDTO(int $id, SupportUpdateDTO $dto): ?Model
    {
        return $this->update($id, $dto->toArrayWithoutNulls());
    }

    /**
     * Obtém estatísticas de tickets por status.
     */
    public function getStatusStats(): array
    {
        return $this->model->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }
}
