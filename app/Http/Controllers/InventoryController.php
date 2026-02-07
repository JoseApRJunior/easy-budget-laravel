<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\Inventory\InventoryFilterDTO;
use App\Http\Controllers\Abstracts\Controller;
use App\Models\Product;
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
        $this->authorize('viewAny', Product::class);
        $result = $this->inventoryManagementService->getDashboardData();

        return view('pages.inventory.dashboard', (array) $result->getData());
    }

    /**
     * Lista de inventário
     */
    public function index(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', Product::class);

        // Se não houver nenhum parâmetro na query (primeiro acesso), mostra estado vazio
        if (empty($request->query())) {
            $result = $this->inventoryManagementService->getEmptyIndexData();
        } else {
            // Se houver qualquer parâmetro (mesmo que vazio, vindo do form de filtro), processa a busca
            $filterDto = InventoryFilterDTO::fromRequest($request->all());
            $result = $this->inventoryManagementService->getIndexData($filterDto);
        }

        if ($result->isError()) {
            return $this->redirectError('provider.inventory.dashboard', 'Erro ao carregar inventário: '.$result->getMessage());
        }

        return view('pages.inventory.index', (array) $result->getData());
    }

    /**
     * Movimentações de inventário
     */
    public function movements(Request $request): View|RedirectResponse
    {
        $this->authorize('viewMovements', \App\Models\Product::class);

        // Se não houver nenhum parâmetro na query (primeiro acesso), mostra estado vazio
        if (empty($request->query())) {
            $result = $this->inventoryManagementService->getEmptyMovementsData();
        } else {
            $result = $this->inventoryManagementService->getMovementsData($request->all());
        }

        if ($result->isError()) {
            return back()->with('error', $result->getMessage())->withInput();
        }

        return view('pages.inventory.movements', (array) $result->getData());
    }

    /**
     * Exibir detalhes de uma movimentação específica
     */
    public function movementShow(int $id): View|RedirectResponse
    {
        $this->authorize('viewMovements', \App\Models\Product::class);
        $result = $this->inventoryManagementService->getMovementDetails($id);

        if ($result->isError()) {
            return redirect()->route('provider.inventory.movements')->with('error', $result->getMessage());
        }

        return view('pages.inventory.movements.show', (array) $result->getData());
    }

    /**
     * Giro de estoque
     */
    public function stockTurnover(Request $request): View|RedirectResponse
    {
        $this->authorize('viewReports', \App\Models\Product::class);

        // Se não houver nenhum parâmetro na query (primeiro acesso), mostra estado vazio
        if (empty($request->query())) {
            $result = $this->inventoryManagementService->getEmptyStockTurnoverData();
        } else {
            $result = $this->inventoryManagementService->getStockTurnoverData($request->all());
        }

        if ($result->isError()) {
            return back()->with('error', $result->getMessage())->withInput();
        }

        return view('pages.inventory.stock-turnover', (array) $result->getData());
    }

    /**
     * Produtos mais usados
     */
    public function mostUsedProducts(Request $request): View|RedirectResponse
    {
        $this->authorize('viewReports', \App\Models\Product::class);

        // Se não houver nenhum parâmetro na query (primeiro acesso), mostra estado vazio
        if (empty($request->query())) {
            $result = $this->inventoryManagementService->getEmptyMostUsedProductsData();
        } else {
            $result = $this->inventoryManagementService->getMostUsedProductsData($request->all());
        }

        if ($result->isError()) {
            return back()->with('error', $result->getMessage())->withInput();
        }

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
     * Exportar inventário do Index
     */
    public function exportIndex(Request $request)
    {
        $this->authorize('viewAny', Product::class);
        $format = $request->input('format') ?? $request->input('type') ?? 'xlsx';

        $filterDto = InventoryFilterDTO::fromRequest($request->all());
        $filterDto->perPage = 10000; // Pegar todos os registros para exportação

        $result = $this->inventoryManagementService->getIndexData($filterDto);
        $data = collect($result->getData()['inventories']->items());

        $this->inventoryExportService->setExportType('inventory');

        return $this->inventoryExportService->export($data, $format, 'inventario_geral');
    }

    /**
     * Relatório de inventário
     */
    public function report(Request $request): View|RedirectResponse
    {
        $this->authorize('viewReports', \App\Models\Product::class);

        // Se não houver nenhum parâmetro na query (primeiro acesso), mostra estado vazio
        if (empty($request->query())) {
            $result = $this->inventoryManagementService->getEmptyReportData();
        } else {
            $result = $this->inventoryManagementService->getReportData($request->all());
        }

        if ($result->isError()) {
            return back()->with('error', $result->getMessage())->withInput();
        }

        return view('pages.inventory.report', (array) $result->getData());
    }

    /**
     * Exportar inventário
     */
    public function export(Request $request)
    {
        $this->authorize('viewReports', \App\Models\Product::class);
        $format = $request->input('format') ?? $request->input('type') ?? 'xlsx';
        $reportType = $request->input('report_type', 'summary');

        // Se report_type for igual ao format (devido ao uso de 'type' em ambos), tenta buscar do request explicitamente
        if ($reportType === $format && $request->has('report_type')) {
            $reportType = $request->input('report_type');
        } elseif ($reportType === $format) {
            // Caso padrão se houver ambiguidade
            $reportType = 'summary';
        }

        // Garantir que o report_type seja passado corretamente para o serviço
        $filters = $request->all();
        $filters['report_type'] = $reportType;

        $result = $this->inventoryManagementService->getReportData($filters);
        $data = collect($result->getData()['reportData']);

        $this->inventoryExportService->setExportType('report_'.$reportType);

        return $this->inventoryExportService->export($data, $format, 'relatorio_inventario_'.$reportType);
    }

    /**
     * Exportar movimentações
     */
    public function exportMovements(Request $request)
    {
        $this->authorize('viewMovements', \App\Models\Product::class);
        $format = $request->input('format') ?? $request->input('type') ?? 'xlsx';

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
        $format = $request->input('format') ?? $request->input('type') ?? 'xlsx';

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
        $format = $request->input('format') ?? $request->input('type') ?? 'xlsx';

        $result = $this->inventoryManagementService->getMostUsedProductsData($request->all());
        $data = collect($result->getData()['products']);

        $this->inventoryExportService->setExportType('most_used');

        return $this->inventoryExportService->export($data, $format, 'produtos_mais_usados');
    }

    /**
     * Exibir detalhes de inventário de um produto
     */
    public function show(Request $request, $sku): View|RedirectResponse
    {
        $result = $this->inventoryManagementService->getProductBySku($sku);

        if (! $result->isSuccess()) {
            return redirect()->back()->with('error', $result->getMessage());
        }

        $product = $result->getData();
        $this->authorize('view', $product);

        $movementsQuery = $product->inventoryMovements();
        $summary = $this->inventoryManagementService->calculateMovementSummary($movementsQuery);

        $movements = $movementsQuery
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('pages.inventory.show', [
            'product' => $product,
            'inventory' => $product->inventory,
            'movements' => $movements,
            'summary' => $summary,
        ]);
    }

    /**
     * Atualizar limites de estoque (mínimo e máximo)
     */
    public function updateLimits(Request $request, $sku): RedirectResponse
    {
        $result = $this->inventoryManagementService->getProductBySku($sku);
        if (! $result->isSuccess()) {
            return redirect()->back()->with('error', $result->getMessage());
        }

        $product = $result->getData();
        $this->authorize('adjustInventory', $product);

        $request->validate([
            'min_quantity' => 'required|integer|min:0',
            'max_quantity' => 'nullable|integer|min:0',
        ]);

        $min = (int) $request->input('min_quantity');
        $max = $request->input('max_quantity') !== null ? (int) $request->input('max_quantity') : null;

        if ($max !== null && $max <= $min) {
            return redirect()->back()->with('error', 'A quantidade máxima deve ser maior que a mínima.')->withInput();
        }

        $updateResult = $this->inventoryManagementService->updateStockLimits(
            (int) $product->id,
            $min,
            $max
        );

        if ($updateResult->isSuccess()) {
            return redirect()
                ->back()
                ->with('success', 'Limites de estoque atualizados com sucesso!');
        }

        return redirect()
            ->back()
            ->with('error', $updateResult->getMessage());
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
                ->back()
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
                ->back()
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
                ->back()
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
