<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ServiceStatus;
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
use App\DTOs\Service\ServiceDTO;

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
        try {
            $budget = null;

            if ($budgetCode) {
                $budgetResult = $this->budgetService->findByCode($budgetCode);
                if ($budgetResult->isSuccess()) {
                    $budget = $budgetResult->getData();
                }
            }

            return view('pages.service.create', [
                'budget' => $budget,
                'categories' => $this->categoryService->list(['type' => 'service'])->getData(),
                'products' => $this->productService->list()->getData(),
            ]);
        } catch (Exception $e) {
            Log::error('Erro ao abrir formulário de criação de serviço', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Erro ao abrir formulário de criação de serviço');
        }
    }

    /**
     * Store a newly created service in storage.
     */
    public function store(ServiceStoreRequest $request): RedirectResponse
    {
        try {
            $dto = ServiceDTO::fromRequest($request->validated());
            $result = $this->serviceService->create($dto);

            if ($result->isError()) {
                return redirect()->back()->withInput()->with('error', $result->getMessage());
            }

            return redirect()->route('provider.services.show', $result->getData()->code)
                ->with('success', 'Serviço criado com sucesso');
        } catch (Exception $e) {
            Log::error('Erro ao criar serviço', ['error' => $e->getMessage()]);
            return redirect()->back()->withInput()->with('error', 'Erro ao criar serviço');
        }
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

        return view('pages.service.show', ['service' => $result->getData()]);
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

        return view('pages.service.edit', [
            'service' => $result->getData(),
            'categories' => $this->categoryService->list(['type' => 'service'])->getData(),
            'products' => $this->productService->list()->getData(),
        ]);
    }

    /**
     * Update the specified service in storage.
     */
    public function update(ServiceUpdateRequest $request, string $code): RedirectResponse
    {
        try {
            $dto = ServiceDTO::fromRequest($request->validated());
            $result = $this->serviceService->update($code, $dto);

            if ($result->isError()) {
                return redirect()->back()->withInput()->with('error', $result->getMessage());
            }

            return redirect()->route('provider.services.show', $result->getData()->code)
                ->with('success', 'Serviço atualizado com sucesso');
        } catch (Exception $e) {
            Log::error('Erro ao atualizar serviço', ['error' => $e->getMessage()]);
            return redirect()->back()->withInput()->with('error', 'Erro ao atualizar serviço');
        }
    }

    /**
     * Change service status.
     */
    public function toggleStatus(Request $request, string $code): RedirectResponse
    {
        try {
            $result = $this->serviceService->changeStatusByCode($code, (string) $request->input('status'));

            if ($result->isError()) {
                return redirect()->back()->with('error', $result->getMessage());
            }

            return redirect()->back()->with('success', 'Status atualizado com sucesso');
        } catch (Exception $e) {
            Log::error('Erro ao atualizar status do serviço', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Erro ao atualizar status do serviço');
        }
    }

    /**
     * Remove the specified service from storage.
     */
    public function destroy(string $code): RedirectResponse
    {
        try {
            $result = $this->serviceService->deleteByCode($code);

            if ($result->isError()) {
                return redirect()->back()->with('error', $result->getMessage());
            }

            return redirect()->route('provider.services.dashboard')->with('success', 'Serviço excluído com sucesso');
        } catch (Exception $e) {
            Log::error('Erro ao excluir serviço', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Erro ao excluir serviço');
        }
    }
}
