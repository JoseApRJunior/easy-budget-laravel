<?php

namespace App\Repositories;

use App\Models\InventoryMovement;
use App\Models\ProductInventory;
use App\Repositories\AbstractTenantRepository;
use Illuminate\Support\Collection;

class InventoryRepository extends AbstractTenantRepository
{
    /**
     * @var ProductInventory
     */
    protected $productInventoryModel;

    /**
     * @var InventoryMovement
     */
    protected $inventoryMovementModel;

    /**
     * InventoryRepository constructor.
     *
     * @param ProductInventory $productInventoryModel
     * @param InventoryMovement $inventoryMovementModel
     */
    public function __construct(ProductInventory $productInventoryModel, InventoryMovement $inventoryMovementModel)
    {
        $this->productInventoryModel = $productInventoryModel;
        $this->inventoryMovementModel = $inventoryMovementModel;
    }

    /**
     * Get inventory for a specific product.
     *
     * @param int $productId
     * @return ProductInventory|null
     */
    public function findByProductId(int $productId): ?ProductInventory
    {
        return $this->productInventoryModel
            ->where('product_id', $productId)
            ->where('tenant_id', $this->getCurrentTenantId())
            ->first();
    }

    /**
     * Get inventory movements for a specific product.
     *
     * @param int $productId
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getMovementsByProductId(int $productId, int $perPage = 15)
    {
        return $this->inventoryMovementModel
            ->where('product_id', $productId)
            ->where('tenant_id', $this->getCurrentTenantId())
            ->with(['product'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get low stock products.
     *
     * @param int $limit
     * @return Collection
     */
    public function getLowStockProducts(int $limit = 10): Collection
    {
        return $this->productInventoryModel
            ->where('tenant_id', $this->getCurrentTenantId())
            ->whereRaw('quantity <= min_quantity')
            ->with(['product'])
            ->limit($limit)
            ->get();
    }

    /**
     * Get inventory summary.
     *
     * @return Collection
     */
    public function getInventorySummary(): Collection
    {
        return $this->productInventoryModel
            ->where('tenant_id', $this->getCurrentTenantId())
            ->with(['product'])
            ->get();
    }

    /**
     * Create or update inventory for a product.
     *
     * @param int $productId
     * @param array $data
     * @return ProductInventory
     */
    public function createOrUpdateInventory(int $productId, array $data): ProductInventory
    {
        $inventory = $this->findByProductId($productId);

        if ($inventory) {
            $inventory->update($data);
            return $inventory;
        }

        $data['product_id'] = $productId;
        $data['tenant_id'] = $this->getCurrentTenantId();
        return $this->productInventoryModel->create($data);
    }

    /**
     * Record inventory movement.
     *
     * @param int $productId
     * @param string $type
     * @param int $quantity
     * @param string $reason
     * @return InventoryMovement
     */
    public function recordMovement(int $productId, string $type, int $quantity, string $reason = ''): InventoryMovement
    {
        return $this->inventoryMovementModel->create([
            'tenant_id' => $this->getCurrentTenantId(),
            'product_id' => $productId,
            'type' => $type,
            'quantity' => $quantity,
            'reason' => $reason,
        ]);
    }

    /**
     * Adjust inventory quantity.
     *
     * @param int $productId
     * @param int $quantity
     * @param string $type
     * @param string $reason
     * @return ProductInventory
     */
    public function adjustQuantity(int $productId, int $quantity, string $type, string $reason = ''): ProductInventory
    {
        return DB::transaction(function () use ($productId, $quantity, $type, $reason) {
            // Get or create inventory
            $inventory = $this->findByProductId($productId);
            if (!$inventory) {
                $inventory = $this->createOrUpdateInventory($productId, [
                    'quantity' => 0,
                    'min_quantity' => 0,
                    'max_quantity' => null,
                ]);
            }

            // Calculate new quantity
            $newQuantity = $inventory->quantity;
            if ($type === 'in') {
                $newQuantity += $quantity;
            } elseif ($type === 'out') {
                $newQuantity -= $quantity;
                
                // Check if there's enough stock
                if ($newQuantity < 0) {
                    throw new \Exception('Estoque insuficiente para esta operação.');
                }
            }

            // Update inventory
            $inventory->update(['quantity' => $newQuantity]);

            // Record movement
            $this->recordMovement($productId, $type, $quantity, $reason);

            return $inventory;
        });
    }

    /**
     * Check if product has inventory.
     *
     * @param int $productId
     * @return bool
     */
    public function hasInventory(int $productId): bool
    {
        return $this->productInventoryModel
            ->where('product_id', $productId)
            ->where('tenant_id', $this->getCurrentTenantId())
            ->exists();
    }

    /**
     * Check if product can be deleted.
     *
     * @param int $productId
     * @return bool
     */
    public function canDeleteProduct(int $productId): bool
    {
        // Check if there are inventory movements
        $hasMovements = $this->inventoryMovementModel
            ->where('product_id', $productId)
            ->where('tenant_id', $this->getCurrentTenantId())
            ->exists();

        // Check if there is inventory record
        $hasInventory = $this->hasInventory($productId);

        return !$hasMovements && !$hasInventory;
    }
}