<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\InventoryMovement;
use App\Services\Shared\CacheService;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\AlertSetting;
use App\Services\AlertService;
use App\Models\Notification;
use App\Models\User;

class InventoryService
{
    protected CacheService $cacheService;
    protected AlertService $alertService;

    public function __construct(CacheService $cacheService, AlertService $alertService)
    {
        $this->cacheService = $cacheService;
        $this->alertService = $alertService;
    }

    /**
     * Consome produto do estoque
     */
    public function consumeProduct(
        int $productId,
        float $quantity,
        string $reason,
        string $referenceType,
        int $referenceId,
        int $tenantId
    ): ServiceResult {
        return $this->processInventoryMovement(
            $productId,
            -abs($quantity),
            'out',
            $reason,
            $referenceType,
            $referenceId,
            $tenantId,
            true // Validar estoque negativo
        );
    }

    /**
     * Adiciona produto ao estoque
     */
    public function addProduct(
        int $productId,
        float $quantity,
        string $reason,
        string $referenceType,
        int $referenceId,
        int $tenantId
    ): ServiceResult {
        return $this->processInventoryMovement(
            $productId,
            abs($quantity),
            'in',
            $reason,
            $referenceType,
            $referenceId,
            $tenantId
        );
    }

    /**
     * Reserva produto no estoque (não consome, apenas marca como reservado)
     */
    public function reserveProduct(
        int $productId,
        float $quantity,
        string $reason,
        string $referenceType,
        int $referenceId,
        int $tenantId
    ): ServiceResult {
        return $this->processInventoryMovement(
            $productId,
            -abs($quantity),
            'reserve',
            $reason,
            $referenceType,
            $referenceId,
            $tenantId,
            true // Validar disponibilidade
        );
    }

    /**
     * Libera reserva de produto
     */
    public function releaseReservation(
        int $productId,
        float $quantity,
        string $reason,
        string $referenceType,
        int $referenceId,
        int $tenantId
    ): ServiceResult {
        return $this->processInventoryMovement(
            $productId,
            abs($quantity),
            'release',
            $reason,
            $referenceType,
            $referenceId,
            $tenantId
        );
    }

    /**
     * Devolve produto ao estoque (reverte consumo)
     */
    public function returnProduct(
        int $productId,
        float $quantity,
        string $reason,
        string $referenceType,
        int $referenceId,
        int $tenantId
    ): ServiceResult {
        return $this->processInventoryMovement(
            $productId,
            abs($quantity),
            'return',
            $reason,
            $referenceType,
            $referenceId,
            $tenantId
        );
    }

    /**
     * Processa movimentação de estoque genérica
     */
    protected function processInventoryMovement(
        int $productId,
        float $quantity,
        string $type,
        string $reason,
        string $referenceType,
        int $referenceId,
        int $tenantId,
        bool $validateAvailability = false
    ): ServiceResult {
        try {
            return DB::transaction(function () use (
                $productId,
                $quantity,
                $type,
                $reason,
                $referenceType,
                $referenceId,
                $tenantId,
                $validateAvailability
            ) {
                // Validar produto
                $product = Product::find($productId);
                if (!$product) {
                    return ServiceResult::error('Produto não encontrado');
                }

                // Obter inventário atual
                $inventory = $this->getOrCreateInventory($productId, $tenantId);
                $previousQuantity = $inventory->quantity;
                $newQuantity = $previousQuantity + $quantity;

                // Validar estoque negativo se necessário
                if ($validateAvailability && $newQuantity < 0) {
                    return ServiceResult::error(
                        'Estoque insuficiente. Disponível: ' . $previousQuantity . ', Solicitado: ' . abs($quantity)
                    );
                }

                // Atualizar inventário
                $inventory->quantity = $newQuantity;
                $inventory->save();

                // Registrar movimentação
                $movement = $this->createInventoryMovement(
                    $productId,
                    $type,
                    abs($quantity),
                    $previousQuantity,
                    $newQuantity,
                    $reason,
                    $referenceType,
                    $referenceId,
                    $tenantId
                );

                // Limpar cache
                $this->clearInventoryCache($productId, $tenantId);

                // Verificar alertas de estoque
                $this->checkStockAlerts($inventory, $product);

                return ServiceResult::success([
                    'inventory' => $inventory,
                    'movement' => $movement,
                    'previous_quantity' => $previousQuantity,
                    'new_quantity' => $newQuantity,
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Erro ao processar movimentação de estoque', [
                'product_id' => $productId,
                'quantity' => $quantity,
                'type' => $type,
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId
            ]);

            return ServiceResult::error('Erro ao processar movimentação: ' . $e->getMessage());
        }
    }

    /**
     * Obtém ou cria registro de inventário para o produto
     */
    protected function getOrCreateInventory(int $productId, int $tenantId): ProductInventory
    {
        $inventory = ProductInventory::where('product_id', $productId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$inventory) {
            $inventory = ProductInventory::create([
                'product_id' => $productId,
                'tenant_id' => $tenantId,
                'quantity' => 0,
                'min_quantity' => 0,
                'max_quantity' => null,
            ]);
        }

        return $inventory;
    }

    /**
     * Cria registro de movimentação de inventário
     */
    protected function createInventoryMovement(
        int $productId,
        string $type,
        float $quantity,
        float $previousQuantity,
        float $newQuantity,
        string $reason,
        string $referenceType,
        int $referenceId,
        int $tenantId
    ): InventoryMovement {
        return InventoryMovement::create([
            'tenant_id' => $tenantId,
            'product_id' => $productId,
            'type' => $type,
            'quantity' => $quantity,
            'previous_quantity' => $previousQuantity,
            'new_quantity' => $newQuantity,
            'reason' => $reason,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
        ]);
    }

    /**
     * Verifica e envia alertas de estoque
     */
    protected function checkStockAlerts(ProductInventory $inventory, Product $product): void
    {
        try {
            // Verificar estoque baixo
            if ($inventory->isLowStock()) {
                $this->sendLowStockAlert($inventory, $product);
            }

            // Verificar estoque alto
            if ($inventory->isHighStock()) {
                $this->sendHighStockAlert($inventory, $product);
            }
        } catch (\Exception $e) {
            Log::error('Erro ao verificar alertas de estoque', [
                'inventory_id' => $inventory->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Envia alerta de estoque baixo
     */
    protected function sendLowStockAlert(ProductInventory $inventory, Product $product): void
    {
        $alertSetting = AlertSetting::where('tenant_id', $inventory->tenant_id)
            ->where('type', 'low_stock')
            ->first();

        if (!$alertSetting || !$alertSetting->is_active) {
            return;
        }

        $users = User::where('tenant_id', $inventory->tenant_id)
            ->whereHas('roles.permissions', function ($query) {
                $query->where('name', 'manage_inventory');
            })
            ->get();

        foreach ($users as $user) {
            $notification = Notification::create([
                'tenant_id' => $inventory->tenant_id,
                'user_id' => $user->id,
                'type' => 'low_stock',
                'title' => 'Estoque Baixo: ' . $product->name,
                'message' => "O produto {$product->name} está com estoque baixo. Quantidade atual: {$inventory->quantity}, Mínimo: {$inventory->min_quantity}",
                'data' => [
                    'product_id' => $product->id,
                    'current_quantity' => $inventory->quantity,
                    'min_quantity' => $inventory->min_quantity,
                ],
                'read' => false,
            ]);

            // Enviar email se configurado
            if ($alertSetting->send_email && $user->email) {
                $this->alertService->sendLowStockEmail($user, $product, $inventory);
            }
        }
    }

    /**
     * Envia alerta de estoque alto
     */
    protected function sendHighStockAlert(ProductInventory $inventory, Product $product): void
    {
        $alertSetting = AlertSetting::where('tenant_id', $inventory->tenant_id)
            ->where('type', 'high_stock')
            ->first();

        if (!$alertSetting || !$alertSetting->is_active) {
            return;
        }

        $users = User::where('tenant_id', $inventory->tenant_id)
            ->whereHas('roles.permissions', function ($query) {
                $query->where('name', 'manage_inventory');
            })
            ->get();

        foreach ($users as $user) {
            $notification = Notification::create([
                'tenant_id' => $inventory->tenant_id,
                'user_id' => $user->id,
                'type' => 'high_stock',
                'title' => 'Estoque Alto: ' . $product->name,
                'message' => "O produto {$product->name} está com estoque alto. Quantidade atual: {$inventory->quantity}, Máximo: {$inventory->max_quantity}",
                'data' => [
                    'product_id' => $product->id,
                    'current_quantity' => $inventory->quantity,
                    'max_quantity' => $inventory->max_quantity,
                ],
                'read' => false,
            ]);

            // Enviar email se configurado
            if ($alertSetting->send_email && $user->email) {
                $this->alertService->sendHighStockEmail($user, $product, $inventory);
            }
        }
    }

    /**
     * Limpa cache do inventário
     */
    protected function clearInventoryCache(int $productId, int $tenantId): void
    {
        $cacheKey = "inventory_product_{$productId}_tenant_{$tenantId}";
        $this->cacheService->forget($cacheKey);
    }

    /**
     * Obtém relatório de giro de estoque
     */
    public function getStockTurnoverReport(int $tenantId, array $filters = []): array
    {
        $startDate = $filters['start_date'] ?? now()->subDays(30);
        $endDate = $filters['end_date'] ?? now();
        $productId = $filters['product_id'] ?? null;

        $movements = InventoryMovement::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($productId, function ($query) use ($productId) {
                return $query->where('product_id', $productId);
            })
            ->with(['product'])
            ->get();

        $report = [];
        foreach ($movements->groupBy('product_id') as $productId => $productMovements) {
            $product = $productMovements->first()->product;
            
            $totalIn = $productMovements->where('type', 'in')->sum('quantity');
            $totalOut = $productMovements->where('type', 'out')->sum('quantity');
            $totalReserved = $productMovements->where('type', 'reserve')->sum('quantity');
            $totalReturned = $productMovements->where('type', 'return')->sum('quantity');

            $averageStock = $this->calculateAverageStock($productId, $tenantId, $startDate, $endDate);
            $turnoverRate = $averageStock > 0 ? ($totalOut / $averageStock) : 0;

            $report[] = [
                'product' => $product,
                'total_in' => $totalIn,
                'total_out' => $totalOut,
                'total_reserved' => $totalReserved,
                'total_returned' => $totalReturned,
                'average_stock' => $averageStock,
                'turnover_rate' => $turnoverRate,
                'turnover_classification' => $this->classifyTurnover($turnoverRate),
            ];
        }

        return $report;
    }

    /**
     * Calcula estoque médio no período
     */
    protected function calculateAverageStock(int $productId, int $tenantId, $startDate, $endDate): float
    {
        $movements = InventoryMovement::where('product_id', $productId)
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at')
            ->get();

        if ($movements->isEmpty()) {
            return 0;
        }

        $totalStock = 0;
        $daysCount = 0;
        $currentStock = $movements->first()->previous_quantity;
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dayMovements = $movements->filter(function ($movement) use ($currentDate) {
                return $movement->created_at->format('Y-m-d') === $currentDate->format('Y-m-d');
            });

            foreach ($dayMovements as $movement) {
                $currentStock = $movement->new_quantity;
            }

            $totalStock += $currentStock;
            $daysCount++;
            $currentDate->addDay();
        }

        return $daysCount > 0 ? ($totalStock / $daysCount) : 0;
    }

    /**
     * Classifica o giro de estoque
     */
    protected function classifyTurnover(float $turnoverRate): string
    {
        if ($turnoverRate >= 12) {
            return 'Muito Alto';
        } elseif ($turnoverRate >= 6) {
            return 'Alto';
        } elseif ($turnoverRate >= 3) {
            return 'Médio';
        } elseif ($turnoverRate >= 1) {
            return 'Baixo';
        } else {
            return 'Muito Baixo';
        }
    }

    /**
     * Obtém produtos mais utilizados
     */
    public function getMostUsedProducts(int $tenantId, int $limit = 10, array $filters = []): array
    {
        $startDate = $filters['start_date'] ?? now()->subDays(30);
        $endDate = $filters['end_date'] ?? now();

        $movements = InventoryMovement::where('tenant_id', $tenantId)
            ->where('type', 'out')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['product'])
            ->get();

        $productUsage = $movements->groupBy('product_id')->map(function ($productMovements) {
            return [
                'product' => $productMovements->first()->product,
                'total_quantity' => $productMovements->sum('quantity'),
                'movement_count' => $productMovements->count(),
                'average_quantity' => $productMovements->avg('quantity'),
            ];
        })->sortByDesc('total_quantity')->take($limit)->values();

        return $productUsage->toArray();
    }

    /**
     * Realiza ajuste manual de estoque
     */
    public function adjustStock(
        int $productId,
        float $newQuantity,
        string $reason,
        int $userId,
        int $tenantId
    ): ServiceResult {
        try {
            return DB::transaction(function () use ($productId, $newQuantity, $reason, $userId, $tenantId) {
                // Validar produto
                $product = Product::find($productId);
                if (!$product) {
                    return ServiceResult::error('Produto não encontrado');
                }

                // Obter inventário atual
                $inventory = $this->getOrCreateInventory($productId, $tenantId);
                $previousQuantity = $inventory->quantity;
                $adjustment = $newQuantity - $previousQuantity;

                // Validar razão do ajuste
                if (empty(trim($reason))) {
                    return ServiceResult::error('Razão do ajuste é obrigatória');
                }

                // Criar movimentação de ajuste
                $movement = $this->createInventoryMovement(
                    $productId,
                    'adjustment',
                    abs($adjustment),
                    $previousQuantity,
                    $newQuantity,
                    "AJUSTE MANUAL: {$reason}",
                    'User',
                    $userId,
                    $tenantId
                );

                // Atualizar inventário
                $inventory->quantity = $newQuantity;
                $inventory->save();

                // Limpar cache
                $this->clearInventoryCache($productId, $tenantId);

                // Verificar alertas de estoque
                $this->checkStockAlerts($inventory, $product);

                // Registrar auditoria
                Log::info('Ajuste manual de estoque realizado', [
                    'product_id' => $productId,
                    'previous_quantity' => $previousQuantity,
                    'new_quantity' => $newQuantity,
                    'adjustment' => $adjustment,
                    'reason' => $reason,
                    'user_id' => $userId,
                    'tenant_id' => $tenantId
                ]);

                return ServiceResult::success([
                    'inventory' => $inventory,
                    'movement' => $movement,
                    'previous_quantity' => $previousQuantity,
                    'new_quantity' => $newQuantity,
                    'adjustment' => $adjustment,
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Erro ao realizar ajuste manual de estoque', [
                'product_id' => $productId,
                'new_quantity' => $newQuantity,
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId
            ]);

            return ServiceResult::error('Erro ao realizar ajuste: ' . $e->getMessage());
        }
    }

    /**
     * Valida ajuste de estoque
     */
    public function validateStockAdjustment(int $productId, float $newQuantity, int $tenantId): array
    {
        $errors = [];

        // Validar produto
        $product = Product::find($productId);
        if (!$product) {
            $errors[] = 'Produto não encontrado';
            return $errors;
        }

        // Validar quantidade
        if ($newQuantity < 0) {
            $errors[] = 'Quantidade não pode ser negativa';
        }

        // Obter inventário atual
        $inventory = $this->getOrCreateInventory($productId, $tenantId);

        // Validar limite máximo se definido
        if ($inventory->max_quantity && $newQuantity > $inventory->max_quantity) {
            $errors[] = "Quantidade excede o limite máximo de {$inventory->max_quantity}";
        }

        return $errors;
    }
}