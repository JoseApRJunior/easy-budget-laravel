<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\CategoryService;
use App\Services\ActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends BaseController
{
    public function __construct(
        private readonly CategoryService $categoryService,
    ) {
        parent::__construct($activityService);
    }

    public function index(): View
    {
        return view( 'pages.categories.index' );
    }

    public function create(): View
    {
        return view( 'pages.categories.create' );
    }

    public function store( Request $request ): RedirectResponse
    {
        $result = $this->categoryService->create( $request->all() );

        if ( $result ) {
            return redirect()->route( 'categories.index' )->with( 'success', 'Categoria criada com sucesso!' );
        }

        return back()->with( 'error', 'Erro ao criar categoria.' );
    }

    public function show( string $id ): View
    {
        $category = $this->categoryService->findById( $id );
        return view( 'pages.categories.show', compact( 'category' ) );
    }

    public function edit( string $id ): View
    {
        $category = $this->categoryService->findById( $id );
        return view( 'pages.categories.edit', compact( 'category' ) );
    }

    public function update( Request $request, string $id ): RedirectResponse
    {
        $result = $this->categoryService->update( $id, $request->all() );

        if ( $result ) {
            return redirect()->route( 'categories.index' )->with( 'success', 'Categoria atualizada com sucesso!' );
        }

        return back()->with( 'error', 'Erro ao atualizar categoria.' );
    }

    public function destroy( string $id ): RedirectResponse
    {
        $result = $this->categoryService->delete( $id );

        if ( $result ) {
            return redirect()->route( 'categories.index' )->with( 'success', 'Categoria deletada com sucesso!' );
        }

        return back()->with( 'error', 'Erro ao deletar categoria.' );
    }

    public function api( Request $request ): JsonResponse
    {
        $categories = $this->categoryService->getAllActive();
        return response()->json( $categories );
    }

}

