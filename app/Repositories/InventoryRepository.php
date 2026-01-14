<?php

namespace App\Repositories;

use App\DTOs\Inventory\ProductInventoryDTO;
use App\Models\ProductInventory;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class InventoryRepository extends AbstractTenantRepository
{
    /**
     * {@inheritdoc}
     */
    protected function makeModel(): Model
    {
        return new ProductInventory;
    }

    /**
     * Cria registro de inventário a partir de um DTO.
     */
    public function createFromDTO(ProductInventoryDTO $dto): Model
    {
        return $this->create($dto->toArrayWithoutNulls());
    }

    /**
     * Atualiza registro de inventário a partir de um DTO.
     */
    public function updateFromDTO(int $id, ProductInventoryDTO $dto): ?Model
    {
        return $this->update($id, $dto->toArrayWithoutNulls());
    }

    // ========== MÉTODOS ORIGINAIS ==========

    /**
     * Inicializa o inventário para um novo produto.
     */
    public function initialize(int $productId): Model
    {
        return $this->create([
            'product_id' => $productId,
            'quantity' => 0,
            'min_quantity' => 0,
            'max_quantity' => null,
        ]);
    }

    /**
     * Busca inventário por produto.
     */
    public function findByProduct(int $productId): ?Model
    {
        return $this->model->newQuery()
            ->where('product_id', $productId)
            ->first();
    }

    public function findByProductId(int $productId): ?ProductInventory
    {
        return $this->findByProduct($productId);
    }

    public function createForProduct(int $productId, array $data): ProductInventory
    {
        return $this->model->create(array_merge($data, ['product_id' => $productId]));
    }

    public function updateStock(int $productId, int $quantity): bool
    {
        return $this->model->where('product_id', $productId)->update(['quantity' => $quantity]);
    }

    public function updateQuantity(int $productId, int $quantity): bool
    {
        return $this->updateStock($productId, $quantity);
    }

    // ========== MÉTODOS ADICIONADOS DE AbstractTenantRepository ==========

    /**
     * {@inheritdoc}
     */
    public function countByTenant(array $filters = []): int
    {
        $query = $this->model->newQuery();

        // Aplicar filtros avançados (os mesmos do getPaginated)
        $this->applyFilters($query, $filters);

        if (! empty($filters['search'])) {
            $query->whereHas('product', function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('sku', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['low_stock']) && $filters['low_stock']) {
            $query->whereRaw('(quantity - reserved_quantity) <= min_quantity')
                ->whereRaw('(quantity - reserved_quantity) > 0');
        }

        if (isset($filters['out_of_stock']) && $filters['out_of_stock']) {
            $query->whereRaw('(quantity - reserved_quantity) <= 0');
        }

        if (isset($filters['high_stock']) && $filters['high_stock']) {
            $query->whereColumn('quantity', '>=', 'max_quantity');
        }

        if (isset($filters['quantity'])) {
            $query->where('quantity', $filters['quantity']);
        }

        if (isset($filters['custom_sufficient']) && $filters['custom_sufficient']) {
            $query->whereRaw('(quantity - reserved_quantity) > min_quantity');
        }

        if (! empty($filters['category'])) {
            $query->whereHas('product', function ($q) use ($filters) {
                $q->where('category_id', $filters['category']);
            });
        }

        // Filtro por período (via movimentos de inventário)
        if (! empty($filters['start_date']) || ! empty($filters['end_date'])) {
            $query->whereHas('product.inventoryMovements', function ($q) use ($filters) {
                $this->applyDateRangeFilter($q, $filters, 'created_at', 'start_date', 'end_date');
            });
        }

        return $query->count();
    }

    /**
     * {@inheritdoc}
     *
     * Implementação específica para inventory com filtros avançados.
     *
     * @param  array<string, mixed>  $filters  Filtros específicos:
     *                                         - search: termo de busca em nome/SKU do produto
     *                                         - low_stock: true para filtrar itens com estoque baixo
     *                                         - high_stock: true para filtrar itens com estoque alto
     *                                         - per_page: número de itens por página
     *                                         - deleted: 'only' para mostrar apenas itens deletados
     * @param  int  $perPage  Número padrão de itens por página (15)
     * @param  array<string>  $with  Relacionamentos para eager loading (padrão: ['product'])
     * @param  array<string, string>|null  $orderBy  Ordenação personalizada
     * @return LengthAwarePaginator Resultado paginado
     */
    public function getPaginated(
        array $filters = [],
        int $perPage = 15,
        array $with = ['product'],
        ?array $orderBy = null,
    ): LengthAwarePaginator {
        $query = $this->model->newQuery();

        // Eager loading paramétrico
        if (! empty($with)) {
            $query->with($with);
        }

        // Aplicar filtros avançados
        $this->applyFilters($query, $filters);

        // Aplicar filtro de soft delete se necessário
        $this->applySoftDeleteFilter($query, $filters);

        // Filtros específicos de inventory
        if (! empty($filters['search'])) {
            $query->whereHas('product', function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('sku', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['low_stock']) && $filters['low_stock']) {
            $query->whereRaw('(quantity - reserved_quantity) <= min_quantity')
                ->whereRaw('(quantity - reserved_quantity) > 0');
        }

        if (isset($filters['out_of_stock']) && $filters['out_of_stock']) {
            $query->whereRaw('(quantity - reserved_quantity) <= 0');
        }

        if (isset($filters['high_stock']) && $filters['high_stock']) {
            $query->whereColumn('quantity', '>=', 'max_quantity');
        }

        if (isset($filters['quantity'])) {
            $query->where('quantity', $filters['quantity']);
        }

        if (isset($filters['custom_sufficient']) && $filters['custom_sufficient']) {
            $query->whereRaw('(quantity - reserved_quantity) > min_quantity');
        }

        if (! empty($filters['category'])) {
            $query->whereHas('product', function ($q) use ($filters) {
                $q->where('category_id', $filters['category']);
            });
        }

        // Filtro por período (via movimentos de inventário)
        if (! empty($filters['start_date']) || ! empty($filters['end_date'])) {
            $query->whereHas('product.inventoryMovements', function ($q) use ($filters) {
                $this->applyDateRangeFilter($q, $filters, 'created_at', 'start_date', 'end_date');
            });
        }

        // Aplicar ordenação
        $this->applyOrderBy($query, $orderBy);

        // Per page dinâmico
        $effectivePerPage = $this->getEffectivePerPage($filters, $perPage);

        return $query->paginate($effectivePerPage);
    }

    public function updateMinQuantity(int $productId, int $minQuantity): bool
    {
        return $this->model
            ->where('product_id', $productId)
            ->update(['min_quantity' => $minQuantity]);
    }

    public function updateMaxQuantity(int $productId, int $maxQuantity): bool
    {
        return $this->model
            ->where('product_id', $productId)
            ->update(['max_quantity' => $maxQuantity]);
    }

    public function getLowStockItems(int $limit = 10): Collection
    {
        return $this->model
            ->whereRaw('(quantity - reserved_quantity) <= min_quantity')
            ->with('product')
            ->limit($limit)
            ->get();
    }

    public function getLowStockCount(): int
    {
        return $this->model
            ->whereRaw('(quantity - reserved_quantity) <= min_quantity')
            ->count();
    }

    public function searchInventory(string $search): Collection
    {
        return $this->model
            ->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            })
            ->with('product')
            ->get();
    }

    public function getHighStockItems(int $limit = 10): Collection
    {
        return $this->model
            ->whereNotNull('max_quantity')
            ->whereColumn('quantity', '>=', 'max_quantity')
            ->with('product')
            ->limit($limit)
            ->get();
    }

    public function getOutOfStockItems(int $limit = 10): Collection
    {
        return $this->model
            ->whereRaw('(quantity - reserved_quantity) <= 0')
            ->with('product')
            ->limit($limit)
            ->get();
    }

    public function getHighStockCount(): int
    {
        return $this->model
            ->whereNotNull('max_quantity')
            ->whereColumn('quantity', '>=', 'max_quantity')
            ->count();
    }

    public function getOutOfStockCount(): int
    {
        return $this->model
            ->whereRaw('(quantity - reserved_quantity) <= 0')
            ->count();
    }

    public function getStatistics(array $filters = []): array
    {
        $query = $this->model->newQuery();
        $this->applyFilters($query, $filters);

        // Filtros específicos de inventory (reutilizando a lógica do getPaginated)
        if (! empty($filters['search'])) {
            $query->whereHas('product', function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('sku', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['low_stock']) && $filters['low_stock']) {
            $query->whereRaw('(quantity - reserved_quantity) <= min_quantity')
                ->whereRaw('(quantity - reserved_quantity) > 0');
        }

        if (isset($filters['out_of_stock']) && $filters['out_of_stock']) {
            $query->whereRaw('(quantity - reserved_quantity) <= 0');
        }

        if (isset($filters['high_stock']) && $filters['high_stock']) {
            $query->whereColumn('quantity', '>=', 'max_quantity');
        }

        if (isset($filters['quantity'])) {
            $query->where('quantity', $filters['quantity']);
        }

        if (isset($filters['custom_sufficient']) && $filters['custom_sufficient']) {
            $query->whereRaw('(quantity - reserved_quantity) > min_quantity');
        }

        if (! empty($filters['category'])) {
            $query->whereHas('product', function ($q) use ($filters) {
                $q->where('category_id', $filters['category']);
            });
        }

        // Filtro por período (via movimentos de inventário)
        if (! empty($filters['start_date']) || ! empty($filters['end_date'])) {
            $query->whereHas('product.inventoryMovements', function ($q) use ($filters) {
                $this->applyDateRangeFilter($q, $filters, 'created_at', 'start_date', 'end_date');
            });
        }

        // Clonar para os diferentes contadores
        $total = (clone $query)->count();
        $lowStock = (clone $query)->whereRaw('(quantity - reserved_quantity) <= min_quantity')->whereRaw('(quantity - reserved_quantity) > 0')->count();
        $outOfStock = (clone $query)->whereRaw('(quantity - reserved_quantity) <= 0')->count();
        $sufficientStock = (clone $query)->whereRaw('(quantity - reserved_quantity) > min_quantity')->count();
        $highStock = (clone $query)->whereNotNull('max_quantity')->whereColumn('quantity', '>=', 'max_quantity')->count();
        $totalReserved = (clone $query)->sum('reserved_quantity');
        $reservedItemsCount = (clone $query)->where('reserved_quantity', '>', 0)->count();

        $totalValue = (clone $query)
            ->join('products', 'product_inventory.product_id', '=', 'products.id')
            ->selectRaw('SUM(product_inventory.quantity * products.price) as total')
            ->value('total') ?? 0;

        return [
            'total_items' => $total,
            'low_stock_items_count' => $lowStock,
            'sufficient_stock_items_count' => $sufficientStock,
            'high_stock_items_count' => $highStock,
            'out_of_stock_items_count' => $outOfStock,
            'total_inventory_value' => (float) $totalValue,
            'total_reserved_quantity' => $totalReserved,
            'reserved_items_count' => $reservedItemsCount,
        ];
    }
}
