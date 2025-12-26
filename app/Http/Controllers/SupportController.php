<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\Support\SupportDTO;
use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\SupportContactRequest;
use App\Services\Domain\SupportService;
use Exception;
use Illuminate\Contracts\View\View;

/**
 * Controller para gerenciamento da página de suporte
 *
 * Responsável por exibir a página de suporte e processar
 * formulários de contato dos usuários.
 */
class SupportController extends Controller
{
    /**
     * Construtor do controller de suporte.
     *
     * @param  SupportService  $supportService  Serviço de suporte
     */
    public function __construct(
        protected SupportService $supportService
    ) {}

    /**
     * Exibe a página de suporte (GET /support)
     */
    public function index(): View
    {
        return view('pages.home.support');
    }

    /**
     * Processa o formulário de contato (POST /support)
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(SupportContactRequest $request)
    {
        try {
            // Obtém dados validados e sanitizados do FormRequest
            $validatedData = $request->getValidatedData();

            // Cria o DTO
            $dto = SupportDTO::fromRequest($validatedData);

            // Cria o ticket de suporte usando o SupportService
            $result = $this->supportService->createSupportTicket($dto);

            if (! $result->isSuccess()) {
                return $this->redirectError('support', $result->getMessage())
                    ->withInput();
            }

            return $this->redirectSuccess('support', 'Ticket de suporte criado com sucesso! Em breve entraremos em contato.');

        } catch (Exception $e) {
            return $this->redirectError('support', 'Erro ao processar ticket: '.$e->getMessage())
                ->withInput();
        }
    }
}
