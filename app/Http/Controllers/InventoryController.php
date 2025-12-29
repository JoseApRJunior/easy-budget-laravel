<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Services\Application\InventoryManagementService;
use App\Services\Domain\InventoryExportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    private InventoryManagementService $inventoryManagementService;

    private InventoryExportService $inventoryExportService;

    public function __construct(
        InventoryManagementService $inventoryManagementService,
        InventoryExportService $inventoryExportService
    ) {
        $this->inventoryManagementService = $inventoryManagementService;
        $this->inventoryExportService = $inventoryExportService;
    }

    /**
     * Dashboard de Inventário
     */
    public function dashboard(): View|RedirectResponse
    {
        $this->authorize('viewAny', \App\Models\Product::class);
        $result = $this->inventoryManagementService->getDashboardData();

        return view('pages.inventory.dashboard', (array) $result->getData());
    }

    /**
     * Lista de inventário
     */
    public function index(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', \App\Models\Product::class);
        $result = $this->inventoryManagementService->getIndexData($request->all());

        return view('pages.inventory.index', (array) $result->getData());
    }

    /**
     * Movimentações de inventário
     */
    public function movements(Request $request): View|RedirectResponse
    {
        $this->authorize('viewMovements', \App\Models\Product::class);
        $result = $this->inventoryManagementService->getMovementsData($request->all());

        return view('pages.inventory.movements', (array) $result->getData());
    }

    /**
     * Giro de estoque
     */
    public function stockTurnover(Request $request): View
    {
        $this->authorize('viewReports', \App\Models\Product::class);
        $result = $this->inventoryManagementService->getStockTurnoverData($request->all());

        return view('pages.inventory.stock-turnover', (array) $result->getData());
    }

    /**
     * Produtos mais usados
     */
    public function mostUsedProducts(Request $request): View
    {
        $this->authorize('viewReports', \App\Models\Product::class);
        $result = $this->inventoryManagementService->getMostUsedProductsData($request->all());

        return view('pages.inventory.most-used', (array) $result->getData());
    }

    /**
     * Alertas de estoque
     */
    public function alerts(): View|RedirectResponse
    {
        $this->authorize('manageAlerts', \App\Models\Product::class);
        $result = $this->inventoryManagementService->getAlertsData();

        return view('pages.inventory.alerts', (array) $result->getData());
    }

    /**
     * Relatório de inventário
     */
    public function report(Request $request): View
    {
        $this->authorize('viewReports', \App\Models\Product::class);
        $result = $this->inventoryManagementService->getReportData($request->all());

        return view('pages.inventory.report', (array) $result->getData());
    }

    /**
     * Exportar inventário
     */
    public function export(Request $request)
    {
        $this->authorize('viewReports', \App\Models\Product::class);
        $format = $request->input('type', 'xlsx');
        $reportType = $request->input('report_type', 'summary');

        $result = $this->inventoryManagementService->getReportData($request->all());
        $data = collect($result->getData()['reportData']);

        $this->inventoryExportService->setExportType('report_' . $reportType);
        return $this->inventoryExportService->export($data, $format, 'relatorio_inventario_' . $reportType);
    }

    /**
     * Exportar movimentações
     */
    public function exportMovements(Request $request)
    {
        $this->authorize('viewMovements', \App\Models\Product::class);
        $format = $request->input('type', 'xlsx');

        $result = $this->inventoryManagementService->getMovementsData($request->all());
        // Pegar todos os registros sem paginação para exportação
        $filters = $request->all();
        $filters['per_page'] = 10000;
        $result = $this->inventoryManagementService->getMovementsData($filters);
        $data = collect($result->getData()['movements']->items());

        $this->inventoryExportService->setExportType('movements');
        return $this->inventoryExportService->export($data, $format, 'movimentacoes_estoque');
    }

    /**
     * Exportar giro de estoque
     */
    public function exportStockTurnover(Request $request)
    {
        $this->authorize('viewReports', \App\Models\Product::class);
        $format = $request->input('type', 'xlsx');

        // Pegar todos os registros sem paginação para exportação
        $filters = $request->all();
        $filters['per_page'] = 10000;
        $result = $this->inventoryManagementService->getStockTurnoverData($filters);
        $data = collect($result->getData()['stockTurnover']->items());

        $this->inventoryExportService->setExportType('stock_turnover');
        return $this->inventoryExportService->export($data, $format, 'giro_estoque');
    }

    /**
     * Exportar produtos mais usados
     */
    public function exportMostUsed(Request $request)
    {
        $this->authorize('viewReports', \App\Models\Product::class);
        $format = $request->input('type', 'xlsx');

        $result = $this->inventoryManagementService->getMostUsedProductsData($request->all());
        $data = collect($result->getData()['products']);

        $this->inventoryExportService->setExportType('most_used');
        return $this->inventoryExportService->export($data, $format, 'produtos_mais_usados');
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

        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $result = $this->inventoryManagementService->checkAvailability(
            (int) $request->input('product_id'),
            (int) $request->input('quantity')
        );

        return response()->json($result->getData(), $result->isSuccess() ? 200 : 400);
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
            (string) $request->input('reason', 'Entrada manual via API')
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
            (string) $request->input('reason', 'Saída manual via API')
        );

        return response()->json([
            'success' => $result->isSuccess(),
            'message' => $result->getMessage(),
            'data' => $result->getData(),
        ], $result->isSuccess() ? 200 : 400);
    }

    /**
     * Ajusta estoque de um produto.
     *
     * Rota: products.inventory.adjust
     */
    public function adjust(Request $request, int $productId): JsonResponse
    {
        $product = \App\Models\Product::findOrFail($productId);
        $this->authorize('adjustInventory', $product);

        $request->validate([
            'quantity' => 'required|integer|min:0',
            'reason' => 'required|string|min:10|max:255',
        ]);

        $result = $this->inventoryManagementService->setStockById(
            $productId,
            (int) $request->input('quantity'),
            (string) $request->input('reason')
        );

        return response()->json([
            'success' => $result->isSuccess(),
            'message' => $result->getMessage(),
            'data' => $result->getData(),
        ], $result->isSuccess() ? 200 : 400);
    }
}
