<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlertController extends BaseController
{
    public function __construct(ActivityService $activityService)
    {
        parent::__construct($activityService);
    }

    public function index(): View
    {
        return view( 'pages.alerts.index' );
    }

    public function create(): View
    {
        return view( 'pages.alerts.create' );
    }

    public function store( Request $request ): RedirectResponse
    {
        // Logic to create alert
        return redirect()->route( 'alerts.index' )->with( 'success', 'Alerta criado com sucesso!' );
    }

    public function show( string $id ): View
    {
        // Logic to show alert
        return view( 'pages.alerts.show', compact( 'id' ) );
    }

    public function markAsRead( Request $request, string $id ): JsonResponse
    {
        // Logic to mark alert as read
        return response()->json( [ 'status' => 'success' ] );
    }

    public function dismiss( Request $request, string $id ): JsonResponse
    {
        // Logic to dismiss alert
        return response()->json( [ 'status' => 'success' ] );
    }

}

