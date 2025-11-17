<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\BudgetStatus;
use App\Http\Requests\BudgetStoreRequest;
use App\Http\Controllers\Abstracts\Controller;
use App\Models\Budget;
use App\Models\User;
use App\Services\Domain\BudgetService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controller simplificado para Budgets
 * Versão limpa para resolver problemas de sintaxe
 */
class BudgetController extends Controller
{
    public function __construct(
        private BudgetService $budgetService,
    ) {}

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $budgets = $this->budgetService->getBudgetsForProvider($user->id, $request->all());
        
        return view('pages.budget.index', [
            'budgets' => $budgets
        ]);
    }

    public function create(): View
    {
        return view('pages.budget.create');
    }

    public function store(BudgetStoreRequest $request): RedirectResponse
    {
        try {
            $result = $this->budgetService->create($request->validated());
            
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
        /** @var User $user */
        $user = Auth::user();
        
        // Usar query builder para evitar problemas com PHPStan
        $budget = Budget::query()
            ->where('code', $code)
            ->where('tenant_id', $user->tenant_id)
            ->firstOrFail();
            
        return view('pages.budget.show', [
            'budget' => $budget
        ]);
    }
}
