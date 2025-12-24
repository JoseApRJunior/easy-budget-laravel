<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\Budget\BudgetDTO;
use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\BudgetStoreRequest;
use App\Http\Requests\BudgetUpdateRequest;
use App\Models\Budget;
use App\Models\User;
use App\Services\Domain\BudgetService;
use App\Services\Domain\CustomerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controller para Budgets
 */
class BudgetController extends Controller
{
    public function __construct(
        private readonly BudgetService $budgetService,
        private readonly CustomerService $customerService,
    ) {}

    /**
     * Dashboard de orçamentos.
     */
    public function dashboard(Request $request): View
    {
        $result = $this->budgetService->getDashboardStats();

        if ($result->isError()) {
            abort(500, 'Erro ao carregar estatísticas do dashboard.');
        }

        return view('pages.budget.dashboard', [
            'stats' => $result->getData(),
        ]);
    }

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $result = $this->budgetService->getBudgetsForProvider($user->id, $request->all());

        if ($result->isError()) {
            abort(500, 'Erro ao carregar orçamentos.');
        }

        return view('pages.budget.index', [
            'budgets' => $result->getData(),
        ]);
    }

    public function create(): View
    {
        $customersResult = $this->customerService->getFilteredCustomers(['per_page' => 200]);

        return view('pages.budget.create', [
            'customers' => $customersResult->isSuccess() ? $customersResult->getData() : [],
        ]);
    }

    public function store(BudgetStoreRequest $request): RedirectResponse
    {
        try {
            $dto = BudgetDTO::fromRequest($request->validated());
            $result = $this->budgetService->create($dto);

            if ($result->isError()) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', $result->getMessage());
            }

            return redirect()->route('provider.budgets.show', $result->getData()->code)
                ->with('success', 'Orçamento criado com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao criar orçamento: ' . $e->getMessage());
        }
    }

    public function show(string $code): View
    {
        $result = $this->budgetService->findByCode($code);

        if ($result->isError()) {
            abort(404, 'Orçamento não encontrado.');
        }

        $budget = $result->getData();
        $budget->load([
            'customer.commonData',
            'customer.contact',
            'items' => function ($q) {
                $q->ordered();
            },
            'services.category',
        ]);

        return view('pages.budget.show', [
            'budget' => $budget,
        ]);
    }

    public function edit(string $code): View
    {
        $result = $this->budgetService->findByCode($code);

        if ($result->isError()) {
            abort(404, 'Orçamento não encontrado.');
        }

        $budget = $result->getData();
        $budget->load(['customer', 'items']);

        $customersResult = $this->customerService->getFilteredCustomers(['per_page' => 200]);

        return view('pages.budget.edit', [
            'budget' => $budget,
            'customers' => $customersResult->isSuccess() ? $customersResult->getData() : [],
        ]);
    }

    public function update(BudgetUpdateRequest $request, string $code): RedirectResponse
    {
        try {
            $dto = BudgetDTO::fromRequest($request->validated());
            $result = $this->budgetService->update($code, $dto);

            if ($result->isError()) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', $result->getMessage());
            }

            return redirect()->route('provider.budgets.show', $result->getData()->code)
                ->with('success', 'Orçamento atualizado com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao atualizar orçamento: ' . $e->getMessage());
        }
    }

    public function toggleStatus(Request $request, string $code): RedirectResponse
    {
        $status = $request->input('status');
        $comment = $request->input('comment', '');

        $result = $this->budgetService->changeStatusByCode($code, (string) $status, (string) $comment);

        if ($result->isError()) {
            return redirect()->back()->with('error', $result->getMessage());
        }

        return redirect()->back()->with('success', 'Status atualizado com sucesso!');
    }

    public function destroy(string $code): RedirectResponse
    {
        $result = $this->budgetService->deleteByCode($code);

        if ($result->isError()) {
            return redirect()->back()->with('error', $result->getMessage());
        }

        return redirect()->route('provider.budgets.index')
            ->with('success', 'Orçamento excluído com sucesso!');
    }
}
