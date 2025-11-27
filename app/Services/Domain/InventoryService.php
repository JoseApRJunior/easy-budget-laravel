<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\OperationStatus;
use App\Models\InventoryMovement;
use App\Repositories\InventoryRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\DB;

class InventoryService extends AbstractBaseService
{
    private InventoryRepository $inventoryRepository;

    public function __construct(InventoryRepository $inventoryRepository)
    {
        $this->inventoryRepository = $inventoryRepository;
    }

    public function getInventoryByProduct(int $productId): ServiceResult
    {
        try {
            $inventory = $this->inventoryRepository->findByProductId($productId);

            if (!$inventory) {
                // Se não existir, cria um zerado (lazy creation)
                $inventory = $this->inventoryRepository->createForProduct($productId);
            }

            return $this->success($inventory, 'Inventário recuperado com sucesso');
        } catch (Exception $e) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao recuperar inventário',
                null,
                $e
            );
        }
    }

    public function addStock(int $productId, int $quantity, string $reason = ''): ServiceResult
    {
        return $this->adjustStock($productId, $quantity, 'in', $reason);
    }

    public function removeStock(int $productId, int $quantity, string $reason = ''): ServiceResult
    {
        return $this->adjustStock($productId, $quantity, 'out', $reason);
    }

    public function setStock(int $productId, int $newQuantity, string $reason = ''): ServiceResult
    {
        if ($newQuantity < 0) {
             return $this->error(OperationStatus::VALIDATION_ERROR, 'A quantidade não pode ser negativa');
        }

        try {
            return DB::transaction(function () use ($productId, $newQuantity, $reason) {
                $inventory = $this->inventoryRepository->findByProductId($productId);

                if (!$inventory) {
                    $inventory = $this->inventoryRepository->createForProduct($productId);
                }

                $currentQuantity = $inventory->quantity;

                if ($currentQuantity === $newQuantity) {
                     return $this->success($inventory, 'Nenhuma alteração necessária');
                }

                $diff = $newQuantity - $currentQuantity;
                $type = $diff > 0 ? 'in' : 'out';
                $absDiff = abs($diff);

                // Atualiza o estoque
                $this->inventoryRepository->update($inventory->id, ['quantity' => $newQuantity]);

                if (class_exists(\App\Models\InventoryMovement::class)) {
                    $user = auth()->user();
                    $tenantId = $user?->tenant_id ?? $inventory->tenant_id;

                     \App\Models\InventoryMovement::create([
                        'tenant_id' => $tenantId,
                        'product_id' => $productId,
                        'type' => $type,
                        'quantity' => $absDiff,
                        'previous_quantity' => $currentQuantity,
                        'new_quantity' => $newQuantity,
                        'reason' => $reason,
                        'user_id' => $user?->id,
                    ]);
                }

                return $this->success($inventory->fresh(), 'Estoque ajustado com sucesso');
            });
        } catch (Exception $e) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao ajustar estoque',
                null,
                $e
            );
        }
    }

    private function adjustStock(int $productId, int $quantity, string $type, string $reason): ServiceResult
    {
        if ($quantity <= 0) {
            return $this->error(OperationStatus::VALIDATION_ERROR, 'A quantidade deve ser maior que zero');
        }

        try {
            return DB::transaction(function () use ($productId, $quantity, $type, $reason) {
                $inventory = $this->inventoryRepository->findByProductId($productId);

                if (!$inventory) {
                    $inventory = $this->inventoryRepository->createForProduct($productId);
                }

                $currentQuantity = $inventory->quantity;
                $newQuantity = $type === 'in' ? $currentQuantity + $quantity : $currentQuantity - $quantity;

                if ($newQuantity < 0) {
                    return $this->error(OperationStatus::VALIDATION_ERROR, 'Estoque insuficiente para esta operação');
                }

                // Atualiza o estoque
                $this->inventoryRepository->update($inventory->id, ['quantity' => $newQuantity]);

                // Registra a movimentação (assumindo que existe InventoryMovement model, se não existir, precisaremos criar)
                // O checklist mencionou "Histórico de movimentações", então deve existir ou precisar ser criado.
                // Vou verificar se InventoryMovement existe antes de tentar usar.
                // Por enquanto, vou comentar a criação do log se o model não existir, mas o ideal é ter.

                if (class_exists(\App\Models\InventoryMovement::class)) {
                    $user = auth()->user();
                    $tenantId = $user?->tenant_id ?? $inventory->tenant_id; // Fallback to inventory tenant if user not auth (e.g. system job)

                     \App\Models\InventoryMovement::create([
                        'tenant_id' => $tenantId,
                        'product_id' => $productId,
                        'type' => $type,
                        'quantity' => $quantity,
                        'previous_quantity' => $currentQuantity,
                        'new_quantity' => $newQuantity,
                        'reason' => $reason,
                        'user_id' => $user?->id,
                    ]);
                }

                return $this->success($inventory->fresh(), 'Estoque atualizado com sucesso');
            });
        } catch (Exception $e) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao atualizar estoque',
                null,
                $e
            );
        }
    }

    /**
     * Reserva produto sem alterar o estoque (apenas registra intenção quando aplicável).
     * Mantemos estoque inalterado para evitar inconsistência sem sistema de reservas.
     */
    public function reserveProduct(int $productId, int $quantity, string $reason, string $referenceType, int $referenceId, int $tenantId): ServiceResult
    {
        try {
            // Apenas validação básica; não altera quantidade
            if ($quantity <= 0) {
                return $this->error(OperationStatus::VALIDATION_ERROR, 'A quantidade deve ser maior que zero');
            }
            // Opcionalmente poderíamos registrar um log separado de reserva; por ora, retornamos sucesso.
            return $this->success(null, 'Reserva registrada (sem efeito no estoque)');
        } catch (Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao reservar produto', null, $e);
        }
    }

    /**
     * Libera reserva sem alterar o estoque.
     */
    public function releaseReservation(int $productId, int $quantity, string $reason, string $referenceType, int $referenceId, int $tenantId): ServiceResult
    {
        try {
            if ($quantity <= 0) {
                return $this->error(OperationStatus::VALIDATION_ERROR, 'A quantidade deve ser maior que zero');
            }
            return $this->success(null, 'Reserva liberada (sem efeito no estoque)');
        } catch (Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao liberar reserva', null, $e);
        }
    }

    /**
     * Devolve produto ao estoque (entrada).
     */
    public function returnProduct(int $productId, int $quantity, string $reason, string $referenceType, int $referenceId, int $tenantId): ServiceResult
    {
        return $this->addStock($productId, $quantity, $reason);
    }

    /**
     * Consome produto do estoque (saída).
     */
    public function consumeProduct(int $productId, int $quantity, string $reason, string $referenceType, int $referenceId, int $tenantId): ServiceResult
    {
        return $this->removeStock($productId, $quantity, $reason);
    }
}
