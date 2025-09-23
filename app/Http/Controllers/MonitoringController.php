<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonitoringController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(): View
    {
        return view( 'pages.monitoring.index' );
    }

    public function system(): JsonResponse
    {
        return response()->json( [ 
            'status' => 'success',
            'data'   => [ 
                'cpu'    => 25,
                'memory' => 60,
                'disk'   => 45
            ]
        ] );
    }

    public function logs( Request $request ): JsonResponse
    {
        return response()->json( [ 
            'status' => 'success',
            'data'   => []
        ] );
    }

    public function health(): JsonResponse
    {
        return response()->json( [ 
            'status'    => 'healthy',
            'timestamp' => now()->toISOString()
        ] );
    }

}
