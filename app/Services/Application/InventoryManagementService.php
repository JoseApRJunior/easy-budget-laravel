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

            $displayFilters = $filterDto->toDisplayArray();

            // Formatar datas para exibição no padrão brasileiro (d/m/Y)
            if (! empty($displayFilters['start_date'])) {
                $displayFilters['start_date'] = \Carbon\Carbon::parse($displayFilters['start_date'])->format('d/m/Y');
            }
            if (! empty($displayFilters['end_date'])) {
                $displayFilters['end_date'] = \Carbon\Carbon::parse($displayFilters['end_date'])->format('d/m/Y');
            }

            return [
                'inventories' => $this->inventoryRepository->getPaginated($filters, $perPage),
                'stats' => $this->inventoryRepository->getStatistics($filters),
                'categories' => Category::whereNull('parent_id')->with('children')->get(),
                'filters' => $displayFilters,
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
            // Normalizar datas
            $filters['start_date'] = \App\Helpers\DateHelper::parseDate($filters['start_date'] ?? null);
            $filters['end_date'] = \App\Helpers\DateHelper::parseDate($filters['end_date'] ?? null);

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
                $query->where('created_at', '>=', $filters['start_date'] . ' 00:00:00');
            }

            if (! empty($filters['end_date'])) {
                $query->where('created_at', '<=', $filters['end_date'] . ' 23:59:59');
            }

            $movements = $query->latest()->paginate($filters['per_page'] ?? 10);

            $displayFilters = $filters;
            // Formatar datas para exibição no padrão brasileiro (d/m/Y)
            if (! empty($displayFilters['start_date'])) {
                $displayFilters['start_date'] = \Carbon\Carbon::parse($displayFilters['start_date'])->format('d/m/Y');
            }
            if (! empty($displayFilters['end_date'])) {
                $displayFilters['end_date'] = \Carbon\Carbon::parse($displayFilters['end_date'])->format('d/m/Y');
            }

            return [
                'movements' => $movements,
                'products' => \App\Models\Product::all(),
                'summary' => $this->calculateMovementSummary($query),
                'filters' => $displayFilters,
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

            if (! $movement) {
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
            // Normalizar datas
            $filters['start_date'] = \App\Helpers\DateHelper::parseDate($filters['start_date'] ?? null);
            $filters['end_date'] = \App\Helpers\DateHelper::parseDate($filters['end_date'] ?? null);

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
                    'start_date' => $startDate ? \Carbon\Carbon::parse($startDate)->format('d/m/Y') : null,
                    'end_date' => $endDate ? \Carbon\Carbon::parse($endDate)->format('d/m/Y') : null,
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
            // Normalizar datas
            $startDate = \App\Helpers\DateHelper::parseDate($filters['start_date'] ?? null);
            $endDate = \App\Helpers\DateHelper::parseDate($filters['end_date'] ?? null);

            $filters['start_date'] = $startDate;
            $filters['end_date'] = $endDate;

            // Validação de datas
            if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
                if ($filters['start_date'] > $filters['end_date']) {
                    throw new Exception('A data inicial não pode ser maior que a data final.');
                }
            }

            $startDate = $filters['start_date'] ?? now()->subMonths(1)->format('Y-m-d');
            $endDate = $filters['end_date'] ?? now()->format('Y-m-d');

            $movementsQuery = \App\Models\InventoryMovement::selectRaw('product_id, SUM(quantity) as total_usage')
                ->where('type', 'exit')
                ->whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
                ->groupBy('product_id')
                ->orderByDesc('total_usage')
                ->with(['product.category', 'product.inventory']);

            // Pegamos o total geral para cálculo de porcentagem e resumo
            $summaryData = \App\Models\InventoryMovement::selectRaw('SUM(quantity) as total_usage, COUNT(DISTINCT product_id) as total_products')
                ->where('type', 'exit')
                ->whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
                ->first();

            $totalUsageAll = $summaryData->total_usage ?? 0;
            $totalProductsAll = $summaryData->total_products ?? 0;

            // Para o valor total, precisamos de uma query um pouco mais complexa ou somar depois
            // Como os produtos podem ter preços diferentes, vamos somar o valor total de uso
            $totalValueAll = \App\Models\InventoryMovement::join('products', 'inventory_movements.product_id', '=', 'products.id')
                ->where('inventory_movements.type', 'exit')
                ->whereBetween('inventory_movements.created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
                ->sum(\DB::raw('inventory_movements.quantity * products.price'));

            $allMovements = $movementsQuery->get();
            $classA = $allMovements->filter(fn ($m) => ($totalUsageAll > 0 ? ($m->total_usage / $totalUsageAll) * 100 : 0) >= 5);
            $classB = $allMovements->filter(fn ($m) => ($totalUsageAll > 0 ? ($m->total_usage / $totalUsageAll) * 100 : 0) >= 1 && ($totalUsageAll > 0 ? ($m->total_usage / $totalUsageAll) * 100 : 0) < 5);
            $classC = $allMovements->filter(fn ($m) => ($totalUsageAll > 0 ? ($m->total_usage / $totalUsageAll) * 100 : 0) < 1);

            $paginatedMovements = $movementsQuery->paginate($filters['per_page'] ?? 10);

            $days = max(1, now()->parse($startDate)->diffInDays(now()->parse($endDate)));

            $paginatedMovements->getCollection()->transform(function ($m) use ($totalUsageAll, $days) {
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
                'products' => $paginatedMovements,
                'summary' => [
                    'total_usage' => $totalUsageAll,
                    'total_value' => $totalValueAll,
                    'total_products' => $totalProductsAll,
                    'average_usage' => $totalProductsAll > 0 ? $totalUsageAll / $totalProductsAll : 0,
                    'abc_analysis' => [
                        'class_a' => [
                            'count' => $classA->count(),
                            'percentage' => $totalUsageAll > 0 ? ($classA->sum('total_usage') / $totalUsageAll) * 100 : 0,
                        ],
                        'class_b' => [
                            'count' => $classB->count(),
                            'percentage' => $totalUsageAll > 0 ? ($classB->sum('total_usage') / $totalUsageAll) * 100 : 0,
                        ],
                        'class_c' => [
                            'count' => $classC->count(),
                            'percentage' => $totalUsageAll > 0 ? ($classC->sum('total_usage') / $totalUsageAll) * 100 : 0,
                        ],
                    ],
                ],
                'filters' => [
                    'start_date' => $startDate ? \Carbon\Carbon::parse($startDate)->format('d/m/Y') : null,
                    'end_date' => $endDate ? \Carbon\Carbon::parse($endDate)->format('d/m/Y') : null,
                    'category_id' => $filters['category_id'] ?? null,
                    'limit' => $filters['limit'] ?? 10,
                    'min_quantity' => $filters['min_quantity'] ?? 0,
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
            $reportType = $filters['report_type'] ?? $filters['type'] ?? 'summary';

            // Normalizar datas
            $startDate = \App\Helpers\DateHelper::parseDate($filters['start_date'] ?? null);
            $endDate = \App\Helpers\DateHelper::parseDate($filters['end_date'] ?? null);

            // Atualizar filtros com as datas normalizadas
            $filters['start_date'] = $startDate;
            $filters['end_date'] = $endDate;

            $perPage = (int) ($filters['per_page'] ?? 10);

            // Verifica se é o estado inicial (sem filtros reais aplicados)
            $isInitial = empty($filters['start_date']) &&
                        empty($filters['end_date']) &&
                        empty($filters['search']) &&
                        empty($filters['category']) &&
                        empty($filters['status']);

            if ($isInitial) {
                return [
                    'reportData' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage),
                    'filters' => $filters,
                    'type' => $reportType,
                    'startDate' => $startDate ? \Carbon\Carbon::parse($startDate)->format('d/m/Y') : null,
                    'endDate' => $endDate ? \Carbon\Carbon::parse($endDate)->format('d/m/Y') : null,
                    'isInitial' => true,
                ];
            }

            // Validação de datas
            if (empty($startDate) || empty($endDate)) {
                throw new \Exception('As datas inicial e final são obrigatórias para gerar o relatório.');
            }

            if ($startDate > $endDate) {
                throw new \Exception('A data inicial não pode ser maior que a data final.');
            }

            switch ($reportType) {
                case 'summary':
                    $reportData = $this->inventoryRepository->getPaginated($filters, $perPage)->through(function ($inventory) {
                        return [
                            'sku' => $inventory->product->sku,
                            'produto' => $inventory->product->name,
                            'categoria' => $inventory->product->category->name ?? 'N/A',
                            'quantidade' => $inventory->quantity,
                            'estoque_min' => $inventory->min_quantity,
                            'estoque_max' => $inventory->max_quantity ?? '-',
                            'status' => ($inventory->quantity - $inventory->reserved_quantity) <= $inventory->min_quantity ? 'Baixo' : ($inventory->quantity >= $inventory->max_quantity && $inventory->max_quantity > 0 ? 'Alto' : 'Normal'),
                        ];
                    });
                    break;

                case 'movements':
                    $query = \App\Models\InventoryMovement::with(['product', 'user']);
                    if ($startDate) {
                        $query->where('created_at', '>=', $startDate . ' 00:00:00');
                    }
                    if ($endDate) {
                        $query->where('created_at', '<=', $endDate . ' 23:59:59');
                    }

                    // Aplicar filtros de busca e categoria
                    $search = $filters['search'] ?? null;
                    $category = $filters['category'] ?? null;

                    if (! empty($search)) {
                        $query->whereHas('product', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('sku', 'like', "%{$search}%");
                        });
                    }

                    if (! empty($category)) {
                        $query->whereHas('product', function ($q) use ($category) {
                            $q->where('category_id', $category);
                        });
                    }

                    $reportData = $query->latest()->paginate($perPage)->through(function ($m) {
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
                    });
                    break;

                case 'valuation':
                    $reportData = $this->inventoryRepository->getPaginated($filters, $perPage)->through(function ($inventory) {
                        $price = (float) ($inventory->product->price ?? 0);

                        return [
                            'sku' => $inventory->product->sku,
                            'produto' => $inventory->product->name,
                            'quantidade' => $inventory->quantity,
                            'preço_unitário' => 'R$ '.number_format($price, 2, ',', '.'),
                            'valor_total' => 'R$ '.number_format($inventory->quantity * $price, 2, ',', '.'),
                        ];
                    });
                    break;

                case 'low_stock':
                case 'low-stock':
                    $filters['low_stock'] = true;
                    $reportData = $this->inventoryRepository->getPaginated($filters, $perPage)->through(function ($inventory) {
                        $diff = $inventory->min_quantity - ($inventory->quantity - $inventory->reserved_quantity);

                        return [
                            'sku' => $inventory->product->sku,
                            'produto' => $inventory->product->name,
                            'quantidade_atual' => $inventory->quantity,
                            'estoque_mínimo' => $inventory->min_quantity,
                            'necessidade' => max(0, $diff),
                        ];
                    });
                    break;
            }

            return [
                'reportData' => $reportData,
                'filters' => $filters,
                'type' => $reportType,
                'startDate' => $startDate ? \Carbon\Carbon::parse($startDate)->format('d/m/Y') : null,
                'endDate' => $endDate ? \Carbon\Carbon::parse($endDate)->format('d/m/Y') : null,
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
