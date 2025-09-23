<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(): View
    {
        return view( 'admin.roles.index' );
    }

    public function create(): View
    {
        return view( 'admin.roles.create' );
    }

    public function store( Request $request ): RedirectResponse
    {
        // Logic to create role
        return redirect()->route( 'admin.roles.index' )->with( 'success', 'Papel criado com sucesso!' );
    }

    public function show( string $id ): View
    {
        // Logic to show role
        return view( 'admin.roles.show', compact( 'id' ) );
    }

    public function edit( string $id ): View
    {
        // Logic to edit role
        return view( 'admin.roles.edit', compact( 'id' ) );
    }

    public function update( Request $request, string $id ): RedirectResponse
    {
        // Logic to update role
        return redirect()->route( 'admin.roles.index' )->with( 'success', 'Papel atualizado com sucesso!' );
    }

    public function destroy( string $id ): RedirectResponse
    {
        // Logic to delete role
        return redirect()->route( 'admin.roles.index' )->with( 'success', 'Papel deletado com sucesso!' );
    }

}
