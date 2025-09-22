<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\BudgetService;
use App\Services\CustomerService;
use App\Services\PlanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private readonly BudgetService $budgetService,
        private readonly CustomerService $customerService,
        private readonly PlanService $planService,
    ) {}

    public function index( Request $request ): View
    {
        $user = Auth::user();
        if ( !$user ) {
            return view( 'pages.home.index', [ 'welcome' => true ] );
        }

        $tenantId   = $user->tenant_id ?? 1;
        $providerId = $user->provider_id ?? $user->id;

        $result = $this->planService->list();
        if ( !$result->isSuccess() ) {
            abort( 500, 'Erro ao carregar planos' );
        }

        $plans = $result->getData();

        // $recentBudgets   = $this->budgetService->getBudgetsForProvider( $providerId, [ 'limit' => 5 ] );
        // $recentCustomers = $this->customerService->search( '', $tenantId, $providerId );

        return view( 'pages.home.index', compact( 'plans' ) );
    }

}
