<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Services\Domain\CategoryService;
use App\Services\Domain\ProductExportService;
use App\Services\Domain\ProductService;
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
    public function __construct(
        private ProductService $productService,
        private CategoryService $categoryService,
        private ProductExportService $productExportService,
    ) {}

    /**
     * Dashboard de Produtos.
     *
     * Rota: provider.products.dashboard
     *
     * Exibe métricas e atalhos rápidos, seguindo o padrão do dashboard de clientes.
     */
    public function dashboard()
    {
        return $this->view( 'pages.product.dashboard', $this->productService->getDashboardData(), 'stats' );
    }

    /**
     * Lista de produtos com filtros avançados.
     *
     * Rota: products.index
     */
    public function index( Request $request ): View
    {
        $filters = $request->only( [ 'search', 'active', 'deleted', 'per_page', 'all', 'category_id', 'min_price', 'max_price' ] );

        if ( empty( $request->query() ) ) {
            $result = $this->emptyResult();
        } else {
            $perPage = (int) ( $filters[ 'per_page' ] ?? 10 );
            $result  = $this->productService->getFilteredProducts( $filters, [ 'category' ], $perPage );
        }

        return $this->view( 'pages.product.index', $result, 'products', [
            'filters'    => $filters,
            'categories' => $this->categoryService->getActive()->getData(),
        ] );
    }

    /**
     * Formulário de criação de produto.
     *
     * Rota: products.create
     */
    public function create(): View
    {
        $result = $this->categoryService->getActive();

        return $this->view( 'pages.product.create', $result, 'categories', [
            'defaults' => [ 'is_active' => true ],
        ] );
    }

    /**
     * Armazena um novo produto.
     *
     * Rota: products.store
     */
    public function store( ProductStoreRequest $request ): RedirectResponse
    {
        $result = $this->productService->createProduct( $request->validated() );

        if ( !$result->isSuccess() ) {
            return $this->redirectBackWithServiceResult( $result, 'Produto criado com sucesso! Você pode cadastrar outro produto agora.' );
        }

        return $this->redirectSuccess( 'provider.products.create', 'Produto criado com sucesso! Você pode cadastrar outro produto agora.' );
    }

    /**
     * Detalhes de um produto por SKU.
     *
     * Rota: products.show
     */
    public function show( string $sku ): View|RedirectResponse
    {

        $result = $this->productService->findBySku( $sku, [ 'category', 'inventory' ] );
        if ( $result->isError() ) {
            return $this->redirectError( 'provider.products.index', $result->getMessage() );
        }

        $result->getData()->load( [
            'category',
            'inventory',
        ] );

        return $this->view( 'pages.product.show', $result, 'product' );
    }

    /**
     * Formulário de edição de produto por SKU.
     *
     * Rota: products.edit
     */
    public function edit( string $sku ): View|RedirectResponse
    {
        $result = $this->productService->findBySku( $sku, [ 'category' ] );

        if ( $result->isError() ) {
            return $this->redirectError( 'provider.products.index', $result->getMessage() );
        }

        $product      = $result->getData();
        $parentResult = $this->categoryService->getActive();

        $categories = $parentResult->isSuccess()
            ? $parentResult->getData()
            : collect();

        return view( 'pages.product.edit', compact( 'product', 'categories' ) );
    }

    /**
     * Atualiza um produto por SKU.
     *
     * Rota: products.update
     */
    public function update( string $sku, ProductUpdateRequest $request ): RedirectResponse
    {
        $result = $this->productService->updateProductBySku( $sku, $request->validated() );

        if ( !$result->isSuccess() ) {
            return $this->redirectBackWithServiceResult( $result, 'Produto atualizado com sucesso!' );
        }

        $product = $result->getData();

        return $this->redirectSuccess( 'provider.products.show', 'Produto atualizado com sucesso!', [ 'sku' => $product->sku ] );
    }

    /**
     * Alterna status (ativo/inativo) de um produto via SKU.
     *
     * Rota: products.toggle-status (PATCH)
     */
    public function toggleStatus( string $sku, Request $request )
    {
        $result = $this->productService->toggleProductStatus( $sku );

        if ( !$result->isSuccess() ) {
            if ( $request->ajax() || $request->wantsJson() ) {
                return response()->json( [
                    'success' => false,
                    'message' => $result->getMessage(),
                ], 400 );
            }
            return $this->redirectError( 'provider.products.index', $result->getMessage() );
        }

        if ( $request->ajax() || $request->wantsJson() ) {
            return response()->json( [
                'success' => true,
                'message' => $result->getMessage(),
            ] );
        }

        $product = $result->getData();
        return $this->redirectSuccess( 'provider.products.show', $result->getMessage(), [ 'sku' => $product->sku ] );
    }

    /**
     * Exclui um produto por SKU.
     *
     * Rota: products.destroy (DELETE)
     */
    public function destroy( string $sku ): RedirectResponse
    {
        $result = $this->productService->deleteProductBySku( $sku );

        return $this->redirectWithServiceResult( 'provider.products.index', $result );
    }

    /**
     * Exclui um produto por SKU (método alternativo mantido para compatibilidade).
     *
     * Rota: products.delete_store (DELETE)
     */
    public function delete_store( string $sku ): RedirectResponse
    {
        return $this->destroy( $sku );
    }

    /**
     * Restaura um produto deletado.
     *
     * Rota: products.restore (POST)
     */
    public function restore( string $sku ): RedirectResponse
    {
        $result = $this->productService->restoreProductBySku( $sku );

        return $this->redirectWithServiceResult( 'provider.products.index', $result );
    }

    /**
     * Métodos de conveniência que delegam ao index com filtros pré-definidos.
     */
    public function search( Request $request ): View
    {
        return $this->index( $request );
    }

    public function active( Request $request ): View
    {
        $request->merge( [ 'active' => '1' ] );
        return $this->index( $request );
    }

    public function deleted( Request $request ): View
    {
        $request->merge( [ 'deleted' => 'only' ] );
        return $this->index( $request );
    }

    public function restoreMultiple( Request $request ): RedirectResponse
    {
        $ids = $request->input( 'ids', [] );

        if ( empty( $ids ) ) {
            return $this->redirectError( 'provider.products.index', 'Nenhum produto selecionado para restauração.' );
        }

        $result = $this->productService->restoreProducts( $ids );

        if ( $result->isError() ) {
            return $this->redirectError( 'provider.products.index', $result->getMessage() );
        }

        return $this->redirectSuccess( 'provider.products.index', "Restaurados produtos com sucesso." );
    }

    /**
     * AJAX endpoint para buscar produtos com filtros.
     */
    public function ajaxSearch( Request $request ): JsonResponse
    {
        $filters = $request->only( [ 'search', 'active', 'category_id', 'min_price', 'max_price' ] );
        $result  = $this->productService->getFilteredProducts( $filters, [ 'category' ] );

        return $this->jsonResponse( $result );
    }

    /**
     * Exporta os produtos para Excel ou PDF.
     *
     * Rota: products.export (GET)
     */
    public function export( Request $request )
    {
        $format = $request->get( 'format', 'xlsx' );

        // Captura TODOS os filtros aplicados na listagem (exceto paginação)
        $filters = $request->only( [ 'search', 'active', 'deleted', 'category_id', 'min_price', 'max_price' ] );

        // Busca produtos com os filtros aplicados
        $result = $this->productService->getFilteredProducts( $filters, [ 'category' ], 1000 );

        if ( $result->isError() ) {
            return $this->redirectError( 'provider.products.index', $result->getMessage() );
        }

        // Extrai a collection do paginator preservando todos os atributos
        $paginatorOrCollection = $result->getData();

        if ( method_exists( $paginatorOrCollection, 'items' ) ) {
            // É um LengthAwarePaginator - pega os items diretamente
            $products = collect( $paginatorOrCollection->items() );
        } elseif ( method_exists( $paginatorOrCollection, 'getCollection' ) ) {
            // É um Paginator - usa getCollection
            $products = $paginatorOrCollection->getCollection();
        } else {
            // Já é uma Collection
            $products = $paginatorOrCollection;
        }

        return $format === 'pdf'
            ? $this->productExportService->exportToPdf( $products )
            : $this->productExportService->exportToExcel( $products, $format );
    }

}
