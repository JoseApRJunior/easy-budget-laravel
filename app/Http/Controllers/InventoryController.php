<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Models\Product;
use App\Services\Domain\InventoryService;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    private InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Dashboard de Inventário
     */
    public function dashboard(): View
    {
        // Total de produtos
        $totalProducts = Product::count();

        // Produtos com estoque baixo, alto e sem estoque
        $lowStockProducts = \App\Models\ProductInventory::query()
            ->whereRaw('quantity <= min_quantity')
            ->count();

        $highStockProducts = \App\Models\ProductInventory::query()
            ->whereNotNull('max_quantity')
            ->whereRaw('quantity >= max_quantity')
            ->count();

        $outOfStockProducts = \App\Models\ProductInventory::query()
            ->where('quantity', '=', 0)
            ->count();

        // Valor total do estoque
        $totalInventoryValue = \DB::table('product_inventory')
            ->join('products', 'product_inventory.product_id', '=', 'products.id')
            ->selectRaw('SUM(product_inventory.quantity * products.price) as total')
            ->value('total') ?? 0;

        // Itens com estoque alto (limit 5)
        $highStockItems = \App\Models\ProductInventory::query()
            ->whereNotNull('max_quantity')
            ->whereRaw('quantity >= max_quantity')
            ->with('product')
            ->limit(5)
            ->get();

        // Itens com estoque baixo (limit 5)
        $lowStockItems = \App\Models\ProductInventory::query()
            ->whereRaw('quantity <= min_quantity')
            ->with('product')
            ->limit(5)
            ->get();

        // Movimentações recentes (limit 10)
        $recentMovements = \App\Models\InventoryMovement::query()
            ->with(['product', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('pages.inventory.dashboard', compact(
            'totalProducts',
            'lowStockProducts',
            'highStockProducts',
            'outOfStockProducts',
            'totalInventoryValue',
            'highStockItems',
            'lowStockItems',
            'recentMovements'
        ));
    }

    /**
     * Lista de inventário
     */
    public function index(Request $request): View
    {
        // Buscar categorias para o filtro
        $categories = \App\Models\Category::all();

        // Query base de inventário
        $query = \App\Models\ProductInventory::query()
            ->with(['product.category']);

        // Filtro por busca (nome ou SKU do produto)
        if ($search = $request->input('search')) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Filtro por categoria
        if ($categoryId = $request->input('category')) {
            $query->whereHas('product', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        // Filtro por status do estoque
        if ($status = $request->input('status')) {
            switch ($status) {
                case 'low':
                    $query->whereRaw('quantity <= min_quantity');
                    break;
                case 'out':
                    $query->where('quantity', '=', 0);
                    break;
                case 'sufficient':
                    $query->whereRaw('quantity > min_quantity');
                    break;
            }
        }

        // Paginar resultados com eager loading
        $inventories = $query
            ->with(['product.category'])
            ->paginate(15);

        return view('pages.inventory.index', compact('categories', 'inventories'));
    }

    /**
     * Movimentações de inventário
     */
    public function movements(Request $request): View
    {
        $products = Product::query()
            ->orderBy('name')
            ->get();

        $query = \App\Models\InventoryMovement::query()
            ->with(['product', 'user'])
            ->orderByDesc('created_at');

        if ($productId = $request->input('product_id')) {
            $query->where('product_id', (int) $productId);
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($start = $request->input('start_date')) {
            $query->whereDate('created_at', '>=', $start);
        }

        if ($end = $request->input('end_date')) {
            $query->whereDate('created_at', '<=', $end);
        }

        $movements = $query->paginate(15);

        $summaryQuery = \App\Models\InventoryMovement::query();
        if ($productId) {
            $summaryQuery->where('product_id', (int) $productId);
        }
        if ($start) {
            $summaryQuery->whereDate('created_at', '>=', $start);
        }
        if ($end) {
            $summaryQuery->whereDate('created_at', '<=', $end);
        }

        $totalEntries = (float) $summaryQuery->clone()
            ->where('type', 'entry')
            ->sum('quantity');

        $totalExits = (float) $summaryQuery->clone()
            ->whereIn('type', ['exit', 'subtraction'])
            ->sum('quantity');

        $totalAdjustments = (float) $summaryQuery->clone()
            ->where('type', 'adjustment')
            ->sum('quantity');

        $totalReservations = (float) $summaryQuery->clone()
            ->where('type', 'reservation')
            ->sum('quantity');

        $totalCancellations = (float) $summaryQuery->clone()
            ->where('type', 'cancellation')
            ->sum('quantity');

        $countEntries = (int) $summaryQuery->clone()->where('type', 'entry')->count();
        $countExits = (int) $summaryQuery->clone()->whereIn('type', ['exit', 'subtraction'])->count();
        $countAdjustments = (int) $summaryQuery->clone()->where('type', 'adjustment')->count();
        $countReservations = (int) $summaryQuery->clone()->where('type', 'reservation')->count();
        $countCancellations = (int) $summaryQuery->clone()->where('type', 'cancellation')->count();

        $summary = [
            'total_entries'       => $totalEntries,
            'total_exits'         => $totalExits,
            'balance'             => $totalEntries - $totalExits,
            'total_adjustments'   => $totalAdjustments,
            'total_reservations'  => $totalReservations,
            'total_cancellations' => $totalCancellations,
            'count_entries'       => $countEntries,
            'count_exits'         => $countExits,
            'count_adjustments'   => $countAdjustments,
            'count_reservations'  => $countReservations,
            'count_cancellations' => $countCancellations,
        ];

        return view('pages.inventory.movements', compact('products', 'movements', 'summary'));
    }

    /**
     * Giro de estoque
     */
    public function stockTurnover(Request $request): View
    {
        $filters = $request->only(['start_date', 'end_date', 'category_id']) + [
            'start_date' => '',
            'end_date' => '',
            'category_id' => ''
        ];

        $categories = \App\Models\Category::all();

        $query = Product::query()
            ->with(['category'])
            ->select(['products.*'])
            ->when(!empty($filters['category_id']), function ($q) use ($filters) {
                $q->where('category_id', (int) $filters['category_id']);
            })
            ->selectSub(
                \DB::table('product_inventory')
                    ->selectRaw('COALESCE(SUM(quantity),0)')
                    ->whereColumn('product_id', 'products.id'),
                'current_stock'
            )
            ->selectSub(
                \DB::table('product_inventory')
                    ->selectRaw('COALESCE(AVG(quantity),0)')
                    ->whereColumn('product_id', 'products.id'),
                'average_stock'
            )
            ->selectSub(
                \DB::table('inventory_movements')
                    ->selectRaw('COALESCE(SUM(quantity),0)')
                    ->whereColumn('product_id', 'products.id')
                    ->where('type', 'entry')
                    ->when(!empty($filters['start_date']), function ($q) use ($filters) {
                        $q->whereDate('created_at', '>=', $filters['start_date']);
                    })
                    ->when(!empty($filters['end_date']), function ($q) use ($filters) {
                        $q->whereDate('created_at', '<=', $filters['end_date']);
                    }),
                'total_entries'
            )
            ->selectSub(
                \DB::table('inventory_movements')
                    ->selectRaw('COALESCE(SUM(quantity),0)')
                    ->whereColumn('product_id', 'products.id')
                    ->whereIn('type', ['exit', 'subtraction'])
                    ->when(!empty($filters['start_date']), function ($q) use ($filters) {
                        $q->whereDate('created_at', '>=', $filters['start_date']);
                    })
                    ->when(!empty($filters['end_date']), function ($q) use ($filters) {
                        $q->whereDate('created_at', '<=', $filters['end_date']);
                    }),
                'total_exits'
            )
            ->selectSub(
                \DB::table('product_inventory')
                    ->selectRaw('COALESCE(MIN(min_quantity),0)')
                    ->whereColumn('product_id', 'products.id'),
                'min_quantity'
            );

        $stockTurnover = $query->paginate(15);

        $totalProducts = (int) $stockTurnover->total();
        $totalEntries = (float) $stockTurnover->sum('total_entries');
        $totalExits = (float) $stockTurnover->sum('total_exits');
        $avgTurnover = 0.0;
        if ($stockTurnover->count() > 0) {
            $sumTurnover = 0.0;
            foreach ($stockTurnover as $item) {
                $avgBase = (float) ($item->average_stock ?? 0);
                $sumTurnover += $avgBase > 0 ? ((float) $item->total_exits) / $avgBase : 0.0;
            }
            $avgTurnover = $sumTurnover / $stockTurnover->count();
        }

        $reportData = [
            'total_products' => $totalProducts,
            'total_entries' => $totalEntries,
            'total_exits' => $totalExits,
            'average_turnover' => $avgTurnover,
        ];

        return view('pages.inventory.stock-turnover', compact('filters', 'categories', 'stockTurnover', 'reportData'));
    }

    /**
     * Produtos mais usados
     */
    public function mostUsedProducts(): View
    {
        return view('pages.inventory.most-used');
    }

    /**
     * Alertas de estoque
     */
    public function alerts(): View
    {
        return view('pages.inventory.alerts');
    }

    /**
     * Relatório de inventário
     */
    public function report(): View
    {
        return view('pages.inventory.report');
    }

    /**
     * Exportar inventário
     */
    public function export()
    {
        // TODO: implementar exportação
        return redirect()->back()->with('warning', 'Exportação em desenvolvimento');
    }

    /**
     * Exportar movimentações
     */
    public function exportMovements()
    {
        // TODO: implementar exportação
        return redirect()->back()->with('warning', 'Exportação em desenvolvimento');
    }

    /**
     * Exportar giro de estoque
     */
    public function exportStockTurnover()
    {
        // TODO: implementar exportação
        return redirect()->back()->with('warning', 'Exportação em desenvolvimento');
    }

    /**
     * Exportar produtos mais usados
     */
    public function exportMostUsed()
    {
        // TODO: implementar exportação
        return redirect()->back()->with('warning', 'Exportação em desenvolvimento');
    }

    /**
     * Exibir detalhes de inventário de um produto
     */
    public function show($sku): View
    {
        $product = Product::where('sku', $sku)->firstOrFail();
        return view('pages.inventory.show', compact('product'));
    }

    /**
     * Formulário de entrada de estoque
     */
    public function entryForm($sku): View
    {
        $product = Product::where('sku', $sku)->firstOrFail();
        return view('pages.inventory.entry', compact('product'));
    }

    /**
     * Processar entrada de estoque
     */
    public function entry(Request $request, $sku)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        $product = Product::where('sku', $sku)->firstOrFail();

        $result = $this->inventoryService->addStock(
            $product->id,
            (int) $request->input('quantity'),
            (string) $request->input('reason', 'Entrada manual')
        );

        if ($result->isSuccess()) {
            return redirect()
                ->route('provider.inventory.index')
                ->with('success', 'Estoque adicionado com sucesso!');
        }

        return redirect()
            ->back()
            ->with('error', $result->getMessage());
    }

    /**
     * Formulário de saída de estoque
     */
    public function exitForm($sku): View
    {
        $product = Product::where('sku', $sku)->firstOrFail();
        return view('pages.inventory.exit', compact('product'));
    }

    /**
     * Processar saída de estoque
     */
    public function exit(Request $request, $sku)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        $product = Product::where('sku', $sku)->firstOrFail();

        $result = $this->inventoryService->removeStock(
            $product->id,
            (int) $request->input('quantity'),
            (string) $request->input('reason', 'Saída manual')
        );

        if ($result->isSuccess()) {
            return redirect()
                ->route('provider.inventory.index')
                ->with('success', 'Estoque removido com sucesso!');
        }

        return redirect()
            ->back()
            ->with('error', $result->getMessage());
    }

    /**
     * Formulário de ajuste de estoque
     */
    public function adjustStockForm($sku): View
    {
        $product = Product::where('sku', $sku)->firstOrFail();
        $inventory = \App\Models\ProductInventory::where('product_id', $product->id)->first();

        return view('pages.inventory.adjust', compact('product', 'inventory'));
    }

    /**
     * Processar ajuste de estoque
     */
    public function adjustStock(Request $request, $sku)
    {
        $request->validate([
            'new_quantity' => 'required|integer|min:0',
            'reason' => 'required|string|min:10|max:255',
        ]);

        $product = Product::where('sku', $sku)->firstOrFail();

        $result = $this->inventoryService->setStock(
            $product->id,
            (int) $request->input('new_quantity'),
            (string) $request->input('reason')
        );

        if ($result->isSuccess()) {
            return redirect()
                ->route('provider.inventory.index')
                ->with('success', 'Estoque ajustado com sucesso!');
        }

        return redirect()
            ->back()
            ->with('error', $result->getMessage());
    }

    /**
     * Verificar disponibilidade (API)
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        // TODO: implementar verificação de disponibilidade
        return response()->json([
            'available' => true,
            'quantity' => 0
        ]);
    }

    /**
     * Adiciona estoque a um produto.
     *
     * Rota: products.inventory.add
     */
    public function add(Request $request, int $productId): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $result = $this->inventoryService->addStock(
                $productId,
                (int) $request->input('quantity'),
                (string) $request->input('reason', '')
            );

            if (!$result->isSuccess()) {
                return response()->json([
                    'success' => false,
                    'message' => $result->getMessage()
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Estoque adicionado com sucesso',
                'data' => $result->getData()
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao adicionar estoque: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove estoque de um produto.
     *
     * Rota: products.inventory.remove
     */
    public function remove(Request $request, int $productId): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $result = $this->inventoryService->removeStock(
                $productId,
                (int) $request->input('quantity'),
                (string) $request->input('reason', '')
            );

            if (!$result->isSuccess()) {
                return response()->json([
                    'success' => false,
                    'message' => $result->getMessage()
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Estoque removido com sucesso',
                'data' => $result->getData()
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover estoque: ' . $e->getMessage()
            ], 500);
        }
    }
}
