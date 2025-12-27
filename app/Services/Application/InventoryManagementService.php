<?php

declare(strict_types=1);

namespace App\Services\Application;

use App\Actions\Inventory\UpdateProductStockAction;
use App\Models\Product;
use App\Repositories\InventoryRepository;
use App\Repositories\ProductRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Serviço para gestão de inventário e estoque.
 */
class InventoryManagementService extends AbstractBaseService
{
    public function __construct(
        protected InventoryRepository $inventoryRepository,
        protected ProductRepository $productRepository,
        private UpdateProductStockAction $updateStockAction
    ) {
        parent::__construct($inventoryRepository);
    }

    /**
     * Obtém dados para o dashboard de inventário.
     */
    public function getDashboardData(): ServiceResult
    {
        return $this->safeExecute(function () {
            return [
                'stats' => $this->inventoryRepository->getStatistics(),
                'low_stock_items' => $this->inventoryRepository->getLowStockItems(5),
                'recent_movements' => \App\Models\InventoryMovement::latest()->take(5)->get(),
            ];
        });
    }

    /**
     * Obtém dados para a listagem principal de inventário.
     */
    public function getIndexData(array $filters = []): ServiceResult
    {
        return $this->safeExecute(function () use ($filters) {
            $perPage = (int) ($filters['per_page'] ?? 15);
            return [
                'inventory' => $this->inventoryRepository->paginate($perPage, ['product'], $filters),
                'stats' => $this->inventoryRepository->getStatistics(),
            ];
        });
    }

    /**
     * Obtém dados de movimentações de inventário.
     */
    public function getMovementsData(array $filters = []): ServiceResult
    {
        return $this->safeExecute(function () use ($filters) {
            $query = \App\Models\InventoryMovement::with(['product', 'user']);

            if (isset($filters['product_id'])) {
                $query->where('product_id', $filters['product_id']);
            }

            if (isset($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            return [
                'movements' => $query->latest()->paginate($filters['per_page'] ?? 15),
            ];
        });
    }

    /**
     * Obtém dados de giro de estoque.
     */
    public function getStockTurnoverData(array $filters = []): ServiceResult
    {
        return $this->safeExecute(function () {
            // Implementação básica, pode ser expandida conforme necessidade
            return [
                'turnover' => [],
            ];
        });
    }

    /**
     * Obtém dados de alertas de estoque.
     */
    public function getAlertsData(): ServiceResult
    {
        return $this->safeExecute(function () {
            return [
                'low_stock' => $this->inventoryRepository->getLowStockItems(),
            ];
        });
    }

    /**
     * Busca um produto pelo SKU.
     */
    public function getProductBySku(string $sku): ServiceResult
    {
        return $this->safeExecute(function () use ($sku) {
            $product = $this->productRepository->findBySku($sku);
            if (!$product) {
                throw new Exception("Produto com SKU {$sku} não encontrado.");
            }
            return $product;
        });
    }

    /**
     * Adiciona estoque a um produto pelo SKU.
     */
    public function addStock(string $sku, int $quantity, string $reason): ServiceResult
    {
        return $this->safeExecute(function () use ($sku, $quantity, $reason) {
            $product = $this->productRepository->findBySku($sku);
            if (!$product) {
                throw new Exception("Produto não encontrado.");
            }

            return $this->updateStockAction->execute($product, $quantity, 'in', $reason);
        });
    }

    /**
     * Remove estoque de um produto pelo SKU.
     */
    public function removeStock(string $sku, int $quantity, string $reason): ServiceResult
    {
        return $this->safeExecute(function () use ($sku, $quantity, $reason) {
            $product = $this->productRepository->findBySku($sku);
            if (!$product) {
                throw new Exception("Produto não encontrado.");
            }

            return $this->updateStockAction->execute($product, $quantity, 'out', $reason);
        });
    }

    /**
     * Ajusta o estoque de um produto pelo SKU.
     */
    public function setStock(string $sku, int $quantity, string $reason): ServiceResult
    {
        return $this->safeExecute(function () use ($sku, $quantity, $reason) {
            $product = $this->productRepository->findBySku($sku);
            if (!$product) {
                throw new Exception("Produto não encontrado.");
            }

            return $this->updateStockAction->execute($product, $quantity, 'adjustment', $reason);
        });
    }

    /**
     * Adiciona estoque a um produto pelo ID.
     */
    public function addStockById(int $id, int $quantity, string $reason): ServiceResult
    {
        return $this->safeExecute(function () use ($id, $quantity, $reason) {
            $product = Product::findOrFail($id);
            return $this->updateStockAction->execute($product, $quantity, 'in', $reason);
        });
    }

    /**
     * Remove estoque de um produto pelo ID.
     */
    public function removeStockById(int $id, int $quantity, string $reason): ServiceResult
    {
        return $this->safeExecute(function () use ($id, $quantity, $reason) {
            $product = Product::findOrFail($id);
            return $this->updateStockAction->execute($product, $quantity, 'out', $reason);
        });
    }
}
