<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Repositories\InventoryRepository;
use App\Services\Domain\InventoryService;
use App\Support\ServiceResult;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    /**
     * @var InventoryService
     */
    protected $inventoryService;

    /**
     * @var InventoryRepository
     */
    protected $inventoryRepository;

    /**
     * InventoryController constructor.
     *
     * @param InventoryService $inventoryService
     * @param InventoryRepository $inventoryRepository
     */
    public function __construct( InventoryService $inventoryService, InventoryRepository $inventoryRepository )
    {
        $this->inventoryService    = $inventoryService;
        $this->inventoryRepository = $inventoryRepository;
    }

    /**
     * Display inventory dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function dashboard()
    {
        $lowStockProducts = $this->inventoryService->getLowStockProducts( 10 );
        $inventorySummary = $this->inventoryService->getInventorySummary();

        return view( 'pages.inventory.dashboard', compact( 'lowStockProducts', 'inventorySummary' ) );
    }

    /**
     * Display a listing of inventory.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index( Request $request )
    {
        $search   = $request->get( 'search' );
        $status   = $request->get( 'status' );
        $category = $request->get( 'category' );

        $query = ProductInventory::with( [ 'product.category' ] )
            ->where( 'tenant_id', auth()->user()->tenant_id );

        if ( $search ) {
            $query->whereHas( 'product', function ( $q ) use ( $search ) {
                $q->where( 'name', 'like', "%{$search}%" )
                    ->orWhere( 'code', 'like', "%{$search}%" );
            } );
        }

        if ( $category ) {
            $query->whereHas( 'product', function ( $q ) use ( $category ) {
                $q->where( 'category_id', $category );
            } );
        }

        if ( $status === 'low' ) {
            $query->whereRaw( 'current_quantity <= minimum_quantity AND current_quantity > 0' );
        } elseif ( $status === 'out' ) {
            $query->where( 'current_quantity', 0 );
        } elseif ( $status === 'sufficient' ) {
            $query->whereRaw( 'current_quantity > minimum_quantity' );
        }

        $inventories = $query->paginate( 15 );
        $categories  = \App\Models\Category::where( 'tenant_id', auth()->user()->tenant_id )->get();

        return view( 'pages.inventory.index', compact( 'inventories', 'search', 'status', 'category', 'categories' ) );
    }

    /**
     * Display inventory movements for a product or all products.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function movements( Request $request )
    {
        $productSearch = $request->get( 'product_search' );
        $type          = $request->get( 'type' );
        $dateFrom      = $request->get( 'date_from' );
        $dateTo        = $request->get( 'date_to' );
        $inventoryId   = $request->get( 'inventory_id' );

        $query = \App\Models\InventoryMovement::with( [ 'product', 'user' ] )
            ->where( 'tenant_id', auth()->user()->tenant_id )
            ->orderBy( 'created_at', 'desc' );

        if ( $productSearch ) {
            $query->whereHas( 'product', function ( $q ) use ( $productSearch ) {
                $q->where( 'name', 'like', "%{$productSearch}%" )
                    ->orWhere( 'code', 'like', "%{$productSearch}%" );
            } );
        }

        if ( $inventoryId ) {
            $query->where( 'product_inventory_id', $inventoryId );
        }

        if ( $type ) {
            $query->where( 'type', $type );
        }

        if ( $dateFrom ) {
            $query->whereDate( 'created_at', '>=', $dateFrom );
        }

        if ( $dateTo ) {
            $query->whereDate( 'created_at', '<=', $dateTo );
        }

        $movements = $query->paginate( 20 );

        // Calcular resumo das movimentações
        $summary = [
            'total_entries'          => $query->clone()->where( 'type', 'entry' )->sum( 'quantity' ),
            'total_entry_value'      => $query->clone()->where( 'type', 'entry' )->sum( \DB::raw( 'quantity * unit_value' ) ),
            'total_exits'            => $query->clone()->where( 'type', 'exit' )->sum( 'quantity' ),
            'total_exit_value'       => $query->clone()->where( 'type', 'exit' )->sum( \DB::raw( 'quantity * unit_value' ) ),
            'total_adjustments'      => $query->clone()->where( 'type', 'adjustment' )->sum( 'quantity' ),
            'total_adjustment_value' => $query->clone()->where( 'type', 'adjustment' )->sum( \DB::raw( 'quantity * unit_value' ) ),
            'total_services'         => $query->clone()->where( 'type', 'service' )->sum( 'quantity' ),
            'total_service_value'    => $query->clone()->where( 'type', 'service' )->sum( \DB::raw( 'quantity * unit_value' ) ),
        ];

        // Se for requisição AJAX para modal, retornar HTML parcial
        if ( $request->ajax() && $inventoryId ) {
            $html = view( 'pages.inventory.partials.movements_table', compact( 'movements' ) )->render();
            return response()->json( [ 'html' => $html ] );
        }

        return view( 'pages.inventory.movements', compact( 'movements', 'summary' ) );
    }

    /**
     * Show the form for adjusting inventory.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function adjust( Request $request )
    {
        $products = Product::where( 'tenant_id', auth()->user()->tenant_id )->get();

        return view( 'pages.inventory.adjust', compact( 'products' ) );
    }

    /**
     * Store inventory adjustment.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeAdjustment( Request $request )
    {
        $request->validate( [
            'product_id'      => 'required|exists:products,id',
            'adjustment_type' => 'required|in:positive,negative,value',
            'reason'          => 'required|string|max:500',
        ] );

        $productId      = $request->product_id;
        $adjustmentType = $request->adjustment_type;

        if ( $adjustmentType === 'value' ) {
            $request->validate( [
                'new_unit_value' => 'required|numeric|min:0.01',
            ] );

            $result = $this->inventoryService->adjustUnitValue(
                $productId,
                $request->new_unit_value,
                $request->reason,
            );
        } else {
            $request->validate( [
                'quantity_adjustment' => 'required|integer|min:1',
            ] );

            $quantity = $request->quantity_adjustment;
            if ( $adjustmentType === 'negative' ) {
                $quantity = -$quantity;
            }

            $result = $this->inventoryService->adjustQuantity(
                $productId,
                $quantity,
                $request->reason,
            );
        }

        if ( $result->isSuccess() ) {
            return redirect()->route( 'inventory.movements' )
                ->with( 'success', 'Ajuste de inventário realizado com sucesso!' );
        }

        return redirect()->back()
            ->withInput()
            ->with( 'error', $result->getMessage() );
    }

    /**
     * Show the form for inventory entry.
     *
     * @return \Illuminate\View\View
     */
    public function entry()
    {
        $products = Product::with( [ 'inventory' ] )
            ->where( 'tenant_id', auth()->user()->tenant_id )
            ->orderBy( 'name' )
            ->get();

        $suppliers = \App\Models\Supplier::where( 'tenant_id', auth()->user()->tenant_id )
            ->orderBy( 'name' )
            ->get();

        return view( 'pages.inventory.entry', compact( 'products', 'suppliers' ) );
    }

    /**
     * Store inventory entry.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeEntry( Request $request )
    {
        $request->validate( [
            'product_id'     => 'required|exists:products,id',
            'quantity'       => 'required|integer|min:1',
            'unit_value'     => 'required|numeric|min:0.01',
            'reason'         => 'required|string|max:500',
            'supplier_id'    => 'nullable|exists:suppliers,id',
            'invoice_number' => 'nullable|string|max:50',
        ] );

        $result = $this->inventoryService->registerEntry(
            $request->product_id,
            $request->quantity,
            $request->reason,
            [
                'unit_value'     => $request->unit_value,
                'supplier_id'    => $request->supplier_id,
                'invoice_number' => $request->invoice_number,
            ],
        );

        if ( $result->isSuccess() ) {
            return redirect()->route( 'inventory.movements' )
                ->with( 'success', 'Entrada de estoque registrada com sucesso!' );
        }

        return redirect()->back()
            ->withInput()
            ->with( 'error', $result->getMessage() );
    }

    /**
     * Show the form for inventory exit.
     *
     * @return \Illuminate\View\View
     */
    public function exit()
    {
        $products = Product::with( [ 'inventory' ] )
            ->where( 'tenant_id', auth()->user()->tenant_id )
            ->whereHas( 'inventory', function ( $query ) {
                $query->where( 'current_quantity', '>', 0 );
            } )
            ->orderBy( 'name' )
            ->get();

        $services = \App\Models\Service::with( [ 'customer', 'serviceType' ] )
            ->where( 'tenant_id', auth()->user()->tenant_id )
            ->whereIn( 'status', [ 'IN_PROGRESS', 'SCHEDULED', 'COMPLETED' ] )
            ->orderBy( 'id', 'desc' )
            ->limit( 20 )
            ->get();

        return view( 'pages.inventory.exit', compact( 'products', 'services' ) );
    }

    /**
     * Store inventory exit.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeExit( Request $request )
    {
        $request->validate( [
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
            'reason'     => 'required|string|max:500',
            'service_id' => 'nullable|exists:services,id',
            'exit_date'  => 'nullable|date',
        ] );

        $result = $this->inventoryService->registerExit(
            $request->product_id,
            $request->quantity,
            $request->reason,
            [
                'service_id' => $request->service_id,
                'exit_date'  => $request->exit_date,
            ],
        );

        if ( $result->isSuccess() ) {
            return redirect()->route( 'inventory.movements' )
                ->with( 'success', 'Saída de estoque registrada com sucesso!' );
        }

        return redirect()->back()
            ->withInput()
            ->with( 'error', $result->getMessage() );
    }

    /**
     * Set inventory parameters.
     *
     * @param Product $product
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function setParameters( Product $product, Request $request )
    {
        $request->validate( [
            'min_quantity' => 'required|integer|min:0',
            'max_quantity' => 'nullable|integer|min:1',
        ] );

        $result = $this->inventoryService->setInventoryParameters(
            $product->id,
            $request->min_quantity,
            $request->max_quantity,
        );

        if ( $result->isSuccess() ) {
            return redirect()->route( 'inventory.movements', $product )
                ->with( 'success', 'Parâmetros de inventário definidos com sucesso!' );
        }

        return redirect()->back()
            ->withInput()
            ->with( 'error', $result->getMessage() );
    }

    /**
     * Get inventory data for AJAX requests.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInventoryData( Request $request )
    {
        $productId = $request->get( 'product_id' );

        if ( !$productId ) {
            return response()->json( [ 'error' => 'Product ID is required' ], 400 );
        }

        $inventory = $this->inventoryRepository->findByProductId( $productId );

        return response()->json( [
            'inventory' => $inventory ? [
                'id'                           => $inventory->id,
                'quantity'                     => $inventory->quantity,
                'min_quantity'                 => $inventory->min_quantity,
                'max_quantity'                 => $inventory->max_quantity,
                'stock_status'                 => $inventory->stock_status,
                'stock_utilization_percentage' => $inventory->stock_utilization_percentage,
                'is_low_stock'                 => $inventory->isLowStock(),
            ] : null
        ] );
    }

    /**
     * Export inventory movements to Excel.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export( Request $request )
    {
        $productSearch = $request->get( 'product_search' );
        $type          = $request->get( 'type' );
        $dateFrom      = $request->get( 'date_from' );
        $dateTo        = $request->get( 'date_to' );

        $query = \App\Models\InventoryMovement::with( [ 'product', 'user' ] )
            ->where( 'tenant_id', auth()->user()->tenant_id )
            ->orderBy( 'created_at', 'desc' );

        if ( $productSearch ) {
            $query->whereHas( 'product', function ( $q ) use ( $productSearch ) {
                $q->where( 'name', 'like', "%{$productSearch}%" )
                    ->orWhere( 'code', 'like', "%{$productSearch}%" );
            } );
        }

        if ( $type ) {
            $query->where( 'type', $type );
        }

        if ( $dateFrom ) {
            $query->whereDate( 'created_at', '>=', $dateFrom );
        }

        if ( $dateTo ) {
            $query->whereDate( 'created_at', '<=', $dateTo );
        }

        $movements = $query->get();

        // Criar arquivo CSV temporário
        $filename = 'movimentacoes_inventario_' . date( 'Y-m-d_H-i-s' ) . '.csv';
        $handle   = fopen( storage_path( 'app/' . $filename ), 'w' );

        // Cabeçalho
        fputcsv( $handle, [
            'Data/Hora',
            'Produto',
            'Código',
            'Tipo',
            'Quantidade',
            'Valor Unitário',
            'Valor Total',
            'Saldo Anterior',
            'Saldo Atual',
            'Motivo',
            'Referência',
            'Responsável'
        ], ';' );

        // Dados
        foreach ( $movements as $movement ) {
            fputcsv( $handle, [
                $movement->created_at->format( 'd/m/Y H:i:s' ),
                $movement->product->name,
                $movement->product->code,
                $this->getMovementTypeLabel( $movement->type ),
                $movement->quantity,
                'R$ ' . number_format( $movement->unit_value, 2, ',', '.' ),
                'R$ ' . number_format( $movement->quantity * $movement->unit_value, 2, ',', '.' ),
                $movement->previous_quantity,
                $movement->current_quantity,
                $movement->reason,
                $movement->reference_type ? ( $movement->reference_type . ' #' . $movement->reference_id ) : '',
                $movement->user ? $movement->user->name : 'Sistema'
            ], ';' );
        }

        fclose( $handle );

        return response()->download( storage_path( 'app/' . $filename ), $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ] )->deleteFileAfterSend( true );
    }

    /**
     * Get movement type label.
     *
     * @param string $type
     * @return string
     */
    private function getMovementTypeLabel( $type )
    {
        $labels = [
            'entry'      => 'Entrada',
            'exit'       => 'Saída',
            'adjustment' => 'Ajuste',
            'service'    => 'Serviço'
        ];

        return $labels[ $type ] ?? 'Desconhecido';
    }

}