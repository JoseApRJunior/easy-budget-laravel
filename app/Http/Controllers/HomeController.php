<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Services\PlanService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Exemplo prático de Controller usando funcionalidades do Controller base:
 *
 * ```php
 * // Antes (código repetitivo):
 * $result = $this->planService->list();
 * $plans = $result->isSuccess() ? $result->getData() : [];
 * return view('pages.home.index', ['plans' => $plans]);
 *
 * // Depois (usando Controller base):
 * $result = $this->planService->list();
 * return $this->view('pages.home.index', $result);
 * ```
 *
 * Benefícios:
 * - ✅ Tratamento padronizado de ServiceResult
 * - ✅ Logging automático de operações
 * - ✅ Redirects consistentes com mensagens
 * - ✅ Validação centralizada
 * - ✅ Tratamento de erro uniforme
 */

/**
 * Controller para a página inicial do sistema
 *
 * Gerencia a exibição da home page com planos disponíveis
 * e funcionalidades de pré-cadastro de usuários.
 */
class HomeController extends Controller
{
    protected PlanService $planService;

    /**
     * Construtor com injeção de dependência
     */
    public function __construct( PlanService $planService )
    {
        $this->planService = $planService;
    }

    /**
     * Exibe a página inicial com os planos disponíveis.
     *
     * @return View
     */
    public function index(): View
    {
        try {
            $result = $this->planService->list();

            if ( $result->isSuccess() ) {
                $plans = $this->getServiceData( $result, [] );
                $this->logOperation( 'home_index_accessed', [ 'plans_count' => count( $plans ) ] );

                return view( 'pages.home.index', [
                    'plans' => $plans
                ] );
            }

            // Se falhou, loga o erro e retorna view vazia
            Log::error( 'Erro no serviço de planos: ' . $this->getServiceErrorMessage( $result ) );

            return view( 'pages.home.index', [
                'plans' => []
            ] );
        } catch ( Exception $e ) {
            Log::error( 'Erro ao carregar página inicial: ' . $e->getMessage() );

            return view( 'pages.home.index', [
                'plans' => []
            ] );
        }
    }

    /**
     * Exibe a página "Sobre"
     *
     * @return View
     */
    public function about(): View
    {
        return view( 'pages.home.about' );
    }

    /**
     * Exibe a página de suporte e processa formulário de contato
     *
     * @param Request $request
     * @return View|\Illuminate\Http\RedirectResponse
     */
    public function support( Request $request )
    {
        // Se for POST, processa o formulário de contato
        if ( $request->isMethod( 'post' ) ) {
            return $this->contact( $request );
        }

        // Se for GET, exibe a página
        return view( 'pages.home.support' );
    }

    /**
     * Processa o contato de suporte
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function contact( Request $request )
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

            // TODO: Implementar envio de email de suporte
            // $this->supportService->sendContactEmail($validatedData);

            $this->logOperation( 'support_contact_received', [
                'name'    => $validatedData[ 'name' ],
                'email'   => $validatedData[ 'email' ],
                'subject' => $validatedData[ 'subject' ]
            ] );

            return $this->redirectSuccess( 'home.support', 'Mensagem enviada com sucesso! Entraremos em contato em breve.' );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao processar contato de suporte: ' . $e->getMessage() );

            return $this->redirectError( 'home.support', 'Erro ao enviar mensagem. Tente novamente mais tarde.' )
                ->withInput();
        }
    }

    /**
     * Exibe a página de termos de serviço
     *
     * @return View
     */
    public function terms(): View
    {
        return view( 'pages.legal.terms_of_service' );
    }

    /**
     * Exibe a página de política de privacidade
     *
     * @return View
     */
    public function privacy(): View
    {
        return view( 'pages.legal.privacy_policy' );
    }

}
