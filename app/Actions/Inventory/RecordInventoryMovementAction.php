<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

/**
 * Action para registrar movimentações de inventário.
 */
class RecordInventoryMovementAction
{
    /**
     * Registra uma movimentação de estoque.
     *
     * @param  Product  $product  Produto que está sendo movimentado.
     * @param  string  $type  Tipo da movimentação (in, out, adjustment).
     * @param  int  $quantity  Quantidade movimentada.
     * @param  int  $previousQuantity  Quantidade antes da movimentação.
     * @param  string|null  $reason  Motivo da movimentação.
     * @param  int|null  $referenceId  ID de referência (ex: ID do pedido).
     * @param  string|null  $referenceType  Tipo de referência (ex: Order, Sale).
     */
    public function execute(
        Product $product,
        string $type,
        int $quantity,
        int $previousQuantity,
        ?string $reason = null,
        ?int $referenceId = null,
        ?string $referenceType = null
    ): InventoryMovement {
        $newQuantity = match ($type) {
            'in' => $previousQuantity + $quantity,
            'out' => $previousQuantity - $quantity,
            'adjustment' => $quantity,
            default => $previousQuantity,
        };

        return InventoryMovement::create([
            'tenant_id' => $product->tenant_id,
            'product_id' => $product->id,
            'type' => $type === 'in' ? 'entry' : ($type === 'out' ? 'exit' : 'adjustment'),
            'quantity' => $quantity,
            'previous_quantity' => $previousQuantity,
            'new_quantity' => $newQuantity,
            'reason' => $reason,
            'reference_id' => $referenceId,
            'reference_type' => $referenceType,
            'user_id' => Auth::id(),
        ]);
    }
}
