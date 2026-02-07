<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\Product\ProductDTO;
use App\Models\Product;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Repositório para gerenciamento de produtos com arquitetura padronizada ("Gold Standard").
 *
 * Segue o padrão estabelecido no CategoryRepository, utilizando métodos auxiliares
 * seguros para aplicação de filtros e ordenação, evitando duplicidade e erros de query.
 */
class ProductRepository extends AbstractTenantRepository
{
    /**
     * {@inheritdoc}
     */
    protected function makeModel(): Model
    {
        return new Product;
    }

    /**
     * Busca produto por SKU.
     */
    public function findBySku(string $sku, array $with = [], bool $withTrashed = true): ?Model
    {
        return $this->findOneBy('sku', $sku, $with, $withTrashed);
    }

    /**
     * Gera SKU único para o tenant.
     */
    public function generateUniqueSku(): string
    {
        $lastProduct = $this->model->newQuery()
            ->where('sku', 'LIKE', 'PROD%')
            ->withTrashed()
            ->orderBy('sku', 'desc')
            ->first();

        if (! $lastProduct) {
            return 'PROD000001';
        }

        $lastNumber = (int) filter_var($lastProduct->sku, FILTER_SANITIZE_NUMBER_INT);
        $nextNumber = $lastNumber + 1;

        return 'PROD'.str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Cria um novo produto a partir de um DTO.
     */
    public function createFromDTO(ProductDTO $dto): Model
    {
        $data = $dto->toDatabaseArray();

        if (empty($data['sku'])) {
            $data['sku'] = $this->generateUniqueSku();
        }

        return $this->create($data);
    }

    /**
     * Atualiza um produto a partir de um DTO.
     */
    public function updateFromDTO(int $id, ProductDTO $dto): ?Model
    {
        $data = $dto->toDatabaseArray(false); // Não inclui nulos no update

        return $this->update($id, $data);
    }

    /**
     * Obtém estatísticas do dashboard para produtos.
     */
    public function getDashboardStats(): array
    {
        $baseQuery = $this->model->newQuery();
        $inventoryQuery = $this->model->newQuery()
            ->join('product_inventory', 'products.id', '=', 'product_inventory.product_id');

        // Margem média apenas para produtos com custo cadastrado
        $avgMargin = (clone $baseQuery)
            ->where('cost_price', '>', 0)
            ->selectRaw('AVG(((price - cost_price) / price) * 100) as avg_margin')
            ->value('avg_margin') ?? 0;

        // Valor do inventário
        $inventoryTotals = (clone $inventoryQuery)
            ->selectRaw('
                SUM(products.cost_price * product_inventory.quantity) as total_cost,
                SUM(products.price * product_inventory.quantity) as total_sale
            ')
            ->first();

        return [
            'total_products' => (clone $baseQuery)->count(),
            'active_products' => (clone $baseQuery)->where('active', true)->count(),
            'inactive_products' => (clone $baseQuery)->where('active', false)->count(),
            'deleted_products' => (clone $baseQuery)->onlyTrashed()->count(),
            'low_stock_count' => $this->getLowStockByTenant()->count(),
            'recent_products' => $this->getRecentByTenant(5),
            'average_profit_margin' => (float) $avgMargin,
            'total_inventory_cost' => (float) ($inventoryTotals->total_cost ?? 0),
            'total_inventory_sale' => (float) ($inventoryTotals->total_sale ?? 0),
        ];
    }

    /**
     * Restaura um produto deletado.
     */
    public function restore(int $id): bool
    {
        $product = $this->model->withTrashed()->find($id);
        if ($product) {
            return (bool) $product->restore();
        }

        return false;
    }

    /**
     * Conta produtos ativos por tenant.
     */
    public function countActiveByTenant(): int
    {
        return $this->countByTenant(['active' => true]);
    }

    /**
     * Obtém produtos recentes por tenant.
     */
    public function getRecentByTenant(int $limit = 5): Collection
    {
        return $this->getAllByTenant([], ['created_at' => 'desc'], $limit);
    }

    /**
     * Obtém produtos com estoque baixo por tenant.
     */
    public function getLowStockByTenant(): Collection
    {
        // Join manual para acessar inventário.
        // Idealmente isso seria via relacionamento, mas para performance ok.
        return $this->model->newQuery()
            ->join('product_inventory', 'products.id', '=', 'product_inventory.product_id')
            ->where('products.active', true)
            ->whereColumn('product_inventory.quantity', '<=', 'product_inventory.min_quantity')
            ->select('products.*', 'product_inventory.quantity', 'product_inventory.min_quantity')
            ->get();
    }

    /**
     * {@inheritdoc}
     *
     * Implementação específica para produtos com filtros padronizados.
     */
    public function getPaginated(
        array $filters = [],
        int $perPage = 15,
        array $with = ['category', 'inventory'],
        ?array $orderBy = null,
    ): LengthAwarePaginator {
        // Remove duplicatas de with
        $with = array_unique($with);

        $effectivePerPage = $this->getEffectivePerPage($filters, $perPage);

        // Se o filtro 'all' estiver ativo, usamos um número bem alto para trazer todos os registros na mesma "página"
        if (isset($filters['all']) && ($filters['all'] === '1' || $filters['all'] === true)) {
            $effectivePerPage = 999999;
        }

        return $this->model->newQuery()
            ->with($with)
            ->tap(fn ($q) => $this->applyAllProductFilters($q, $filters))
            ->when(! $orderBy, fn ($q) => $q->orderBy('sku'))
            ->when($orderBy, fn ($q) => $this->applyOrderBy($q, $orderBy))
            ->paginate($effectivePerPage);
    }

    /**
     * Verifica se o produto pode ser desativado ou deletado.
     *
     * Regra: Não pode ser desativado/deletado se estiver em uso em algum service_item.
     */
    public function canBeDeactivatedOrDeleted(int $productId): bool
    {
        return ! $this->model->newQuery()
            ->where('id', $productId)
            ->whereHas('serviceItems')
            ->exists();
    }

    /**
     * Atualiza o status do produto.
     */
    public function updateStatus(int $id, bool $active): bool
    {
        return (bool) $this->model->newQuery()
            ->where('id', $id)
            ->update(['active' => $active]);
    }

    /**
     * Aplica todos os filtros de produto de forma segura.
     *
     * Substitui o uso genérico de applyFilters para evitar problemas com strings vazias
     * e garantir que cada filtro seja aplicado corretamente.
     */
    protected function applyAllProductFilters(Builder $query, array $filters): void
    {
        // Filtros padrão da Trait (Search, Boolean, SoftDelete)
        $this->applySearchFilter($query, $filters, ['name', 'sku', 'description']);
        $this->applyBooleanFilter($query, $filters, 'active', 'active');
        $this->applySoftDeleteFilter($query, $filters);

        // Filtro de Categoria (Slug)
        $query->when(! empty($filters['category']), function ($q) use ($filters) {
            $q->whereHas('category', function ($subQuery) use ($filters) {
                $subQuery->where('slug', $filters['category']);
            });
        });

        // Filtros de Preço
        $query->when(isset($filters['min_price']) && $filters['min_price'] !== '', function ($q) use ($filters) {
            $q->where('price', '>=', $filters['min_price']);
        });

        $query->when(isset($filters['max_price']) && $filters['max_price'] !== '', function ($q) use ($filters) {
            $q->where('price', '<=', $filters['max_price']);
        });

        // Filtro de Data
        $this->applyDateRangeFilter($query, $filters, 'created_at', 'start_date', 'end_date');
    }
}
