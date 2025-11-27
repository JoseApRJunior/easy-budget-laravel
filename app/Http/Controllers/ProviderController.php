<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Models\User;
use App\Services\Application\ProviderManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controller moderno para gerenciar operações relacionadas aos providers.
 *
 * Este controller utiliza Eloquent, Form Requests e injeção de dependências
 * seguindo os padrões Laravel modernos.
 *
 * Funcionalidades:
 * - Dashboard do provider
 * - Atualização de dados do provider
 * - Alteração de senha
 * - Upload de imagens
 */
class ProviderController extends Controller
{
    public function __construct(
        private ProviderManagementService $providerService,
    ) {}

    /**
     * Dashboard do provider com resumo de orçamentos, atividades e financeiro.
     */
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();

        $dashboardData = $this->providerService->getDashboardData(
            $user->tenant_id,
        );

        return view('pages.provider.index', [
            'budgets' => $dashboardData['budgets'],
            'activities' => $dashboardData['activities'],
            'financial_summary' => $dashboardData['financial_summary'],
            'total_activities' => count($dashboardData['activities']),
            'events' => $dashboardData['events'] ?? [],
        ]);
    }

    /**
     * Exibe formulário de atualização do provider (legacy - redireciona para nova estrutura).
     */
    public function update(): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('provider.business.edit')
            ->with('info', 'Use a nova interface separada para atualizar seus dados.');
    }

    /**
     * Processa atualização dos dados do provider (legacy - redireciona para nova estrutura).
     */
    public function update_store(Request $request): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('provider.business.edit')
            ->with('info', 'Use a nova interface separada para atualizar seus dados.');
    }

    /**
     * Exibe formulário de alteração de senha.
     */
    public function change_password(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $isGoogleUser = is_null($user->password);

        return view('pages.provider.change_password', [
            'isGoogleUser' => $isGoogleUser,
            'userEmail' => $user->email,
        ]);
    }

    /**
     * Processa alteração de senha.
     */
    public function change_password_store(ChangePasswordRequest $request): RedirectResponse
    {
        try {
            $this->providerService->changePassword($request->validated()['password']);

            /** @var User $user */
            $user = Auth::user();
            $isGoogleUser = is_null($user->password);
            $successMessage = $isGoogleUser ? 'Senha definida com sucesso!' : 'Senha alterada com sucesso!';

            return redirect()->route('settings.index')
                ->with('success', $successMessage);
        } catch (\Exception $e) {
            return redirect()->route('provider.change_password')
                ->with('error', 'Erro ao atualizar senha: '.$e->getMessage());
        }
    }
}
