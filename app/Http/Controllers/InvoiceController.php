<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\InvoiceStoreRequest;
use App\Http\Requests\InvoiceStoreFromBudgetRequest;
use App\Http\Requests\InvoiceUpdateRequest;
use App\Models\Budget;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Domain\CustomerService;
use App\Services\Domain\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Contracts\View\View;

/**
 * Controller para gerenciamento de faturas.
 *
 * Versão final mínima para resolver problemas de sintaxe
 */
class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService,
        private CustomerService $customerService,
    ) {}

    /**
     * Exibe lista de faturas.
     */
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $filters = $request->only(['status', 'customer_id', 'date_from', 'date_to', 'search']);

        $result = $this->invoiceService->getFilteredInvoices($filters, [
            'customer:id,name',
            'service:id,code,description',
            'invoiceStatus'
        ]);

        if (!$result->isSuccess()) {
            abort(500, 'Erro ao carregar lista de faturas');
        }

        $invoices = $result->getData();
        
        return view('invoices.index', [
            'invoices'      => $invoices,
            'filters'       => $filters,
            'statusOptions' => InvoiceStatus::cases(),
            'customers'     => $this->customerService->listCustomers($user->tenant_id)->isSuccess()
                ? $this->customerService->listCustomers($user->tenant_id)->getData()
                : []
        ]);
    }

    /**
     * Exibe formulário de criação de fatura.
     */
    public function create(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        
        return view('invoices.create', [
            'service'       => null,
            'customers'     => $this->customerService->listCustomers($user->tenant_id)->isSuccess()
                ? $this->customerService->listCustomers($user->tenant_id)->getData()
                : [],
            'services'      => [],
            'statusOptions' => InvoiceStatus::cases()
        ]);
    }

    /**
     * Armazena nova fatura no sistema.
     */
    public function store(InvoiceStoreRequest $request): RedirectResponse
    {
        $result = $this->invoiceService->createInvoice($request->validated());

        if (!$result->isSuccess()) {
            return redirect()->back()
                ->withInput()
                ->with('error', $result->getMessage());
        }

        $invoice = $result->getData();

        return redirect()->route('invoices.show', $invoice->code)
            ->with('success', 'Fatura criada com sucesso!');
    }

    /**
     * Exibe detalhes de uma fatura específica.
     */
    public function show(Invoice $invoice): View
    {
        $invoice->load([
            'customer.commonData',
            'service.budget',
            'invoiceItems.product',
            'invoiceStatus',
            'payments'
        ]);

        return view('invoices.show', [
            'invoice' => $invoice
        ]);
    }

    /**
     * Exibe formulário de edição de fatura.
     */
    public function edit(Invoice $invoice): View
    {
        /** @var User $user */
        $user = Auth::user();
        
        $invoice->load([
            'invoiceItems.product',
            'customer',
            'service'
        ]);

        return view('invoices.edit', [
            'invoice'       => $invoice,
            'customers'     => $this->customerService->listCustomers($user->tenant_id)->isSuccess()
                ? $this->customerService->listCustomers($user->tenant_id)->getData()
                : [],
            'services'      => [],
            'statusOptions' => InvoiceStatus::cases()
        ]);
    }

    /**
     * Atualiza fatura existente.
     */
    public function update(InvoiceUpdateRequest $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validated();
        $invoice->update($data);

        return redirect()->route('invoices.show', $invoice->code)
            ->with('success', 'Fatura atualizada com sucesso!');
    }

    /**
     * Remove fatura do sistema.
     */
    public function destroy(Invoice $invoice): RedirectResponse
    {
        $invoice->delete();

        return redirect()->route('invoices.index')
            ->with('success', 'Fatura excluída com sucesso!');
    }

    /**
     * Atualiza status da fatura.
     */
    public function change_status(Invoice $invoice, Request $request): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', array_map(fn($case) => $case->value, InvoiceStatus::cases()))]
        ]);

        $invoice->update(['status' => $request->input('status')]);

        return redirect()->route('invoices.show', $invoice->code)
            ->with('success', 'Status da fatura alterado com sucesso!');
    }

    /**
     * Create invoice from budget.
     */
    public function createFromBudget(Budget $budget): View
    {
        /** @var User $user */
        $user = Auth::user();
        
        $budget->load([
            'customer.commonData',
            'services.serviceItems.product'
        ]);

        return view('invoices.create-from-budget', [
            'budget'           => $budget,
            'alreadyBilled'    => 0,
            'remainingBalance' => 0,
            'customers'        => $this->customerService->listCustomers($user->tenant_id)->isSuccess()
                ? $this->customerService->listCustomers($user->tenant_id)->getData()
                : [],
            'statusOptions'    => InvoiceStatus::cases()
        ]);
    }

    /**
     * Store invoice from budget.
     */
    public function storeFromBudget(Budget $budget, InvoiceStoreFromBudgetRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $result = $this->invoiceService->createPartialInvoiceFromBudget($budget->code, $payload);

        if (!$result->isSuccess()) {
            return redirect()->back()
                ->withInput()
                ->with('error', $result->getMessage());
        }

        $invoice = $result->getData();
        return redirect()->route('invoices.show', $invoice->code)
            ->with('success', 'Fatura criada a partir do orçamento!');
    }

    /**
     * Search invoices via AJAX.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $limit = $request->get('limit', 10);

        $result = $this->invoiceService->searchInvoices($query, $limit);

        if (!$result->isSuccess()) {
            return response()->json([
                'success' => false,
                'message' => $result->getMessage()
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data'    => $result->getData()
        ]);
    }

    /**
     * Export invoices to Excel/CSV.
     */
    public function export(Request $request): BinaryFileResponse|JsonResponse
    {
        $format  = $request->get('format', 'xlsx');
        $filters = $request->only(['status', 'customer_id', 'date_from', 'date_to']);

        $result = $this->invoiceService->exportInvoices($filters, $format);

        if (!$result->isSuccess()) {
            return response()->json([
                'success' => false,
                'message' => $result->getMessage()
            ], 400);
        }

        $data = $result->getData();

        return response()->download($data['file_path'], $data['filename'])
            ->deleteFileAfterSend();
    }

    /**
     * AJAX endpoint para filtrar faturas.
     */
    public function ajaxFilter(Request $request): JsonResponse
    {
        $filters = $request->only(['status','customer_id','service_id','date_from','date_to','due_date_from','due_date_to','min_amount','max_amount','search','sort_by','sort_direction']);
        $result = $this->invoiceService->getFilteredInvoices($filters, ['customer:id,name','service:id,code,description','invoiceStatus']);
        return $result->isSuccess()
            ? response()->json(['success' => true, 'data' => $result->getData()])
            : response()->json(['success' => false, 'message' => $result->getMessage()], 400);
    }
}