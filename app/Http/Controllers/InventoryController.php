<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Services\Application\InventoryManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    private InventoryManagementService $inventoryManagementService;

    public function __construct(
        InventoryManagementService $inventoryManagementService
    ) {
        $this->inventoryManagementService = $inventoryManagementService;
    }

    /**
     * Dashboard de Inventário
     */
    public function dashboard(): View|RedirectResponse
    {
        $this->authorize('viewAny', \App\Models\Product::class);
        $result = $this->inventoryManagementService->getDashboardData();

        return view('pages.inventory.dashboard', $result->getData());
    }

    /**
     * Lista de inventário
     */
    public function index(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', \App\Models\Product::class);
        $result = $this->inventoryManagementService->getIndexData($request->all());

        return view('pages.inventory.index', $result->getData());
    }

    /**
     * Movimentações de inventário
     */
    public function movements(Request $request): View|RedirectResponse
    {
        $this->authorize('viewMovements', \App\Models\Product::class);
        $result = $this->inventoryManagementService->getMovementsData($request->all());

        return view('pages.inventory.movements', $result->getData());
    }

    /**
     * Giro de estoque
     */
    public function stockTurnover(Request $request): View|RedirectResponse
    {
        $this->authorize('viewReports', \App\Models\Product::class);
        $result = $this->inventoryManagementService->getStockTurnoverData($request->all());

        return view('pages.inventory.stock-turnover', $result->getData());
    }

    /**
     * Produtos mais usados
     */
    public function mostUsedProducts(): View
    {
        $this->authorize('viewReports', \App\Models\Product::class);

        return view('pages.inventory.most-used');
    }

    /**
     * Alertas de estoque
     */
    public function alerts(): View|RedirectResponse
    {
        $this->authorize('manageAlerts', \App\Models\Product::class);
        $result = $this->inventoryManagementService->getAlertsData();

        return view('pages.inventory.alerts', $result->getData());
    }

    /**
     * Relatório de inventário
     */
    public function report(Request $request): View
    {
        $this->authorize('viewReports', \App\Models\Product::class);
        $type = $request->input('type', 'summary');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Dados do relatório (a serem implementados conforme necessidade)
        $reportData = [];

        return view('pages.inventory.report', compact('type', 'startDate', 'endDate', 'reportData'));
    }

    /**
     * Exportar inventário
     */
    public function export()
    {
        $this->authorize('viewReports', \App\Models\Product::class);

        // TODO: implementar exportação
        return redirect()->back()->with('warning', 'Exportação em desenvolvimento');
    }

    /**
     * Exportar movimentações
     */
    public function exportMovements()
    {
        $this->authorize('viewMovements', \App\Models\Product::class);

        // TODO: implementar exportação
        return redirect()->back()->with('warning', 'Exportação em desenvolvimento');
    }

    /**
     * Exportar giro de estoque
     */
    public function exportStockTurnover()
    {
        $this->authorize('viewReports', \App\Models\Product::class);

        // TODO: implementar exportação
        return redirect()->back()->with('warning', 'Exportação em desenvolvimento');
    }

    /**
     * Exportar produtos mais usados
     */
    public function exportMostUsed()
    {
        $this->authorize('viewReports', \App\Models\Product::class);

        // TODO: implementar exportação
        return redirect()->back()->with('warning', 'Exportação em desenvolvimento');
    }

    /**
     * Exibir detalhes de inventário de um produto
     */
    public function show($sku): View|RedirectResponse
    {
        $result = $this->inventoryManagementService->getProductBySku($sku);

        if (! $result->isSuccess()) {
            return redirect()->back()->with('error', $result->getMessage());
        }

        $product = $result->getData();
        $this->authorize('view', $product);

        return view('pages.inventory.show', ['product' => $product]);
    }

    /**
     * Formulário de entrada de estoque
     */
    public function entryForm($sku): View|RedirectResponse
    {
        $result = $this->inventoryManagementService->getProductBySku($sku);

        if (! $result->isSuccess()) {
            return redirect()->back()->with('error', $result->getMessage());
        }

        $product = $result->getData();
        $this->authorize('adjustInventory', $product);

        return view('pages.inventory.entry', ['product' => $product]);
    }

    /**
     * Processar entrada de estoque
     */
    public function entry(Request $request, $sku)
    {
        $result = $this->inventoryManagementService->getProductBySku($sku);
        if (! $result->isSuccess()) {
            return redirect()->back()->with('error', $result->getMessage());
        }

        $product = $result->getData();
        $this->authorize('adjustInventory', $product);

        $request->validate([
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        $entryResult = $this->inventoryManagementService->addStock(
            (string) $sku,
            (int) $request->input('quantity'),
            (string) $request->input('reason', 'Entrada manual')
        );

        if ($entryResult->isSuccess()) {
            return redirect()
                ->route('provider.inventory.index')
                ->with('success', 'Estoque adicionado com sucesso!');
        }

        return redirect()
            ->back()
            ->with('error', $entryResult->getMessage());
    }

    /**
     * Formulário de saída de estoque
     */
    public function exitForm($sku): View|RedirectResponse
    {
        $productResult = $this->inventoryManagementService->getProductBySku($sku);

        if (! $productResult->isSuccess()) {
            return redirect()->back()->with('error', $productResult->getMessage());
        }

        $product = $productResult->getData();
        $this->authorize('adjustInventory', $product);

        return view('pages.inventory.exit', ['product' => $product]);
    }

    /**
     * Processar saída de estoque
     */
    public function exit(Request $request, $sku)
    {
        $productResult = $this->inventoryManagementService->getProductBySku($sku);
        if (! $productResult->isSuccess()) {
            return redirect()->back()->with('error', $productResult->getMessage());
        }

        $product = $productResult->getData();
        $this->authorize('adjustInventory', $product);

        $request->validate([
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        $result = $this->inventoryManagementService->removeStock(
            (string) $sku,
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
    public function adjustStockForm($sku): View|RedirectResponse
    {
        $productResult = $this->inventoryManagementService->getProductBySku($sku);

        if (! $productResult->isSuccess()) {
            return redirect()->back()->with('error', $productResult->getMessage());
        }

        $product = $productResult->getData();
        $this->authorize('adjustInventory', $product);

        $inventory = $product->inventory;

        return view('pages.inventory.adjust', compact('product', 'inventory'));
    }

    /**
     * Processar ajuste de estoque
     */
    public function adjustStock(Request $request, $sku)
    {
        $productResult = $this->inventoryManagementService->getProductBySku($sku);
        if (! $productResult->isSuccess()) {
            return redirect()->back()->with('error', $productResult->getMessage());
        }

        $product = $productResult->getData();
        $this->authorize('adjustInventory', $product);

        $request->validate([
            'new_quantity' => 'required|integer|min:0',
            'reason' => 'required|string|min:10|max:255',
        ]);

        $result = $this->inventoryManagementService->setStock(
            (string) $sku,
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
        $this->authorize('viewAny', \App\Models\Product::class);

        // TODO: implementar verificação de disponibilidade
        return response()->json([
            'available' => true,
            'quantity' => 0,
        ]);
    }

    /**
     * Adiciona estoque a um produto.
     *
     * Rota: products.inventory.add
     */
    public function add(Request $request, int $productId): JsonResponse
    {
        $product = \App\Models\Product::findOrFail($productId);
        $this->authorize('adjustInventory', $product);

        $request->validate([
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        $result = $this->inventoryManagementService->addStockById(
            $productId,
            (int) $request->input('quantity'),
            (string) $request->input('reason', '')
        );

        return response()->json([
            'success' => $result->isSuccess(),
            'message' => $result->getMessage(),
            'data' => $result->getData(),
        ], $result->isSuccess() ? 200 : 400);
    }

    /**
     * Remove estoque de um produto.
     *
     * Rota: products.inventory.remove
     */
    public function remove(Request $request, int $productId): JsonResponse
    {
        $product = \App\Models\Product::findOrFail($productId);
        $this->authorize('adjustInventory', $product);

        $request->validate([
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        $result = $this->inventoryManagementService->removeStockById(
            $productId,
            (int) $request->input('quantity'),
            (string) $request->input('reason', '')
        );

        return response()->json([
            'success' => $result->isSuccess(),
            'message' => $result->getMessage(),
            'data' => $result->getData(),
        ], $result->isSuccess() ? 200 : 400);
    }
}
