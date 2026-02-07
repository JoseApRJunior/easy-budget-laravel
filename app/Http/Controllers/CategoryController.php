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
    public function dashboard(): View|RedirectResponse
    {
        $this->authorize('viewAny', Category::class);

        $result = $this->categoryService->getDashboardData();

        if ($result->isError()) {
            return $this->redirectError('dashboard', 'Erro ao carregar dashboard de categorias: '.$result->getMessage());
        }

        return $this->view('pages.category.dashboard', $result, 'stats');
    }

    /**
     * Lista categorias com filtros e paginação.
     */
    public function index(Request $request): View|RedirectResponse
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

        if ($result->isError()) {
            return $this->redirectError('dashboard', 'Não foi possível carregar as categorias: '.$result->getMessage());
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

        if ($result->isError()) {
            return $this->redirectError('provider.categories.index', 'Erro ao carregar categorias pai.');
        }

        return $this->view('pages.category.create', $result, 'parents', [
            'defaults' => ['is_active' => true],
        ]);
    }

    /**
     * Salva nova categoria.
     */
    public function store(CategoryStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Category::class);

        $dto = CategoryDTO::fromRequest($request->validated());
        $result = $this->categoryService->createCategory($dto);

        return $this->redirectWithServiceResult(
            'provider.categories.index',
            $result,
            'Categoria criada com sucesso!'
        );
    }

    /**
     * Exibe detalhes da categoria.
     */
    public function show(string $slug): View|RedirectResponse
    {
        $result = $this->categoryService->findBySlug($slug);

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
        $result = $this->categoryService->findBySlug($slug);

        if ($result->isError()) {
            return $this->redirectError('provider.categories.index', $result->getMessage());
        }

        $category = $result->getData();
        $this->authorize('update', $category);

        $parentsResult = $this->categoryService->getParentCategories($category->id);

        return $this->view('pages.category.edit', $result, 'category', [
            'parents' => $parentsResult->getData(),
        ]);
    }

    /**
     * Atualiza categoria.
     */
    public function update(CategoryUpdateRequest $request, string $slug): RedirectResponse
    {
        $categoryResult = $this->categoryService->findBySlug($slug);
        if ($categoryResult->isError()) {
            return $this->redirectError('provider.categories.index', 'Categoria não encontrada');
        }

        $category = $categoryResult->getData();
        $this->authorize('update', $category);

        $dto = CategoryDTO::fromRequest($request->validated());
        $result = $this->categoryService->updateCategory($category->id, $dto);

        return $this->redirectWithServiceResult(
            'provider.categories.index',
            $result,
            'Categoria atualizada com sucesso!'
        );
    }

    /**
     * Remove categoria (Soft Delete).
     */
    public function destroy(string $slug): RedirectResponse
    {
        $categoryResult = $this->categoryService->findBySlug($slug);
        if ($categoryResult->isError()) {
            return $this->redirectError('provider.categories.index', 'Categoria não encontrada');
        }

        $category = $categoryResult->getData();
        $this->authorize('delete', $category);

        $result = $this->categoryService->deleteCategory($category->id);

        return $this->redirectWithServiceResult(
            'provider.categories.index',
            $result,
            'Categoria removida com sucesso!'
        );
    }

    /**
     * Restaura categoria removida.
     */
    public function restore(int $id): RedirectResponse
    {
        $result = $this->categoryService->restoreCategory($id);

        return $this->redirectWithServiceResult(
            'provider.categories.index',
            $result,
            'Categoria restaurada com sucesso!'
        );
    }

    /**
     * Ativa/Desativa categoria.
     */
    public function toggleStatus(int $id): RedirectResponse
    {
        $categoryResult = $this->categoryService->getCategoryById($id);
        if ($categoryResult->isError()) {
            return $this->redirectError('provider.categories.index', 'Categoria não encontrada');
        }

        $category = $categoryResult->getData();
        $this->authorize('update', $category);

        $result = $this->categoryService->updateStatus($id, ! $category->is_active);

        return $this->redirectWithServiceResult(
            'provider.categories.index',
            $result,
            'Status da categoria atualizado!'
        );
    }

    /**
     * Exporta categorias para PDF.
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Category::class);

        $filterDto = CategoryFilterDTO::fromRequest($request->all());
        $result = $this->categoryService->getFilteredCategories($filterDto, false);

        if ($result->isError()) {
            return $this->redirectError('provider.categories.index', 'Erro ao buscar categorias para exportação.');
        }

        return $this->exportService->exportToPdf($result->getData());
    }
}
