<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\Invoice\InvoiceDTO;
use App\DTOs\Invoice\InvoiceFromBudgetDTO;
use App\DTOs\Invoice\InvoiceFromServiceDTO;
use App\DTOs\Invoice\InvoiceUpdateDTO;
use App\Enums\InvoiceStatus;
use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\InvoiceStoreFromBudgetRequest;
use App\Http\Requests\InvoiceStoreRequest;
use App\Http\Requests\InvoiceUpdateRequest;
use App\Models\Budget;
use App\Models\Invoice;
use App\Repositories\ProductRepository;
use App\Services\Domain\BudgetService;
use App\Services\Domain\CustomerService;
use App\Services\Domain\InvoiceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly CustomerService $customerService,
        private readonly ProductRepository $productRepository,
        private readonly BudgetService $budgetService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Invoice::class);
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
            'invoices' => $invoices,
            'filters' => $filters,
            'statusOptions' => InvoiceStatus::cases(),
            'customers' => $customersResult->isSuccess() ? $customersResult->getData() : [],
        ]);
    }

    public function dashboard(Request $request): View
    {
        $this->authorize('viewAny', Invoice::class);
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
        $this->authorize('create', Invoice::class);
        $products = $this->productRepository->getAllByTenant(['active' => true], ['name' => 'asc']);

        $customersResult = $this->customerService->listCustomers([]);

        return view('pages.invoice.create', [
            'service' => null,
            'customers' => $customersResult->isSuccess() ? $customersResult->getData() : [],
            'services' => [],
            'products' => $products,
            'statusOptions' => InvoiceStatus::cases(),
        ]);
    }

    public function store(InvoiceStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Invoice::class);
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
    public function show(string $code): View
    {
        $result = $this->invoiceService->findByCode($code, [
            'customer',
            'service',
            'invoiceItems',
            'paymentMercadoPagoInvoice',
        ]);

        if ($result->isError()) {
            abort(404, $result->getMessage());
        }

        $invoice = $result->getData();
        $this->authorize('view', $invoice);

        return view('pages.invoice.show', [
            'invoice' => $invoice,
        ]);
    }

    /**
     * Exibe formulário de edição de fatura.
     */
    public function edit(string $code): View
    {
        $result = $this->invoiceService->findByCode($code, [
            'invoiceItems.product',
            'customer',
            'service',
        ]);

        if ($result->isError()) {
            abort(404, $result->getMessage());
        }

        $invoice = $result->getData();
        $this->authorize('update', $invoice);

        $customersResult = $this->customerService->listCustomers([]);

        return view('pages.invoice.edit', [
            'invoice' => $invoice,
            'customers' => $customersResult->isSuccess() ? $customersResult->getData() : [],
            'services' => [],
            'statusOptions' => InvoiceStatus::cases(),
        ]);
    }

    /**
     * Atualiza fatura existente.
     */
    public function update(InvoiceUpdateRequest $request, string $code): RedirectResponse
    {
        $result = $this->invoiceService->findByCode($code);
        if ($result->isError()) {
            return redirect()->back()->with('error', $result->getMessage());
        }

        $invoice = $result->getData();
        $this->authorize('update', $invoice);

        $dto = InvoiceUpdateDTO::fromRequest($request->validated());
        $updateResult = $this->invoiceService->updateInvoiceByCode($code, $dto);

        if ($updateResult->isError()) {
            return redirect()->back()
                ->withInput()
                ->with('error', $updateResult->getMessage());
        }

        return redirect()->route('provider.invoices.show', $code)
            ->with('success', 'Fatura atualizada com sucesso!');
    }

    public function destroy(string $code): RedirectResponse
    {
        $result = $this->invoiceService->findByCode($code);
        if ($result->isError()) {
            return redirect()->back()->with('error', $result->getMessage());
        }

        $invoice = $result->getData();
        $this->authorize('delete', $invoice);

        $deleteResult = $this->invoiceService->deleteByCode($code);

        if ($deleteResult->isError()) {
            return redirect()->back()->with('error', $deleteResult->getMessage());
        }

        return redirect()->route('provider.invoices.index')
            ->with('success', 'Fatura excluída com sucesso!');
    }

    public function change_status(string $code, Request $request): RedirectResponse
    {
        $result = $this->invoiceService->findByCode($code);
        if ($result->isError()) {
            return redirect()->back()->with('error', $result->getMessage());
        }

        $invoice = $result->getData();
        $this->authorize('update', $invoice);

        $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', array_map(fn ($case) => $case->value, InvoiceStatus::cases()))],
        ]);

        $statusResult = $this->invoiceService->updateStatusByCode($code, $request->input('status'));

        if ($statusResult->isError()) {
            return redirect()->back()->with('error', $statusResult->getMessage());
        }

        return redirect()->route('provider.invoices.show', $code)
            ->with('success', 'Status da fatura alterado com sucesso!');
    }

    /**
     * Create invoice from budget.
     */
    public function createFromBudget(string $budgetCode): View
    {
        $result = $this->budgetService->findByCode($budgetCode, [
            'customer.commonData',
            'services.serviceItems.product',
        ]);

        if ($result->isError()) {
            abort(404, $result->getMessage());
        }

        $budget = $result->getData();
        $this->authorize('view', $budget);

        $customersResult = $this->customerService->listCustomers([]);

        return view('pages.invoice.create-from-budget', [
            'budget' => $budget,
            'customers' => $customersResult->isSuccess() ? $customersResult->getData() : [],
            'statusOptions' => InvoiceStatus::cases(),
        ]);
    }

    /**
     * Create partial invoice from service (Interface para faturas parciais)
     */
    public function createPartialFromService(string $serviceCode): View|RedirectResponse
    {
        $this->authorize('create', Invoice::class);
        $result = $this->invoiceService->generateInvoiceDataFromService($serviceCode);

        if ($result->isError()) {
            return redirect()->back()
                ->with('error', $result->getMessage());
        }

        $invoiceData = $result->getData();
        $customersResult = $this->customerService->listCustomers([]);

        return view('pages.invoice.create-partial-from-service', [
            'invoiceData' => $invoiceData,
            'serviceCode' => $serviceCode,
            'customers' => $customersResult->isSuccess() ? $customersResult->getData() : [],
            'statusOptions' => InvoiceStatus::cases(),
        ]);
    }

    /**
     * Create invoice from service (Nova funcionalidade do sistema antigo)
     */
    public function createFromService(string $serviceCode): View|RedirectResponse
    {
        $this->authorize('create', Invoice::class);
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
            'invoiceData' => $invoiceData,
            'serviceCode' => $serviceCode,
            'customers' => $customersResult->isSuccess() ? $customersResult->getData() : [],
            'statusOptions' => InvoiceStatus::cases(),
        ]);
    }

    /**
     * Store manual invoice from service (Nova funcionalidade para faturas manuais)
     */
    public function storeManualFromService(Request $request, string $serviceCode): RedirectResponse
    {
        $this->authorize('create', Invoice::class);
        $request->validate([
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'notes' => 'nullable|string|max:1000',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
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
        $this->authorize('create', Invoice::class);
        $request->validate([
            'service_code' => 'required|string|exists:services,code',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
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
        $this->authorize('view', $budget);
        $this->authorize('create', Invoice::class);

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
        $this->authorize('viewAny', Invoice::class);
        $query = $request->get('q', '');
        $limit = $request->get('limit', 10);

        $result = $this->invoiceService->searchInvoices($query, $limit);

        if (! $result->isSuccess()) {
            return response()->json([
                'success' => false,
                'message' => $result->getMessage(),
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $result->getData(),
        ]);
    }

    /**
     * Export invoices to Excel/CSV.
     */
    public function export(Request $request): BinaryFileResponse|JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);
        $format = $request->get('format', 'xlsx');
        $filters = $request->only(['status', 'customer_id', 'date_from', 'date_to']);

        $result = $this->invoiceService->exportInvoices($filters, $format);

        if (! $result->isSuccess()) {
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
        $this->authorize('viewAny', Invoice::class);
        $filters = $request->only(['status', 'customer_id', 'service_id', 'date_from', 'date_to', 'due_date_from', 'due_date_to', 'min_amount', 'max_amount', 'search', 'sort_by', 'sort_direction']);
        $result = $this->invoiceService->getFilteredInvoices($filters, ['customer:id,name', 'service:id,code,description', 'invoiceStatus']);

        return $result->isSuccess()
            ? response()->json(['success' => true, 'data' => $result->getData()])
            : response()->json(['success' => false, 'message' => $result->getMessage()], 400);
    }

    /**
     * Print invoice for provider access.
     */
    public function print(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);
        $result = $this->invoiceService->findByCode($invoice->code, [
            'customer.commonData',
            'service',
            'invoiceItems.product',
        ]);

        if ($result->isError()) {
            abort(404, $result->getMessage());
        }

        return view('pages.invoice.print', [
            'invoice' => $result->getData(),
        ]);
    }
}
