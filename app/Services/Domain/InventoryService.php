<?php

namespace App\Services\Domain;

use App\Repositories\InventoryRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryService extends AbstractBaseService
{
    public function __construct(
        private InventoryRepository $inventoryRepository,
    ) {
        parent::__construct( $inventoryRepository );
    }

    // ========== VALIDAÇÕES DE NEGÓCIO ==========

    private function validateQuantity( int $quantity ): ServiceResult
    {
        if ( $quantity < 0 ) {
            return $this->error( 'Quantidade não pode ser negativa' );
        }
        return $this->success();
    }

    private function validateSufficientStock( int $productId, int $tenantId, int $quantity ): ServiceResult
    {
        $inventory = $this->inventoryRepository->findByProduct( $productId, $tenantId );

        if ( !$inventory ) {
            return $this->error( 'Produto não encontrado no estoque' );
        }

        if ( $inventory->quantity < $quantity ) {
            return $this->error(
                "Estoque insuficiente. Disponível: {$inventory->quantity}, Solicitado: {$quantity}",
            );
        }

        return $this->success( $inventory );
    }

    private function validateMinMaxQuantity( ?int $minQuantity, ?int $maxQuantity ): ServiceResult
    {
        if ( $minQuantity !== null && $minQuantity < 0 ) {
            return $this->error( 'Quantidade mínima não pode ser negativa' );
        }

        if ( $maxQuantity !== null && $maxQuantity < 0 ) {
            return $this->error( 'Quantidade máxima não pode ser negativa' );
        }

        if ( $minQuantity !== null && $maxQuantity !== null && $minQuantity > $maxQuantity ) {
            return $this->error( 'Quantidade mínima não pode ser maior que a máxima' );
        }

        return $this->success();
    }

    // ========== MÉTODOS PÚBLICOS COM VALIDAÇÕES ==========

    public function addStock( int $productId, int $tenantId, int $quantity, ?string $reason = null ): ServiceResult
    {
        $validation = $this->validateQuantity( $quantity );
        if ( !$validation->isSuccess() ) {
            return $validation;
        }

        try {
            DB::beginTransaction();

            $inventory = $this->inventoryRepository->findByProduct( $productId, $tenantId );

            if ( !$inventory ) {
                $inventory = $this->inventoryRepository->updateOrCreate( $productId, $tenantId, [
                    'quantity'     => $quantity,
                    'min_quantity' => 0,
                    'max_quantity' => null,
                ] );
            } else {
                $newQuantity = $inventory->quantity + $quantity;
                $this->inventoryRepository->updateQuantity( $productId, $tenantId, $newQuantity );
                $inventory->quantity = $newQuantity;
            }

            Log::info( 'Stock added', [
                'product_id' => $productId,
                'tenant_id'  => $tenantId,
                'quantity'   => $quantity,
                'new_total'  => $inventory->quantity,
                'reason'     => $reason,
            ] );

            DB::commit();
            return $this->success( $inventory, 'Estoque adicionado com sucesso' );

        } catch ( \Exception $e ) {
            DB::rollBack();
            Log::error( 'Error adding stock', [ 'product_id' => $productId, 'error' => $e->getMessage() ] );
            return $this->error( 'Erro ao adicionar estoque: ' . $e->getMessage() );
        }
    }

    public function removeStock( int $productId, int $tenantId, int $quantity, ?string $reason = null ): ServiceResult
    {
        $validation = $this->validateQuantity( $quantity );
        if ( !$validation->isSuccess() ) {
            return $validation;
        }

        $stockValidation = $this->validateSufficientStock( $productId, $tenantId, $quantity );
        if ( !$stockValidation->isSuccess() ) {
            return $stockValidation;
        }

        try {
            DB::beginTransaction();

            $inventory   = $stockValidation->getData();
            $newQuantity = $inventory->quantity - $quantity;

            $this->inventoryRepository->updateQuantity( $productId, $tenantId, $newQuantity );
            $inventory->quantity = $newQuantity;

            Log::info( 'Stock removed', [
                'product_id' => $productId,
                'tenant_id'  => $tenantId,
                'quantity'   => $quantity,
                'new_total'  => $inventory->quantity,
                'reason'     => $reason,
            ] );

            DB::commit();
            return $this->success( $inventory, 'Estoque removido com sucesso' );

        } catch ( \Exception $e ) {
            DB::rollBack();
            Log::error( 'Error removing stock', [ 'product_id' => $productId, 'error' => $e->getMessage() ] );
            return $this->error( 'Erro ao remover estoque: ' . $e->getMessage() );
        }
    }

    public function setStock( int $productId, int $tenantId, int $quantity, ?string $reason = null ): ServiceResult
    {
        $validation = $this->validateQuantity( $quantity );
        if ( !$validation->isSuccess() ) {
            return $validation;
        }

        try {
            DB::beginTransaction();

            $inventory = $this->inventoryRepository->updateOrCreate( $productId, $tenantId, [
                'quantity' => $quantity,
            ] );

            Log::info( 'Stock set', [ 'product_id' => $productId, 'tenant_id' => $tenantId, 'quantity' => $quantity, 'reason' => $reason ] );

            DB::commit();
            return $this->success( $inventory, 'Estoque ajustado com sucesso' );

        } catch ( \Exception $e ) {
            DB::rollBack();
            Log::error( 'Error setting stock', [ 'product_id' => $productId, 'error' => $e->getMessage() ] );
            return $this->error( 'Erro ao ajustar estoque: ' . $e->getMessage() );
        }
    }

    public function updateMinMaxQuantities( int $productId, int $tenantId, ?int $minQuantity = null, ?int $maxQuantity = null ): ServiceResult
    {
        $validation = $this->validateMinMaxQuantity( $minQuantity, $maxQuantity );
        if ( !$validation->isSuccess() ) {
            return $validation;
        }

        try {
            $data = [];
            if ( $minQuantity !== null ) $data[ 'min_quantity' ] = $minQuantity;
            if ( $maxQuantity !== null ) $data[ 'max_quantity' ] = $maxQuantity;

            $inventory = $this->inventoryRepository->updateOrCreate( $productId, $tenantId, $data );
            return $this->success( $inventory, 'Limites de estoque atualizados' );

        } catch ( \Exception $e ) {
            Log::error( 'Error updating min/max', [ 'product_id' => $productId, 'error' => $e->getMessage() ] );
            return $this->error( 'Erro ao atualizar limites: ' . $e->getMessage() );
        }
    }

    public function hasSufficientStock( int $productId, int $tenantId, int $requiredQuantity ): ServiceResult
    {
        $validation = $this->validateSufficientStock( $productId, $tenantId, $requiredQuantity );
        return $validation->isSuccess()
            ? $this->success( true, 'Estoque suficiente' )
            : $this->error( $validation->getMessage() );
    }

    public function getLowStockAlerts( int $tenantId ): ServiceResult
    {
        try {
            $lowStockItems = $this->inventoryRepository->getLowStockItems( $tenantId, 50 );
            return $this->success( [ 'items' => $lowStockItems, 'count' => $lowStockItems->count() ] );
        } catch ( \Exception $e ) {
            Log::error( 'Error getting alerts', [ 'tenant_id' => $tenantId, 'error' => $e->getMessage() ] );
            return $this->error( 'Erro ao buscar alertas: ' . $e->getMessage() );
        }
    }

}
