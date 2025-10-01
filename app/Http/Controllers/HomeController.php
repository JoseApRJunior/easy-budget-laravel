<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PlanService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

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

            // Verificar se o resultado foi bem-sucedido e extrair dados
            $plansData = [];
            if ( $result->isSuccess() && is_array( $result->getData() ) ) {
                $plansData = $result->getData();
            }

            return view( 'home.index', [
                'plans' => $plansData
            ] );
        } catch ( Exception $e ) {
            Log::error( 'Erro ao carregar página inicial: ' . $e->getMessage() );

            return view( 'home.index', [
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
        return view( 'home.about' );
    }

    /**
     * Exibe a página de suporte
     *
     * @return View
     */
    public function support(): View
    {
        return view( 'home.support' );
    }

    /**
     * Processa o contato de suporte
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function contact( Request $request )
    {
        $validatedData = $request->validate( [
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
        ] );

        try {
            // TODO: Implementar envio de email de suporte
            // $this->supportService->sendContactEmail($validatedData);

            return redirect()->back()->with( 'success', 'Mensagem enviada com sucesso! Entraremos em contato em breve.' );

        } catch ( Exception $e ) {
            return redirect()->back()
                ->with( 'error', 'Erro ao enviar mensagem. Tente novamente mais tarde.' )
                ->withInput();
        }
    }

}
