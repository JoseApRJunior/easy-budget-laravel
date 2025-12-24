<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\CustomerRequest;
use App\Http\Requests\CustomerUpdateRequest;
use App\Models\AreaOfActivity;
use App\Models\Profession;
use App\Models\User;
use App\Services\Domain\CustomerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
    ) {}

    /**
     * Mostrar lista de clientes.
     */
    public function index(Request $request): View
    {
        $filters        = $request->only(['search', 'status', 'type', 'area_of_activity_id', 'deleted']);
        $perPage        = (int) ($filters['per_page'] ?? 10);
        $allowedPerPage = [10, 20, 50];

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }
        $filters['per_page'] = $perPage;

        try {
            // Se não houver filtros, mostramos uma lista vazia ou inicial?
            // O padrão do sistema parece ser mostrar os registros mesmo sem busca.
            $result = $this->customerService->getFilteredCustomers($filters);

            $areasOfActivity = AreaOfActivity::where('is_active', true)->orderBy('name')->get();

            return $this->view('pages.customer.index', $result, 'customers', [
                'filters'           => $filters,
                'areas_of_activity' => $areasOfActivity,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao carregar clientes', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            abort(500, 'Erro ao carregar clientes');
        }
    }

    /**
     * Mostrar formulário de criação de cliente.
     */
    public function create(): View
    {
        /** @var User $user */
        $user = Auth::user();

        // Dados necessários para o formulário
        $areasOfActivity = AreaOfActivity::where('is_active', true)
            ->orderBy('name')
            ->get();

        $professions = Profession::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('pages.customer.create', [
            'areas_of_activity' => $areasOfActivity,
            'professions'       => $professions,
            'customer'          => new \App\Models\Customer,
        ]);
    }

    /**
     * Criar cliente (Pessoa Física ou Jurídica) - Método unificado
     */
    public function store(CustomerRequest $request): RedirectResponse
    {
        try {
            $dto = \App\DTOs\Customer\CustomerDTO::fromRequest($request->validated());

            // Usar o serviço para criar cliente
            $result = $this->customerService->createCustomer($dto);

            // Verificar resultado do serviço
            if (!$result->isSuccess()) {
                return redirect()
                    ->route('provider.customers.create')
                    ->with('error', $result->getMessage())
                    ->withInput();
            }

            return redirect()
                ->route('provider.customers.create')
                ->with('success', 'Cliente criado com sucesso! Você pode cadastrar outro cliente agora.');
        } catch (\Exception $e) {
            Log::error('Erro inesperado ao criar cliente', [
                'user_id'   => auth()->id(),
                'tenant_id' => auth()->user()?->tenant_id,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            return redirect()
                ->route('provider.customers.create')
                ->with('error', 'Erro interno ao criar cliente. Tente novamente.')
                ->withInput();
        }
    }

    /**
     * Mostrar cliente específico.
     */
    public function show(string $id): View
    {
        $result = $this->customerService->findCustomer((int) $id);

        if (!$result->isSuccess()) {
            abort(404, $result->getMessage());
        }

        return view('pages.customer.show', [
            'customer' => $result->getData(),
        ]);
    }

    /**
     * Mostrar formulário de edição de cliente.
     */
    public function edit(string $id): View
    {
        $result = $this->customerService->findCustomer((int) $id);

        if (!$result->isSuccess()) {
            abort(404, $result->getMessage());
        }

        $customer = $result->getData();

        // Dados necessários para o formulário
        $areasOfActivity = AreaOfActivity::where('is_active', true)
            ->orderBy('name')
            ->get();

        $professions = \App\Models\Profession::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('pages.customer.edit', [
            'customer'          => $customer,
            'areas_of_activity' => $areasOfActivity,
            'professions'       => $professions,
        ]);
    }

    /**
     * Atualizar cliente (Pessoa Física ou Jurídica) - Método unificado
     */
    public function update(CustomerUpdateRequest $request, \App\Models\Customer $customer): RedirectResponse
    {
        try {
            $dto = \App\DTOs\Customer\CustomerDTO::fromRequest($request->validated());

            // Usar o serviço para atualizar cliente
            $result = $this->customerService->updateCustomer((int) $customer->id, $dto);

            // Verificar resultado do serviço
            if (!$result->isSuccess()) {
                return redirect()
                    ->route('provider.customers.edit', $customer->id)
                    ->with('error', $result->getMessage())
                    ->withInput();
            }

            return redirect()->route('provider.customers.index')
                ->with('success', 'Cliente atualizado com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro inesperado ao atualizar cliente', [
                'customer_id' => $customer->id,
                'user_id'     => auth()->id(),
                'tenant_id'   => auth()->user()?->tenant_id,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);

            return redirect()
                ->route('provider.customers.edit', $customer->id)
                ->with('error', 'Erro interno ao atualizar cliente. Tente novamente.')
                ->withInput();
        }
    }

    /**
     * Excluir cliente.
     */
    public function destroy(\App\Models\Customer $customer): RedirectResponse
    {
        try {
            $result = $this->customerService->deleteCustomer((int) $customer->id);

            if (!$result->isSuccess()) {
                return redirect()
                    ->route('provider.customers.index')
                    ->with('error', $result->getMessage());
            }

            return redirect()->route('provider.customers.index')
                ->with('success', 'Cliente excluído com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro inesperado ao excluir cliente', [
                'customer_id' => $customer->id,
                'user_id'     => auth()->id(),
                'tenant_id'   => auth()->user()?->tenant_id,
                'error'       => $e->getMessage(),
            ]);

            return redirect()
                ->route('provider.customers.index')
                ->with('error', 'Erro interno ao excluir cliente.');
        }
    }

    /**
     * Alterar status do cliente (ativo/inativo).
     */
    public function toggleStatus(Request $request, \App\Models\Customer $customer): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        try {
            $result = $this->customerService->toggleStatus((int) $customer->id);

            $status  = $result->isSuccess() ? 'success' : 'error';
            $message = $result->isSuccess()
                ? 'Status do cliente alterado com sucesso!'
                : $result->getMessage();

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => $result->isSuccess(),
                    'message' => $message,
                ], $result->isSuccess() ? 200 : 400);
            }

            return redirect()
                ->route('provider.customers.index')
                ->with($status, $message);
        } catch (\Exception $e) {
            Log::error('Erro inesperado ao alterar status do cliente', [
                'customer_id' => $customer->id,
                'user_id'     => auth()->id(),
                'tenant_id'   => auth()->user()?->tenant_id,
                'error'       => $e->getMessage(),
            ]);

            return redirect()
                ->route('provider.customers.index')
                ->with('error', 'Erro interno ao alterar status. Tente novamente.');
        }
    }

    /**
     * Restaurar cliente excluído.
     */
    public function restore(string $id): RedirectResponse
    {
        try {
            $result = $this->customerService->restoreCustomer((int) $id);

            $status  = $result->isSuccess() ? 'success' : 'error';
            $message = $result->isSuccess()
                ? 'Cliente restaurado com sucesso!'
                : $result->getMessage();

            return redirect()
                ->route('provider.customers.index')
                ->with($status, $message);
        } catch (\Exception $e) {
            Log::error('Erro inesperado ao restaurar cliente', [
                'customer_id' => $id,
                'user_id'     => auth()->id(),
                'tenant_id'   => auth()->user()?->tenant_id,
                'error'       => $e->getMessage(),
            ]);

            return redirect()
                ->route('provider.customers.index')
                ->with('error', 'Erro interno ao restaurar cliente. Tente novamente.');
        }
    }

    /**
     * Buscar clientes próximos (por CEP).
     */
    public function findNearby(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $cep = $request->get('cep');

        if (!$cep) {
            return redirect()->route('provider.customers.index')
                ->with('error', 'CEP é obrigatório para busca por proximidade.');
        }

        $result = $this->customerService->findNearbyCustomers($cep);

        if (!$result->isSuccess()) {
            return redirect()->route('provider.customers.index')
                ->with('error', $result->getMessage());
        }

        // Por enquanto, redirecionar para a página principal com dados filtrados
        // TODO: Implementar view de busca por proximidade
        return redirect()->route('provider.customers.index')
            ->with('info', 'Busca por proximidade não implementada. Use os filtros da página principal.');
    }

    /**
     * Buscar clientes (AJAX).
     */
    public function search(Request $request): \Illuminate\Http\JsonResponse
    {
        $filters = $request->only(['search', 'type', 'status', 'area_of_activity_id', 'deleted']);

        $result = $this->customerService->getFilteredCustomers($filters);

        if (!$result->isSuccess()) {
            return response()->json(['error' => $result->getMessage()], 400);
        }

        /** @var \Illuminate\Pagination\LengthAwarePaginator $customers */
        $customers = $result->getData();

        $data = collect($customers->items())->map(function ($customer) {
            $commonData = $customer->commonData;
            $contact    = $customer->contact;

            return [
                'id'             => $customer->id,
                'customer_name'  => $commonData ? ($commonData->company_name ?: trim(($commonData->first_name ?? '') . ' ' . ($commonData->last_name ?? ''))) : 'Nome não informado',
                'cpf'            => $commonData->cpf ?? '',
                'cnpj'           => $commonData->cnpj ?? '',
                'email'          => $contact->email_personal ?? '',
                'email_business' => $contact->email_business ?? '',
                'phone'          => $contact->phone_personal ?? '',
                'phone_business' => $contact->phone_business ?? '',
                'status'         => $customer->status,
                'status_label'   => $customer->status_label ?? ucfirst((string) $customer->status),
                'created_at'     => $customer->created_at?->toISOString(),
                'deleted_at'     => $customer->deleted_at?->toISOString(),
            ];
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Autocompletar clientes (AJAX).
     */
    public function autocomplete(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = $request->get('q', '');

        $result = $this->customerService->searchForAutocomplete($query);

        if (!$result->isSuccess()) {
            return response()->json([
                'error' => $result->getMessage(),
            ], 400);
        }

        return response()->json([
            'suggestions' => $result->getData(),
        ]);
    }

    /**
     * Exportar clientes.
     */
    public function export(Request $request): \Illuminate\Http\JsonResponse
    {
        $filters = $request->only(['search', 'status', 'type']);

        $result = $this->customerService->exportCustomers($filters);

        if (!$result->isSuccess()) {
            return response()->json([
                'error' => $result->getMessage(),
            ], 400);
        }

        return response()->json([
            'data' => $result->getData(),
        ]);
    }

    /**
     * Dashboard de clientes.
     */
    public function dashboard(): View
    {
        $result = $this->customerService->getDashboardData();

        if (!$result->isSuccess()) {
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
