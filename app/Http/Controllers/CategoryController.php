<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\Category\CategoryDTO;
use App\DTOs\Category\CategoryFilterDTO;
use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\CategoryStoreRequest;
use App\Http\Requests\CategoryUpdateRequest;
use App\Models\Category;
use App\Services\Domain\CategoryExportService;
use App\Services\Domain\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
        $this->authorize('viewAny', Category::class);

        // Se nenhum parâmetro foi passado na URL, definimos filtros padrão
        if (empty($request->query())) {
            $filters = ['active' => '1', 'deleted' => 'current'];
            $result = $this->emptyResult();
        } else {
            $filterDto = CategoryFilterDTO::fromRequest($request->all());
            $result = $this->categoryService->getFilteredCategories($filterDto);
            $filters = $filterDto->toFilterArray();
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
        $this->authorize('create', Category::class);
        $result = $this->categoryService->getParentCategories();

        return $this->view('pages.category.create', $result, 'parents', [
            'defaults' => ['is_active' => true],
        ]);
    }

    /**
     * Persiste nova categoria.
     */
    public function store(CategoryStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Category::class);
        $dto = CategoryDTO::fromRequest($request->validated());
        $result = $this->categoryService->createCategory($dto);

        return $this->redirectBackWithServiceResult($result, 'Categoria criada com sucesso!');
    }

    /**
     * Mostra detalhes da categoria por slug.
     */
    public function show(string $slug): View|RedirectResponse
    {
        $result = $this->categoryService->findBySlug(
            $slug,
            ['parent' => fn ($q) => $q->withTrashed()],
            true, // withTrashed (para a própria categoria)
            ['children', 'services', 'products'] // loadCounts
        );

        if ($result->isError()) {
            return $this->redirectError('provider.categories.index', $result->getMessage());
        }

        $this->authorize('view', $result->getData());

        return $this->view('pages.category.show', $result, 'category');
    }

    /**
     * Form para editar categoria.
     */
    public function edit(string $slug): View|RedirectResponse
    {
        $result = $this->categoryService->findBySlug(
            $slug,
            ['parent' => fn ($q) => $q->withTrashed()],
            false, // withTrashed (não editamos deletados via edit normal)
            ['children', 'services', 'products']
        );

        if ($result->isError()) {
            return $this->redirectError('provider.categories.index', $result->getMessage());
        }

        $category = $result->getData();
        $this->authorize('update', $category);

        $parentResult = $this->categoryService->getParentCategories();

        $parents = $parentResult->isSuccess()
            ? $parentResult->getData()->filter(fn ($p) => $p->id !== $category->id)
            : collect();

        // Se a categoria tem um pai e ele está inativo, garantimos que ele esteja na lista
        if ($category->parent_id && ! $parents->contains('id', $category->parent_id)) {
            if ($category->parent) {
                $parents->push($category->parent);
            }
        }

        return $this->view('pages.category.edit', $result, 'category', [
            'parents' => $parents->sortBy('name'),
        ]);
    }

    /**
     * Atualiza categoria.
     */
    public function update(CategoryUpdateRequest $request, string $slug): RedirectResponse
    {
        $result = $this->categoryService->findBySlug($slug);
        if ($result->isError()) {
            return $this->redirectError('provider.categories.index', 'Categoria não encontrada');
        }

        $category = $result->getData();
        $this->authorize('update', $category);

        $dto = CategoryDTO::fromRequest($request->validated());
        $updateResult = $this->categoryService->updateCategory($category->id, $dto);

        // Se a atualização for bem-sucedida, usamos o novo slug para o redirecionamento
        $redirectSlug = $updateResult->isSuccess() ? $updateResult->getData()->slug : $slug;

        return $this->redirectWithServiceResult(
            'provider.categories.show',
            $updateResult,
            'Categoria atualizada com sucesso.',
            ['slug' => $redirectSlug]
        );
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

        return $this->redirectSuccess('provider.categories.index', 'Restauradas categorias com sucesso.');
    }

    public function export(Request $request)
    {
        $this->authorize('viewAny', \App\Models\Category::class);
        $format = $request->get('format', 'xlsx');

        // Usa o DTO para capturar e validar os filtros, definindo um limite alto para exportação
        $filterDto = CategoryFilterDTO::fromRequest(array_merge($request->all(), ['per_page' => 1000]));
        $result = $this->categoryService->getFilteredCategories($filterDto);

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
