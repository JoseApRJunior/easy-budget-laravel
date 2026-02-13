<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\Budget\BudgetDTO;
use App\Helpers\DateHelper;
use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\BudgetStoreRequest;
use App\Http\Requests\BudgetUpdateRequest;
use App\Models\Budget;
use App\Models\User;
use App\Services\Domain\BudgetService;
use App\Services\Domain\BudgetShareService;
use App\Services\Domain\CustomerService;
use App\Services\Infrastructure\BudgetPDFService;
use App\Services\Infrastructure\MailerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Controller para Budgets
 */
class BudgetController extends Controller
{
    public function __construct(
        private readonly BudgetService $budgetService,
        private readonly CustomerService $customerService,
        private readonly BudgetShareService $budgetShareService,
        private readonly BudgetPDFService $pdfService,
        private readonly MailerService $mailerService,
    ) {}

    /**
     * Dashboard de orçamentos.
     */
    public function dashboard(Request $request): View
    {
        $this->authorize('viewAny', Budget::class);
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
        $this->authorize('viewAny', Budget::class);
        $filters = $request->all();

        // Normalizar datas para o banco
        if (isset($filters['start_date'])) {
            $filters['start_date'] = DateHelper::parseDate($filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $filters['end_date'] = DateHelper::parseDate($filters['end_date']);
        }

        $result = $this->budgetService->getBudgetsForProvider($filters);

        if ($result->isError()) {
            abort(500, 'Erro ao carregar orçamentos.');
        }

        return view('pages.budget.index', [
            'budgets' => $result->getData(),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Budget::class);
        $filterDto = \App\DTOs\Customer\CustomerFilterDTO::fromRequest(['per_page' => 200]);
        $customersResult = $this->customerService->getFilteredCustomers($filterDto);

        $selectedCustomer = null;
        if ($customerId = $request->query('customer_id')) {
            $customerResult = $this->customerService->findCustomer((int) $customerId);
            if ($customerResult->isSuccess()) {
                $selectedCustomer = $customerResult->getData();
            }
        }

        return view('pages.budget.create', [
            'customers' => $customersResult->isSuccess() ? $customersResult->getData() : [],
            'selectedCustomer' => $selectedCustomer,
        ]);
    }

    public function store(BudgetStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Budget::class);

        $validated = $request->validated();
        if (isset($validated['due_date'])) {
            $validated['due_date'] = DateHelper::parseDate($validated['due_date']);
        }

        $dto = BudgetDTO::fromRequest($validated);
        $result = $this->budgetService->create($dto);

        if ($result->isSuccess()) {
            $budget = $result->getData();

            return $this->redirectWithServiceResult(
                'provider.budgets.edit',
                $result,
                'Orçamento criado com sucesso!',
                ['code' => $budget->code]
            );
        }

        return $this->redirectBackWithServiceResult(
            $result,
            'Orçamento criado com sucesso!'
        );
    }

    public function show(string $code): View
    {
        $result = $this->budgetService->findByCode($code, [
            'customer.commonData',
            'customer.contact',
            'services.serviceItems' => function ($q) {
                // $q->ordered(); // Ordered might not exist on ServiceItem, check if needed
            },
            'services.category',
            'actionHistory' => function ($q) {
                $q->orderBy('created_at', 'desc');
            },
        ]);

        if ($result->isError()) {
            abort(404, 'Orçamento não encontrado.');
        }

        $budget = $result->getData();
        $this->authorize('view', $budget);

        return view('pages.budget.show', [
            'budget' => $budget,
        ]);
    }

    public function edit(string $code): View
    {
        $result = $this->budgetService->findByCode($code, ['customer.commonData']);

        if ($result->isError()) {
            abort(404, 'Orçamento não encontrado.');
        }

        $budget = $result->getData();
        $this->authorize('update', $budget);

        $filterDto = \App\DTOs\Customer\CustomerFilterDTO::fromRequest(['per_page' => 200]);
        $customersResult = $this->customerService->getFilteredCustomers($filterDto);

        return view('pages.budget.edit', [
            'budget' => $budget,
            'customers' => $customersResult->isSuccess() ? $customersResult->getData() : [],
        ]);
    }

    public function update(BudgetUpdateRequest $request, string $code): RedirectResponse
    {
        \Illuminate\Support\Facades\Log::info('[BudgetController@update] Iniciando atualização', [
            'code' => $code,
            'request_all' => $request->all(),
        ]);

        $result = $this->budgetService->findByCode($code);
        if ($result->isError()) {
            \Illuminate\Support\Facades\Log::error('[BudgetController@update] Orçamento não encontrado', ['code' => $code]);

            return redirect()->back()->with('error', $result->getMessage());
        }

        $budget = $result->getData();
        $this->authorize('update', $budget);

        $validated = $request->validated();
        if (isset($validated['due_date'])) {
            $validated['due_date'] = DateHelper::parseDate($validated['due_date']);
        }

        $dto = BudgetDTO::fromRequest($validated);
        $updateResult = $this->budgetService->update($budget->id, $dto);

        return $this->redirectBackWithServiceResult(
            $updateResult,
            'Orçamento atualizado com sucesso!'
        );
    }

    public function toggleStatus(Request $request, string $code): RedirectResponse
    {
        $result = $this->budgetService->findByCode($code);
        if ($result->isError()) {
            return redirect()->back()->with('error', $result->getMessage());
        }

        $budget = $result->getData();
        $this->authorize('update', $budget);

        $status = $request->input('status');
        $comment = $request->input('comment', '');

        $toggleResult = $this->budgetService->changeStatusByCode($code, (string) $status, (string) $comment);

        if ($toggleResult->isError()) {
            return redirect()->back()->with('error', $toggleResult->getMessage());
        }

        return redirect()->back()->with('success', 'Status atualizado com sucesso!');
    }

    public function destroy(string $code): RedirectResponse
    {
        $result = $this->budgetService->findByCode($code);
        if ($result->isError()) {
            return redirect()->back()->with('error', $result->getMessage());
        }

        $budget = $result->getData();
        $this->authorize('delete', $budget);

        $deleteResult = $this->budgetService->deleteByCode($code);

        if ($deleteResult->isError()) {
            return redirect()->back()->with('error', $deleteResult->getMessage());
        }

        return redirect()->route('provider.budgets.index')
            ->with('success', 'Orçamento excluído com sucesso!');
    }

    /**
     * Imprimir ou gerar PDF do orçamento.
     */
    public function print(Request $request, string $code, ?string $token = null)
    {
        // Se houver um token, valida o acesso público
        if ($token) {
            $accessResult = $this->budgetShareService->validateAccess($token);
            if ($accessResult->isError()) {
                abort(403, $accessResult->getMessage());
            }

            $accessData = $accessResult->getData();
            $budget = $accessData['budget'];
            $permissions = $accessData['permissions'];

            if (! $permissions['can_print']) {
                abort(403, 'Você não tem permissão para imprimir este orçamento.');
            }

            // O provider é o dono do orçamento (tenant), já carregado via validateAccess
            $provider = $budget->tenant->provider;
        } else {
            // Acesso via área do provider (autenticado)
            $result = $this->budgetService->findByCode($code, [
                'customer.commonData',
                'customer.contact',
                'customer.address',
                'services.serviceItems',
                'services.category',
            ]);

            if ($result->isError()) {
                abort(404, 'Orçamento não encontrado.');
            }

            $budget = $result->getData();
            $this->authorize('view', $budget);

            /** @var User $user */
            $user = Auth::user();
            $provider = $user->provider()->with(['commonData', 'contact', 'address', 'businessData'])->first();
        }

        $isPdf = $request->has('pdf');
        $download = $request->has('download');

        if ($isPdf) {
            $path = $this->pdfService->generatePdf($budget, ['provider' => $provider]);

            if (! Storage::exists($path)) {
                abort(404, 'O arquivo PDF não foi gerado corretamente.');
            }

            $fullPath = Storage::path($path);
            $filename = "orcamento_{$budget->code}.pdf";

            return response()->download($fullPath, $filename, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => ($download ? 'attachment' : 'inline').'; filename="'.$filename.'"',
            ]);
        }

        // Se não for PDF, retorna a view de impressão HTML otimizada
        return view('pages.budget.print', compact('budget', 'provider'));
    }

    /**
     * Envia o orçamento para o cliente por e-mail.
     */
    public function sendToCustomer(Request $request, string $code): RedirectResponse
    {
        $request->validate([
            'message' => ['nullable', 'string', 'max:500'],
        ], [
            'message.max' => 'A mensagem personalizada não pode ter mais de 500 caracteres.',
        ]);

        $message = trim($request->input('message') ?? '');
        $message = $message === '' ? null : $message;

        \Illuminate\Support\Facades\Log::info('[BudgetController@sendToCustomer] Mensagem capturada:', ['message' => $message, 'code' => $code]);

        $result = $this->budgetService->sendToCustomer($code, $message);

        if ($result->isError()) {
            return redirect()->back()->with('error', $result->getMessage());
        }

        return redirect()->back()->with('success', $result->getMessage());
    }

    /**
     * Exibe a página pública para o cliente escolher o status do orçamento (Aprovar/Rejeitar).
     */
    public function chooseBudgetStatus(string $code, string $token): View
    {
        $result = $this->budgetShareService->validateAccess($token);

        if ($result->isError()) {
            abort(403, $result->getMessage());
        }

        $data = $result->getData();

        return view('pages.budget.choose_budget_status', [
            'budget' => $data['budget'],
            'share' => $data['share'],
            'permissions' => $data['permissions'] ?? ['view', 'print', 'comment', 'approve'],
            'token' => $token,
        ]);
    }

    /**
     * Processa a decisão do cliente sobre o orçamento.
     */
    public function chooseBudgetStatusStore(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'action' => ['required', 'string', 'in:approve,reject'],
            'comment' => ['nullable', 'string', 'max:500'],
        ], [
            'comment.max' => 'O comentário não pode ter mais de 500 caracteres.',
        ]);

        $token = $request->input('token');
        $action = $request->input('action'); // 'approve' ou 'reject'
        $comment = $request->input('comment');

        if ($action === 'approve') {
            $result = $this->budgetShareService->approveBudget($token, $comment);
        } elseif ($action === 'reject') {
            $result = $this->budgetShareService->rejectBudget($token, $comment);
        } else {
            return redirect()->back()->with('error', 'Ação inválida.');
        }

        if ($result->isError()) {
            return redirect()->back()->with('error', $result->getMessage())->withInput();
        }

        return redirect()->back()->with('success', $result->getMessage());
    }
}
