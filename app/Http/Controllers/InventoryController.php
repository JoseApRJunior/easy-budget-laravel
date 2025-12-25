<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Services\Application\InventoryManagementService;
use App\Services\Domain\InventoryService;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    private InventoryService $inventoryService;
    private InventoryManagementService $inventoryManagementService;

    public function __construct(
        InventoryService $inventoryService,
        InventoryManagementService $inventoryManagementService
    ) {
        $this->inventoryService = $inventoryService;
        $this->inventoryManagementService = $inventoryManagementService;
    }

    /**
     * Dashboard de Inventário
     */
    public function dashboard(): View|RedirectResponse
    {
        $result = $this->inventoryManagementService->getDashboardData();

        if (!$result->isSuccess()) {
            return redirect()->back()->with('error', $result->getMessage());
        }

        return view('pages.inventory.dashboard', $result->getData());
    }

    /**
     * Lista de inventário
     */
    public function index(Request $request): View|RedirectResponse
    {
        $result = $this->inventoryManagementService->getIndexData($request->all());

        if (!$result->isSuccess()) {
            return redirect()->back()->with('error', $result->getMessage());
        }

        return view('pages.inventory.index', $result->getData());
    }

    /**
     * Movimentações de inventário
     */
    public function movements(Request $request): View|RedirectResponse
    {
        $result = $this->inventoryManagementService->getMovementsData($request->all());

        if (!$result->isSuccess()) {
            return redirect()->back()->with('error', $result->getMessage());
        }

        return view('pages.inventory.movements', $result->getData());
    }

    /**
     * Giro de estoque
     */
    public function stockTurnover(Request $request): View|RedirectResponse
    {
        $result = $this->inventoryManagementService->getStockTurnoverData($request->all());

        if (!$result->isSuccess()) {
            return redirect()->back()->with('error', $result->getMessage());
        }

        return view('pages.inventory.stock-turnover', $result->getData());
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
    public function alerts(): View|RedirectResponse
    {
        $result = $this->inventoryManagementService->getAlertsData();

        if (!$result->isSuccess()) {
            return redirect()->back()->with('error', $result->getMessage());
        }

        return view('pages.inventory.alerts', $result->getData());
    }

    /**
     * Relatório de inventário
     */
    public function report(Request $request): View
    {
        $type      = $request->input('type', 'summary');
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');

        // Dados do relatório (a serem implementados conforme necessidade)
        $reportData = [];

        return view('pages.inventory.report', compact('type', 'startDate', 'endDate', 'reportData'));
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
    public function show($sku): View|RedirectResponse
    {
        $result = $this->inventoryManagementService->getProductBySku($sku);

        if (!$result->isSuccess()) {
            return redirect()->back()->with('error', $result->getMessage());
        }

        return view('pages.inventory.show', ['product' => $result->getData()]);
    }

    /**
     * Formulário de entrada de estoque
     */
    public function entryForm($sku): View|RedirectResponse
    {
        $result = $this->inventoryManagementService->getProductBySku($sku);

        if (!$result->isSuccess()) {
            return redirect()->back()->with('error', $result->getMessage());
        }

        return view('pages.inventory.entry', ['product' => $result->getData()]);
    }

    /**
     * Processar entrada de estoque
     */
    public function entry(Request $request, $sku)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
            'reason'   => 'nullable|string|max:255',
        ]);

        $productResult = $this->inventoryManagementService->getProductBySku($sku);

        if (!$productResult->isSuccess()) {
            return redirect()->back()->with('error', $productResult->getMessage());
        }

        $product = $productResult->getData();

        $result = $this->inventoryService->addStock(
            (int) $product->id,
            (int) $product->tenant_id,
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
    public function exitForm($sku): View|RedirectResponse
    {
        $productResult = $this->inventoryManagementService->getProductBySku($sku);

        if (!$productResult->isSuccess()) {
            return redirect()->back()->with('error', $productResult->getMessage());
        }

        return view('pages.inventory.exit', ['product' => $productResult->getData()]);
    }

    /**
     * Processar saída de estoque
     */
    public function exit(Request $request, $sku)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
            'reason'   => 'nullable|string|max:255',
        ]);

        $productResult = $this->inventoryManagementService->getProductBySku($sku);

        if (!$productResult->isSuccess()) {
            return redirect()->back()->with('error', $productResult->getMessage());
        }

        $product = $productResult->getData();

        $result = $this->inventoryService->removeStock(
            (int) $product->id,
            (int) $product->tenant_id,
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

        if (!$productResult->isSuccess()) {
            return redirect()->back()->with('error', $productResult->getMessage());
        }

        $product = $productResult->getData();
        $inventory = $product->inventory;

        return view('pages.inventory.adjust', compact('product', 'inventory'));
    }

    /**
     * Processar ajuste de estoque
     */
    public function adjustStock(Request $request, $sku)
    {
        $request->validate([
            'new_quantity' => 'required|integer|min:0',
            'reason'       => 'required|string|min:10|max:255',
        ]);

        $productResult = $this->inventoryManagementService->getProductBySku($sku);

        if (!$productResult->isSuccess()) {
            return redirect()->back()->with('error', $productResult->getMessage());
        }

        $product = $productResult->getData();

        $result = $this->inventoryService->setStock(
            (int) $product->id,
            (int) $product->tenant_id,
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
            'quantity'  => 0,
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
            'reason'   => 'nullable|string|max:255',
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
                    'message' => $result->getMessage(),
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Estoque adicionado com sucesso',
                'data'    => $result->getData(),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao adicionar estoque: ' . $e->getMessage(),
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
            'reason'   => 'nullable|string|max:255',
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
                    'message' => $result->getMessage(),
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Estoque removido com sucesso',
                'data'    => $result->getData(),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover estoque: ' . $e->getMessage(),
            ], 500);
        }
    }
}
