<?php

namespace App\Services\Domain;

use App\Models\Product;
use App\Models\ProductInventory;
use App\Repositories\InventoryRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryService extends AbstractBaseService
{
    /**
     * @var InventoryRepository
     */
    protected $inventoryRepository;

    /**
     * InventoryService constructor.
     *
     * @param InventoryRepository $inventoryRepository
     */
    public function __construct(InventoryRepository $inventoryRepository)
    {
        $this->inventoryRepository = $inventoryRepository;
    }

    /**
     * Get inventory for a product.
     *
     * @param int $productId
     * @return ServiceResult
     */
    public function getProductInventory(int $productId): ServiceResult
    {
        try {
            $inventory = $this->inventoryRepository->findByProductId($productId);
            
            if (!$inventory) {
                return $this->success(null, 'Produto sem registro de inventário');
            }

            return $this->success($inventory, 'Inventário encontrado');
        } catch (Exception $e) {
            return $this->error(
                'error',
                'Erro ao buscar inventário',
                null,
                $e
            );
        }
    }

    /**
     * Get inventory movements for a product.
     *
     * @param int $productId
     * @param int $perPage
     * @return ServiceResult
     */
    public function getProductMovements(int $productId, int $perPage = 15): ServiceResult
    {
        try {
            $movements = $this->inventoryRepository->getMovementsByProductId($productId, $perPage);
            
            return $this->success($movements, 'Movimentações encontradas');
        } catch (Exception $e) {
            return $this->error(
                'error',
                'Erro ao buscar movimentações',
                null,
                $e
            );
        }
    }

    /**
     * Get low stock products.
     *
     * @param int $limit
     * @return ServiceResult
     */
    public function getLowStockProducts(int $limit = 10): ServiceResult
    {
        try {
            $products = $this->inventoryRepository->getLowStockProducts($limit);
            
            return $this->success($products, 'Produtos com estoque baixo encontrados');
        } catch (Exception $e) {
            return $this->error(
                'error',
                'Erro ao buscar produtos com estoque baixo',
                null,
                $e
            );
        }
    }

    /**
     * Get inventory summary.
     *
     * @return ServiceResult
     */
    public function getInventorySummary(): ServiceResult
    {
        try {
            $summary = $this->inventoryRepository->getInventorySummary();
            
            return $this->success($summary, 'Resumo de inventário obtido');
        } catch (Exception $e) {
            return $this->error(
                'error',
                'Erro ao obter resumo de inventário',
                null,
                $e
            );
        }
    }

    /**
     * Register inventory entry.
     *
     * @param int $productId
     * @param int $quantity
     * @param string $reason
     * @param array $additionalData
     * @return ServiceResult
     */
    public function registerEntry(int $productId, int $quantity, string $reason = '', array $additionalData = []): ServiceResult
    {
        try {
            return DB::transaction(function () use ($productId, $quantity, $reason, $additionalData) {
                if ($quantity <= 0) {
                    return $this->error(
                        'validation_error',
                        'Quantidade deve ser maior que zero'
                    );
                }

                $inventory = $this->inventoryRepository->adjustQuantity(
                    $productId,
                    $quantity,
                    'in',
                    $reason ?: 'Entrada de estoque',
                    $additionalData
                );

                return $this->success($inventory, 'Entrada de estoque registrada com sucesso');
            });
        } catch (Exception $e) {
            return $this->error(
                'error',
                'Erro ao registrar entrada de estoque',
                null,
                $e
            );
        }
    }

    /**
     * Register inventory exit.
     *
     * @param int $productId
     * @param int $quantity
     * @param string $reason
     * @param array $additionalData
     * @return ServiceResult
     */
    public function registerExit(int $productId, int $quantity, string $reason = '', array $additionalData = []): ServiceResult
    {
        try {
            return DB::transaction(function () use ($productId, $quantity, $reason, $additionalData) {
                if ($quantity <= 0) {
                    return $this->error(
                        'validation_error',
                        'Quantidade deve ser maior que zero'
                    );
                }

                // Check if there's enough stock
                $inventory = $this->inventoryRepository->findByProductId($productId);
                if (!$inventory || $inventory->quantity < $quantity) {
                    return $this->error(
                        'validation_error',
                        'Estoque insuficiente para esta operação'
                    );
                }

                $inventory = $this->inventoryRepository->adjustQuantity(
                    $productId,
                    $quantity,
                    'out',
                    $reason ?: 'Saída de estoque',
                    $additionalData
                );

                return $this->success($inventory, 'Saída de estoque registrada com sucesso');
            });
        } catch (Exception $e) {
            return $this->error(
                'error',
                'Erro ao registrar saída de estoque',
                null,
                $e
            );
        }
    }

    /**
     * Adjust inventory quantity.
     *
     * @param int $productId
     * @param int $newQuantity
     * @param string $reason
     * @return ServiceResult
     */
    public function adjustQuantity(int $productId, int $newQuantity, string $reason = ''): ServiceResult
    {
        try {
            return DB::transaction(function () use ($productId, $newQuantity, $reason) {
                if ($newQuantity < 0) {
                    return $this->error(
                        'validation_error',
                        'Quantidade não pode ser negativa'
                    );
                }

                $inventory = $this->inventoryRepository->findByProductId($productId);
                $currentQuantity = $inventory ? $inventory->quantity : 0;
                $difference = $newQuantity - $currentQuantity;

                if ($difference === 0) {
                    return $this->success($inventory, 'Quantidade já está correta');
                }

                $type = $difference > 0 ? 'in' : 'out';
                $quantity = abs($difference);

                $inventory = $this->inventoryRepository->adjustQuantity(
                    $productId,
                    $quantity,
                    $type,
                    $reason ?: 'Ajuste de inventário'
                );

                return $this->success($inventory, 'Quantidade ajustada com sucesso');
            });
        } catch (Exception $e) {
            return $this->error(
                'error',
                'Erro ao ajustar quantidade',
                null,
                $e
            );
        }
    }

    /**
     * Set inventory parameters.
     *
     * @param int $productId
     * @param int $minQuantity
     * @param int|null $maxQuantity
     * @return ServiceResult
     */
    public function setInventoryParameters(int $productId, int $minQuantity, ?int $maxQuantity = null): ServiceResult
    {
        try {
            $data = [
                'min_quantity' => $minQuantity,
                'max_quantity' => $maxQuantity,
            ];

            $inventory = $this->inventoryRepository->createOrUpdateInventory($productId, $data);

            return $this->success($inventory, 'Parâmetros de inventário definidos com sucesso');
        } catch (Exception $e) {
            return $this->error(
                'error',
                'Erro ao definir parâmetros de inventário',
                null,
                $e
            );
        }
    }

    /**
     * Check if product can be deleted.
     *
     * @param int $productId
     * @return ServiceResult
     */
    public function canDeleteProduct(int $productId): ServiceResult
    {
        try {
            $canDelete = $this->inventoryRepository->canDeleteProduct($productId);
            
            if ($canDelete) {
                return $this->success(true, 'Produto pode ser excluído');
            } else {
                return $this->error(
                    'validation_error',
                    'Produto não pode ser excluído pois possui movimentações de inventário'
                );
            }
        } catch (Exception $e) {
            return $this->error(
                'error',
                'Erro ao verificar se produto pode ser excluído',
                null,
                $e
            );
        }
    }

    /**
     * Process inventory for service items.
     *
     * @param array $items
     * @param string $type
     * @param string $reason
     * @return ServiceResult
     */
    public function processServiceItems(array $items, string $type, string $reason = ''): ServiceResult
    {
        try {
            return DB::transaction(function () use ($items, $type, $reason) {
                foreach ($items as $item) {
                    if (isset($item['product_id']) && isset($item['quantity'])) {
                        if ($type === 'out') {
                            $result = $this->registerExit(
                                $item['product_id'],
                                $item['quantity'],
                                $reason ?: 'Utilização em serviço'
                            );
                        } elseif ($type === 'in') {
                            $result = $this->registerEntry(
                                $item['product_id'],
                                $item['quantity'],
                                $reason ?: 'Devolução de serviço'
                            );
                        }

                        if (!$result->isSuccess()) {
                            return $result;
                        }
                    }
                }

                return $this->success(null, 'Itens do serviço processados com sucesso');
            });
        } catch (Exception $e) {
            return $this->error(
                'error',
                'Erro ao processar itens do serviço',
                null,
                $e
            );
        }
    }
}