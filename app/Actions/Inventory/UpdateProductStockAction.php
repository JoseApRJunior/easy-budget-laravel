<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\Models\Product;
use App\Models\ProductInventory;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * Action para atualizar o estoque de um produto.
 */
class UpdateProductStockAction
{
    public function __construct(
        private RecordInventoryMovementAction $recordMovementAction
    ) {}

    /**
     * Atualiza a quantidade em estoque de um produto.
     *
     * @param Product $product
     * @param int $quantity Quantidade a ser adicionada/subtraída ou valor absoluto se for ajuste.
     * @param string $type Tipo da movimentação (in, out, adjustment).
     * @param string|null $reason
     * @param int|null $referenceId
     * @param string|null $referenceType
     * @return ProductInventory
     * @throws Exception
     */
    public function execute(
        Product $product,
        int $quantity,
        string $type = 'adjustment',
        ?string $reason = null,
        ?int $referenceId = null,
        ?string $referenceType = null
    ): ProductInventory {
        return DB::transaction(function () use ($product, $quantity, $type, $reason, $referenceId, $referenceType) {
            $inventory = $product->inventory()->firstOrCreate([
                'tenant_id' => $product->tenant_id,
                'product_id' => $product->id,
            ], [
                'quantity' => 0,
                'min_quantity' => 0,
            ]);

            $previousQuantity = (int) $inventory->quantity;

            // Calcula a nova quantidade baseada no tipo
            $newQuantity = match ($type) {
                'in' => $previousQuantity + $quantity,
                'out' => (function() use ($previousQuantity, $quantity, $inventory) {
                    $new = $previousQuantity - $quantity;
                    if ($new < $inventory->reserved_quantity) {
                        throw new Exception("Não é possível reduzir o estoque abaixo da quantidade reservada ({$inventory->reserved_quantity}). Disponível para saída: " . ($previousQuantity - $inventory->reserved_quantity));
                    }
                    return $new;
                })(),
                'adjustment' => (function() use ($quantity, $inventory) {
                    if ($quantity < $inventory->reserved_quantity) {
                        throw new Exception("O novo estoque ({$quantity}) não pode ser menor que a quantidade reservada ({$inventory->reserved_quantity}).");
                    }
                    return $quantity;
                })(),
                default => throw new Exception("Tipo de movimentação inválido: {$type}"),
            };

            // Impede estoque negativo se não for permitido (opcional, aqui vamos permitir para fins de flexibilidade, mas registrar o aviso)
            // if ($newQuantity < 0) { ... }

            // Atualiza o inventário
            $inventory->update([
                'quantity' => $newQuantity
            ]);

            // Registra a movimentação no histórico
            $this->recordMovementAction->execute(
                $product,
                $type,
                $quantity,
                $previousQuantity,
                $reason,
                $referenceId,
                $referenceType
            );

            return $inventory->fresh();
        });
    }
}
