<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PlanService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private readonly PlanService $planService,
    ) {}

    public function index( Request $request )
    {
        // Carregar planos para todos os usuários (autenticados e não autenticados)
        $result = $this->planService->list();
        if ( !$result->isSuccess() ) {
            $plans = [];
            return view( 'pages.home.index', compact( 'plans' ) );
        }

        $plans = $result->getData();

        return view( 'pages.home.index', compact( 'plans' ) );
    }

}