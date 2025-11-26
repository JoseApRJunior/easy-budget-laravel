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
            $query->whereHas('product', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Filtro por categoria
        if ($categoryId = $request->input('category')) {
            $query->whereHas('product', function($q) use ($categoryId) {
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

        // Paginar resultados
        $inventories = $query->paginate(15);

        return view('pages.inventory.index', compact('categories', 'inventories'));
    }

    /**
     * Movimentações de inventário
     */
    public function movements(): View
    {
        return view('pages.inventory.movements');
    }

    /**
     * Giro de estoque
     */
    public function stockTurnover(): View
    {
        return view('pages.inventory.stock-turnover');
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
    public function show($product): View
    {
        return view('pages.inventory.show', compact('product'));
    }

    /**
     * Formulário de ajuste de estoque
     */
    public function adjustStockForm($product): View
    {
        return view('pages.inventory.adjust', compact('product'));
    }

    /**
     * Processar ajuste de estoque
     */
    public function adjustStock(Request $request, $product)
    {
        // TODO: implementar ajuste de estoque
        return redirect()->back()->with('success', 'Estoque ajustado com sucesso');
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
