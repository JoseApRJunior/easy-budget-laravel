<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Services\Domain\PlanService;
use Exception;
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
    public function __construct(PlanService $planService)
    {
        $this->planService = $planService;
    }

    /**
     * Exibe a página inicial com os planos disponíveis.
     */
    public function index(): View
    {
        try {
            $result = $this->planService->list();

            if ($result->isSuccess()) {
                $plans = $this->getServiceData($result, []);
                $this->logOperation('home_index_accessed', ['plans_count' => count($plans)]);

                return view('pages.home.index', [
                    'plans' => $plans,
                ]);
            }

            // Se falhou, loga o erro e retorna view vazia
            Log::error('Erro no serviço de planos: '.$this->getServiceErrorMessage($result));

            return view('pages.home.index', [
                'plans' => [],
            ]);
        } catch (Exception $e) {
            Log::error('Erro ao carregar página inicial: '.$e->getMessage());

            return view('pages.home.index', [
                'plans' => [],
            ]);
        }
    }

    /**
     * Exibe a página "Sobre"
     */
    public function about(): View
    {
        return view('pages.home.about');
    }

    /**
     * Exibe a página de termos de serviço
     */
    public function terms(): View
    {
        return view('pages.legal.terms_of_service');
    }

    /**
     * Exibe a página de política de privacidade
     */
    public function privacy(): View
    {
        return view('pages.legal.privacy_policy');
    }
}
