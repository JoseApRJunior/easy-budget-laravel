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
    private ProductService  $productService;
    private CategoryService $categoryService;

    public function __construct( ProductService $productService, CategoryService $categoryService )
    {
        $this->productService  = $productService;
        $this->categoryService = $categoryService;
    }

    public function index( Request $request ): View
    {
        try {
            $filters = $request->only( [ 'search', 'category_id', 'active', 'min_price', 'max_price' ] );

            $result = $this->productService->getFilteredProducts( $filters, [ 'category' ] );

            if ( !$result->isSuccess() ) {
                abort( 500, 'Erro ao carregar lista de produtos' );
            }

            $products = $result->getData();

            return view( 'products.index', [
                'products'   => $products,
                'filters'    => $filters,
                'categories' => $this->categoryService->getActive()
            ] );

        } catch ( Exception $e ) {
            abort( 500, 'Erro ao carregar produtos' );
        }
    }

    public function create(): View
    {
        try {
            return view( 'products.create', [
                'categories' => $this->categoryService->getActive()
            ] );
        } catch ( Exception $e ) {
            abort( 500, 'Erro ao carregar formulário de criação de produto' );
        }
    }

    public function store( ProductStoreRequest $request ): RedirectResponse
    {
        try {
            $result = $this->productService->createProduct( $request->validated() );

            if ( !$result->isSuccess() ) {
                return redirect()->back()
                    ->withInput()
                    ->with( 'error', $result->getMessage() );
            }

            $product = $result->getData();

            return redirect()->route( 'products.show', $product->sku )
                ->with( 'success', 'Produto criado com sucesso!' );

        } catch ( Exception $e ) {
            return redirect()->back()
                ->withInput()
                ->with( 'error', 'Erro ao criar produto: ' . $e->getMessage() );
        }
    }

    public function show( string $sku ): View
    {
        try {
            $result = $this->productService->findBySku( $sku, [ 'category' ] );

            if ( !$result->isSuccess() ) {
                abort( 404, 'Produto não encontrado' );
            }

            $product = $result->getData();

            return view( 'products.show', [
                'product' => $product
            ] );

        } catch ( Exception $e ) {
            abort( 500, 'Erro ao carregar detalhes do produto' );
        }
    }

    public function edit( string $sku ): View
    {
        try {
            $result = $this->productService->findBySku( $sku, [ 'category' ] );

            if ( !$result->isSuccess() ) {
                abort( 404, 'Produto não encontrado' );
            }

            $product = $result->getData();

            return view( 'products.edit', [
                'product'    => $product,
                'categories' => $this->categoryService->getActive()
            ] );

        } catch ( Exception $e ) {
            abort( 500, 'Erro ao carregar formulário de edição de produto' );
        }
    }

    public function update( string $sku, ProductUpdateRequest $request ): RedirectResponse
    {
        try {
            $result = $this->productService->updateProductBySku( $sku, $request->validated() );

            if ( !$result->isSuccess() ) {
                return redirect()->back()
                    ->withInput()
                    ->with( 'error', $result->getMessage() );
            }

            $product = $result->getData();

            return redirect()->route( 'products.show', $product->sku )
                ->with( 'success', 'Produto atualizado com sucesso!' );

        } catch ( Exception $e ) {
            return redirect()->back()
                ->withInput()
                ->with( 'error', 'Erro ao atualizar produto: ' . $e->getMessage() );
        }
    }

    public function toggle_status( string $sku ): JsonResponse
    {
        try {
            $result = $this->productService->toggleProductStatus( $sku );

            if ( !$result->isSuccess() ) {
                return response()->json( [ 'success' => false, 'message' => $result->getMessage() ], 400 );
            }

            return response()->json( [ 'success' => true, 'message' => $result->getMessage() ] );

        } catch ( Exception $e ) {
            return response()->json( [ 'success' => false, 'message' => 'Erro ao alterar status do produto: ' . $e->getMessage() ], 500 );
        }
    }

    public function delete_store( string $sku ): RedirectResponse
    {
        try {
            $result = $this->productService->deleteProductBySku( $sku );

            if ( !$result->isSuccess() ) {
                return redirect()->back()
                    ->with( 'error', $result->getMessage() );
            }

            return redirect()->route( 'products.index' )
                ->with( 'success', 'Produto excluído com sucesso!' );

        } catch ( Exception $e ) {
            return redirect()->back()
                ->with( 'error', 'Erro ao excluir produto: ' . $e->getMessage() );
        }
    }

}
