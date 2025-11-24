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
        /** @var User $user */
        $user = Auth::user();

        $customers = \App\Models\Customer::where('tenant_id', $user->tenant_id)
            ->with(['commonData', 'contact'])
            ->latest('created_at')
            ->limit(200)
            ->get();

        $selectedCustomer = null;

        return view('pages.budget.create', [
            'customers' => $customers,
            'selectedCustomer' => $selectedCustomer,
        ]);
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

        $budget = Budget::query()
            ->with([
                'customer.commonData',
                'customer.contact',
                'items' => function ($q) {
                    $q->ordered();
                },
                'services.category',
            ])
            ->where('code', $code)
            ->where('tenant_id', $user->tenant_id)
            ->firstOrFail();

        return view('pages.budget.show', [
            'budget' => $budget,
        ]);
    }

    public function edit(string $code): View
    {
        /** @var User $user */
        $user = Auth::user();

        // Busca o orçamento por código
        $budget = Budget::query()
            ->where('code', $code)
            ->where('tenant_id', $user->tenant_id)
            ->firstOrFail();

        return view('pages.budget.edit', [
            'budget' => $budget
        ]);
    }

    public function update(BudgetStoreRequest $request, string $code): RedirectResponse
    {
        try {
            // Prepara os dados para atualização
            $data = $request->validated();

            // Força o status para "pending" após edição
            $data['status'] = 'pending';

            $result = $this->budgetService->updateByCode($code, $data);

            if (!$result->isSuccess()) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', $result->getMessage());
            }

            return redirect()->route('provider.budgets.show', $code)
                ->with('success', 'Orçamento atualizado com sucesso! Status alterado para Pendente.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao atualizar orçamento: ' . $e->getMessage());
        }
    }

    public function print( Request $request, Budget $budget )
    {
        /** @var User $user */
        $user = Auth::user();

        if ($budget->tenant_id !== ($user->tenant_id ?? 0)) {
            abort(404);
        }

        $budget->load([
            'customer.commonData',
            'customer.contact',
            'services.category',
            'services.items',
        ]);

        if ( $request->boolean( 'pdf' ) ) {
            $html = view( 'pages.budget.pdf_budget', [
                'budget' => $budget,
            ] )->render();

            $mpdf = new \Mpdf\Mpdf( [
                'mode'          => 'utf-8',
                'format'        => 'A4',
                'margin_left'   => 12,
                'margin_right'  => 12,
                'margin_top'    => 14,
                'margin_bottom' => 14,
            ] );

            $mpdf->SetHeader( 'Orçamento ' . $budget->code . ' - ' . config( 'app.name' ) . '||Gerado em: ' . now()->format( 'd/m/Y' ) );
            $mpdf->SetFooter( 'Página {PAGENO} de {nb}|Usuário: ' . ( $user->name ?? 'N/A' ) . '|' . config( 'app.url' ) );
            $mpdf->WriteHTML( $html );

            $filename = 'orcamento_' . $budget->code . '_' . now()->format( 'Ymd_His' ) . '.pdf';
            $content  = $mpdf->Output( '', 'S' );

            $disposition = $request->boolean( 'download' ) ? 'attachment' : 'inline';

            return response( $content, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => $disposition . '; filename="' . $filename . '"'
            ] );
        }

        return view( 'pages.budget.pdf_budget', [
            'budget' => $budget,
        ] );
    }

    /**
     * Dashboard de orçamentos
     */
    public function dashboard(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $tenantId = (int) ($user->tenant_id ?? 0);

        $total = Budget::where('tenant_id', $tenantId)->count();
        $approved = Budget::where('tenant_id', $tenantId)->where('status', BudgetStatus::APPROVED->value)->count();
        $pending = Budget::where('tenant_id', $tenantId)->where('status', BudgetStatus::PENDING->value)->count();
        $rejected = Budget::where('tenant_id', $tenantId)->where('status', BudgetStatus::REJECTED->value)->count();
        $totalValue = (float) Budget::where('tenant_id', $tenantId)->sum('total');

        $statusBreakdown = [
            'draft' => Budget::where('tenant_id', $tenantId)->where('status', BudgetStatus::DRAFT->value)->count(),
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
            'cancelled' => Budget::where('tenant_id', $tenantId)->where('status', BudgetStatus::CANCELLED->value)->count(),
            'completed' => Budget::where('tenant_id', $tenantId)->where('status', BudgetStatus::COMPLETED->value)->count(),
        ];

        $recent = Budget::where('tenant_id', $tenantId)
            ->latest('created_at')
            ->limit(10)
            ->with(['customer.commonData'])
            ->get();

        $stats = [
            'total_budgets' => $total,
            'approved_budgets' => $approved,
            'pending_budgets' => $pending,
            'rejected_budgets' => $rejected,
            'total_budget_value' => $totalValue,
            'status_breakdown' => $statusBreakdown,
            'recent_budgets' => $recent,
        ];

        return view('pages.budget.dashboard', compact('stats'));
    }
}
