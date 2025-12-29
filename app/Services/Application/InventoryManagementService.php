<?php

declare(strict_types=1);

namespace App\Services\Application;

use App\Actions\Inventory\ReserveProductStockAction;
use App\Actions\Inventory\UpdateProductStockAction;
use App\DTOs\Inventory\InventoryFilterDTO;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Repositories\InventoryRepository;
use App\Repositories\ProductRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Serviço para gestão de inventário e estoque.
 */
class InventoryManagementService extends AbstractBaseService
{
    public function __construct(
        protected InventoryRepository $inventoryRepository,
        protected ProductRepository $productRepository,
        private UpdateProductStockAction $updateStockAction,
        private ReserveProductStockAction $reserveStockAction
    ) {
        parent::__construct($inventoryRepository);
    }

    /**
     * Obtém dados para o dashboard de inventário.
     */
    public function getDashboardData(): ServiceResult
    {
        return $this->safeExecute(function () {
            $stats = $this->inventoryRepository->getStatistics();

            return [
                'totalProducts' => $stats['total_items'],
                'sufficientStockProducts' => $stats['sufficient_stock_items_count'],
                'lowStockProducts' => $stats['low_stock_items_count'],
                'highStockProducts' => $stats['high_stock_items_count'],
                'outOfStockProducts' => $stats['out_of_stock_items_count'],
                'totalInventoryValue' => $stats['total_inventory_value'],
                'reservedItemsCount' => $stats['reserved_items_count'],
                'totalReservedQuantity' => $stats['total_reserved_quantity'],
                'lowStockItems' => $this->inventoryRepository->getLowStockItems(5),
                'highStockItems' => $this->inventoryRepository->getHighStockItems(5),
                'recentMovements' => InventoryMovement::with(['product', 'user'])->latest()->take(5)->get(),
            ];
        });
    }

    /**
     * Obtém dados para a listagem principal de inventário com estado inicial vazio.
     */
    public function getEmptyIndexData(): ServiceResult
    {
        return $this->safeExecute(function () {
            return [
                'inventories' => new LengthAwarePaginator([], 0, 10),
                'stats' => [
                    'total_items' => 0,
                    'total_inventory_value' => 0,
                    'sufficient_stock_items_count' => 0,
                    'low_stock_items_count' => 0,
                    'out_of_stock_items_count' => 0,
                ],
                'categories' => Category::whereNull('parent_id')->with('children')->get(),
                'filters' => [],
            ];
        });
    }

    /**
     * Obtém dados para a listagem principal de inventário.
     */
    public function getIndexData(InventoryFilterDTO $filterDto): ServiceResult
    {
        return $this->safeExecute(function () use ($filterDto) {
            $filters = $filterDto->toFilterArray();
            $perPage = $filterDto->per_page;

            // Mapeamento de status amigável para filtros do repositório
            if (! empty($filters['status'])) {
                match ($filters['status']) {
                    'low' => $filters['low_stock'] = true,
                    'out' => $filters['out_of_stock'] = true,
                    'sufficient' => $filters['custom_sufficient'] = true,
                    default => null
                };
            }

            return [
                'inventories' => $this->inventoryRepository->getPaginated($filters, $perPage),
                'stats' => $this->inventoryRepository->getStatistics($filters),
                'categories' => Category::whereNull('parent_id')->with('children')->get(),
                'filters' => $filterDto->toDisplayArray(),
            ];
        });
    }

    /**
     * Obtém dados para a listagem de movimentações com estado inicial vazio.
     */
    public function getEmptyMovementsData(): ServiceResult
    {
        return $this->safeExecute(function () {
            return [
                'movements' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10),
                'products' => \App\Models\Product::all(),
                'summary' => [
                    'total_entries' => 0,
                    'count_entries' => 0,
                    'total_exits' => 0,
                    'count_exits' => 0,
                    'total_adjustments' => 0,
                    'count_adjustments' => 0,
                    'total_reservations' => 0,
                    'count_reservations' => 0,
                    'total_cancellations' => 0,
                    'count_cancellations' => 0,
                    'balance' => 0,
                ],
            ];
        });
    }

    /**
     * Obtém dados de movimentações de inventário.
     */
    public function getMovementsData(array $filters = []): ServiceResult
    {
        return $this->safeExecute(function () use ($filters) {
            $query = \App\Models\InventoryMovement::with(['product', 'user']);

            // Validação de datas
            if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
                if ($filters['start_date'] > $filters['end_date']) {
                    throw new Exception('A data inicial não pode ser maior que a data final.');
                }
            }

            if (! empty($filters['product_id'])) {
                $query->where('product_id', $filters['product_id']);
            } elseif (! empty($filters['search'])) {
                $search = $filters['search'];
                $query->whereHas('product', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            } elseif (! empty($filters['sku'])) {
                $product = \App\Models\Product::where('sku', $filters['sku'])->first();
                if ($product) {
                    $query->where('product_id', $product->id);
                }
            }

            if (! empty($filters['type']) && $filters['type'] !== 'all') {
                $query->where('type', $filters['type']);
            }

            if (! empty($filters['start_date'])) {
                $query->whereDate('created_at', '>=', $filters['start_date']);
            }

            if (! empty($filters['end_date'])) {
                $query->whereDate('created_at', '<=', $filters['end_date']);
            }

            $movements = $query->latest()->paginate($filters['per_page'] ?? 10);

            return [
                'movements' => $movements,
                'products' => \App\Models\Product::all(),
                'summary' => $this->calculateMovementSummary($query),
            ];
        });
    }

    /**
     * Calcula o resumo de movimentações baseado em uma query.
     */
    public function calculateMovementSummary($query): array
    {
        $summaryQuery = clone $query;
        $summary = [
            'total_entries' => (clone $summaryQuery)->where('type', 'entry')->sum('quantity'),
            'count_entries' => (clone $summaryQuery)->where('type', 'entry')->count(),
            'total_exits' => (clone $summaryQuery)->where('type', 'exit')->sum('quantity'),
            'count_exits' => (clone $summaryQuery)->where('type', 'exit')->count(),
            'total_adjustments' => (clone $summaryQuery)->where('type', 'adjustment')->sum('quantity'),
            'count_adjustments' => (clone $summaryQuery)->where('type', 'adjustment')->count(),
            'total_reservations' => (clone $summaryQuery)->where('type', 'reservation')->sum('quantity'),
            'count_reservations' => (clone $summaryQuery)->where('type', 'reservation')->count(),
            'total_cancellations' => (clone $summaryQuery)->where('type', 'cancellation')->sum('quantity'),
            'count_cancellations' => (clone $summaryQuery)->where('type', 'cancellation')->count(),
        ];
        $summary['balance'] = $summary['total_entries'] - $summary['total_exits'];

        return $summary;
    }

    /**
     * Obtém detalhes de uma movimentação específica.
     */
    public function getMovementDetails(int $id): ServiceResult
    {
        return $this->safeExecute(function () use ($id) {
            $movement = \App\Models\InventoryMovement::with(['product.inventory', 'user'])->find($id);

            if (!$movement) {
                throw new Exception("Movimentação #{$id} não encontrada.");
            }

            return [
                'movement' => $movement,
                'product' => $movement->product,
                'user' => $movement->user,
            ];
        });
    }

    /**
     * Obtém dados de giro de estoque.
     */
    public function getStockTurnoverData(array $filters = []): ServiceResult
    {
        return $this->safeExecute(function () use ($filters) {
            // Validação de datas
            if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
                if ($filters['start_date'] > $filters['end_date']) {
                    throw new Exception('A data inicial não pode ser maior que a data final.');
                }
            }

            $startDate = $filters['start_date'] ?? now()->startOfMonth()->format('Y-m-d');
            $endDate = $filters['end_date'] ?? now()->format('Y-m-d');
            $categoryId = $filters['category_id'] ?? null;
            $search = $filters['search'] ?? null;

            $query = \App\Models\Product::query()
                ->select('products.*')
                ->join('product_inventory', 'products.id', '=', 'product_inventory.product_id')
                ->with(['category', 'inventory']);

            if ($categoryId) {
                $query->where('category_id', $categoryId);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            }

            $products = $query->get()->map(function ($product) use ($startDate, $endDate) {
                $movements = \App\Models\InventoryMovement::where('product_id', $product->id)
                    ->whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59']);

                $totalEntries = (clone $movements)->where('type', 'entry')->sum('quantity');
                $totalExits = (clone $movements)->where('type', 'exit')->sum('quantity');

                // Cálculo simplificado de estoque médio: (estoque inicial + estoque final) / 2
                // Para simplificar ainda mais, vamos usar o estoque atual como aproximação se não tivermos dados históricos precisos
                $currentStock = $product->inventory->quantity ?? 0;
                $initialStock = $currentStock - $totalEntries + $totalExits;
                $averageStock = ($initialStock + $currentStock) / 2;

                $product->total_entries = $totalEntries;
                $product->total_exits = $totalExits;
                $product->average_stock = $averageStock > 0 ? $averageStock : 1; // Evita divisão por zero

                return $product;
            });

            $totalProducts = $products->count();
            $totalEntries = $products->sum('total_entries');
            $totalExits = $products->sum('total_exits');

            $totalTurnover = $products->sum(function ($p) {
                return $p->average_stock > 0 ? $p->total_exits / $p->average_stock : 0;
            });
            $averageTurnover = $totalProducts > 0 ? $totalTurnover / $totalProducts : 0;

            $paginatedProducts = $query->paginate($filters['per_page'] ?? 10);
            $paginatedProducts->getCollection()->transform(function ($product) use ($startDate, $endDate) {
                $movements = \App\Models\InventoryMovement::where('product_id', $product->id)
                    ->whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59']);

                $totalEntries = (clone $movements)->where('type', 'entry')->sum('quantity');
                $totalExits = (clone $movements)->where('type', 'exit')->sum('quantity');

                $currentStock = $product->inventory->quantity ?? 0;
                $initialStock = $currentStock - $totalEntries + $totalExits;
                $averageStock = ($initialStock + $currentStock) / 2;

                $product->total_entries = $totalEntries;
                $product->total_exits = $totalExits;
                $product->average_stock = $averageStock > 0 ? $averageStock : 1;

                return $product;
            });

            return [
                'stockTurnover' => $paginatedProducts,
                'categories' => \App\Models\Category::all(),
                'filters' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'category_id' => $categoryId,
                    'search' => $search,
                ],
                'reportData' => [
                    'total_products' => $totalProducts,
                    'total_entries' => $totalEntries,
                    'total_exits' => $totalExits,
                    'average_turnover' => $averageTurnover,
                ],
            ];
        });
    }

    /**
     * Obtém dados de produtos mais utilizados.
     */
    public function getMostUsedProductsData(array $filters = []): ServiceResult
    {
        return $this->safeExecute(function () use ($filters) {
            // Validação de datas
            if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
                if ($filters['start_date'] > $filters['end_date']) {
                    throw new Exception('A data inicial não pode ser maior que a data final.');
                }
            }

            $startDate = $filters['start_date'] ?? now()->subMonths(1)->format('Y-m-d');
            $endDate = $filters['end_date'] ?? now()->format('Y-m-d');

            $movements = \App\Models\InventoryMovement::selectRaw('product_id, SUM(quantity) as total_usage')
                ->where('type', 'exit')
                ->whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
                ->groupBy('product_id')
                ->orderByDesc('total_usage')
                ->with(['product.category', 'product.inventory'])
                ->get();

            $totalUsageAll = $movements->sum('total_usage');
            $days = max(1, now()->parse($startDate)->diffInDays(now()->parse($endDate)));

            $products = $movements->map(function ($m) use ($totalUsageAll, $days) {
                $p = $m->product;

                return [
                    'id' => $p->id,
                    'sku' => $p->sku,
                    'name' => $p->name,
                    'category' => $p->category->name ?? 'N/A',
                    'total_usage' => $m->total_usage,
                    'average_usage' => $m->total_usage / $days,
                    'total_value' => $m->total_usage * ($p->price ?? 0),
                    'unit_price' => $p->price ?? 0,
                    'percentage_of_total' => $totalUsageAll > 0 ? ($m->total_usage / $totalUsageAll) * 100 : 0,
                    'current_stock' => $p->inventory->quantity ?? 0,
                    'min_quantity' => $p->inventory->min_quantity ?? 0,
                ];
            });

            return [
                'products' => $products,
                'filters' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
            ];
        });
    }

    /**
     * Obtém dados de alertas de estoque.
     */
    public function getAlertsData(): ServiceResult
    {
        return $this->safeExecute(function () {
            return [
                'lowStockProducts' => $this->inventoryRepository->getPaginated(['low_stock' => true], 10),
                'highStockProducts' => $this->inventoryRepository->getPaginated(['high_stock' => true], 10),
            ];
        });
    }

    /**
     * Busca um produto pelo SKU com inventário carregado.
     */
    public function getProductBySku(string $sku): ServiceResult
    {
        return $this->safeExecute(function () use ($sku) {
            $product = $this->productRepository->findBySku($sku, ['inventory', 'inventoryMovements.user']);
            if (! $product) {
                throw new Exception("Produto com SKU {$sku} não encontrado.");
            }

            return $product;
        });
    }

    /**
     * Obtém dados para relatórios customizados de inventário.
     */
    public function getReportData(array $filters = []): ServiceResult
    {
        return $this->safeExecute(function () use ($filters) {
            // Validação de datas
            if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
                if ($filters['start_date'] > $filters['end_date']) {
                    throw new Exception('A data inicial não pode ser maior que a data final.');
                }
            }

            $reportType = $filters['report_type'] ?? $filters['type'] ?? 'summary';
            $startDate = $filters['start_date'] ?? null;
            $endDate = $filters['end_date'] ?? null;

            $reportData = [];

            switch ($reportType) {
                case 'summary':
                    $reportData = $this->inventoryRepository->getPaginated($filters, 1000)->map(function ($inventory) {
                        return [
                            'sku' => $inventory->product->sku,
                            'produto' => $inventory->product->name,
                            'categoria' => $inventory->product->category->name ?? 'N/A',
                            'quantidade' => $inventory->quantity,
                            'estoque_min' => $inventory->min_quantity,
                            'estoque_max' => $inventory->max_quantity ?? '-',
                            'status' => ($inventory->quantity - $inventory->reserved_quantity) <= $inventory->min_quantity ? 'Baixo' : ($inventory->quantity >= $inventory->max_quantity && $inventory->max_quantity > 0 ? 'Alto' : 'Normal'),
                        ];
                    })->toArray();
                    break;

                case 'movements':
                    $query = \App\Models\InventoryMovement::with(['product', 'user']);
                    if ($startDate) {
                        $query->whereDate('created_at', '>=', $startDate);
                    }
                    if ($endDate) {
                        $query->whereDate('created_at', '<=', $endDate);
                    }

                    $reportData = $query->latest()->get()->map(function ($m) {
                        return [
                            'data' => $m->created_at->format('d/m/Y H:i'),
                            'sku' => $m->product->sku,
                            'produto' => $m->product->name,
                            'tipo' => match ($m->type) {
                                'entry' => 'Entrada',
                                'exit' => 'Saída',
                                'adjustment' => 'Ajuste',
                                'reservation' => 'Reserva',
                                'cancellation' => 'Cancel.',
                                default => ucfirst($m->type)
                            },
                            'quantidade' => $m->quantity,
                            'usuario' => $m->user->name ?? 'N/A',
                            'motivo' => $m->reason ?? '-',
                        ];
                    })->toArray();
                    break;

                case 'valuation':
                    $reportData = $this->inventoryRepository->getPaginated($filters, 1000)->map(function ($inventory) {
                        $price = $inventory->product->price ?? 0;

                        return [
                            'sku' => $inventory->product->sku,
                            'produto' => $inventory->product->name,
                            'quantidade' => $inventory->quantity,
                            'preço_unitário' => 'R$ '.number_format($price, 2, ',', '.'),
                            'valor_total' => 'R$ '.number_format($inventory->quantity * $price, 2, ',', '.'),
                        ];
                    })->toArray();
                    break;

                case 'low_stock':
                case 'low-stock':
                    $filters['low_stock'] = true;
                    $reportData = $this->inventoryRepository->getPaginated($filters, 1000)->map(function ($inventory) {
                        $diff = $inventory->min_quantity - ($inventory->quantity - $inventory->reserved_quantity);

                        return [
                            'sku' => $inventory->product->sku,
                            'produto' => $inventory->product->name,
                            'quantidade_atual' => $inventory->quantity,
                            'estoque_mínimo' => $inventory->min_quantity,
                            'necessidade' => max(0, $diff),
                        ];
                    })->toArray();
                    break;
            }

            return [
                'reportData' => $reportData,
                'filters' => $filters,
            ];
        });
    }

    /**
     * Adiciona estoque a um produto pelo SKU.
     */
    public function addStock(string $sku, int $quantity, string $reason): ServiceResult
    {
        return $this->safeExecute(function () use ($sku, $quantity, $reason) {
            $product = $this->productRepository->findBySku($sku);
            if (! $product) {
                throw new Exception('Produto não encontrado.');
            }

            return $this->updateStockAction->execute($product, $quantity, 'in', $reason);
        });
    }

    /**
     * Adiciona estoque a um produto pelo ID.
     */
    public function addStockById(int $productId, int $quantity, string $reason): ServiceResult
    {
        return $this->safeExecute(function () use ($productId, $quantity, $reason) {
            $product = $this->productRepository->find($productId);
            if (! $product) {
                throw new Exception('Produto não encontrado.');
            }

            return $this->updateStockAction->execute($product, $quantity, 'in', $reason);
        });
    }

    /**
     * Remove estoque de um produto pelo SKU.
     */
    public function removeStock(string $sku, int $quantity, string $reason): ServiceResult
    {
        return $this->safeExecute(function () use ($sku, $quantity, $reason) {
            $product = $this->productRepository->findBySku($sku);
            if (! $product) {
                throw new Exception('Produto não encontrado.');
            }

            return $this->updateStockAction->execute($product, $quantity, 'out', $reason);
        });
    }

    /**
     * Remove estoque de um produto pelo ID.
     */
    public function removeStockById(int $productId, int $quantity, string $reason): ServiceResult
    {
        return $this->safeExecute(function () use ($productId, $quantity, $reason) {
            $product = $this->productRepository->find($productId);
            if (! $product) {
                throw new Exception('Produto não encontrado.');
            }

            return $this->updateStockAction->execute($product, $quantity, 'out', $reason);
        });
    }

    /**
     * Ajusta o estoque para uma quantidade específica.
     */
    public function setStock(string $sku, int $newQuantity, string $reason): ServiceResult
    {
        return $this->safeExecute(function () use ($sku, $newQuantity, $reason) {
            $product = $this->productRepository->findBySku($sku);
            if (! $product) {
                throw new Exception('Produto não encontrado.');
            }

            return $this->updateStockAction->execute($product, $newQuantity, 'adjustment', $reason);
        });
    }

    /**
     * Reserva uma quantidade de estoque para um produto.
     */
    public function reserveStock(string $sku, int $quantity): ServiceResult
    {
        return $this->safeExecute(function () use ($sku, $quantity) {
            $product = $this->productRepository->findBySku($sku);
            if (! $product) {
                throw new Exception('Produto não encontrado.');
            }

            return $this->reserveStockAction->reserve($product, $quantity);
        });
    }

    /**
     * Libera uma quantidade reservada de estoque.
     */
    public function releaseStock(string $sku, int $quantity): ServiceResult
    {
        return $this->safeExecute(function () use ($sku, $quantity) {
            $product = $this->productRepository->findBySku($sku);
            if (! $product) {
                throw new Exception('Produto não encontrado.');
            }

            return $this->reserveStockAction->release($product, $quantity);
        });
    }

    /**
     * Confirma a reserva, transformando-a em saída física.
     */
    public function confirmReservation(string $sku, int $quantity, string $reason = 'Reserva confirmada'): ServiceResult
    {
        return $this->safeExecute(function () use ($sku, $quantity, $reason) {
            $product = $this->productRepository->findBySku($sku);
            if (! $product) {
                throw new Exception('Produto não encontrado.');
            }

            return $this->reserveStockAction->confirm($product, $quantity, $this->updateStockAction, $reason);
        });
    }

    /**
     * Ajusta o estoque de um produto pelo ID.
     */
    public function setStockById(int $productId, int $quantity, string $reason): ServiceResult
    {
        return $this->safeExecute(function () use ($productId, $quantity, $reason) {
            $product = $this->productRepository->find($productId);
            if (! $product) {
                throw new Exception('Produto não encontrado.');
            }

            return $this->updateStockAction->execute($product, $quantity, 'adjustment', $reason);
        });
    }

    /**
     * Verifica disponibilidade de estoque.
     */
    public function checkAvailability(int $productId, int $requestedQuantity): ServiceResult
    {
        return $this->safeExecute(function () use ($productId, $requestedQuantity) {
            $inventory = $this->inventoryRepository->findByProduct($productId);

            if (! $inventory) {
                return [
                    'available' => false,
                    'quantity' => 0,
                    'message' => 'Produto não possui registro de inventário.',
                ];
            }

            return [
                'available' => $inventory->quantity >= $requestedQuantity,
                'quantity' => $inventory->quantity,
                'min_quantity' => $inventory->min_quantity,
            ];
        });
    }
}
