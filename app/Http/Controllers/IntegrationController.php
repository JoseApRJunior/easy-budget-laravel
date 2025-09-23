<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IntegrationController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(): View
    {
        return view( 'pages.integrations.index' );
    }

    public function webhook( Request $request ): JsonResponse
    {
        return response()->json( [ 'status' => 'success' ] );
    }

    public function api( Request $request ): JsonResponse
    {
        return response()->json( [ 'status' => 'success' ] );
    }

}
