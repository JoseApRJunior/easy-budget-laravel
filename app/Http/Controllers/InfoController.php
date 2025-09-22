<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class InfoController extends Controller
{
    /**
     * Exibe a página "Sobre" da aplicação
     *
     * @return View
     */
    public function about(): View
    {
        return view( 'pages.home.about' );
    }

}
