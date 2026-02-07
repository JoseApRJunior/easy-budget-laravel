<?php

namespace App\Services\Domain;

use App\DTOs\Inventory\InventoryMovementDTO;
use App\DTOs\Inventory\ProductInventoryDTO;
use App\Repositories\InventoryMovementRepository;
use App\Repositories\InventoryRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryService extends AbstractBaseService
{
    public function __construct(
        private InventoryRepository $inventoryRepository,
        private InventoryMovementRepository $movementRepository,
        private \App\Actions\Inventory\ReserveProductStockAction $reserveAction,
        private \App\Actions\Inventory\UpdateProductStockAction $updateAction,
    ) {
        parent::__construct($inventoryRepository);
    }

    // ========== VALIDAÇÕES DE NEGÓCIO ==========

    private function validateQuantity(int $quantity): ServiceResult
    {
        if ($quantity < 0) {
            return $this->error('Quantidade não pode ser negativa');
        }

        return $this->success();
    }

    private function validateSufficientStock(int $productId, int $quantity): ServiceResult
    {
        $inventory = $this->inventoryRepository->findByProduct($productId);

        if (! $inventory) {
            return $this->error('Produto não encontrado no estoque');
        }

        // Usar a quantidade disponível (quantity - reserved_quantity)
        if ($inventory->available_quantity < $quantity) {
            return $this->error(
                "Estoque disponível insuficiente. Disponível: {$inventory->available_quantity}, Solicitado: {$quantity}",
            );
        }

        return $this->success($inventory);
    }

    private function validateMinMaxQuantity(?int $minQuantity, ?int $maxQuantity): ServiceResult
    {
        if ($minQuantity !== null && $minQuantity < 0) {
            return $this->error('Quantidade mínima não pode ser negativa');
        }

        if ($maxQuantity !== null && $maxQuantity < 0) {
            return $this->error('Quantidade máxima não pode ser negativa');
        }

        if ($minQuantity !== null && $maxQuantity !== null && $minQuantity > $maxQuantity) {
            return $this->error('Quantidade mínima não pode ser maior que a máxima');
        }

        return $this->success();
    }

    // ========== MÉTODOS PÚBLICOS COM VALIDAÇÕES ==========

    public function addStock(int $productId, int $quantity, ?string $reason = null): ServiceResult
    {
        return $this->safeExecute(function () use ($productId, $quantity, $reason) {
            $validation = $this->validateQuantity($quantity);
            if (! $validation->isSuccess()) {
                return $validation;
            }

            return DB::transaction(function () use ($productId, $quantity, $reason) {
                $inventory = $this->inventoryRepository->findByProduct($productId);

                if (! $inventory) {
                    $inventory = $this->inventoryRepository->createFromDTO(new ProductInventoryDTO(
                        product_id: $productId,
                        quantity: $quantity,
                        min_quantity: 0,
                        max_quantity: null
                    ));
                } else {
                    $newQuantity = $inventory->quantity + $quantity;
                    $this->inventoryRepository->updateQuantity($productId, $newQuantity);
                    $inventory->quantity = $newQuantity;
                }

                // Record movement
                $this->movementRepository->createFromDTO(new InventoryMovementDTO(
                    product_id: $productId,
                    type: 'in',
                    quantity: $quantity,
                    previous_quantity: $inventory->quantity - $quantity,
                    new_quantity: $inventory->quantity,
                    reason: $reason ?? 'Entrada de estoque'
                ));

                Log::info('Stock added', [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'new_total' => $inventory->quantity,
                    'reason' => $reason,
                ]);

                return $this->success($inventory, 'Estoque adicionado com sucesso');
            });
        }, 'Erro ao adicionar estoque.');
    }

    public function removeStock(int $productId, int $quantity, ?string $reason = null): ServiceResult
    {
        return $this->safeExecute(function () use ($productId, $quantity, $reason) {
            $validation = $this->validateQuantity($quantity);
            if (! $validation->isSuccess()) {
                return $validation;
            }

            $stockValidation = $this->validateSufficientStock($productId, $quantity);
            if (! $stockValidation->isSuccess()) {
                return $stockValidation;
            }

            return DB::transaction(function () use ($productId, $quantity, $reason, $stockValidation) {
                $inventory = $stockValidation->getData();
                $previousQuantity = $inventory->quantity;
                $newQuantity = $previousQuantity - $quantity;

                $this->inventoryRepository->updateQuantity($productId, $newQuantity);
                $inventory->quantity = $newQuantity;

                // Check for low stock alert
                if ($inventory->isLowStock()) {
                    Log::warning('Low stock alert', [
                        'product_id' => $productId,
                        'current_quantity' => $newQuantity,
                        'min_quantity' => $inventory->min_quantity,
                        'tenant_id' => $inventory->tenant_id ?? 'N/A',
                    ]);

                    // TODO: Trigger actual notification (Email/Push) when system is ready
                }

                // Record movement
                $this->movementRepository->createFromDTO(new InventoryMovementDTO(
                    product_id: $productId,
                    type: 'out',
                    quantity: $quantity,
                    previous_quantity: $previousQuantity,
                    new_quantity: $newQuantity,
                    reason: $reason ?? 'Saída de estoque'
                ));

                Log::info('Stock removed', [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'new_total' => $newQuantity,
                    'reason' => $reason,
                ]);

                return $this->success($inventory, 'Estoque removido com sucesso');
            });
        }, 'Erro ao remover estoque.');
    }

    public function setStock(int $productId, int $quantity, ?string $reason = null): ServiceResult
    {
        return $this->safeExecute(function () use ($productId, $quantity, $reason) {
            $validation = $this->validateQuantity($quantity);
            if (! $validation->isSuccess()) {
                return $validation;
            }

            return DB::transaction(function () use ($productId, $quantity, $reason) {
                $inventory = $this->inventoryRepository->findByProduct($productId);
                $previousQuantity = $inventory ? $inventory->quantity : 0;

                if (! $inventory) {
                    $inventory = $this->inventoryRepository->createFromDTO(new ProductInventoryDTO(
                        product_id: $productId,
                        quantity: $quantity,
                        min_quantity: 0,
                        max_quantity: null
                    ));
                } else {
                    $this->inventoryRepository->updateStock($productId, $quantity);
                    $inventory->quantity = $quantity;
                }

                // Record movement
                $this->movementRepository->createFromDTO(new InventoryMovementDTO(
                    product_id: $productId,
                    type: $quantity >= $previousQuantity ? 'in' : 'out',
                    quantity: abs($quantity - $previousQuantity),
                    previous_quantity: $previousQuantity,
                    new_quantity: $quantity,
                    reason: $reason ?? 'Ajuste de estoque'
                ));

                Log::info('Stock set', [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'reason' => $reason,
                ]);

                return $this->success($inventory, 'Estoque ajustado com sucesso');
            });
        }, 'Erro ao ajustar estoque.');
    }

    public function updateMinMaxQuantities(int $productId, ?int $minQuantity = null, ?int $maxQuantity = null): ServiceResult
    {
        return $this->safeExecute(function () use ($productId, $minQuantity, $maxQuantity) {
            $validation = $this->validateMinMaxQuantity($minQuantity, $maxQuantity);
            if (! $validation->isSuccess()) {
                return $validation;
            }

            $inventory = $this->inventoryRepository->findByProduct($productId);

            if (! $inventory) {
                $inventory = $this->inventoryRepository->createFromDTO(new ProductInventoryDTO(
                    product_id: $productId,
                    quantity: 0,
                    min_quantity: $minQuantity ?? 0,
                    max_quantity: $maxQuantity
                ));
            } else {
                $this->inventoryRepository->updateFromDTO($inventory->id, new ProductInventoryDTO(
                    product_id: $productId,
                    quantity: $inventory->quantity,
                    min_quantity: $minQuantity ?? $inventory->min_quantity,
                    max_quantity: $maxQuantity ?? $inventory->max_quantity
                ));
                $inventory->refresh();
            }

            return $this->success($inventory, 'Limites de estoque atualizados');
        }, 'Erro ao atualizar limites de estoque.');
    }

    public function hasSufficientStock(int $productId, int $requiredQuantity): ServiceResult
    {
        return $this->safeExecute(function () use ($productId, $requiredQuantity) {
            $validation = $this->validateSufficientStock($productId, $requiredQuantity);

            return $validation->isSuccess()
                ? $this->success(true, 'Estoque suficiente')
                : $this->error($validation->getMessage());
        }, 'Erro ao verificar disponibilidade de estoque.');
    }

    public function getLowStockAlerts(): ServiceResult
    {
        return $this->safeExecute(function () {
            $lowStockItems = $this->inventoryRepository->getLowStockItems(50);

            return $this->success(['items' => $lowStockItems, 'count' => $lowStockItems->count()], 'Alertas de estoque baixo recuperados');
        }, 'Erro ao buscar alertas de estoque.');
    }

    public function consumeProduct(
        int $productId,
        float $quantity,
        string $reason,
        string $relatedType,
        int $relatedId,
    ): ServiceResult {
        return $this->safeExecute(function () use ($productId, $quantity, $reason, $relatedType, $relatedId) {
            $inventory = $this->inventoryRepository->findByProduct($productId);
            if (! $inventory) {
                return $this->error('Produto não encontrado no estoque');
            }

            $product = $inventory->product;
            if (! $product) {
                return $this->error('Produto não vinculado ao registro de estoque');
            }

            try {
                // Se o produto estiver reservado, confirmamos a reserva (baixa física + limpa reserva)
                if ($inventory->reserved_quantity >= $quantity) {
                    $this->reserveAction->confirm(
                        $product,
                        (int) $quantity,
                        $this->updateAction,
                        $reason,
                        $relatedId,
                        $relatedType
                    );
                } else {
                    // Se não houver reserva suficiente, apenas faz a baixa física direta
                    $this->updateAction->execute(
                        $product,
                        (int) $quantity,
                        'out',
                        $reason,
                        $relatedId,
                        $relatedType
                    );
                }

                return $this->success(null, 'Estoque consumido com sucesso');
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        });
    }

    public function reserveProduct(
        int $productId,
        float $quantity,
        string $reason,
        string $relatedType,
        int $relatedId,
    ): ServiceResult {
        return $this->safeExecute(function () use ($productId, $quantity) {
            $inventory = $this->inventoryRepository->findByProduct($productId);
            if (! $inventory) {
                return $this->error('Produto não encontrado no estoque');
            }

            $product = $inventory->product;
            if (! $product) {
                return $this->error('Produto não vinculado ao registro de estoque');
            }

            try {
                $this->reserveAction->reserve($product, (int) $quantity);

                return $this->success(null, 'Produto reservado com sucesso');
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        });
    }

    public function releaseReservation(
        int $productId,
        float $quantity,
        string $reason,
        string $relatedType,
        int $relatedId,
    ): ServiceResult {
        return $this->safeExecute(function () use ($productId, $quantity) {
            $inventory = $this->inventoryRepository->findByProduct($productId);
            if (! $inventory) {
                return $this->error('Produto não encontrado no estoque');
            }

            $product = $inventory->product;
            if (! $product) {
                return $this->error('Produto não vinculado ao registro de estoque');
            }

            try {
                $this->reserveAction->release($product, (int) $quantity);

                return $this->success(null, 'Reserva liberada com sucesso');
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        });
    }

    public function returnProduct(
        int $productId,
        float $quantity,
        string $reason,
        string $relatedType,
        int $relatedId,
    ): ServiceResult {
        return $this->addStock($productId, (int) $quantity, $reason);
    }

    public function getFilteredInventory(array $filters = []): ServiceResult
    {
        return $this->safeExecute(function () use ($filters) {
            $perPage = (int) ($filters['per_page'] ?? 10);
            $normalizedFilters = $this->normalizeInventoryFilters($filters);

            $inventory = $this->inventoryRepository->getPaginated(
                $normalizedFilters,
                $perPage,
                ['product:id,name,sku']
            );

            return $this->success($inventory, 'Dados de inventário recuperados com sucesso');
        }, 'Erro ao buscar dados de inventário.');
    }

    private function normalizeInventoryFilters(array $filters): array
    {
        $normalized = [];

        if (! empty($filters['product_name'])) {
            $normalized['search'] = $filters['product_name'];
        }

        if (! empty($filters['status'])) {
            $normalized['stock_status'] = $filters['status'];
        }

        if (isset($filters['min_quantity']) && $filters['min_quantity'] !== '') {
            $normalized['quantity'] = ['operator' => '>=', 'value' => (int) $filters['min_quantity']];
        }

        if (isset($filters['max_quantity']) && $filters['max_quantity'] !== '') {
            $normalized['quantity'] = ['operator' => '<=', 'value' => (int) $filters['max_quantity']];
        }

        return $normalized;
    }
}
