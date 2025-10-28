<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller para gestão de clientes - Interface Web
 *
 * Gerencia todas as operações relacionadas a clientes através
 * da interface web, incluindo CRUD, busca e filtros.
 */
class CustomerController extends Controller
{
    public function index( Request $request ): View
    {
        return view( 'pages.customer.index' );
    }

}
