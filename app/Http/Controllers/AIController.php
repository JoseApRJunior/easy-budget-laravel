<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AIController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(): View
    {
        return view( 'pages.ai.index' );
    }

    public function chat( Request $request ): JsonResponse
    {
        $message = $request->input( 'message', '' );

        return response()->json( [ 
            'status'   => 'success',
            'response' => 'AI response to: ' . $message
        ] );
    }

    public function analyze( Request $request ): JsonResponse
    {
        $data = $request->input( 'data', [] );

        return response()->json( [ 
            'status'   => 'success',
            'analysis' => 'AI analysis completed'
        ] );
    }

    public function generate( Request $request ): JsonResponse
    {
        $prompt = $request->input( 'prompt', '' );

        return response()->json( [ 
            'status'    => 'success',
            'generated' => 'Generated content based on: ' . $prompt
        ] );
    }

}
