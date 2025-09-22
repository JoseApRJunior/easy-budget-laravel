<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class LogController extends Controller
{
    /**
     * Exibe a página de logs do sistema
     *
     * @param Request $request
     * @return View
     */
    public function index( Request $request ): View
    {
        return view( 'pages.admin.logs.index' );
    }

}
