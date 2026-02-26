<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\Product\ProductDTO;
use App\DTOs\Product\ProductFilterDTO;
use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Models\Product;
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
    public function dashboard(): View|RedirectResponse
    {
        $this->authorize('viewAny', Product::class);

        $result = $this->productService->getDashboardData();

        if ($result->isError()) {
            return $this->redirectError('dashboard', 'Erro ao carregar dashboard de produtos: '.$result->getMessage());
        }

        return $this->view('pages.product.dashboard', $result, 'stats');
    }

    /**
     * Lista de produtos com filtros avançados.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', Product::class);

        if (empty($request->query())) {
            $filters = ['active' => '1', 'deleted' => 'current'];
            $result = $this->emptyResult();
        } else {
            $filterDto = ProductFilterDTO::fromRequest($request->all());
            $result = $this->productService->getFilteredProducts($filterDto, ['category']);
            $filters = $filterDto->toDisplayArray();
        }

        if ($result->isError()) {
            return $this->redirectError('provider.products.dashboard', 'Não foi possível carregar a lista de produtos: '.$result->getMessage());
        }

        return $this->view('pages.product.index', $result, 'products', [
            'filters' => $filters,
            'categories' => $this->categoryService->getActive(orderBy: ['name' => 'asc'])->getData(),
        ]);
    }

    /**
     * Formulário de criação de produto.
     *
     * Rota: products.create
     */
    public function create(): View|RedirectResponse
    {
        $this->authorize('create', Product::class);

        $result = $this->categoryService->getActive(orderBy: ['name' => 'asc']);
        $nextSkuResult = $this->productService->generateNextSku();

        if ($result->isError() || $nextSkuResult->isError()) {
            return $this->redirectError('provider.products.index', 'Erro ao preparar formulário de criação.');
        }

        return $this->view('pages.product.create', $result, 'categories', [
            'defaults' => [
                'is_active' => true,
                'sku' => $nextSkuResult->getData(),
            ],
        ]);
    }

    /**
     * Armazena um novo produto.
     */
    public function store(ProductStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $dto = ProductDTO::fromRequest($request->validated());
        $result = $this->productService->createProduct($dto);

        return $this->redirectWithServiceResult(
            'provider.products.create',
            $result,
            'Produto criado com sucesso! Você pode cadastrar outro produto agora.'
        );
    }

    /**
     * Exibe os detalhes de um produto.
     */
    public function show(string $sku): View|RedirectResponse
    {
        $result = $this->productService->findBySku(
            $sku,
            ['category'],
            true // withTrashed
        );

        if ($result->isError()) {
            return $this->redirectError('provider.products.index', $result->getMessage());
        }

        $this->authorize('view', $result->getData());

        return $this->view('pages.product.show', $result, 'product');
    }

    /**
     * Formulário de edição de produto.
     */
    public function edit(string $sku): View|RedirectResponse
    {
        $result = $this->productService->findBySku($sku, ['category']);

        if ($result->isError()) {
            return $this->redirectError('provider.products.index', $result->getMessage());
        }

        $product = $result->getData();
        $this->authorize('update', $product);

        return $this->view('pages.product.edit', $result, 'product', [
            'categories' => $this->categoryService->getActive(orderBy: ['name' => 'asc'])->getData(),
        ]);
    }

    /**
     * Atualiza um produto.
     */
    public function update(ProductUpdateRequest $request, string $sku): RedirectResponse
    {
        $result = $this->productService->findBySku($sku);
        if ($result->isError()) {
            return $this->redirectError('provider.products.index', 'Produto não encontrado');
        }

        $product = $result->getData();
        $this->authorize('update', $product);

        $dto = ProductDTO::fromRequest($request->validated());
        $updateResult = $this->productService->updateProductBySku(
            $sku,
            $dto,
            (bool) $request->boolean('remove_image')
        );

        // Se o SKU mudou, usamos o novo SKU para o redirecionamento
        $redirectSku = $updateResult->isSuccess() ? $updateResult->getData()->sku : $sku;

        return $this->redirectWithServiceResult(
            'provider.products.show',
            $updateResult,
            'Produto atualizado com sucesso.',
            ['sku' => $redirectSku]
        );
    }

    /**
     * Alterna status (ativo/inativo) de um produto via SKU.
     */
    public function toggleStatus(string $sku, Request $request): RedirectResponse|JsonResponse
    {
        $result = $this->productService->findBySku($sku);
        if ($result->isError()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $result->getMessage()], 404);
            }

            return $this->redirectError('provider.products.index', $result->getMessage());
        }

        $product = $result->getData();
        $this->authorize('update', $product);

        $updateResult = $this->productService->updateStatus($product, ! $product->is_active);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => $updateResult->isSuccess(),
                'message' => $updateResult->getMessage(),
                'new_status' => $updateResult->isSuccess() ? $updateResult->getData()->is_active : null,
            ]);
        }

        return $this->redirectWithServiceResult(
            'provider.products.index',
            $updateResult,
            'Status do produto atualizado.'
        );
    }

    /**
     * Remove um produto (Soft Delete).
     */
    public function destroy(string $sku): RedirectResponse
    {
        $result = $this->productService->findBySku($sku);
        if ($result->isError()) {
            return $this->redirectError('provider.products.index', $result->getMessage());
        }

        $this->authorize('delete', $result->getData());

        $deleteResult = $this->productService->deleteProductBySku($sku);

        return $this->redirectWithServiceResult(
            'provider.products.index',
            $deleteResult,
            'Produto removido com sucesso.'
        );
    }

    /**
     * Restaura um produto removido.
     */
    public function restore(string $sku): RedirectResponse
    {
        $result = $this->productService->restoreProductBySku($sku);

        return $this->redirectWithServiceResult(
            'provider.products.index',
            $result,
            'Produto restaurado com sucesso.'
        );
    }

    /**
     * Exporta produtos para PDF.
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Product::class);

        $filterDto = ProductFilterDTO::fromRequest($request->all());
        $productsResult = $this->productService->getFilteredProducts($filterDto, ['category'], false);

        if ($productsResult->isError()) {
            return $this->redirectError('provider.products.index', 'Erro ao buscar produtos para exportação.');
        }

        return $this->productExportService->exportToPdf($productsResult->getData());
    }
}
