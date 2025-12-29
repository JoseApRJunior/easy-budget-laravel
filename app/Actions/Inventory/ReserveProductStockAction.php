<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\Models\Product;
use App\Models\ProductInventory;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * Action para gerenciar a reserva de estoque de um produto.
 */
class ReserveProductStockAction
{
    /**
     * Reserva uma quantidade do estoque.
     *
     * @param Product $product
     * @param int $quantity
     * @return ProductInventory
     * @throws Exception
     */
    public function reserve(Product $product, int $quantity): ProductInventory
    {
        return DB::transaction(function () use ($product, $quantity) {
            $inventory = $product->inventory()->firstOrCreate([
                'tenant_id' => $product->tenant_id,
                'product_id' => $product->id,
            ], [
                'quantity' => 0,
                'reserved_quantity' => 0,
            ]);

            $available = $inventory->available_quantity;

            if ($available < $quantity) {
                throw new Exception("Quantidade disponível insuficiente para reserva. Disponível: {$available}");
            }

            $inventory->increment('reserved_quantity', $quantity);

            return $inventory->fresh();
        });
    }

    /**
     * Libera uma quantidade reservada do estoque (sem saída física).
     *
     * @param Product $product
     * @param int $quantity
     * @return ProductInventory
     * @throws Exception
     */
    public function release(Product $product, int $quantity): ProductInventory
    {
        return DB::transaction(function () use ($product, $quantity) {
            $inventory = $product->inventory;

            if (!$inventory || $inventory->reserved_quantity < $quantity) {
                throw new Exception("Quantidade reservada insuficiente para liberação.");
            }

            $inventory->decrement('reserved_quantity', $quantity);

            return $inventory->fresh();
        });
    }

    /**
     * Confirma uma reserva transformando-a em saída física.
     *
     * @param Product $product
     * @param int $quantity
     * @param UpdateProductStockAction $updateAction
     * @param string|null $reason
     * @param int|null $referenceId
     * @param string|null $referenceType
     * @return ProductInventory
     * @throws Exception
     */
    public function confirm(
        Product $product,
        int $quantity,
        UpdateProductStockAction $updateAction,
        ?string $reason = null,
        ?int $referenceId = null,
        ?string $referenceType = null
    ): ProductInventory {
        return DB::transaction(function () use ($product, $quantity, $updateAction, $reason, $referenceId, $referenceType) {
            // Primeiro libera a reserva
            $this->release($product, $quantity);

            // Depois registra a saída física
            return $updateAction->execute(
                $product,
                $quantity,
                'out',
                $reason,
                $referenceId,
                $referenceType
            );
        });
    }
}
