<?php

declare(strict_types=1);

namespace App\Services\Application;

use App\Repositories\CategoryRepository;
use App\Repositories\InventoryMovementRepository;
use App\Repositories\InventoryRepository;
use App\Repositories\ProductRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Services\Domain\InventoryService;
use App\Support\ServiceResult;

class InventoryManagementService extends AbstractBaseService
{
    public function __construct(
        private InventoryRepository $inventoryRepository,
        private InventoryMovementRepository $movementRepository,
        private ProductRepository $productRepository,
        private CategoryRepository $categoryRepository,
        private InventoryService $inventoryService
    ) {
        parent::__construct($inventoryRepository);
    }

    /**
     * Add stock to a product.
     */
    public function addStock(string $sku, int $quantity, ?string $reason = null): ServiceResult
    {
        return $this->safeExecute(function () use ($sku, $quantity, $reason) {
            $product = $this->productRepository->findBySku($sku);

            if (! $product) {
                return ServiceResult::error(404, 'Produto não encontrado.');
            }

            return $this->inventoryService->addStock(
                (int) $product->id,
                $quantity,
                $reason ?? 'Entrada manual'
            );
        }, 'Erro ao adicionar estoque.');
    }

    /**
     * Remove stock from a product.
     */
    public function removeStock(string $sku, int $quantity, ?string $reason = null): ServiceResult
    {
        return $this->safeExecute(function () use ($sku, $quantity, $reason) {
            $product = $this->productRepository->findBySku($sku);

            if (! $product) {
                return ServiceResult::error(404, 'Produto não encontrado.');
            }

            return $this->inventoryService->removeStock(
                (int) $product->id,
                $quantity,
                $reason ?? 'Saída manual'
            );
        }, 'Erro ao remover estoque.');
    }

    /**
     * Set stock for a product.
     */
    public function setStock(string $sku, int $quantity, string $reason): ServiceResult
    {
        return $this->safeExecute(function () use ($sku, $quantity, $reason) {
            $product = $this->productRepository->findBySku($sku);

            if (! $product) {
                return ServiceResult::error(404, 'Produto não encontrado.');
            }

            return $this->inventoryService->setStock(
                (int) $product->id,
                $quantity,
                $reason
            );
        }, 'Erro ao ajustar estoque.');
    }

    /**
     * Add stock by product ID (for API).
     */
    public function addStockById(int $productId, int $quantity, ?string $reason = null): ServiceResult
    {
        return $this->safeExecute(function () use ($productId, $quantity, $reason) {
            return $this->inventoryService->addStock(
                $productId,
                $quantity,
                $reason ?? 'Entrada via API'
            );
        }, 'Erro ao adicionar estoque via API.');
    }

    /**
     * Remove stock by product ID (for API).
     */
    public function removeStockById(int $productId, int $quantity, ?string $reason = null): ServiceResult
    {
        return $this->safeExecute(function () use ($productId, $quantity, $reason) {
            return $this->inventoryService->removeStock(
                $productId,
                $quantity,
                $reason ?? 'Saída via API'
            );
        }, 'Erro ao remover estoque via API.');
    }

    /**
     * Get dashboard data for inventory.
     */
    public function getDashboardData(): ServiceResult
    {
        return $this->safeExecute(function () {
            $stats = $this->inventoryRepository->getStatistics();
            $totalProducts = $this->productRepository->countByTenant();

            $lowStockItems = $this->inventoryRepository->getLowStockItems(5);
            $highStockItems = $this->inventoryRepository->getPaginated(
                ['high_stock' => true],
                5,
                ['product']
            );

            $recentMovements = $this->movementRepository->getRecentMovements(10);

            // Additional stats that might not be in getStatistics
            $outOfStockCount = $this->inventoryRepository->countByTenant(['quantity' => 0]);

            $highStockCount = $this->inventoryRepository->countByTenant(['high_stock' => true]);

            return ServiceResult::success([
                'totalProducts' => $totalProducts,
                'lowStockProducts' => $stats['low_stock_items'],
                'highStockProducts' => $highStockCount,
                'outOfStockProducts' => $outOfStockCount,
                'totalInventoryValue' => $stats['total_inventory_value'],
                'highStockItems' => $highStockItems,
                'lowStockItems' => $lowStockItems,
                'recentMovements' => $recentMovements,
            ]);
        }, 'Erro ao carregar dashboard de inventário.');
    }

    /**
     * Get index data for inventory list.
     */
    public function getIndexData(array $filters): ServiceResult
    {
        return $this->safeExecute(function () use ($filters) {
            $categories = $this->categoryRepository->getAllByTenant([], ['name' => 'asc']);

            // Map controller status filters to repository filters
            if (isset($filters['status'])) {
                switch ($filters['status']) {
                    case 'low':
                        $filters['low_stock'] = true;
                        break;
                    case 'out':
                        $filters['quantity'] = 0;
                        break;
                    case 'sufficient':
                        $filters['custom_sufficient'] = true;
                        break;
                }
            }

            $inventories = $this->inventoryRepository->getPaginated(
                $filters,
                15,
                ['product.category']
            );

            return ServiceResult::success([
                'categories' => $categories,
                'inventories' => $inventories,
            ]);
        }, 'Erro ao carregar lista de inventário.');
    }

    /**
     * Get movements data.
     */
    public function getMovementsData(array $filters): ServiceResult
    {
        return $this->safeExecute(function () use ($filters) {
            $products = $this->productRepository->getAllByTenant([], ['name' => 'asc']);

            $movements = $this->movementRepository->getPaginated(
                $filters,
                15,
                ['product', 'user'],
                ['created_at' => 'desc']
            );

            $summary = $this->movementRepository->getStatisticsByPeriod(
                $filters['start_date'] ?? null,
                $filters['end_date'] ?? null
            );

            return ServiceResult::success([
                'products' => $products,
                'movements' => $movements,
                'summary' => $summary,
            ]);
        }, 'Erro ao carregar movimentações de inventário.');
    }

    /**
     * Get stock turnover data.
     */
    public function getStockTurnoverData(array $filters): ServiceResult
    {
        return $this->safeExecute(function () use ($filters) {
            $categories = $this->categoryRepository->getAllByTenant([], ['name' => 'asc']);

            // This is a complex query that might need a dedicated repository method
            // For now, let's keep it similar to the controller but using repositories
            $stockTurnover = $this->productRepository->getPaginated(
                $filters,
                15,
                ['category']
            );

            // We need to enrich the paginated results with turnover data
            // This is better done in the repository, but for a quick refactor:
            $items = $stockTurnover->getCollection()->map(function ($product) use ($filters) {
                $inventory = $this->inventoryRepository->findByProduct((int) $product->id);

                $stats = $this->movementRepository->getStatisticsByPeriod(
                    $filters['start_date'] ?? null,
                    $filters['end_date'] ?? null
                );

                // Note: This is still a bit inefficient (N+1), should be moved to repository query
                return $product;
            });

            // For now, let's return a basic structure.
            // TODO: Move the complex turnover query to ProductRepository or a specialized reporter.

            return ServiceResult::success([
                'filters' => $filters,
                'categories' => $categories,
                'stockTurnover' => $stockTurnover,
                'reportData' => [
                    'total_products' => $stockTurnover->total(),
                    'total_entries' => 0, // Placeholder
                    'total_exits' => 0, // Placeholder
                    'average_turnover' => 0, // Placeholder
                ],
            ]);
        }, 'Erro ao carregar giro de estoque.');
    }

    /**
     * Get alerts data.
     */
    public function getAlertsData(): ServiceResult
    {
        return $this->safeExecute(function () {
            $lowStockProducts = $this->inventoryRepository->getPaginated(
                ['low_stock' => true],
                15,
                ['product.category'],
                ['updated_at' => 'desc']
            );

            $highStockProducts = $this->inventoryRepository->getPaginated(
                ['high_stock' => true],
                15,
                ['product.category'],
                ['updated_at' => 'desc']
            );

            return ServiceResult::success([
                'lowStockProducts' => $lowStockProducts,
                'highStockProducts' => $highStockProducts,
            ]);
        }, 'Erro ao carregar alertas de inventário.');
    }

    /**
     * Get data for show/edit/forms.
     */
    public function getProductBySku(string $sku): ServiceResult
    {
        return $this->safeExecute(function () use ($sku) {
            $product = $this->productRepository->findBySku($sku, ['inventory']);

            if (! $product) {
                return ServiceResult::error(\App\Enums\OperationStatus::NOT_FOUND, 'Produto não encontrado.');
            }

            return ServiceResult::success($product);
        }, 'Erro ao buscar produto.');
    }
}
