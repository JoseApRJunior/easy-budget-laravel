<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\Service\ServiceDTO;
use App\Enums\ServiceStatus;
use App\Helpers\DateHelper;
use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\ServiceStoreRequest;
use App\Http\Requests\ServiceUpdateRequest;
use App\Models\Service;
use App\Services\Domain\BudgetService;
use App\Services\Domain\CategoryService;
use App\Services\Domain\ProductService;
use App\Services\Domain\ServiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Controller para gestão de serviços - Interface Web
 */
class ServiceController extends Controller
{
    protected ServiceService $serviceService;

    protected CategoryService $categoryService;

    protected BudgetService $budgetService;

    protected ProductService $productService;

    public function __construct(
        ServiceService $serviceService,
        CategoryService $categoryService,
        BudgetService $budgetService,
        ProductService $productService,
    ) {
        $this->serviceService = $serviceService;
        $this->categoryService = $categoryService;
        $this->budgetService = $budgetService;
        $this->productService = $productService;
    }

    /**
     * List all services for the current tenant.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Service::class);

        $filters = $request->validate([
            'status' => ['nullable', 'string', Rule::in(array_column(ServiceStatus::cases(), 'value'))],
            'category_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string'],
            'start_date' => ['nullable', 'string'],
            'end_date' => ['nullable', 'string'],
        ]);

        // Normalizar datas para o banco
        if (isset($filters['start_date'])) {
            $filters['start_date'] = DateHelper::parseDate($filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $filters['end_date'] = DateHelper::parseDate($filters['end_date']);
        }

        $result = $this->serviceService->paginate($filters);

        if ($result->isError()) {
            abort(500, 'Erro ao carregar lista de serviços.');
        }

        return view('pages.service.index', [
            'services' => $result->getData(),
            'categories' => $this->categoryService->list(['type' => 'service'])->getData(),
            'statuses' => ServiceStatus::getOptions(),
            'filters' => $filters,
        ]);
    }

    /**
     * Dashboard de serviços
     */
    public function dashboard(Request $request): View
    {
        $this->authorize('viewAny', Service::class);
        $result = $this->serviceService->getDashboardStats();

        if ($result->isError()) {
            abort(500, 'Erro ao carregar estatísticas do dashboard.');
        }

        return view('pages.service.dashboard', [
            'stats' => $result->getData(),
        ]);
    }

    /**
     * Show the form for creating a new service.
     */
    public function create(?string $budgetCode = null): View|RedirectResponse
    {
        $this->authorize('create', Service::class);
        $budget = null;

        if ($budgetCode) {
            $budgetResult = $this->budgetService->findByCode($budgetCode);
            if ($budgetResult->isSuccess()) {
                $budget = $budgetResult->getData();
                $this->authorize('view', $budget);

                // Verificar se orçamento está editável (skill: budget-lifecycle-rules)
                if (! $budget->canBeEdited()) {
                    return redirect()->route('provider.budgets.show', $budget->code)
                        ->with('error', 'Orçamentos com status '.$budget->status->label().' não podem ter serviços adicionados.');
                }
            }
        }

        // Create requires selecting a budget, so we list them
        $budgetsResult = $this->budgetService->getBudgetsForProvider(['per_page' => 100]);
        $budgets = $budgetsResult->isSuccess() ? $budgetsResult->getData() : [];

        // Gera o próximo código para preview
        $nextCodeResult = $this->serviceService->generateNextCode();
        $nextCode = $nextCodeResult->isSuccess() ? $nextCodeResult->getData() : 'SRV000001';

        return view('pages.service.create', [
            'budget' => $budget,
            'budgets' => $budgets,
            'categories' => $this->categoryService->list(['type' => 'service'])->getData() ?? collect(),
            'products' => $this->productService->list()->getData() ?? collect(),
            'statusOptions' => [ServiceStatus::DRAFT], // Apenas rascunho na criação
            'nextCode' => $nextCode,
        ]);
    }

    /**
     * Store a newly created service in storage.
     */
    public function store(ServiceStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Service::class);
        $dto = ServiceDTO::fromRequest($request->getValidatedData());
        $result = $this->serviceService->create($dto);

        if ($result->isError()) {
            return redirect()->back()->withInput()->with('error', $result->getMessage());
        }

        return $this->redirectBackWithServiceResult(
            $result,
            'Serviço criado com sucesso! Você pode cadastrar outro agora.'
        );
    }

    /**
     * Display the specified service.
     */
    public function show(string $code): View
    {
        $result = $this->serviceService->findByCode($code, [
            'customer.commonData',
            'customer.contact',
            'customer.address',
            'budget',
            'category',
            'serviceItems.product',
        ]);

        if ($result->isError()) {
            abort(404, $result->getMessage());
        }

        $service = $result->getData();
        $this->authorize('view', $service);

        return view('pages.service.show', ['service' => $service]);
    }

    /**
     * Show the form for editing the specified service.
     */
    public function edit(string $code): View
    {
        $result = $this->serviceService->findByCode($code, ['serviceItems.product']);

        if ($result->isError()) {
            abort(404, $result->getMessage());
        }

        $service = $result->getData();
        $this->authorize('update', $service);

        return view('pages.service.edit', [
            'service' => $service,
            'categories' => $this->categoryService->list(['type' => 'service'])->getData() ?? collect(),
            'products' => $this->productService->list()->getData() ?? collect(),
            'budgets' => $this->budgetService->list()->getData() ?? collect(),
            'statusOptions' => ServiceStatus::cases(),
        ]);
    }

    /**
     * Update the specified service in storage.
     */
    public function update(ServiceUpdateRequest $request, string $code): RedirectResponse
    {
        $result = $this->serviceService->findByCode($code);
        if ($result->isError()) {
            return redirect()->back()->with('error', $result->getMessage());
        }

        $service = $result->getData();
        $this->authorize('update', $service);

        $dto = ServiceDTO::fromRequest($request->validated());
        $updateResult = $this->serviceService->update($code, $dto);

        if ($updateResult->isError()) {
            return redirect()->back()->withInput()->with('error', $updateResult->getMessage());
        }

        return redirect()->route('provider.services.show', $updateResult->getData()->code)
            ->with('success', 'Serviço atualizado com sucesso');
    }

    /**
     * Change service status.
     */
    public function change_status(Request $request, string $code): RedirectResponse
    {
        $result = $this->serviceService->findByCode($code);
        if ($result->isError()) {
            return redirect()->back()->with('error', $result->getMessage());
        }

        $service = $result->getData();
        $this->authorize('update', $service);

        $statusResult = $this->serviceService->changeStatusByCode($code, (string) $request->input('status'));

        if ($statusResult->isError()) {
            return redirect()->back()->with('error', $statusResult->getMessage());
        }

        return redirect()->back()->with('success', 'Status do serviço atualizado com sucesso');
    }

    /**
     * Cancel service.
     */
    public function cancel(string $code): RedirectResponse
    {
        $result = $this->serviceService->findByCode($code);
        if ($result->isError()) {
            return redirect()->back()->with('error', $result->getMessage());
        }

        $service = $result->getData();
        $this->authorize('update', $service);

        $cancelResult = $this->serviceService->changeStatusByCode($code, ServiceStatus::CANCELLED->value);

        if ($cancelResult->isError()) {
            return redirect()->back()->with('error', $cancelResult->getMessage());
        }

        return redirect()->back()->with('success', 'Serviço cancelado com sucesso');
    }

    /**
     * Search services via AJAX.
     */
    public function search(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('viewAny', Service::class);
        $search = $request->input('q');
        $result = $this->serviceService->list(['search' => $search, 'limit' => 10]);

        return response()->json($result->getData());
    }

    /**
     * AJAX filter for services.
     */
    public function ajaxFilter(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('viewAny', Service::class);
        $filters = $request->all();
        $result = $this->serviceService->list($filters);

        return response()->json($result->getData());
    }

    /**
     * Visualiza o status de um serviço publicamente.
     */
    public function viewServiceStatus(string $code, string $token): View
    {
        $result = $this->serviceService->findByCode($code, [
            'budget.customer.commonData',
            'budget.customer.contact',
            'budget.customer.address',
            'budget.services.category',
            'category',
            'serviceItems.product',
            'schedules',
            'tenant.provider.businessData',
            'tenant.provider.commonData',
            'tenant.provider.contact',
            'tenant.provider.address',
        ]);

        if ($result->isError()) {
            abort(404, 'Serviço não encontrado.');
        }

        $service = $result->getData();

        // Validar o token
        if ($service->public_token !== $token) {
            abort(403, 'Acesso negado. Token inválido.');
        }

        return view('pages.service.public.view-status', [
            'service' => $service,
            'token' => $token,
            'title' => "Status do Serviço - {$service->code}",
        ]);
    }

    /**
     * Atualiza o status de um serviço via link público (ação do cliente).
     */
    public function chooseServiceStatus(Request $request): RedirectResponse
    {
        $request->validate([
            'service_code' => ['required', 'string'],
            'token' => ['required', 'string'],
            'service_status_id' => ['required', 'string', Rule::in([
                ServiceStatus::APPROVED->value,
                ServiceStatus::REJECTED->value,
                ServiceStatus::CANCELLED->value,
                ServiceStatus::SCHEDULED->value,
            ])],
        ]);

        $code = $request->input('service_code');
        $token = $request->input('token');
        $newStatus = $request->input('service_status_id');

        $result = $this->serviceService->findByCode($code);
        if ($result->isError()) {
            abort(404, 'Serviço não encontrado.');
        }

        $service = $result->getData();

        // Validar o token
        if ($service->public_token !== $token) {
            abort(403, 'Acesso negado.');
        }

        $updateResult = $this->serviceService->changeStatusByCode($code, $newStatus);

        if ($updateResult->isError()) {
            return redirect()->back()->with('error', $updateResult->getMessage());
        }

        return redirect()->back()->with('success', 'Status do serviço atualizado com sucesso!');
    }

    /**
     * Versão para impressão do status do serviço.
     */
    public function print(string $code, string $token): View
    {
        $result = $this->serviceService->findByCode($code, [
            'budget.customer',
            'category',
            'serviceItems.product',
        ]);

        if ($result->isError()) {
            abort(404, 'Serviço não encontrado.');
        }

        $service = $result->getData();

        if ($service->public_token !== $token) {
            abort(403, 'Acesso negado.');
        }

        return view('pages.service.public.print', [
            'service' => $service,
        ]);
    }

    /**
     * Remove the specified service from storage.
     */
    public function destroy(string $code): RedirectResponse
    {
        $result = $this->serviceService->findByCode($code);
        if ($result->isError()) {
            return redirect()->back()->with('error', $result->getMessage());
        }

        $service = $result->getData();
        $this->authorize('delete', $service);

        $deleteResult = $this->serviceService->deleteByCode($code);

        if ($deleteResult->isError()) {
            return redirect()->back()->with('error', $deleteResult->getMessage());
        }

        return redirect()->route('provider.services.index')
            ->with('success', 'Serviço excluído com sucesso');
    }
}
