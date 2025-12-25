<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\Product\ProductDTO;
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
     */
    public function dashboard(): View
    {
        $this->authorize('viewAny', \App\Models\Product::class);
        $result = $this->productService->getDashboardData();
        return $this->view('pages.product.dashboard', $result, 'stats');
    }

    /**
     * Lista de produtos com filtros avançados.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', \App\Models\Product::class);
        $filters = $request->only(['search', 'active', 'deleted', 'per_page', 'all', 'category_id', 'min_price', 'max_price']);

        if (empty($request->query())) {
            $result = $this->emptyResult();
        } else {
            $perPage = (int) ($filters['per_page'] ?? 15);
            $result  = $this->productService->getFilteredProducts($filters, ['category'], $perPage);
        }

        return $this->view('pages.product.index', $result, 'products', [
            'filters'    => $filters,
            'categories' => $this->categoryService->getActive()->getData(),
        ]);
    }

    /**
     * Formulário de criação de produto.
     *
     * Rota: products.create
     */
    public function create(): View
    {
        $this->authorize('create', \App\Models\Product::class);
        $result = $this->categoryService->getActive();

        return $this->view('pages.product.create', $result, 'categories', [
            'defaults' => ['is_active' => true],
        ]);
    }

    /**
     * Armazena um novo produto.
     */
    public function store(ProductStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', \App\Models\Product::class);
        try {
            $dto = ProductDTO::fromRequest($request->validated());
            $result = $this->productService->createProduct($dto);

            return $this->redirectWithServiceResult(
                'provider.products.create',
                $result,
                'Produto criado com sucesso! Você pode cadastrar outro produto agora.'
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erro inesperado ao criar produto', [
                'error' => $e->getMessage(),
            ]);
            return $this->redirectError('provider.products.create', 'Erro interno ao criar produto.');
        }
    }

    /**
     * Detalhes de um produto por SKU.
     */
    public function show(string $sku): View|RedirectResponse
    {
        $result = $this->productService->findBySku($sku, ['category', 'inventory']);

        if ($result->isError()) {
            return $this->redirectError('provider.products.index', $result->getMessage());
        }

        $product = $result->getData();
        $this->authorize('view', $product);

        return $this->view('pages.product.show', $result, 'product');
    }

    /**
     * Formulário de edição de produto por SKU.
     */
    public function edit(string $sku): View|RedirectResponse
    {
        $result = $this->productService->findBySku($sku, ['category']);

        if ($result->isError()) {
            return $this->redirectError('provider.products.index', $result->getMessage());
        }

        $product = $result->getData();
        $this->authorize('update', $product);

        $categories = $this->categoryService->getActive()->getData();

        return view('pages.product.edit', compact('product', 'categories'));
    }

    /**
     * Atualiza um produto por SKU.
     */
    public function update(string $sku, ProductUpdateRequest $request): RedirectResponse
    {
        try {
            $result = $this->productService->findBySku($sku);
            if ($result->isError()) {
                return $this->redirectError('provider.products.index', $result->getMessage());
            }

            $product = $result->getData();
            $this->authorize('update', $product);

            $dto = ProductDTO::fromRequest($request->validated());
            $removeImage = (bool) $request->input('remove_image', false);

            $updateResult = $this->productService->updateProductBySku($sku, $dto, $removeImage);

            return $this->redirectWithServiceResult(
                'provider.products.show',
                $updateResult,
                'Produto atualizado com sucesso!',
                ['sku' => $sku]
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erro inesperado ao atualizar produto', [
                'sku'   => $sku,
                'error' => $e->getMessage(),
            ]);
            return $this->redirectError('provider.products.edit', 'Erro interno ao atualizar produto.', ['sku' => $sku]);
        }
    }

    /**
     * Alterna status (ativo/inativo) de um produto via SKU.
     */
    public function toggleStatus(string $sku, Request $request): RedirectResponse|JsonResponse
    {
        try {
            $result = $this->productService->findBySku($sku);
            if ($result->isError()) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $result->getMessage()], 404);
                }
                return $this->redirectError('provider.products.index', $result->getMessage());
            }

            $product = $result->getData();
            $this->authorize('update', $product);

            $toggleResult = $this->productService->toggleProductStatus($sku);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => $toggleResult->isSuccess(),
                    'message' => $toggleResult->getMessage(),
                ], $toggleResult->isSuccess() ? 200 : 400);
            }

            return $this->redirectWithServiceResult(
                'provider.products.show',
                $toggleResult,
                $toggleResult->getMessage(),
                ['sku' => $sku]
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erro inesperado ao alterar status do produto', [
                'sku'   => $sku,
                'error' => $e->getMessage(),
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro interno ao alterar status.',
                ], 500);
            }

            return $this->redirectError('provider.products.index', 'Erro interno ao alterar status.');
        }
    }

    /**
     * Exclui um produto por SKU.
     */
    public function destroy(string $sku): RedirectResponse
    {
        try {
            $result = $this->productService->findBySku($sku);
            if ($result->isError()) {
                return $this->redirectError('provider.products.index', $result->getMessage());
            }

            $product = $result->getData();
            $this->authorize('delete', $product);

            $deleteResult = $this->productService->deleteProductBySku($sku);

            return $this->redirectWithServiceResult(
                'provider.products.index',
                $deleteResult,
                'Produto excluído com sucesso.'
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erro inesperado ao excluir produto', [
                'sku'   => $sku,
                'error' => $e->getMessage(),
            ]);
            return $this->redirectError('provider.products.index', 'Erro interno ao excluir produto.');
        }
    }

    /**
     * Restaura um produto deletado.
     */
    public function restore(string $sku): RedirectResponse
    {
        try {
            $result = $this->productService->findBySku($sku, [], true);

            if ($result->isError()) {
                return $this->redirectError('provider.products.index', 'Produto não encontrado para restauração.');
            }

            $product = $result->getData();
            $this->authorize('update', $product);

            $restoreResult = $this->productService->restoreProductBySku($sku);

            return $this->redirectWithServiceResult(
                'provider.products.show',
                $restoreResult,
                'Produto restaurado com sucesso!',
                ['sku' => $sku]
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erro inesperado ao restaurar produto', [
                'sku'   => $sku,
                'error' => $e->getMessage(),
            ]);
            return $this->redirectError('provider.products.index', 'Erro interno ao restaurar produto.');
        }
    }

    /**
     * Métodos de conveniência que delegam ao index com filtros pré-definidos.
     */
    public function search(Request $request): View
    {
        return $this->index($request);
    }

    public function active(Request $request): View
    {
        $request->merge(['active' => '1']);
        return $this->index($request);
    }

    public function deleted(Request $request): View
    {
        $request->merge(['deleted' => 'only']);
        return $this->index($request);
    }

    public function restoreMultiple(Request $request): RedirectResponse
    {
        $this->authorize('update', \App\Models\Product::class);
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return $this->redirectError('provider.products.index', 'Nenhum produto selecionado para restauração.');
        }

        $result = $this->productService->restoreProducts($ids);

        if ($result->isError()) {
            return $this->redirectError('provider.products.index', $result->getMessage());
        }

        return $this->redirectSuccess('provider.products.index', "Restaurados produtos com sucesso.");
    }

    /**
     * AJAX endpoint para buscar produtos com filtros.
     */
    public function ajaxSearch(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Product::class);
        $filters = $request->only(['search', 'active', 'category_id', 'min_price', 'max_price']);
        $result  = $this->productService->getFilteredProducts($filters, ['category']);

        return $this->jsonResponse($result);
    }

    /**
     * Exporta os produtos para Excel ou PDF.
     *
     * Rota: products.export (GET)
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', \App\Models\Product::class);
        $format = $request->get('format', 'xlsx');

        // Captura TODOS os filtros aplicados na listagem (exceto paginação)
        $filters = $request->only(['search', 'active', 'deleted', 'category_id', 'min_price', 'max_price']);

        // Busca produtos com os filtros aplicados
        $result = $this->productService->getFilteredProducts($filters, ['category'], 1000);

        if ($result->isError()) {
            return $this->redirectError('provider.products.index', $result->getMessage());
        }

        // Extrai a collection do paginator preservando todos os atributos
        $paginatorOrCollection = $result->getData();

        if (method_exists($paginatorOrCollection, 'items')) {
            // É um LengthAwarePaginator - pega os items diretamente
            $products = collect($paginatorOrCollection->items());
        } elseif (method_exists($paginatorOrCollection, 'getCollection')) {
            // É um Paginator - usa getCollection
            $products = $paginatorOrCollection->getCollection();
        } else {
            // Já é uma Collection
            $products = $paginatorOrCollection;
        }

        return $format === 'pdf'
            ? $this->productExportService->exportToPdf($products)
            : $this->productExportService->exportToExcel($products, $format);
    }
}
