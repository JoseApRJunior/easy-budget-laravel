<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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
        return $this->view( 'pages.category.dashboard', $this->categoryService->getDashboardData(), 'stats' );
    }

    /**
     * Lista categorias com filtros e paginação.
     */
    public function index( Request $request ): View
    {
        $filters = $request->only( [ 'search', 'active', 'per_page', 'deleted', 'all' ] );

        // Se nenhum parâmetro foi passado na URL, iniciamos com a lista vazia
        // O helper $this->view lida com o ServiceResult automaticamente
        if ( empty( $request->query() ) ) {
            $result = $this->emptyResult();
        } else {
            $perPage = (int) ( $filters[ 'per_page' ] ?? 10 );
            $result  = $this->categoryService->getCategories( $filters, $perPage );
        }

        return $this->view( 'pages.category.index', $result, 'categories', [
            'filters' => $filters,
            'error'   => $this->getServiceErrorMessage( $result, null ),
        ] );
    }

    /**
     * Form para criar categoria.
     */
    public function create(): View|RedirectResponse
    {
        $result = $this->categoryService->getParentCategories();

        return $this->view( 'pages.category.create', $result, 'parents', [
            'defaults' => [ 'is_active' => true ],
        ] );
    }

    /**
     * Persiste nova categoria.
     */
    public function store( StoreCategoryRequest $request ): RedirectResponse
    {
        $data = $request->validated();
        if ( isset( $data[ 'name' ] ) ) {
            $data[ 'name' ] = mb_convert_case( $data[ 'name' ], MB_CASE_TITLE, 'UTF-8' );
        }

        $result = $this->categoryService->createCategory( $data );

        if ( $result->isError() && str_contains( $result->getMessage(), 'Slug já existe' ) ) {
            return back()->withErrors( [ 'slug' => 'Este slug já está em uso nesta empresa.' ] )->withInput();
        }

        return $this->redirectBackWithServiceResult( $result, 'Categoria criada com sucesso!' );
    }

    /**
     * Mostra detalhes da categoria por slug.
     */
    public function show( string $slug ): View|RedirectResponse
    {
        $result = $this->categoryService->findBySlug( $slug );
        if ( $result->isError() ) {
            return $this->redirectError( 'categories.index', $result->getMessage() );
        }

        $result->getData()->load( [
            'parent',
            'children' => fn( $q ) => $q->where( 'is_active', true ),
        ] )->loadCount( [ 'children', 'services', 'products' ] );

        return $this->view( 'pages.category.show', $result, 'category' );
    }

    /**
     * Form para editar categoria.
     */
    public function edit( string $slug ): View|RedirectResponse
    {
        $result = $this->categoryService->findBySlug( $slug );
        if ( $result->isError() ) {
            return $this->redirectError( 'categories.index', $result->getMessage() );
        }

        $category = $result->getData()->loadCount( [ 'children', 'services', 'products' ] );
        $parentResult = $this->categoryService->getParentCategories();

        $parents = $parentResult->isSuccess()
            ? $parentResult->getData()->filter( fn( $p ) => $p->id !== $category->id )
            : collect();

        return view( 'pages.category.edit', compact( 'category', 'parents' ) );
    }

    /**
     * Atualiza categoria.
     */
    public function update( UpdateCategoryRequest $request, string $slug ): RedirectResponse
    {
        $result = $this->categoryService->findBySlug( $slug );
        if ( $result->isError() ) {
            return $this->redirectError( 'categories.index', 'Categoria não encontrada' );
        }

        $category = $result->getData();
        $data     = $request->validated();
        if ( isset( $data[ 'name' ] ) ) {
            $data[ 'name' ] = mb_convert_case( $data[ 'name' ], MB_CASE_TITLE, 'UTF-8' );
        }

        $updateResult = $this->categoryService->updateCategory( $category->id, $data );

        if ( $updateResult->isError() && str_contains( $updateResult->getMessage(), 'Slug já existe' ) ) {
            return back()->withErrors( [ 'slug' => 'Este slug já está em uso nesta empresa.' ] )->withInput();
        }

        return $this->redirectWithServiceResult( 'categories.index', $updateResult, 'Categoria atualizada com sucesso.' );
    }

    /**
     * Exclui categoria.
     */
    public function destroy( string $slug ): RedirectResponse
    {
        $result = $this->categoryService->findBySlug( $slug );
        if ( $result->isError() ) {
            return $this->redirectError( 'categories.index', 'Categoria não encontrada' );
        }

        $deleteResult = $this->categoryService->deleteCategory( $result->getData()->id );

        return $this->redirectWithServiceResult( 'categories.index', $deleteResult, 'Categoria excluída com sucesso.' );
    }

    /**
     * Alterna status ativo/inativo da categoria.
     */
    public function toggle_status( string $slug ): RedirectResponse
    {
        $result = $this->categoryService->findBySlug( $slug );
        if ( $result->isError() ) {
            return $this->redirectError( 'categories.index', 'Categoria não encontrada' );
        }

        $category     = $result->getData();
        $updateResult = $this->categoryService->updateCategory( $category->id, [
            'is_active' => !$category->is_active
        ] );

        if ( $updateResult->isError() ) {
            return $this->redirectError( 'categories.index', $updateResult->getMessage() );
        }

        $statusText = $updateResult->getData()->is_active ? 'ativada' : 'desativada';
        return $this->redirectSuccess( 'categories.index', "Categoria {$statusText} com sucesso." );
    }

    /**
     * Restaura categoria deletada (soft delete).
     */
    public function restore( string $slug ): RedirectResponse
    {
        $result = $this->categoryService->restoreCategoriesBySlug( $slug );

        if ( $result->isError() ) {
            return $this->redirectError( 'categories.index', $result->getMessage() );
        }

        return $this->redirectSuccess( 'categories.index', 'Categoria restaurada com sucesso!' );
    }

    /**
     * Busca categorias por nome/descrição com pesquisa parcial.
     */
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
            return $this->redirectError( 'categories.index', 'Nenhuma categoria selecionada para restauração.' );
        }

        $result = $this->categoryService->restoreCategories( $ids );

        if ( $result->isError() ) {
            return $this->redirectError( 'categories.index', $result->getMessage() );
        }

        return $this->redirectSuccess( 'categories.index', "Restauradas categorias com sucesso." );
    }

    public function export( Request $request ): StreamedResponse|RedirectResponse
    {
        $format  = $request->get( 'format', 'xlsx' );
        $filters = $request->only( [ 'search', 'active' ] );
        $result  = $this->categoryService->getCategories( $filters + [ 'all' => true ], 1000 );

        if ( $result->isError() ) {
            return $this->redirectError( 'categories.index', $result->getMessage() );
        }

        $categories = $result->getData();
        if ( method_exists( $categories, 'getCollection' ) ) {
            $categories = $categories->getCollection();
        }

        return $format === 'pdf'
            ? $this->exportService->exportToPdf( $categories )
            : $this->exportService->exportToExcel( $categories, $format );
    }

}
