<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BackupController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(): View
    {
        return view( 'pages.backups.index' );
    }

    public function create(): View
    {
        return view( 'pages.backups.create' );
    }

    public function store( Request $request ): RedirectResponse
    {
        // Logic to create backup
        return redirect()->route( 'backups.index' )->with( 'success', 'Backup criado com sucesso!' );
    }

    public function download( Request $request, string $id ): JsonResponse
    {
        // Logic to download backup
        return response()->json( [ 'status' => 'success' ] );
    }

    public function restore( Request $request, string $id ): RedirectResponse
    {
        // Logic to restore backup
        return redirect()->route( 'backups.index' )->with( 'success', 'Backup restaurado com sucesso!' );
    }

}
