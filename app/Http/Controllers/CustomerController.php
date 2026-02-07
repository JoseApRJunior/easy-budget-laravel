<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\Customer\CustomerDTO;
use App\DTOs\Customer\CustomerFilterDTO;
use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\CustomerRequest;
use App\Http\Requests\CustomerUpdateRequest;
use App\Services\Domain\CustomerExportService;
use App\Services\Domain\CustomerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller de Clientes
 *
 * Gerencia CRUD de clientes (Pessoa Física e Jurídica) com separação
 * de validação e processamento.
 */
class CustomerController extends Controller
{
    public function __construct(
        private CustomerService $customerService,
        private CustomerExportService $customerExportService,
    ) {}

    /**
     * Mostrar lista de clientes.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', \App\Models\Customer::class);

        if (empty($request->query())) {
            // Inicialmente a lista começa vazia, seguindo o padrão de Category e Product
            $filters = [
                'active' => 'active',
                'deleted' => 'current',
                'per_page' => 10,
            ];
            $result = $this->emptyResult();
        } else {
            $filterDTO = CustomerFilterDTO::fromRequest($request->all());
            $result = $this->customerService->getFilteredCustomers($filterDTO);
            $filters = $filterDTO->toViewArray();
        }

        $areasOfActivity = $this->customerService->getAreasOfActivity()->getData() ?? [];

        return $this->view('pages.customer.index', $result, 'customers', [
            'filters' => $filters,
            'areas_of_activity' => $areasOfActivity,
        ]);
    }

    /**
     * Mostrar formulário de criação de cliente.
     */
    public function create(): View
    {
        $this->authorize('create', \App\Models\Customer::class);

        // Dados necessários para o formulário
        $areasOfActivity = $this->customerService->getAreasOfActivity()->getData() ?? [];
        $professions = $this->customerService->getProfessions()->getData() ?? [];

        return view('pages.customer.create', [
            'areas_of_activity' => $areasOfActivity ?? [],
            'professions' => $professions ?? [],
            'customer' => null, // Removido new \App\Models\Customer
        ]);
    }

    /**
     * Criar cliente (Pessoa Física ou Jurídica) - Método unificado
     */
    public function store(CustomerRequest $request): RedirectResponse
    {
        $this->authorize('create', \App\Models\Customer::class);

        $dto = CustomerDTO::fromRequest($request->validated());
        $result = $this->customerService->createCustomer($dto);

        return $this->redirectBackWithServiceResult(
            $result,
            'Cliente criado com sucesso! Você pode cadastrar outro cliente agora.'
        );
    }

    /**
     * Mostrar cliente específico.
     */
    public function show(string $id): View
    {
        $result = $this->customerService->findCustomer((int) $id);

        if (! $result->isSuccess()) {
            abort(404, $result->getMessage());
        }

        $customer = $result->getData();
        $this->authorize('view', $customer);

        return view('pages.customer.show', [
            'customer' => $customer,
        ]);
    }

    /**
     * Mostrar formulário de edição de cliente.
     */
    public function edit(string $id): View
    {
        $result = $this->customerService->findCustomer((int) $id);

        if (! $result->isSuccess()) {
            abort(404, $result->getMessage());
        }

        $customer = $result->getData();
        $this->authorize('update', $customer);

        // Dados necessários para o formulário
        $areasOfActivity = $this->customerService->getAreasOfActivity()->getData() ?? [];
        $professions = $this->customerService->getProfessions()->getData() ?? [];

        return view('pages.customer.edit', [
            'customer' => $customer,
            'areas_of_activity' => $areasOfActivity,
            'professions' => $professions,
        ]);
    }

    /**
     * Atualizar cliente (Pessoa Física ou Jurídica) - Método unificado
     */
    public function update(CustomerUpdateRequest $request, \App\Models\Customer $customer): RedirectResponse
    {
        $this->authorize('update', $customer);

        $dto = CustomerDTO::fromRequest($request->validated());
        $result = $this->customerService->updateCustomer((int) $customer->id, $dto);

        return $this->redirectBackWithServiceResult(
            $result,
            'Cliente atualizado com sucesso!'
        );
    }

    /**
     * Excluir cliente.
     */
    public function destroy(\App\Models\Customer $customer): RedirectResponse
    {
        $this->authorize('delete', $customer);

        $result = $this->customerService->deleteCustomer((int) $customer->id);

        return $this->redirectWithServiceResult(
            'provider.customers.index',
            $result,
            'Cliente excluído com sucesso!'
        );
    }

    /**
     * Alterar status do cliente (ativo/inativo).
     */
    public function toggleStatus(Request $request, \App\Models\Customer $customer): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorize('toggleStatus', $customer);

        $result = $this->customerService->toggleStatus((int) $customer->id);

        if ($request->ajax() || $request->expectsJson()) {
            return $this->jsonResponse($result);
        }

        return $this->redirectWithServiceResult(
            'provider.customers.index',
            $result,
            'Status do cliente alterado com sucesso!'
        );
    }

    /**
     * Restaurar cliente excluído.
     */
    public function restore(string $id): RedirectResponse
    {
        $result = $this->customerService->findCustomer((int) $id);
        if ($result->isSuccess()) {
            $this->authorize('restore', $result->getData());
        }

        $result = $this->customerService->restoreCustomer((int) $id);

        return $this->redirectWithServiceResult(
            'provider.customers.index',
            $result,
            'Cliente restaurado com sucesso!'
        );
    }

    /**
     * Buscar clientes próximos (por CEP).
     */
    public function findNearby(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', \App\Models\Customer::class);

        $cep = $request->get('cep');

        if (! $cep) {
            return redirect()->route('provider.customers.index')
                ->with('error', 'CEP é obrigatório para busca por proximidade.');
        }

        // Redirecionar para a página principal com o filtro de CEP
        return redirect()->route('provider.customers.index', ['cep' => $cep])
            ->with('info', "Resultados filtrados pelo CEP: {$cep}");
    }

    /**
     * Buscar clientes (AJAX).
     */
    public function search(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Customer::class);

        $filterDTO = CustomerFilterDTO::fromRequest($request->all());
        $result = $this->customerService->getFilteredCustomers($filterDTO);

        if (! $result->isSuccess()) {
            return $this->jsonResponse($result);
        }

        /** @var \Illuminate\Pagination\LengthAwarePaginator $customers */
        $customers = $result->getData();

        $data = collect($customers->items())->map(function ($customer) {
            $commonData = $customer->commonData;
            $contact = $customer->contact;
            $address = $customer->address;

            $name = $commonData ? ($commonData->company_name ?: trim(($commonData->first_name ?? '').' '.($commonData->last_name ?? ''))) : 'Nome não informado';

            return [
                'id' => $customer->id,
                'customer_name' => $name,
                'cpf' => $commonData->cpf ?? '',
                'cnpj' => $commonData->cnpj ?? '',
                'email' => $contact->email_personal ?? '',
                'email_business' => $contact->email_business ?? '',
                'phone' => $contact->phone_personal ?? '',
                'phone_business' => $contact->phone_business ?? '',
                'status' => $customer->status,
                'status_label' => $customer->status_label ?? ucfirst((string) $customer->status),
                'created_at' => $customer->created_at?->toISOString(),
                'deleted_at' => $customer->deleted_at?->toISOString(),
            ];
        });

        return $this->jsonSuccess($data);
    }

    /**
     * Autocompletar clientes (AJAX).
     */
    public function autocomplete(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Customer::class);

        $query = $request->get('q', '');

        $result = $this->customerService->searchForAutocomplete($query);

        return $this->jsonResponse($result);
    }

    /**
     * Exportar clientes.
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', \App\Models\Customer::class);

        $filterDTO = CustomerFilterDTO::fromRequest($request->all());
        $result = $this->customerService->getFilteredCustomers($filterDTO, false);

        if ($result->isError()) {
            return $this->redirectError('provider.customers.index', 'Erro ao buscar clientes para exportação.');
        }

        $format = $request->get('format', 'xlsx');

        if ($format === 'pdf') {
            return $this->customerExportService->exportToPdf(
                $result->getData(),
                'clientes',
                'A4-L',
                $filterDTO->toViewArray()
            );
        }

        return $this->customerExportService->exportToExcel($result->getData(), $format);
    }

    /**
     * Dashboard de clientes.
     */
    public function dashboard(): View
    {
        $this->authorize('viewAny', \App\Models\Customer::class);

        $result = $this->customerService->getDashboardData();

        if (! $result->isSuccess()) {
            return view('pages.customer.dashboard', [
                'stats' => [],
                'error' => $result->getMessage(),
            ]);
        }

        return view('pages.customer.dashboard', [
            'stats' => $result->getData(),
        ]);
    }
}
