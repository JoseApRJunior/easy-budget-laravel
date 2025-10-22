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
    public function __construct(SupportService $supportService)
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
    public function store(SupportContactRequest $request)
    {
        try {
            // Obtém dados validados e sanitizados do FormRequest
            $validatedData = $request->getValidatedData();

            // Cria o ticket de suporte usando o SupportService
            $result = $this->supportService->createSupportTicket($validatedData);

            if (!$result->isSuccess()) {
                Log::warning('Falha ao criar ticket de suporte', [
                    'email' => $validatedData['email'],
                    'subject' => $validatedData['subject'],
                    'error' => $result->getMessage(),
                    'log_data' => $request->getLogData()
                ]);

                return $this->redirectError('support', $result->getMessage())
                    ->withInput();
            }

            $support = $result->getData();

            $this->logOperation('support_contact_received', [
                'support_id' => $support->id,
                'full_name' => $request->getFullName(),
                'email' => $validatedData['email'],
                'subject' => $validatedData['subject'],
                'has_full_name' => $request->hasFullName(),
                'log_data' => $request->getLogData()
            ]);

            return $this->redirectSuccess('support', 'Mensagem enviada com sucesso! Entraremos em contato em breve.');

        } catch (Exception $e) {
            Log::error('Erro ao processar contato de suporte: ' . $e->getMessage(), [
                'email' => $request->input('email', 'N/A'),
                'subject' => $request->input('subject', 'N/A'),
                'log_data' => $request->getLogData()
            ]);

            return $this->redirectError('support', 'Erro ao enviar mensagem. Tente novamente mais tarde.')
                ->withInput();
        }
    }

}
