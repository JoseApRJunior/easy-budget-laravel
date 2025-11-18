<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Domain\InventoryService;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\InventoryMovement;
use App\Models\Category;
use App\Support\ServiceResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Abstracts\Controller;

class InventoryController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Dashboard de estoque com visão geral
     */
    public function dashboard(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        
        try {
            // Estatísticas gerais
            $totalProducts = Product::where('tenant_id', $tenantId)->count();
            $productsWithInventory = ProductInventory::where('tenant_id', $tenantId)->count();
            $lowStockProducts = ProductInventory::where('tenant_id', $tenantId)
                ->whereRaw('quantity <= min_quantity')
                ->count();
            $highStockProducts = ProductInventory::where('tenant_id', $tenantId)
                ->whereRaw('quantity >= max_quantity')
                ->count();

            // Produtos em estoque baixo
            $lowStockItems = ProductInventory::where('tenant_id', $tenantId)
                ->whereRaw('quantity <= min_quantity')
                ->with(['product'])
                ->limit(10)
                ->get();

            // Movimentações recentes
            $recentMovements = InventoryMovement::where('tenant_id', $tenantId)
                ->with(['product'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // Produtos sem estoque
            $outOfStockProducts = Product::where('tenant_id', $tenantId)
                ->whereHas('productInventory', function ($query) {
                    $query->where('quantity', 0);
                })
                ->count();

            // Produtos com estoque alto
            $highStockItems = ProductInventory::where('tenant_id', $tenantId)
                ->whereRaw('quantity >= max_quantity')
                ->with(['product'])
                ->limit(10)
                ->get();

            // Valor total do estoque
            $totalInventoryValue = Product::where('tenant_id', $tenantId)
                ->with(['productInventory'])
                ->get()
                ->sum(function ($product) {
                    return $product->price * $product->total_stock;
                });

            return view('pages.inventory.dashboard', compact(
                'totalProducts',
                'productsWithInventory',
                'lowStockProducts',
                'highStockProducts',
                'outOfStockProducts',
                'lowStockItems',
                'highStockItems',
                'recentMovements',
                'totalInventoryValue'
            ));

        } catch (\Exception $e) {
            Log::error('Erro ao carregar dashboard de estoque', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Erro ao carregar dashboard de estoque');
        }
    }

    /**
     * Lista de produtos em estoque
     */
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        
        // Query ProductInventory instead of Product to match view expectations
        $query = ProductInventory::where('tenant_id', $tenantId)
            ->with(['product', 'product.category']);

        // Filtros
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $categoryId = $request->get('category_id');
            $query->whereHas('product', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        if ($request->filled('stock_status')) {
            switch ($request->get('stock_status')) {
                case 'low':
                    $query->whereRaw('quantity <= min_quantity');
                    break;
                case 'high':
                    $query->whereRaw('quantity >= max_quantity');
                    break;
                case 'out':
                    $query->where('quantity', 0);
                    break;
                case 'available':
                    $query->where('quantity', '>', 0);
                    break;
            }
        }

        $inventories = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get all categories for the filter dropdown
        $categories = Category::orderBy('name')->get();

        return view('pages.inventory.index', compact('inventories', 'categories'));
    }

    /**
     * Detalhes do produto e seu estoque
     */
    public function show(Product $product)
    {
        $this->authorize('view', $product);

        $inventory = $product->productInventory()->first();
        $movements = $product->inventoryMovements()
            ->with(['product'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('pages.inventory.show', compact('product', 'inventory', 'movements'));
    }

    /**
     * Tela de ajuste manual de estoque
     */
    public function adjustStockForm(Product $product)
    {
        $this->authorize('update', $product);

        $inventory = $product->productInventory()->first();
        
        // Get recent movements for this product
        $recentMovements = InventoryMovement::where('product_id', $product->id)
            ->where('tenant_id', Auth::user()->tenant_id)
            ->with(['product', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        // Get last movement
        $lastMovement = InventoryMovement::where('product_id', $product->id)
            ->where('tenant_id', Auth::user()->tenant_id)
            ->orderBy('created_at', 'desc')
            ->first();

        return view('pages.inventory.adjust-stock', compact('product', 'inventory', 'recentMovements', 'lastMovement'));
    }

    /**
     * Processa ajuste manual de estoque
     */
    public function adjustStock(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        $validator = Validator::make($request->all(), [
            'adjustment_type' => 'required|in:addition,subtraction,correction',
            'quantity' => 'required|numeric|min:0.01',
            'reason' => 'required|string|min:10|max:500',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $adjustmentType = $request->get('adjustment_type');
            $quantity = (float) $request->get('quantity');
            $reason = $request->get('reason');
            
            // Handle different adjustment types
            switch ($adjustmentType) {
                case 'addition':
                    $result = $this->inventoryService->addProduct(
                        $product->id,
                        $quantity,
                        $reason,
                        'manual_adjustment',
                        0,
                        Auth::user()->tenant_id
                    );
                    break;
                    
                case 'subtraction':
                    $result = $this->inventoryService->consumeProduct(
                        $product->id,
                        $quantity,
                        $reason,
                        'manual_adjustment',
                        0,
                        Auth::user()->tenant_id
                    );
                    break;
                    
                case 'correction':
                    // For correction, we need to calculate the difference
                    $currentInventory = $product->productInventory()->first();
                    $currentQuantity = $currentInventory ? $currentInventory->quantity : 0;
                    $difference = $quantity - $currentQuantity;
                    
                    if ($difference > 0) {
                        $result = $this->inventoryService->addProduct(
                            $product->id,
                            abs($difference),
                            "Correção: {$reason}",
                            'manual_correction',
                            0,
                            Auth::user()->tenant_id
                        );
                    } elseif ($difference < 0) {
                        $result = $this->inventoryService->consumeProduct(
                            $product->id,
                            abs($difference),
                            "Correção: {$reason}",
                            'manual_correction',
                            0,
                            Auth::user()->tenant_id
                        );
                    } else {
                        return back()->with('warning', 'Nenhuma alteração necessária - o estoque já está correto.')->withInput();
                    }
                    break;
                    
                default:
                    return back()->with('error', 'Tipo de ajuste inválido.')->withInput();
            }

            if ($result->isSuccess()) {
                return redirect()->route('provider.inventory.show', $product)
                    ->with('success', 'Estoque ajustado com sucesso!');
            } else {
                return back()->with('error', $result->getMessage())->withInput();
            }

        } catch (\Exception $e) {
            Log::error('Erro ao ajustar estoque', [
                'product_id' => $product->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Erro ao ajustar estoque')->withInput();
        }
    }

    /**
     * Lista de movimentações de estoque
     */
    public function movements(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $query = InventoryMovement::where('tenant_id', $tenantId)
            ->with(['product', 'user']);

        // Filtros
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->get('product_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->get('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->get('end_date'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('reason', 'like', "%{$search}%")
                    ->orWhere('reference_type', 'like', "%{$search}%");
            });
        }

        $movements = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get products for filter dropdown
        $products = Product::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get();

        // Calculate summary data
        $summaryQuery = InventoryMovement::where('tenant_id', $tenantId);
        
        // Apply same filters to summary
        if ($request->filled('product_id')) {
            $summaryQuery->where('product_id', $request->get('product_id'));
        }
        if ($request->filled('type')) {
            $summaryQuery->where('type', $request->get('type'));
        }
        if ($request->filled('start_date')) {
            $summaryQuery->whereDate('created_at', '>=', $request->get('start_date'));
        }
        if ($request->filled('end_date')) {
            $summaryQuery->whereDate('created_at', '<=', $request->get('end_date'));
        }

        $summary = [
            'total_entries' => $summaryQuery->where('type', 'entry')->sum('quantity'),
            'total_exits' => $summaryQuery->where('type', 'exit')->sum('quantity'),
            'balance' => $summaryQuery->where('type', 'entry')->sum('quantity') - $summaryQuery->where('type', 'exit')->sum('quantity')
        ];

        return view('pages.inventory.movements', compact('movements', 'products', 'summary'));
    }

    /**
     * Exportar movimentações de estoque
     */
    public function exportMovements(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $query = InventoryMovement::where('tenant_id', $tenantId)
            ->with(['product', 'user']);

        // Aplicar os mesmos filtros do método movements
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->get('product_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->get('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->get('end_date'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('reason', 'like', "%{$search}%")
                    ->orWhere('reference_type', 'like', "%{$search}%");
            });
        }

        $movements = $query->orderBy('created_at', 'desc')->get();

        // Preparar dados para exportação
        $exportData = $movements->map(function ($movement) {
            return [
                'Data' => $movement->created_at->format('d/m/Y H:i'),
                'Produto' => $movement->product->name,
                'SKU' => $movement->product->sku,
                'Tipo' => $movement->type === 'entry' ? 'Entrada' : 'Saída',
                'Quantidade' => $movement->quantity,
                'Valor Unitário' => number_format($movement->unit_price, 2, ',', '.'),
                'Valor Total' => number_format($movement->total_value, 2, ',', '.'),
                'Motivo' => $movement->reason,
                'Referência' => $movement->reference_type . ' #' . $movement->reference_id,
                'Usuário' => $movement->user->name,
                'Saldo Anterior' => $movement->previous_balance,
                'Saldo Atual' => $movement->current_balance,
            ];
        });

        // Por enquanto, retornar como JSON (pode ser adaptado para Excel/PDF posteriormente)
        return response()->json([
            'success' => true,
            'message' => 'Dados de movimentações exportados com sucesso',
            'data' => $exportData,
            'total_records' => $exportData->count()
        ]);
    }

    /**
     * Relatório de giro de estoque
     */
    public function stockTurnover(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $filters = [
            'start_date' => $request->get('start_date', now()->subDays(30)->format('Y-m-d')),
            'end_date' => $request->get('end_date', now()->format('Y-m-d')),
            'category_id' => $request->get('category_id'),
        ];

        try {
            $stockTurnoverData = $this->inventoryService->getStockTurnoverReport($tenantId, $filters);
            
            // Transform data to match view expectations
            $stockTurnover = collect($stockTurnoverData)->map(function ($item) {
                return (object) [
                    'id' => $item['product']->id,
                    'sku' => $item['product']->sku,
                    'name' => $item['product']->name,
                    'category' => $item['product']->category,
                    'unit' => $item['product']->unit,
                    'current_stock' => $item['product']->productInventory->quantity ?? 0,
                    'min_quantity' => $item['product']->productInventory->min_quantity ?? 0,
                    'total_entries' => $item['total_in'],
                    'total_exits' => $item['total_out'],
                    'average_stock' => $item['average_stock'],
                ];
            });
            
            // Get categories for filter dropdown (categories are global, not tenant-specific)
            $categories = \App\Models\Category::orderBy('name')->get();

            // Calculate summary data
            $reportData = [
                'total_products' => $stockTurnover->count(),
                'total_entries' => $stockTurnover->sum('total_entries'),
                'total_exits' => $stockTurnover->sum('total_exits'),
                'average_turnover' => $stockTurnover->avg(function($item) {
                    return $item->average_stock > 0 ? $item->total_exits / $item->average_stock : 0;
                })
            ];

            return view('pages.inventory.stock-turnover', compact('stockTurnover', 'filters', 'categories', 'reportData'));

        } catch (\Exception $e) {
            Log::error('Erro ao gerar relatório de giro de estoque', [
                'tenant_id' => $tenantId,
                'filters' => $filters,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Erro ao gerar relatório de giro de estoque: ' . $e->getMessage());
        }
    }

    /**
     * Produtos mais utilizados
     */
    public function mostUsedProducts(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $filters = [
            'start_date' => $request->get('start_date', now()->subDays(30)->format('Y-m-d')),
            'end_date' => $request->get('end_date', now()->format('Y-m-d')),
            'category_id' => $request->get('category_id'),
            'limit' => $request->get('limit', 20),
            'min_quantity' => $request->get('min_quantity', 0),
        ];

        try {
            $mostUsedProductsData = $this->inventoryService->getMostUsedProducts($tenantId, $filters['limit'], $filters);
            
            // Transform data to match view expectations
            $mostUsedProducts = collect($mostUsedProductsData)->map(function ($item) {
                $product = $item['product'];
                return (object) [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'category' => $product->category,
                    'unit' => $product->unit,
                    'inventory' => $product->productInventory,
                    'total_quantity_used' => $item['total_quantity'],
                    'total_value_used' => $item['total_quantity'] * $product->price,
                ];
            });
            
            // Get categories for filter dropdown (categories are global, not tenant-specific)
            $categories = \App\Models\Category::orderBy('name')->get();

            // Calculate report data
            $reportData = [
                'total_products' => $mostUsedProducts->count(),
                'total_quantity_used' => $mostUsedProducts->sum('total_quantity_used'),
                'total_value_used' => $mostUsedProducts->sum('total_value_used'),
                'average_usage_per_product' => $mostUsedProducts->count() > 0 ? round($mostUsedProducts->sum('total_quantity_used') / $mostUsedProducts->count(), 2) : 0
            ];

            return view('pages.inventory.most-used-products', compact('mostUsedProducts', 'filters', 'categories', 'reportData'));

        } catch (\Exception $e) {
            Log::error('Erro ao gerar relatório de produtos mais utilizados', [
                'tenant_id' => $tenantId,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Erro ao gerar relatório de produtos mais utilizados');
        }
    }

    /**
     * Alertas de estoque
     */
    public function alerts(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $lowStockProducts = ProductInventory::where('tenant_id', $tenantId)
            ->whereRaw('quantity <= min_quantity')
            ->with(['product'])
            ->paginate(20);

        $highStockProducts = ProductInventory::where('tenant_id', $tenantId)
            ->whereNotNull('max_quantity')
            ->whereRaw('quantity >= max_quantity')
            ->with(['product'])
            ->paginate(20);

        return view('pages.inventory.alerts', compact('lowStockProducts', 'highStockProducts'));
    }

    /**
     * Relatórios de inventário
     */
    public function report(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $type = $request->get('type', 'summary');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = InventoryMovement::where('tenant_id', $tenantId)
            ->with(['product']);

        // Aplicar filtros de data se fornecidos
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $reportData = [];

        switch ($type) {
            case 'movements':
                $reportData = $query->orderBy('created_at', 'desc')->get()->map(function ($movement) {
                    return [
                        'data' => $movement->created_at->format('d/m/Y H:i'),
                        'produto' => $movement->product->name,
                        'tipo' => $movement->type,
                        'quantidade' => $movement->quantity,
                        'valor_unitario' => $movement->unit_price,
                        'valor_total' => $movement->total_value,
                        'saldo_anterior' => $movement->previous_balance,
                        'saldo_atual' => $movement->current_balance,
                    ];
                })->toArray();
                break;

            case 'valuation':
                $reportData = Product::where('tenant_id', $tenantId)
                    ->with(['productInventory'])
                    ->get()
                    ->filter(function ($product) {
                        return $product->productInventory && $product->productInventory->quantity > 0;
                    })
                    ->map(function ($product) {
                        return [
                            'produto' => $product->name,
                            'quantidade' => $product->productInventory->quantity,
                            'valor_unitario' => $product->price,
                            'valor_total' => $product->price * $product->productInventory->quantity,
                            'categoria' => $product->category ?? 'Sem categoria',
                        ];
                    })
                    ->toArray();
                break;

            case 'low-stock':
                $reportData = ProductInventory::where('tenant_id', $tenantId)
                    ->whereRaw('quantity <= min_quantity')
                    ->with(['product'])
                    ->get()
                    ->map(function ($inventory) {
                        return [
                            'produto' => $inventory->product->name,
                            'quantidade_atual' => $inventory->quantity,
                            'estoque_minimo' => $inventory->min_quantity,
                            'estoque_maximo' => $inventory->max_quantity,
                            'diferenca' => $inventory->min_quantity - $inventory->quantity,
                            'categoria' => $inventory->product->category ?? 'Sem categoria',
                        ];
                    })
                    ->toArray();
                break;

            case 'summary':
            default:
                $reportData = [
                    ['metrica' => 'Total de Produtos', 'valor' => Product::where('tenant_id', $tenantId)->count()],
                    ['metrica' => 'Produtos com Estoque', 'valor' => ProductInventory::where('tenant_id', $tenantId)->count()],
                    ['metrica' => 'Produtos em Baixa', 'valor' => ProductInventory::where('tenant_id', $tenantId)->whereRaw('quantity <= min_quantity')->count()],
                    ['metrica' => 'Produtos em Alta', 'valor' => ProductInventory::where('tenant_id', $tenantId)->whereRaw('quantity >= max_quantity')->count()],
                    ['metrica' => 'Movimentações no Período', 'valor' => $query->count()],
                ];
                break;
        }

        return view('pages.inventory.report', compact('type', 'startDate', 'endDate', 'reportData'));
    }

    /**
     * Exportar relatórios de inventário
     */
    public function export(Request $request)
    {
        $type = $request->get('type', 'pdf');
        $reportType = $request->get('report_type', 'summary');
        
        // For now, return a simple response with the export functionality
        // In a real implementation, you would use a library like Laravel Excel or DomPDF
        return response()->json([
            'success' => true,
            'message' => 'Funcionalidade de exportação será implementada em breve',
            'type' => $type,
            'report_type' => $reportType
        ]);
    }

    /**
     * API: Verificar disponibilidade de estoque
     */
    public function checkAvailability(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $productId = $request->get('product_id');
            $requestedQuantity = $request->get('quantity');
            $tenantId = Auth::user()->tenant_id;

            $inventory = ProductInventory::where('product_id', $productId)
                ->where('tenant_id', $tenantId)
                ->first();

            $availableQuantity = $inventory ? $inventory->quantity : 0;
            $hasStock = $availableQuantity >= $requestedQuantity;

            return response()->json([
                'success' => true,
                'data' => [
                    'product_id' => $productId,
                    'requested_quantity' => $requestedQuantity,
                    'available_quantity' => $availableQuantity,
                    'has_stock' => $hasStock,
                    'stock_status' => $inventory ? $inventory->stock_status : 'Sem estoque',
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao verificar disponibilidade de estoque', [
                'product_id' => $request->get('product_id'),
                'quantity' => $request->get('quantity'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao verificar disponibilidade'
            ], 500);
        }
    }
}