<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\SupportContactRequest;
use App\Services\Domain\SupportService;
use Exception;
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
    protected SupportService $supportService;

    /**
     * Construtor do controller de suporte.
     *
     * @param SupportService $supportService Serviço de suporte
     */
    public function __construct( SupportService $supportService )
    {
        $this->supportService = $supportService;
    }

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
     * @param SupportContactRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store( SupportContactRequest $request )
    {
        Log::info( 'SupportController::store - Iniciando processamento do formulário', [
            'email'          => $request->input( 'email' ),
            'subject'        => $request->input( 'subject' ),
            'has_first_name' => $request->filled( 'first_name' ),
            'has_last_name'  => $request->filled( 'last_name' ),
            'message_length' => strlen( $request->input( 'message', '' ) ),
            'ip'             => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ] );

        try {
            // Obtém dados validados e sanitizados do FormRequest
            $validatedData = $request->getValidatedData();

            Log::info( 'SupportController::store - Dados validados obtidos', [
                'email'         => $validatedData[ 'email' ],
                'subject'       => $validatedData[ 'subject' ],
                'full_name'     => $request->getFullName(),
                'has_full_name' => $request->hasFullName(),
            ] );

            // Cria o ticket de suporte usando o SupportService
            $result = $this->supportService->createSupportTicket( $validatedData );

            if ( !$result->isSuccess() ) {
                Log::warning( 'SupportController::store - Falha ao criar ticket', [
                    'email'    => $validatedData[ 'email' ],
                    'subject'  => $validatedData[ 'subject' ],
                    'error'    => $result->getMessage(),
                    'log_data' => $request->getLogData()
                ] );

                return $this->redirectError( 'support', $result->getMessage() )
                    ->withInput();
            }

            $support = $result->getData();

            Log::info( 'SupportController::store - Ticket criado com sucesso', [
                'support_id' => $support->id,
                'email'      => $validatedData[ 'email' ],
                'subject'    => $validatedData[ 'subject' ],
                'tenant_id'  => $support->tenant_id,
            ] );

            $this->logOperation( 'support_contact_received', [
                'support_id'    => $support->id,
                'full_name'     => $request->getFullName(),
                'email'         => $validatedData[ 'email' ],
                'subject'       => $validatedData[ 'subject' ],
                'has_full_name' => $request->hasFullName(),
                'log_data'      => $request->getLogData()
            ] );

            Log::info( 'SupportController::store - Processamento concluído com sucesso', [
                'support_id'     => $support->id,
                'email'          => $validatedData[ 'email' ],
                'redirect_route' => 'support',
            ] );

            return $this->redirectSuccess( 'support', 'Mensagem enviada com sucesso! Entraremos em contato em breve.' );

        } catch ( Exception $e ) {
            Log::error( 'SupportController::store - Erro durante processamento', [
                'error'       => $e->getMessage(),
                'error_file'  => $e->getFile(),
                'error_line'  => $e->getLine(),
                'email'       => $request->input( 'email', 'N/A' ),
                'subject'     => $request->input( 'subject', 'N/A' ),
                'log_data'    => $request->getLogData(),
                'stack_trace' => $e->getTraceAsString(),
            ] );

            return $this->redirectError( 'support', 'Erro ao enviar mensagem. Tente novamente mais tarde.' )
                ->withInput();
        }
    }

}
