<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\Category\CategoryDTO;
use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Services\Domain\CategoryExportService;
use App\Services\Domain\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controller para gerenciamento de categorias com arquitetura refinada.
 *
 * Categorias são isoladas por tenant - cada empresa gerencia suas próprias categorias.
 * Implementa a arquitetura padronizada com tratamento consistente de ServiceResult.
 */
class CategoryController extends Controller
{
    public function __construct(
        private CategoryService $categoryService,
        private CategoryExportService $exportService,
    ) {}

    /**
     * Dashboard de categorias com estatísticas.
     */
    public function dashboard(): View
    {
        $this->authorize('viewAny', \App\Models\Category::class);
        return $this->view('pages.category.dashboard', $this->categoryService->getDashboardData(), 'stats');
    }

    /**
     * Lista categorias com filtros e paginação.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', \App\Models\Category::class);
        $filters = $request->only(['search', 'active', 'per_page', 'deleted', 'all']);

        // Se nenhum parâmetro foi passado na URL, iniciamos com a lista vazia
        // O helper $this->view lida com o ServiceResult automaticamente
        if (empty($request->query())) {
            $result = $this->emptyResult();
        } else {
            $perPage = (int) ($filters['per_page'] ?? 10);
            $result  = $this->categoryService->getFilteredCategories($filters, $perPage);
        }

        return $this->view('pages.category.index', $result, 'categories', [
            'filters' => $filters,
        ]);
    }

    /**
     * Form para criar categoria.
     */
    public function create(): View|RedirectResponse
    {
        $this->authorize('create', \App\Models\Category::class);
        $result = $this->categoryService->getParentCategories();

        return $this->view('pages.category.create', $result, 'parents', [
            'defaults' => ['is_active' => true],
        ]);
    }

    /**
     * Persiste nova categoria.
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $this->authorize('create', \App\Models\Category::class);
        $dto = CategoryDTO::fromRequest($request->validated());
        $result = $this->categoryService->createCategory($dto);

        return $this->redirectBackWithServiceResult($result, 'Categoria criada com sucesso!');
    }

    /**
     * Mostra detalhes da categoria por slug.
     */
    public function show(string $slug): View|RedirectResponse
    {
        $result = $this->categoryService->findBySlug($slug, [
            'parent',
            'children' => fn($q) => $q->where('is_active', true),
        ]);

        if ($result->isError()) {
            return $this->redirectError('provider.categories.index', $result->getMessage());
        }

        $category = $result->getData();
        $this->authorize('view', $category);

        $category->loadCount(['children', 'services', 'products']);

        return $this->view('pages.category.show', $result, 'category');
    }

    /**
     * Form para editar categoria.
     */
    public function edit(string $slug): View|RedirectResponse
    {
        $result = $this->categoryService->findBySlug($slug);
        if ($result->isError()) {
            return $this->redirectError('provider.categories.index', $result->getMessage());
        }

        $category = $result->getData();
        $this->authorize('update', $category);

        $category->loadCount(['children', 'services', 'products']);
        $parentResult = $this->categoryService->getParentCategories();

        $parents = $parentResult->isSuccess()
            ? $parentResult->getData()->filter(fn($p) => $p->id !== $category->id)
            : collect();

        return view('pages.category.edit', compact('category', 'parents'));
    }

    /**
     * Atualiza categoria.
     */
    public function update(UpdateCategoryRequest $request, string $slug): RedirectResponse
    {
        $result = $this->categoryService->findBySlug($slug);
        if ($result->isError()) {
            return $this->redirectError('provider.categories.index', 'Categoria não encontrada');
        }

        $category = $result->getData();
        $this->authorize('update', $category);

        $dto = CategoryDTO::fromRequest($request->validated());
        $updateResult = $this->categoryService->updateCategory($category->id, $dto);

        return $this->redirectWithServiceResult('provider.categories.show', $updateResult, 'Categoria atualizada com sucesso.', ['slug' => $slug]);
    }

    /**
     * Exclui categoria.
     */
    public function destroy(string $slug): RedirectResponse
    {
        $result = $this->categoryService->findBySlug($slug);
        if ($result->isError()) {
            return $this->redirectError('provider.categories.index', 'Categoria não encontrada');
        }

        $category = $result->getData();
        $this->authorize('delete', $category);

        $deleteResult = $this->categoryService->deleteCategory($category->id);

        if ($deleteResult->isError()) {
            return $this->redirectError('provider.categories.index', $deleteResult->getMessage() ?: 'Erro ao excluir categoria.');
        }

        return $this->redirectSuccess('provider.categories.index', 'Categoria excluída com sucesso.');
    }

    /**
     * Alterna status ativo/inativo da categoria.
     */
    public function toggleStatus(string $slug): RedirectResponse
    {
        $result = $this->categoryService->findBySlug($slug);
        if ($result->isError()) {
            return $this->redirectError('provider.categories.index', 'Categoria não encontrada');
        }

        $category = $result->getData();
        $this->authorize('update', $category);

        $toggleResult = $this->categoryService->toggleCategoryStatus($slug);

        if ($toggleResult->isError()) {
            return $this->redirectError('provider.categories.index', $toggleResult->getMessage());
        }

        return $this->redirectSuccess('provider.categories.show', $toggleResult->getMessage(), ['slug' => $slug]);
    }

    /**
     * Restaura categoria deletada (soft delete).
     */
    public function restore(string $slug): RedirectResponse
    {
        $result = $this->categoryService->findBySlug($slug, [], true);
        if ($result->isError()) {
            return $this->redirectError('provider.categories.index', 'Categoria não encontrada para restauração.');
        }

        $category = $result->getData();
        $this->authorize('update', $category);

        $restoreResult = $this->categoryService->restoreCategoriesBySlug($slug);

        if ($restoreResult->isError()) {
            return $this->redirectError('provider.categories.index', $restoreResult->getMessage());
        }

        return $this->redirectSuccess('provider.categories.show', 'Categoria restaurada com sucesso!', ['slug' => $slug]);
    }

    /**
     * Busca categorias por nome/descrição com pesquisa parcial.
     */
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
        $this->authorize('update', \App\Models\Category::class);
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return $this->redirectError('provider.categories.index', 'Nenhuma categoria selecionada para restauração.');
        }

        $result = $this->categoryService->restoreCategories($ids);

        if ($result->isError()) {
            return $this->redirectError('provider.categories.index', $result->getMessage());
        }

        return $this->redirectSuccess('provider.categories.index', "Restauradas categorias com sucesso.");
    }

    public function export(Request $request): StreamedResponse|RedirectResponse
    {
        $this->authorize('viewAny', \App\Models\Category::class);
        $format = $request->get('format', 'xlsx');

        // Captura TODOS os filtros aplicados na listagem (exceto paginação)
        $filters = $request->only(['search', 'active', 'deleted']);

        // DEBUG: Ver o que está vindo
        // Busca categorias com os filtros aplicados
        $result = $this->categoryService->getFilteredCategories($filters, 1000);

        if ($result->isError()) {
            return $this->redirectError('provider.categories.index', $result->getMessage());
        }

        // Extrai a collection do paginator preservando todos os atributos
        $paginatorOrCollection = $result->getData();

        if (method_exists($paginatorOrCollection, 'items')) {
            // É um LengthAwarePaginator - pega os items diretamente
            $categories = collect($paginatorOrCollection->items());
        } elseif (method_exists($paginatorOrCollection, 'getCollection')) {
            // É um Paginator - usa getCollection
            $categories = $paginatorOrCollection->getCollection();
        } else {
            // Já é uma Collection
            $categories = $paginatorOrCollection;
        }

        return $format === 'pdf'
            ? $this->exportService->exportToPdf($categories)
            : $this->exportService->exportToExcel($categories, $format);
    }
}
