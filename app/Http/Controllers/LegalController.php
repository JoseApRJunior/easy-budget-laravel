<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LegalController extends Controller
{
    /**
     * Exibir página de termos de serviço
     */
    public function termsOfService(): Response
    {
        return response()->view( 'pages.legal.terms_of_service' );
    }

    /**
     * Exibir página de política de privacidade
     */
    public function privacyPolicy(): Response
    {
        return response()->view( 'pages.legal.privacy_policy' );
    }

}
