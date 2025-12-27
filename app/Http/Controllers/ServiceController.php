<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\Service\ServiceDTO;
use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\ServiceStoreRequest;
use App\Http\Requests\ServiceUpdateRequest;
use App\Models\Service;
use App\Services\Domain\BudgetService;
use App\Services\Domain\CategoryService;
use App\Services\Domain\ProductService;
use App\Services\Domain\ServiceService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
    public function create(?string $budgetCode = null): View
    {
        $this->authorize('create', Service::class);
        $budget = null;

        if ($budgetCode) {
            $budgetResult = $this->budgetService->findByCode($budgetCode);
            if ($budgetResult->isSuccess()) {
                $budget = $budgetResult->getData();
                $this->authorize('view', $budget);
            }
        }

        return view('pages.service.create', [
            'budget' => $budget,
            'categories' => $this->categoryService->list(['type' => 'service'])->getData(),
            'products' => $this->productService->list()->getData(),
        ]);
    }

    /**
     * Store a newly created service in storage.
     */
    public function store(ServiceStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Service::class);
        $dto = ServiceDTO::fromRequest($request->validated());
        $result = $this->serviceService->create($dto);

        if ($result->isError()) {
            return redirect()->back()->withInput()->with('error', $result->getMessage());
        }

        return redirect()->route('provider.services.show', $result->getData()->code)
            ->with('success', 'Serviço criado com sucesso!');
    }

    /**
     * Display the specified service.
     */
    public function show(string $code): View
    {
        $result = $this->serviceService->findByCode($code, ['budget.customer', 'category', 'items.product', 'statusHistory']);

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
        $result = $this->serviceService->findByCode($code, ['items.product']);

        if ($result->isError()) {
            abort(404, $result->getMessage());
        }

        $service = $result->getData();
        $this->authorize('update', $service);

        return view('pages.service.edit', [
            'service' => $service,
            'categories' => $this->categoryService->list(['type' => 'service'])->getData(),
            'products' => $this->productService->list()->getData(),
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
    public function toggleStatus(Request $request, string $code): RedirectResponse
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
