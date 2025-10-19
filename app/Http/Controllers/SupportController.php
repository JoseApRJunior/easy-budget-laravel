<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Controller para gerenciamento da página de suporte
 *
 * Responsável por exibir a página de suporte e processar
 * formulários de contato dos usuários.
 */
class SupportController extends Controller
{
    /**
     * Exibe a página de suporte (GET /support)
     *
     * @return View
     */
    public function index(): View
    {
        return view( 'pages.home.support' );
    }

    /**
     * Processa o formulário de contato (POST /support)
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store( Request $request )
    {
        $validatedData = $this->validateRequest( $request, [
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|max:255',
            'subject'    => 'required|string|max:255',
            'message'    => 'required|string|max:1000',
        ] );

        try {
            // Combina nome e sobrenome
            $validatedData[ 'name' ] = $validatedData[ 'first_name' ] . ' ' . $validatedData[ 'last_name' ];

            $this->supportService->sendContactEmail( $validatedData );

            $this->logOperation( 'support_contact_received', [
                'name'    => $validatedData[ 'name' ],
                'email'   => $validatedData[ 'email' ],
                'subject' => $validatedData[ 'subject' ]
            ] );

            return $this->redirectSuccess( 'support', 'Mensagem enviada com sucesso! Entraremos em contato em breve.' );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao processar contato de suporte: ' . $e->getMessage() );

            return $this->redirectError( 'support', 'Erro ao enviar mensagem. Tente novamente mais tarde.' )
                ->withInput();
        }
    }

}
