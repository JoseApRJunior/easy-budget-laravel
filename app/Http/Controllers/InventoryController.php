<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\InventoryMovementStoreRequest;
use App\Models\Product;
use App\Models\Category;
use App\Repositories\InventoryMovementRepository;
use App\Repositories\ProductInventoryRepository;
use App\Services\Domain\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

/**
 * Controller para gerenciamento de inventário
 */
class InventoryController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly ProductInventoryRepository $productInventoryRepository,
        private readonly InventoryMovementRepository $inventoryMovementRepository,
        private readonly InventoryService $inventoryService,
    ) {}

    /**
     * Exibe dashboard de inventário
     */
    public function index( Request $request ): View
    {
        $filters = [
            'search'      => $request->get( 'search' ),
            'status'      => $request->get( 'status' ),
            'low_stock'   => $request->get( 'low_stock' ),
            'category_id' => $request->get( 'category_id' ),
        ];

        $category = $request->get('category');
        if ($category && empty($filters['category_id'])) {
            $filters['category_id'] = $category;
        }

        $inventories = $this->productInventoryRepository->getPaginated( 15, $filters );
        $lowStockCount = $this->productInventoryRepository->getLowStockCount();
        $totalValue = $this->inventoryService->calculateTotalInventoryValue();
        $categories = Category::orderBy('name')->get();

        return view( 'pages.inventory.index', compact( 'inventories', 'lowStockCount', 'totalValue', 'filters', 'categories' ) );
    }

    /**
     * Dashboard de estoque
     */
    public function dashboard( Request $request ): View
    {
        $inventorySummary = $this->productInventoryRepository->getPaginated( 50, [] )->getCollection();
        $lowStockProducts = $this->productInventoryRepository->getLowStockItems();

        return view( 'pages.inventory.dashboard', compact( 'inventorySummary', 'lowStockProducts' ) );
    }

    /**
     * Exibe detalhes do inventário de um produto
     */
    public function show( Product $product ): View
    {
        $inventory = $this->productInventoryRepository->findByProduct( $product->id );
        $movements = $this->inventoryMovementRepository->getByProduct( $product->id, 20 );

        return view( 'pages.inventory.show', compact( 'product', 'inventory', 'movements' ) );
    }

    /**
     * Exibe formulário de ajuste de inventário
     */
    public function adjust( Product $product ): View
    {
        $inventory = $this->productInventoryRepository->findByProduct( $product->id );

        return view( 'pages.inventory.adjust', compact( 'product', 'inventory' ) );
    }

    /**
     * Processa ajuste de inventário
     */
    public function storeAdjustment( InventoryMovementStoreRequest $request, Product $product ): RedirectResponse
    {
        $validated = $request->validated();

        $movement = $this->inventoryService->adjustInventory(
            $product,
            $validated['type'],
            $validated['quantity'],
            $validated['reason'] ?? 'Ajuste manual de inventário',
            $validated['reference_id'] ?? null,
            $validated['reference_type'] ?? null
        );

        return redirect()
            ->route( 'inventory.show', $product )
            ->with( 'success', "Inventário ajustado com sucesso. Movimento #{$movement->id} registrado." );
    }

    /**
     * Exibe relatório de inventário
     */
    public function report( Request $request ): View
    {
        $type = $request->get( 'type', 'summary' );
        $startDate = $request->get( 'start_date' );
        $endDate = $request->get( 'end_date' );

        $reportData = match ( $type ) {
            'movements' => $this->inventoryService->generateMovementReport( $startDate, $endDate ),
            'valuation' => $this->inventoryService->generateValuationReport(),
            'low-stock' => $this->inventoryService->generateLowStockReport(),
            default => $this->inventoryService->generateSummaryReport(),
        };

        return view( 'pages.inventory.report', compact( 'reportData', 'type', 'startDate', 'endDate' ) );
    }

    /**
     * Lista movimentos de estoque de um produto
     */
    public function movements( Product $product ): View
    {
        $inventory = $this->productInventoryRepository->findByProduct( $product->id );
        $movements = $this->inventoryMovementRepository->getByProduct( $product->id, 50 );

        return view( 'pages.inventory.movements', compact( 'product', 'inventory', 'movements' ) );
    }

    /**
     * Form de entrada de estoque
     */
    public function entry( Product $product ): View
    {
        $inventory = $this->productInventoryRepository->findByProduct( $product->id );

        return view( 'pages.inventory.entry', compact( 'product', 'inventory' ) );
    }

    /**
     * Form de saída de estoque
     */
    public function exit( Product $product ): View
    {
        $inventory = $this->productInventoryRepository->findByProduct( $product->id );

        return view( 'pages.inventory.exit', compact( 'product', 'inventory' ) );
    }

    /**
     * API: Lista produtos com estoque baixo
     */
    public function lowStock(): JsonResponse
    {
        $lowStockItems = $this->productInventoryRepository->getLowStockItems();

        return response()->json( [
            'data' => $lowStockItems,
            'count' => $lowStockItems->count(),
        ] );
    }

    /**
     * API: Atualiza quantidade mínima de estoque
     */
    public function updateMinQuantity( Request $request, Product $product ): JsonResponse
    {
        $validated = $request->validate( [
            'min_quantity' => 'required|integer|min:0',
        ] );

        $inventory = $this->productInventoryRepository->updateMinQuantity( $product->id, $validated['min_quantity'] );

        return response()->json( [
            'success' => true,
            'message' => 'Quantidade mínima atualizada com sucesso.',
            'data' => [
                'product_id' => $inventory->product_id,
                'min_quantity' => $inventory->min_quantity,
                'current_quantity' => $inventory->quantity,
                'is_low_stock' => $inventory->isLowStock(),
            ],
        ] );
    }

    /**
     * API: Atualiza quantidade máxima de estoque
     */
    public function updateMaxQuantity( Request $request, Product $product ): JsonResponse
    {
        $validated = $request->validate( [
            'max_quantity' => 'nullable|integer|min:1',
        ] );

        $inventory = $this->productInventoryRepository->updateMaxQuantity( $product->id, $validated['max_quantity'] );

        return response()->json( [
            'success' => true,
            'message' => 'Quantidade máxima atualizada com sucesso.',
            'data' => [
                'product_id' => $inventory->product_id,
                'max_quantity' => $inventory->max_quantity,
                'current_quantity' => $inventory->quantity,
                'is_high_stock' => $inventory->isHighStock(),
            ],
        ] );
    }

    /**
     * API: Busca informações de inventário
     */
    public function search( Request $request ): JsonResponse
    {
        $search = $request->get( 'q' );
        $limit = min( $request->get( 'limit', 10 ), 50 );

        $results = $this->productInventoryRepository->searchInventory( $search, $limit );

        return response()->json( [
            'data' => $results,
        ] );
    }

    /**
     * Exporta relatório de inventário
     */
    public function export( Request $request )
    {
        $type = $request->get( 'type', 'pdf' );
        $reportType = $request->get( 'report_type', 'summary' );

        return $this->inventoryService->exportReport( $reportType, $type );
    }
}