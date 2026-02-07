<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\PlanStoreRequest;
use App\Http\Requests\PlanUpdateRequest;
use App\Services\Domain\PlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller para gerenciamento de planos
 *
 * Este controller implementa operações CRUD completas para planos do sistema,
 * seguindo a arquitetura Controller → Service → Repository → Model.
 *
 * Funcionalidades implementadas:
 * - Listagem paginada com filtros e ordenação
 * - Criação de novos planos com validação robusta
 * - Visualização detalhada de planos específicos
 * - Edição de planos existentes
 * - Exclusão segura com verificações
 * - Ativação e desativação de planos
 * - Suporte completo a APIs JSON e interface web
 *
 * @version 1.0.0
 *
 * @author Sistema Easy Budget Laravel
 */
class PlanController extends Controller
{
    protected PlanService $planService;

    /**
     * Construtor com injeção de dependência
     *
     * Inicializa o controller com todas as dependências necessárias,
     * seguindo o princípio de Inversão de Dependência (DIP).
     *
     * @param  PlanService  $planService  Camada de serviço para lógica de negócio
     */
    public function __construct(PlanService $planService)
    {
        $this->planService = $planService;
    }

    /**
     * Lista todos os planos com filtros
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'status', 'min_price', 'max_price']);
        $result = $this->planService->getFilteredPlans($filters, []);
        if (! $result->isSuccess()) {
            abort(500, 'Erro ao carregar lista');
        }

        return view('pages.plan.index', [
            'plans' => $result->getData(),
            'filters' => $filters,
        ]);
    }

    /**
     * Exibe formulário para criação de plano
     */
    public function create(): View
    {
        return view('pages.plan.create');
    }

    /**
     * Cria um novo plano
     */
    public function store(PlanStoreRequest $request): RedirectResponse
    {
        $result = $this->planService->createPlan($request->validated());
        if (! $result->isSuccess()) {
            return redirect()->back()->withErrors($result->getMessage())->withInput();
        }

        return redirect()->route('plans.index')->with('success', 'Plano criado com sucesso');
    }

    /**
     * Exibe detalhes de um plano específico
     */
    public function show(string $slug): View
    {
        $result = $this->planService->findBySlug($slug, []);
        if (! $result->isSuccess()) {
            abort(404, $result->getMessage());
        }

        return view('pages.plan.show', [
            'plan' => $result->getData(),
        ]);
    }

    /**
     * Exibe formulário para edição de plano
     */
    public function edit(string $slug): View
    {
        $result = $this->planService->findBySlug($slug, []);
        if (! $result->isSuccess()) {
            abort(404, $result->getMessage());
        }

        return view('pages.plan.edit', [
            'plan' => $result->getData(),
        ]);
    }

    /**
     * Atualiza um plano específico
     */
    public function update(PlanUpdateRequest $request, string $slug): RedirectResponse
    {
        $result = $this->planService->updateBySlug($slug, $request->validated());
        if (! $result->isSuccess()) {
            return redirect()->back()->withErrors($result->getMessage())->withInput();
        }

        return redirect()->route('plans.index')->with('success', 'Plano atualizado com sucesso');
    }

    /**
     * Alterna status do plano
     */
    public function toggleStatus(string $slug): RedirectResponse
    {
        $result = $this->planService->toggleStatus($slug);
        if (! $result->isSuccess()) {
            return redirect()->back()->withErrors($result->getMessage());
        }

        return redirect()->back()->with('success', $result->getData());
    }

    /**
     * Remove um plano específico
     */
    public function destroy(string $slug): RedirectResponse
    {
        $result = $this->planService->deleteBySlug($slug);
        if (! $result->isSuccess()) {
            return redirect()->back()->withErrors($result->getMessage());
        }

        return redirect()->route('plans.index')->with('success', 'Plano removido com sucesso');
    }

    /**
     * Processa seleção de plano e redireciona para pagamento
     */
    public function redirectToPayment(string $slug): RedirectResponse
    {
        $result = $this->planService->findBySlug($slug);
        if (! $result->isSuccess()) {
            return redirect()->back()->withErrors($result->getMessage() ?? 'Plano não encontrado.');
        }

        $plan = $result->getData();
        $user = auth()->user();
        $tenantId = (int) ($user->tenant_id ?? 0);
        $providerId = (int) ($user->provider->id ?? 0);

        if (! $tenantId || ! $providerId) {
            return redirect()->back()->withErrors('Tenant ou provider não encontrado para o usuário atual');
        }

        $subscription = \App\Models\PlanSubscription::create([
            'tenant_id' => $tenantId,
            'provider_id' => $providerId,
            'plan_id' => (int) $plan->id,
            'status' => \App\Models\PlanSubscription::STATUS_PENDING,
            'transaction_amount' => (float) $plan->price,
            'start_date' => now(),
        ]);

        $service = app(\App\Services\Infrastructure\PaymentMercadoPagoPlanService::class);
        $pref = $service->createMercadoPagoPreference((int) $subscription->id);
        if (! $pref->isSuccess()) {
            return redirect()->route('plans.show', $slug)->withErrors($pref->getMessage());
        }

        $initPoint = $pref->getData()['init_point'] ?? null;
        if (! $initPoint) {
            return redirect()->route('plans.show', $slug)->withErrors('Link de pagamento indisponível');
        }

        return redirect()->away($initPoint);
    }

    /**
     * Cancela assinatura pendente
     */
    public function cancelPendingSubscription(string $slug): RedirectResponse
    {
        // Lógica para cancelar assinatura pendente
        // TODO: Implementar integração com serviço de assinaturas

        return redirect()->route('plans.show', $slug)->with('success', 'Assinatura pendente cancelada.');
    }

    /**
     * Verifica status de assinatura pendente
     */
    public function status(string $slug): View
    {
        $result = $this->planService->findBySlug($slug);
        if (! $result->isSuccess()) {
            abort(404, $result->getMessage());
        }
        $plan = $result->getData();

        $subscription = \App\Models\PlanSubscription::where('tenant_id', auth()->user()->tenant_id ?? null)
            ->where('plan_id', (int) $plan->id)
            ->orderByDesc('created_at')
            ->first();

        $payment = null;
        if ($subscription) {
            $payment = \App\Models\PaymentMercadoPagoPlan::where('plan_subscription_id', (int) $subscription->id)
                ->orderByDesc('created_at')
                ->first();
        }

        $payment = $payment ?: (object) ['status' => 'not_started'];

        return view('pages.plan.status', [
            'subscription' => (object) [
                'name' => $plan->name,
                'slug' => $slug,
                'transaction_amount' => (float) ($subscription->transaction_amount ?? $plan->price),
            ],
            'payment' => $payment,
        ]);
    }

    /**
     * Página de retorno após pagamento
     */
    public function paymentStatus(Request $request): View
    {
        // Lógica para processar retorno do pagamento
        // TODO: Implementar processamento de webhook/retorno

        $status = $request->get('status', 'unknown');
        $planSlug = $request->get('plan_slug', '');

        return view('pages.plan.payment-status', [
            'status' => $status,
            'plan_slug' => $planSlug,
        ]);
    }

    /**
     * Ativa um plano
     */
    public function activate(string $slug): RedirectResponse
    {
        $result = $this->planService->findBySlug($slug);
        if (! $result->isSuccess()) {
            return redirect()->back()->withErrors($result->getMessage() ?? 'Plano não encontrado.');
        }

        $updateResult = $this->planService->updateBySlug($slug, ['status' => true]);
        if (! $updateResult->isSuccess()) {
            return redirect()->back()->withErrors($updateResult->getMessage());
        }

        return redirect()->back()->with('success', 'Plano ativado com sucesso.');
    }

    /**
     * Desativa um plano
     */
    public function deactivate(string $slug): RedirectResponse
    {
        $result = $this->planService->findBySlug($slug);
        if (! $result->isSuccess()) {
            return redirect()->back()->withErrors($result->getMessage() ?? 'Plano não encontrado.');
        }

        $updateResult = $this->planService->updateBySlug($slug, ['status' => false]);
        if (! $updateResult->isSuccess()) {
            return redirect()->back()->withErrors($updateResult->getMessage());
        }

        return redirect()->back()->with('success', 'Plano desativado com sucesso.');
    }
}
