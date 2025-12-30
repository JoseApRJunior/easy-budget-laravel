<?php

namespace App\Repositories;

use App\DTOs\Inventory\InventoryMovementDTO;
use App\Models\InventoryMovement;
use App\Repositories\Abstracts\AbstractTenantRepository;
use App\Repositories\Traits\RepositoryFiltersTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Repositório para gerenciamento de movimentações de inventário
 */
class InventoryMovementRepository extends AbstractTenantRepository
{
    use RepositoryFiltersTrait;

    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new InventoryMovement;
    }

    /**
     * Cria uma movimentação de inventário a partir de um DTO.
     */
    public function createFromDTO(InventoryMovementDTO $dto): Model
    {
        return $this->create($dto->toArrayWithoutNulls());
    }

    /**
     * Retorna movimentações por produto
     */
    public function getByProduct(int $productId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with(['product'])
            ->where('product_id', $productId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    // Usando implementação padrão do AbstractTenantRepository
    // que é suficiente para as funcionalidades básicas de paginação

    // Métodos específicos podem ser adicionados conforme necessário

    /**
     * Retorna estatísticas de movimentações por período
     */
    public function getStatisticsByPeriod(?string $startDate = null, ?string $endDate = null): array
    {
        $query = $this->model->newQuery();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate . ' 00:00:00');
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate . ' 23:59:59');
        }

        $movementsIn = $query->clone()->where('type', 'in');
        $movementsOut = $query->clone()->where('type', 'out');

        return [
            'total_movements' => $query->count(),
            'total_in' => $movementsIn->count(),
            'total_out' => $movementsOut->count(),
            'quantity_in' => $movementsIn->sum('quantity'),
            'quantity_out' => $movementsOut->sum('quantity'),
            'net_movement' => $movementsIn->sum('quantity') - $movementsOut->sum('quantity'),
        ];
    }

    /**
     * Retorna movimentações recentes
     */
    public function getRecentMovements(int $limit = 10): Collection
    {
        return $this->model->newQuery()
            ->with(['product'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Retorna produtos mais movimentados
     */
    public function getMostMovedProducts(int $limit = 10, ?string $startDate = null, ?string $endDate = null): Collection
    {
        return $this->model->newQuery()
            ->selectRaw('product_id, SUM(quantity) as total_quantity, COUNT(*) as movement_count')
            ->when($startDate, function ($query) use ($startDate) {
                $query->where('created_at', '>=', $startDate . ' 00:00:00');
            })
            ->when($endDate, function ($query) use ($endDate) {
                $query->where('created_at', '<=', $endDate . ' 23:59:59');
            })
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->with(['product'])
            ->get();
    }

    /**
     * Retorna resumo de movimentações por tipo
     */
    public function getSummaryByType(?string $startDate = null, ?string $endDate = null): array
    {
        $query = $this->model->newQuery();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate . ' 00:00:00');
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate . ' 23:59:59');
        }

        $summary = $query->selectRaw('type, COUNT(*) as count, SUM(quantity) as total_quantity')
            ->groupBy('type')
            ->get();

        return [
            'in' => $summary->where('type', 'in')->first(),
            'out' => $summary->where('type', 'out')->first(),
        ];
    }
}
