<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class SupportController extends Controller
{
    /**
     * Exibir página de suporte
     */
    public function index(): View
    {
        return view( 'pages.home.support' );
    }

    /**
     * Processar formulário de suporte
     */
    public function store( Request $request ): RedirectResponse
    {
        try {
            // Validar os dados do formulário
            $validated = $request->validate( [ 
                'name'    => 'required|string|max:255',
                'email'   => 'required|email|max:255',
                'subject' => 'required|string|max:255',
                'message' => 'required|string',
            ] );

            // TODO: Implementar envio de email de suporte
            // Por enquanto, apenas simular sucesso

            return redirect()->route( 'support' )
                ->with( 'success', 'Email de suporte enviado com sucesso!' );

        } catch ( \Exception $e ) {
            return redirect()->route( 'support' )
                ->with( 'error', 'Falha ao enviar o email, tente novamente mais tarde ou entre em contato com suporte!' );
        }
    }

}
