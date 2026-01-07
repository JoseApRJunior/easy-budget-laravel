<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\BudgetShareRequest;
use App\Models\Budget;
use App\Models\BudgetShare;
use App\Models\User;
use App\Services\Domain\BudgetShareService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controller para gerenciamento de compartilhamento de orçamentos
 *
 * Este controller gerencia o compartilhamento de orçamentos através de links
temporários e tokens de acesso, permitindo que prestadores compartilhem
 * orçamentos com clientes de forma segura.
 */
class BudgetShareController extends Controller
{
    public function __construct(
        private BudgetShareService $budgetShareService,
    ) {}

    /**
     * Dashboard de compartilhamentos de orçamentos
     */
    public function dashboard(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $tenantId = (int) ($user->tenant_id ?? 0);

        // Obtém estatísticas de compartilhamentos
        $result = $this->budgetShareService->getShareStats($tenantId);

        if (! $result->isSuccess()) {
            // Em caso de erro, retorna estatísticas vazias
            $stats = [
                'total_shares' => 0,
                'active_shares' => 0,
                'expired_shares' => 0,
                'recent_shares' => collect(),
                'most_shared_budgets' => collect(),
                'access_count' => 0,
            ];
        } else {
            $stats = $result->getData();
        }

        return view('pages.budget-share.dashboard', compact('stats'));
    }

    /**
     * Rejeita um compartilhamento (recusa o acesso)
     */
    public function rejectShare(string $token, Request $request): JsonResponse|RedirectResponse
    {
        $result = $this->budgetShareService->rejectShare($token);

        if ($request->expectsJson()) {
            return $this->jsonResponse($result);
        }

        if (! $result->isSuccess()) {
            return redirect()->back()
                ->with('error', $this->getServiceErrorMessage($result, 'Erro ao rejeitar compartilhamento'));
        }

        return redirect()->route('budgets.public.shared.view', ['token' => $token])
            ->with('success', 'Compartilhamento rejeitado com sucesso.');
    }

    /**
     * Lista todos os compartilhamentos do tenant
     */
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();

        // Obtém todos os compartilhamentos do tenant com paginação
        $shares = BudgetShare::with(['budget' => function ($query) {
            $query->select('id', 'code', 'customer_id', 'title');
        }, 'budget.customer' => function ($query) {
            $query->select('id', 'name');
        }])
            ->where('tenant_id', $user->tenant_id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('pages.budget-share.index', [
            'shares' => $shares,
        ]);
    }

    /**
     * Exibe o formulário de criação de novo compartilhamento
     */
    public function create(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();

        // Obtém orçamentos disponíveis para compartilhamento
        $budgets = Budget::query()
            ->where('tenant_id', $user->tenant_id)
            ->whereIn('status', ['pending', 'approved'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('pages.budget-share.create', [
            'budgets' => $budgets,
        ]);
    }

    /**
     * Cria um novo compartilhamento de orçamento
     */
    public function store(BudgetShareRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $result = $this->budgetShareService->createShare([
            'budget_id' => $request->input('budget_id'),
            'expires_at' => $request->input('expires_at'),
            'permissions' => $request->input('permissions', ['view']),
            'notes' => $request->input('notes'),
            'created_by' => $user->id,
        ]);

        if ($result->isSuccess()) {
            return $this->redirectSuccess(
                'provider.budgets.shares.index',
                'Compartilhamento criado com sucesso!'
            );
        }

        return redirect()->back()
            ->withInput()
            ->with('error', $this->getServiceErrorMessage($result, 'Erro ao criar compartilhamento'));
    }

    /**
     * Exibe os detalhes de um compartilhamento
     */
    public function show(string $id): View
    {
        /** @var User $user */
        $user = Auth::user();

        $result = $this->budgetShareService->find($id);

        if (! $result->isSuccess()) {
            return $this->redirectError('provider.budget-shares.index', $this->getServiceErrorMessage($result, 'Compartilhamento não encontrado'));
        }

        return view('pages.budget-share.show', [
            'budgetShare' => $result->getData(),
        ]);
    }

    /**
     * Exibe o formulário de edição de compartilhamento
     */
    public function edit(string $id): View
    {
        /** @var User $user */
        $user = Auth::user();

        // Obtém o compartilhamento
        $result = $this->budgetShareService->find($id);

        if (! $result->isSuccess()) {
            return $this->redirectError('provider.budget-shares.index', $this->getServiceErrorMessage($result, 'Compartilhamento não encontrado'));
        }

        // Obtém orçamentos disponíveis
        $budgets = Budget::query()
            ->where('tenant_id', $user->tenant_id)
            ->whereIn('status', ['pending', 'approved'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('pages.budget-share.edit', [
            'budgetShare' => $result->getData(),
            'budgets' => $budgets,
        ]);
    }

    /**
     * Atualiza um compartilhamento
     */
    public function update(BudgetShareRequest $request, string $id): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $result = $this->budgetShareService->update($id, [
            'budget_id' => $request->input('budget_id'),
            'expires_at' => $request->input('expires_at'),
            'permissions' => $request->input('permissions', ['view']),
            'notes' => $request->input('notes'),
            'status' => $request->input('status', 'active'),
            'updated_by' => $user->id,
        ]);

        return $this->redirectWithServiceResult(
            'provider.budget-shares.index',
            $result,
            'Compartilhamento atualizado com sucesso!'
        );
    }

    /**
     * Revoga um compartilhamento
     */
    public function destroy(string $id): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $result = $this->budgetShareService->revokeShare($id);

        return $this->redirectWithServiceResult(
            'provider.budget-shares.index',
            $result,
            'Compartilhamento revogado com sucesso!'
        );
    }

    /**
     * Acesso público ao orçamento compartilhado via token
     */
    public function access(Request $request, string $token): View
    {
        $result = $this->budgetShareService->validateAccess($token);

        if (! $result->isSuccess()) {
            return view('pages.budget-share.invalid', [
                'error' => $this->getServiceErrorMessage($result, 'Link de compartilhamento inválido ou expirado'),
            ]);
        }

        $shareData = $result->getData();

        return view('pages.budget-share.public', [
            'budget' => $shareData['budget'],
            'budgetShare' => $shareData['share'],
            'permissions' => $shareData['permissions'],
        ]);
    }

    /**
     * Regenera o token de um compartilhamento
     */
    public function regenerateToken(string $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $result = $this->budgetShareService->renewToken((int) $id);

        return $this->jsonResponse($result);
    }

    /**
     * Aprova um orçamento via compartilhamento
     */
    public function approve(string $token, Request $request): JsonResponse|RedirectResponse
    {
        $result = $this->budgetShareService->approveBudget($token);

        if ($request->expectsJson()) {
            return $this->jsonResponse($result);
        }

        if ($result->isSuccess()) {
            return redirect()->route('budgets.public.shared.view', $token)
                ->with('success', 'Orçamento aprovado com sucesso!');
        }

        return redirect()->route('budgets.public.shared.view', $token)
            ->with('error', $this->getServiceErrorMessage($result, 'Erro ao aprovar orçamento'));
    }

    /**
     * Adiciona um comentário ao orçamento
     */
    public function addComment(string $token, Request $request): JsonResponse
    {
        $request->validate([
            'comment' => 'required|string|max:1000',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);

        $result = $this->budgetShareService->addComment($token, $request->only(['comment', 'name', 'email']));

        return $this->jsonResponse($result);
    }
}
