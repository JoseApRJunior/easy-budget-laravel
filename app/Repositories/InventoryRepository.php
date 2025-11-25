<?php

namespace App\Repositories;

use App\Models\ProductInventory;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Model;

class InventoryRepository extends AbstractTenantRepository
{
    public function __construct(ProductInventory $model)
    {
        $this->model = $model;
    }

    protected function makeModel(): Model
    {
        return new ProductInventory();
    }

    public function findByProductId(int $productId): ?ProductInventory
    {
        return $this->model->where('product_id', $productId)->first();
    }

    public function createForProduct(int $productId, int $quantity = 0): ProductInventory
    {
        return $this->create([
            'product_id' => $productId,
            'quantity' => $quantity,
            'min_quantity' => 0,
        ]);
    }

    public function updateStock(int $productId, int $newQuantity): ProductInventory
    {
        $inventory = $this->findByProductId($productId);

        if (!$inventory) {
            $inventory = $this->createForProduct($productId, $newQuantity);
        } else {
            $this->update($inventory->id, ['quantity' => $newQuantity]);
        }

        return $inventory->fresh();
    }
}
