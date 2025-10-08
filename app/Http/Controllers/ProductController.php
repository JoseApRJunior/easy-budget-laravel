<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller para gestão de produtos - Interface Web
 *
 * Gerencia todas as operações relacionadas a produtos através
 * da interface web, incluindo CRUD, busca e filtros.
 */
class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService,
    ) {}

    /**
     * Lista de produtos com filtros e paginação.
     */
    public function index( Request $request ): View
    {
        $filters = $request->only( [
            'search', 'status', 'category_id', 'unit_id',
            'price_min', 'price_max', 'sort_by', 'sort_direction', 'per_page'
        ] );

        $products = $this->productService->searchProducts( $filters, auth()->user() );

        // Dados adicionais para a view
        $categories = Category::where( 'tenant_id', auth()->user()->tenant_id )
            ->active()
            ->ordered()
            ->get();

        $units = Unit::where( 'tenant_id', auth()->user()->tenant_id )
            ->active()
            ->ordered()
            ->get();

        $stats = $this->productService->getProductStats( auth()->user() );

        return view( 'products.index', compact( 'products', 'categories', 'units', 'stats', 'filters' ) );
    }

    /**
     * Formulário de criação de produto.
     */
    public function create(): View
    {
        $categories = Category::where( 'tenant_id', auth()->user()->tenant_id )
            ->active()
            ->ordered()
            ->get();

        $units = Unit::where( 'tenant_id', auth()->user()->tenant_id )
            ->active()
            ->ordered()
            ->get();

        return view( 'products.create', compact( 'categories', 'units' ) );
    }

    /**
     * Salva produto.
     */
    public function store( ProductRequest $request ): RedirectResponse
    {
        try {
            $product = $this->productService->createProduct( $request->validated(), auth()->user() );

            return redirect()->route( 'products.show', $product->code )
                ->with( 'success', 'Produto cadastrado com sucesso!' );

        } catch ( \Exception $e ) {
            return back()->withInput()
                ->with( 'error', 'Erro ao cadastrar produto: ' . $e->getMessage() );
        }
    }

    /**
     * Exibe detalhes de um produto.
     */
    public function show( Product $product ): View
    {
        // Verificar se o produto pertence ao tenant do usuário
        if ( $product->tenant_id !== auth()->user()->tenant_id ) {
            abort( 403 );
        }

        $product->load( [ 'category', 'unit', 'inventory' ] );

        return view( 'products.show', compact( 'product' ) );
    }

    /**
     * Formulário de edição de produto.
     */
    public function edit( Product $product ): View
    {
        // Verificar se o produto pertence ao tenant do usuário
        if ( $product->tenant_id !== auth()->user()->tenant_id ) {
            abort( 403 );
        }

        $categories = Category::where( 'tenant_id', auth()->user()->tenant_id )
            ->active()
            ->ordered()
            ->get();

        $units = Unit::where( 'tenant_id', auth()->user()->tenant_id )
            ->active()
            ->ordered()
            ->get();

        return view( 'products.edit', compact( 'product', 'categories', 'units' ) );
    }

    /**
     * Atualiza produto.
     */
    public function update( ProductRequest $request, Product $product ): RedirectResponse
    {
        // Verificar se o produto pertence ao tenant do usuário
        if ( $product->tenant_id !== auth()->user()->tenant_id ) {
            abort( 403 );
        }

        try {
            $updatedProduct = $this->productService->updateProduct( $product, $request->validated(), auth()->user() );

            return redirect()->route( 'products.show', $updatedProduct->code )
                ->with( 'success', 'Produto atualizado com sucesso!' );

        } catch ( \Exception $e ) {
            return back()->withInput()
                ->with( 'error', 'Erro ao atualizar produto: ' . $e->getMessage() );
        }
    }

    /**
     * Desativa produto.
     */
    public function deactivate( Product $product ): RedirectResponse
    {
        // Verificar se o produto pertence ao tenant do usuário
        if ( $product->tenant_id !== auth()->user()->tenant_id ) {
            abort( 403 );
        }

        try {
            $this->productService->deactivateProduct( $product, auth()->user() );

            return redirect()->route( 'products.show', $product->code )
                ->with( 'success', 'Produto desativado com sucesso!' );

        } catch ( \Exception $e ) {
            return back()->with( 'error', 'Erro ao desativar produto: ' . $e->getMessage() );
        }
    }

    /**
     * Ativa produto.
     */
    public function activate( Product $product ): RedirectResponse
    {
        // Verificar se o produto pertence ao tenant do usuário
        if ( $product->tenant_id !== auth()->user()->tenant_id ) {
            abort( 403 );
        }

        try {
            $this->productService->activateProduct( $product, auth()->user() );

            return redirect()->route( 'products.show', $product->code )
                ->with( 'success', 'Produto ativado com sucesso!' );

        } catch ( \Exception $e ) {
            return back()->with( 'error', 'Erro ao ativar produto: ' . $e->getMessage() );
        }
    }

    /**
     * Remove produto (soft delete).
     */
    public function destroy( Product $product ): RedirectResponse
    {
        // Verificar se o produto pertence ao tenant do usuário
        if ( $product->tenant_id !== auth()->user()->tenant_id ) {
            abort( 403 );
        }

        try {
            $this->productService->deleteProduct( $product, auth()->user() );

            return redirect()->route( 'products.index' )
                ->with( 'success', 'Produto removido com sucesso!' );

        } catch ( \Exception $e ) {
            return back()->with( 'error', 'Erro ao remover produto: ' . $e->getMessage() );
        }
    }

    /**
     * Busca produtos via AJAX.
     */
    public function search( Request $request ): \Illuminate\Http\JsonResponse
    {
        $request->validate( [
            'query' => 'required|string|min:2',
        ] );

        $products = Product::where( 'tenant_id', auth()->user()->tenant_id )
            ->where( 'status', 'active' )
            ->where( function ( $query ) use ( $request ) {
                $query->where( 'name', 'like', "%{$request->query}%" )
                    ->orWhere( 'code', 'like', "%{$request->query}%" )
                    ->orWhere( 'description', 'like', "%{$request->query}%" );
            } )
            ->limit( 10 )
            ->get();

        return response()->json( [
            'products' => $products->map( function ( $product ) {
                return [
                    'id'    => $product->id,
                    'code'  => $product->code,
                    'text'  => $product->name,
                    'price' => $product->sale_price,
                    'unit'  => $product->unit?->name,
                ];
            } )
        ] );
    }

    /**
     * Exporta lista de produtos.
     */
    public function export( Request $request )
    {
        $filters = $request->only( [
            'search', 'status', 'category_id', 'unit_id'
        ] );

        $products = $this->productService->searchProducts(
            array_merge( $filters, [ 'per_page' => 1000 ] ),
            auth()->user(),
        );

        // TODO: Implementar exportação para Excel/CSV
        // Por ora, retorna JSON
        return response()->json( [
            'products' => $products->items(),
            'total'    => $products->total(),
        ] );
    }

    /**
     * Imprime produto.
     */
    public function print( Product $product ): View
    {
        // Verificar se o produto pertence ao tenant do usuário
        if ( $product->tenant_id !== auth()->user()->tenant_id ) {
            abort( 403 );
        }

        $product->load( [ 'category', 'unit' ] );

        return view( 'products.print', compact( 'product' ) );
    }

}
