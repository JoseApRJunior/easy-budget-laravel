<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(): View
    {
        return view( 'admin.logs.index' );
    }

    public function show( Request $request, string $id ): View
    {
        // Logic to show specific log
        return view( 'admin.logs.show', compact( 'id' ) );
    }

    public function download( Request $request, string $id ): JsonResponse
    {
        // Logic to download log file
        return response()->json( [ 'status' => 'success' ] );
    }

}
