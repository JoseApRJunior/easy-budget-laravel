<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantController extends BaseController
{
    public function __construct(ActivityService $activityService) { parent::__construct($activityService);
    }

    public function index(): View
    {
        return view( 'admin.tenants.index' );
    }

    public function create(): View
    {
        return view( 'admin.tenants.create' );
    }

    public function store( Request $request ): RedirectResponse
    {
        // Logic to create tenant
        return redirect()->route( 'admin.tenants.index' )->with( 'success', 'Tenant criado com sucesso!' );
    }

    public function show( string $id ): View
    {
        // Logic to show tenant
        return view( 'admin.tenants.show', compact( 'id' ) );
    }

    public function edit( string $id ): View
    {
        // Logic to edit tenant
        return view( 'admin.tenants.edit', compact( 'id' ) );
    }

    public function update( Request $request, string $id ): RedirectResponse
    {
        // Logic to update tenant
        return redirect()->route( 'admin.tenants.index' )->with( 'success', 'Tenant atualizado com sucesso!' );
    }

    public function destroy( string $id ): RedirectResponse
    {
        // Logic to delete tenant
        return redirect()->route( 'admin.tenants.index' )->with( 'success', 'Tenant deletado com sucesso!' );
    }

}


