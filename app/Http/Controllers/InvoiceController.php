<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\Invoice\InvoiceDTO;
use App\DTOs\Invoice\InvoiceFromBudgetDTO;
use App\DTOs\Invoice\InvoiceFromServiceDTO;
use App\DTOs\Invoice\InvoiceUpdateDTO;
use App\Enums\InvoiceStatus;
use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\InvoiceStoreRequest;
use App\Http\Requests\InvoiceUpdateRequest;
use App\Models\Budget;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use App\Services\Domain\CustomerService;
use App\Services\Domain\InvoiceService;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly CustomerService $customerService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['status', 'customer_id', 'date_from', 'date_to', 'search']);

        $result = $this->invoiceService->getFilteredInvoices($filters, [
            'customer:id,name',
            'service:id,code,description',
        ]);

        if ($result->isError()) {
            abort(500, 'Erro ao carregar lista de faturas');
        }

        $invoices = $result->getData();
        $customersResult = $this->customerService->listCustomers([]);

        return view('pages.invoice.index', [
            'invoices'      => $invoices,
            'filters'       => $filters,
            'statusOptions' => InvoiceStatus::cases(),
            'customers'     => $customersResult->isSuccess() ? $customersResult->getData() : [],
        ]);
    }

    public function dashboard(Request $request): View
    {
        $result = $this->invoiceService->getDashboardStats();

        if ($result->isError()) {
            abort(500, 'Erro ao carregar estatísticas do faturamento');
        }

        return view('pages.invoice.dashboard', [
            'stats' => $result->getData(),
        ]);
    }

    /**
     * Exibe formulário de criação de fatura.
     */
    public function create(Request $request): View
    {
        /** @var User $user */
        $user     = Auth::user();
        $products = Product::byTenant((int) ($user->tenant_id ?? 0))->active()->orderBy('name')->get();

        $customersResult = $this->customerService->listCustomers([]);

        return view('pages.invoice.create', [
            'service'       => null,
            'customers'     => $customersResult->isSuccess() ? $customersResult->getData() : [],
            'services'      => [],
            'products'      => $products,
            'statusOptions' => InvoiceStatus::cases(),
        ]);
    }

    public function store(InvoiceStoreRequest $request): RedirectResponse
    {
        $dto = InvoiceDTO::fromRequest($request->validated());
        $result = $this->invoiceService->createInvoice($dto);

        if ($result->isError()) {
            return redirect()->back()
                ->withInput()
                ->with('error', $result->getMessage());
        }

        $invoice = $result->getData();

        return redirect()->route('provider.invoices.show', $invoice->code)
            ->with('success', 'Fatura criada com sucesso!');
    }

    /**
     * Exibe detalhes de uma fatura específica.
     */
    public function show(Invoice $invoice): View
    {

        $invoice->load([
            'customer',
            'service',
            'invoiceItems',
            'paymentMercadoPagoInvoice',
        ]);

        return view('pages.invoice.show', [
            'invoice' => $invoice,
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
            'service',
        ]);

        $customersResult = $this->customerService->listCustomers([]);

        return view('pages.invoice.edit', [
            'invoice'       => $invoice,
            'customers'     => $customersResult->isSuccess() ? $customersResult->getData() : [],
            'services'      => [],
            'statusOptions' => InvoiceStatus::cases(),
        ]);
    }

    /**
     * Atualiza fatura existente.
     */
    public function update(InvoiceUpdateRequest $request, Invoice $invoice): RedirectResponse
    {
        $dto = InvoiceUpdateDTO::fromRequest($request->validated());
        $result = $this->invoiceService->updateInvoiceByCode($invoice->code, $dto);

        if ($result->isError()) {
            return redirect()->back()
                ->withInput()
                ->with('error', $result->getMessage());
        }

        return redirect()->route('provider.invoices.show', $invoice->code)
            ->with('success', 'Fatura atualizada com sucesso!');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $result = $this->invoiceService->deleteByCode($invoice->code);

        if ($result->isError()) {
            return redirect()->back()->with('error', $result->getMessage());
        }

        return redirect()->route('provider.invoices.index')
            ->with('success', 'Fatura excluída com sucesso!');
    }

    public function change_status(Invoice $invoice, Request $request): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', array_map(fn($case) => $case->value, InvoiceStatus::cases()))],
        ]);

        $result = $this->invoiceService->updateStatusByCode($invoice->code, $request->input('status'));

        if ($result->isError()) {
            return redirect()->back()->with('error', $result->getMessage());
        }

        return redirect()->route('provider.invoices.show', $invoice->code)
            ->with('success', 'Status da fatura alterado com sucesso!');
    }

    /**
     * Create invoice from budget.
     */
    public function createFromBudget(Budget $budget): View
    {
        $budget->load([
            'customer.commonData',
            'services.serviceItems.product',
        ]);

        $customersResult = $this->customerService->listCustomers([]);

        return view('pages.invoice.create-from-budget', [
            'budget'        => $budget,
            'customers'     => $customersResult->isSuccess() ? $customersResult->getData() : [],
            'statusOptions' => InvoiceStatus::cases(),
        ]);
    }

    /**
     * Create partial invoice from service (Interface para faturas parciais)
     */
    public function createPartialFromService(string $serviceCode): View|RedirectResponse
    {
        $result = $this->invoiceService->generateInvoiceDataFromService($serviceCode);

        if ($result->isError()) {
            return redirect()->back()
                ->with('error', $result->getMessage());
        }

        $invoiceData = $result->getData();
        $customersResult = $this->customerService->listCustomers([]);

        return view('pages.invoice.create-partial-from-service', [
            'invoiceData'   => $invoiceData,
            'serviceCode'   => $serviceCode,
            'customers'     => $customersResult->isSuccess() ? $customersResult->getData() : [],
            'statusOptions' => InvoiceStatus::cases(),
        ]);
    }

    /**
     * Create invoice from service (Nova funcionalidade do sistema antigo)
     */
    public function createFromService(string $serviceCode): View|RedirectResponse
    {
        $result = $this->invoiceService->generateInvoiceDataFromService($serviceCode);

        if ($result->isError()) {
            return redirect()->back()
                ->with('error', $result->getMessage());
        }

        $invoiceData = $result->getData();

        // Verificar se já existe fatura para este serviço
        if ($this->invoiceService->checkExistingInvoiceForService($invoiceData['service_id'])) {
            return redirect()->route('provider.invoices.index')
                ->with('error', 'Já existe uma fatura para este serviço.');
        }

        $customersResult = $this->customerService->listCustomers([]);

        return view('pages.invoice.create-from-service', [
            'invoiceData'   => $invoiceData,
            'serviceCode'   => $serviceCode,
            'customers'     => $customersResult->isSuccess() ? $customersResult->getData() : [],
            'statusOptions' => InvoiceStatus::cases(),
        ]);
    }

    /**
     * Store manual invoice from service (Nova funcionalidade para faturas manuais)
     */
    public function storeManualFromService(Request $request, string $serviceCode): RedirectResponse
    {
        $request->validate([
            'issue_date'         => 'required|date',
            'due_date'           => 'required|date|after_or_equal:issue_date',
            'notes'              => 'nullable|string|max:1000',
            'items'              => 'nullable|array',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity'   => 'required|numeric|min:0.01',
            'items.*.unit_value' => 'required|numeric|min:0.01',
        ]);

        $data = $request->all();
        $data['service_code'] = $serviceCode;
        $data['is_automatic'] = false;

        $dto = InvoiceFromServiceDTO::fromRequest($data);
        $result = $this->invoiceService->createInvoiceFromService($dto);

        if ($result->isError()) {
            return redirect()->back()
                ->withInput()
                ->with('error', $result->getMessage());
        }

        $invoice = $result->getData();

        return redirect()->route('provider.invoices.show', $invoice->code)
            ->with('success', 'Fatura manual criada com sucesso!');
    }

    /**
     * Store invoice from service (Nova funcionalidade do sistema antigo)
     */
    public function storeFromService(Request $request): RedirectResponse
    {
        $request->validate([
            'service_code' => 'required|string|exists:services,code',
            'issue_date'   => 'required|date',
            'due_date'     => 'required|date|after_or_equal:issue_date',
        ]);

        $dto = InvoiceFromServiceDTO::fromRequest($request->all());
        $result = $this->invoiceService->createInvoiceFromService($dto);

        if ($result->isError()) {
            return redirect()->back()
                ->withInput()
                ->with('error', $result->getMessage());
        }

        $invoice = $result->getData();

        return redirect()->route('provider.invoices.show', $invoice->code)
            ->with('success', 'Fatura gerada com sucesso a partir do serviço!');
    }

    /**
     * Store invoice from budget.
     */
    public function storeFromBudget(Budget $budget, InvoiceStoreFromBudgetRequest $request): RedirectResponse
    {
        $dto = InvoiceFromBudgetDTO::fromRequest($request->validated());
        $result = $this->invoiceService->createPartialInvoiceFromBudget($budget->code, $dto);

        if ($result->isError()) {
            return redirect()->back()
                ->withInput()
                ->with('error', $result->getMessage());
        }

        $invoice = $result->getData();

        return redirect()->route('provider.invoices.show', $invoice->code)
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
                'message' => $result->getMessage(),
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data'    => $result->getData(),
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
                'message' => $result->getMessage(),
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
        $filters = $request->only(['status', 'customer_id', 'service_id', 'date_from', 'date_to', 'due_date_from', 'due_date_to', 'min_amount', 'max_amount', 'search', 'sort_by', 'sort_direction']);
        $result  = $this->invoiceService->getFilteredInvoices($filters, ['customer:id,name', 'service:id,code,description', 'invoiceStatus']);

        return $result->isSuccess()
            ? response()->json(['success' => true, 'data' => $result->getData()])
            : response()->json(['success' => false, 'message' => $result->getMessage()], 400);
    }

    /**
     * Print invoice for provider access.
     */
    public function print(Invoice $invoice): View
    {
        /** @var User $user */
        $user = Auth::user();

        if ($invoice->tenant_id !== ($user->tenant_id ?? 0)) {
            abort(404);
        }

        $invoice->load([
            'customer.commonData',
            'service',
            'invoiceItems.product',
        ]);

        return view('pages.invoice.print', [
            'invoice' => $invoice,
        ]);
    }
}
