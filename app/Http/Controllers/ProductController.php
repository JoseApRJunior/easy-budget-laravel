<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Services\Domain\CategoryService;
use App\Services\Domain\ProductService;
use Exception;
use Illuminate\Http\JsonResponse;
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
    private ProductService $productService;

    private CategoryService $categoryService;

    private function normalizeCurrencyFilter( ?string $val ): ?float
    {
        if ( $val === null ) return null;
        $digits = preg_replace( '/[^0-9]/', '', $val );
        if ( $digits === null || $digits === '' ) return null;
        if ( strlen( $digits ) === 1 ) $digits = '0' . $digits;
        $intPart    = substr( $digits, 0, -2 );
        $decPart    = substr( $digits, -2 );
        $normalized = ( $intPart !== '' ? $intPart : '0' ) . '.' . $decPart;
        return (float) $normalized;
    }

    public function __construct( ProductService $productService, CategoryService $categoryService )
    {
        $this->productService  = $productService;
        $this->categoryService = $categoryService;
    }

    /**
     * Lista de produtos com filtros avançados.
     *
     * Rota: products.index
     */
    public function index( Request $request ): View
    {
        $filters        = $request->only( [ 'search', 'category_id', 'active', 'min_price', 'max_price', 'deleted', 'per_page' ] );
        $perPage        = (int) ( $filters[ 'per_page' ] ?? 10 );
        $allowedPerPage = [ 10, 20, 50 ];
        if ( !in_array( $perPage, $allowedPerPage, true ) ) {
            $perPage = 10;
        }
        $filters[ 'per_page' ] = $perPage;

        try {
            $showOnlyTrashed = ( $filters[ 'deleted' ] ?? '' ) === 'only';

            if ( $showOnlyTrashed ) {
                $result   = $this->productService->getDeletedProducts( $filters, [ 'category' ] );
                $products = $result->isSuccess() ? $result->getData() : collect();
            } else {
                $result = $this->productService->getFilteredProducts( $filters, [ 'category' ] );

                if ( !$result->isSuccess() ) {
                    abort( 500, 'Erro ao carregar lista de produtos' );
                }

                $products = $result->getData();
                if ( method_exists( $products, 'appends' ) ) {
                    $products = $products->appends( $request->query() );
                }
            }

            return view( 'pages.product.index', [
                'products'   => $products,
                'filters'    => $filters,
                'categories' => $this->categoryService->getActive(),
            ] );
        } catch ( Exception ) {
            abort( 500, 'Erro ao carregar produtos' );
        }
    }

    /**
     * Formulário de criação de produto.
     *
     * Rota: products.create
     */
    public function create(): View
    {
        try {
            return view( 'pages.product.create', [
                'categories' => $this->categoryService->getActive(),
            ] );
        } catch ( Exception ) {
            abort( 500, 'Erro ao carregar formulário de criação de produto' );
        }
    }

    /**
     * Armazena um novo produto.
     *
     * Rota: products.store
     */
    public function store( ProductStoreRequest $request ): RedirectResponse
    {
        try {
            $result = $this->productService->createProduct( $request->validated() );

            if ( !$result->isSuccess() ) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with( 'error', $result->getMessage() );
            }

            return redirect()
                ->route( 'provider.products.create' )
                ->with( 'success', 'Produto criado com sucesso! Você pode cadastrar outro produto agora.' );
        } catch ( Exception $e ) {
            return redirect()
                ->back()
                ->withInput()
                ->with( 'error', 'Erro ao criar produto: ' . $e->getMessage() );
        }
    }

    /**
     * Detalhes de um produto por SKU.
     *
     * Rota: products.show
     */
    public function show( string $sku ): View
    {
        try {
            $result = $this->productService->findBySku( $sku, [ 'category', 'productInventory' ] );

            if ( !$result->isSuccess() ) {
                abort( 404, 'Produto não encontrado' );
            }

            return view( 'pages.product.show', [
                'product' => $result->getData(),
            ] );
        } catch ( Exception ) {
            abort( 500, 'Erro ao carregar detalhes do produto' );
        }
    }

    /**
     * Formulário de edição de produto por SKU.
     *
     * Rota: products.edit
     */
    public function edit( string $sku ): View
    {
        try {
            $result = $this->productService->findBySku( $sku, [ 'category' ] );

            if ( !$result->isSuccess() ) {
                abort( 404, 'Produto não encontrado' );
            }

            return view( 'pages.product.edit', [
                'product'    => $result->getData(),
                'categories' => $this->categoryService->getActive(),
            ] );
        } catch ( Exception ) {
            abort( 500, 'Erro ao carregar formulário de edição de produto' );
        }
    }

    /**
     * Atualiza um produto por SKU.
     *
     * Rota: products.update
     */
    public function update( string $sku, ProductUpdateRequest $request ): RedirectResponse
    {
        try {
            $result = $this->productService->updateProductBySku( $sku, $request->validated() );

            if ( !$result->isSuccess() ) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with( 'error', $result->getMessage() );
            }

            $product = $result->getData();

            return redirect()
                ->route( 'provider.products.show', $product->sku )
                ->with( 'success', 'Produto atualizado com sucesso!' );
        } catch ( Exception $e ) {
            return redirect()
                ->back()
                ->withInput()
                ->with( 'error', 'Erro ao atualizar produto: ' . $e->getMessage() );
        }
    }

    /**
     * Alterna status (ativo/inativo) de um produto via SKU.
     *
     * Rota: products.toggle-status (PATCH)
     */
    public function toggle_status( string $sku, Request $request )
    {
        try {
            $result = $this->productService->toggleProductStatus( $sku );

            if ( !$result->isSuccess() ) {
                if ( $request->ajax() || $request->wantsJson() ) {
                    return response()->json( [
                        'success' => false,
                        'message' => $result->getMessage(),
                    ], 400 );
                }
                return redirect()->back()->with( 'error', $result->getMessage() );
            }

            if ( $request->ajax() || $request->wantsJson() ) {
                return response()->json( [
                    'success' => true,
                    'message' => $result->getMessage(),
                ] );
            }

            $product = $result->getData();
            return redirect()
                ->route( 'provider.products.show', $product->sku )
                ->with( 'success', $result->getMessage() );
        } catch ( Exception $e ) {
            if ( $request->ajax() || $request->wantsJson() ) {
                return response()->json( [
                    'success' => false,
                    'message' => 'Erro ao alterar status do produto: ' . $e->getMessage(),
                ], 500 );
            }
            return redirect()->back()->with( 'error', 'Erro ao alterar status do produto: ' . $e->getMessage() );
        }
    }

    /**
     * Exclui um produto por SKU.
     *
     * Rota: products.destroy (DELETE)
     */
    public function delete_store( string $sku ): RedirectResponse
    {
        try {
            $result = $this->productService->deleteProductBySku( $sku );

            if ( !$result->isSuccess() ) {
                return redirect()
                    ->back()
                    ->with( 'error', $result->getMessage() );
            }

            return redirect()
                ->route( 'provider.products.index' )
                ->with( 'success', 'Produto excluído com sucesso!' );
        } catch ( Exception $e ) {
            return redirect()
                ->back()
                ->with( 'error', 'Erro ao excluir produto: ' . $e->getMessage() );
        }
    }

    /**
     * Dashboard de Produtos.
     *
     * Rota: provider.products.dashboard
     *
     * Exibe métricas e atalhos rápidos, seguindo o padrão do dashboard de clientes.
     */
    public function dashboard()
    {
        $result = $this->productService->getDashboardData( auth()->user()->tenant_id );

        if ( !$result->isSuccess() ) {
            return view( 'pages.product.dashboard', [
                'stats' => [],
                'error' => $result->getMessage(),
            ] );
        }

        return view( 'pages.product.dashboard', [
            'stats' => $result->getData(),
        ] );
    }

    /**
     * Restaura um produto deletado.
     *
     * Rota: products.restore (POST)
     */
    public function restore( string $sku ): RedirectResponse
    {
        try {
            $result = $this->productService->restoreProductBySku( $sku );

            if ( !$result->isSuccess() ) {
                return redirect()
                    ->route( 'provider.products.index' )
                    ->with( 'error', $result->getMessage() );
            }

            return redirect()
                ->route( 'provider.products.index' )
                ->with( 'success', 'Produto restaurado com sucesso!' );
        } catch ( Exception $e ) {
            return redirect()
                ->route( 'provider.products.index' )
                ->with( 'error', 'Erro ao restaurar produto: ' . $e->getMessage() );
        }
    }

    /**
     * AJAX endpoint para buscar produtos com filtros.
     */
    public function ajaxSearch( Request $request ): JsonResponse
    {
        $filters = $request->only( [ 'search', 'active', 'min_price', 'max_price', 'category_id' ] );
        if ( isset( $filters[ 'min_price' ] ) ) {
            $filters[ 'min_price' ] = $this->normalizeCurrencyFilter( $filters[ 'min_price' ] );
        }
        if ( isset( $filters[ 'max_price' ] ) ) {
            $filters[ 'max_price' ] = $this->normalizeCurrencyFilter( $filters[ 'max_price' ] );
        }
        $result = $this->productService->getFilteredProducts( $filters, [ 'category' ] );

        return $result->isSuccess()
            ? response()->json( [ 'success' => true, 'data' => $result->getData() ] )
            : response()->json( [ 'success' => false, 'message' => $result->getMessage() ], 400 );
    }

}
