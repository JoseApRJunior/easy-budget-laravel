<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanController extends Controller
{
    /**
     * Exibe a página de assinaturas de planos
     *
     * @param Request $request
     * @return View
     */
    public function subscriptions( Request $request ): View
    {
        return view( 'pages.admin.plans.subscriptions' );
    }

}
