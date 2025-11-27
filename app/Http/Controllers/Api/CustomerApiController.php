<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\CustomerPessoaFisicaRequest;
use App\Http\Requests\CustomerPessoaJuridicaRequest;
use App\Models\CommonData;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerContact;
use App\Models\CustomerInteraction;
use App\Services\Application\CustomerInteractionService;
use App\Services\Domain\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * API RESTful para gestão de clientes
 *
 * Fornece endpoints para operações CRUD de clientes,
 * endereços, contatos, interações e tags.
 */
class CustomerApiController extends Controller
{
    public function __construct(
        private CustomerService $customerService,
        private CustomerInteractionService $interactionService,
    ) {}

    /**
     * Lista de clientes com paginação e filtros.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search', 'status', 'customer_type', 'priority_level',
            'tags', 'created_from', 'created_to', 'sort_by', 'sort_direction', 'per_page',
        ]);

        $customers = $this->customerService->searchCustomers($filters, auth()->user());

        return response()->json([
            'customers' => $customers->items(),
            'pagination' => [
                'current_page' => $customers->currentPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
                'last_page' => $customers->lastPage(),
                'from' => $customers->firstItem(),
                'to' => $customers->lastItem(),
            ],
        ]);
    }

    /**
     * Cria novo cliente pessoa física.
     */
    public function storePessoaFisica(CustomerPessoaFisicaRequest $request): JsonResponse
    {
        try {
            $customer = $this->customerService->create($request->validated());

            return response()->json([
                'message' => 'Cliente pessoa física criado com sucesso',
                'customer' => $customer->load(['address', 'contact', 'tags']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao criar cliente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cria novo cliente pessoa jurídica.
     */
    public function storePessoaJuridica(CustomerPessoaJuridicaRequest $request): JsonResponse
    {
        try {
            $customer = $this->customerService->create($request->validated());

            return response()->json([
                'message' => 'Cliente pessoa jurídica criado com sucesso',
                'customer' => $customer->load(['address', 'contact', 'tags']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao criar cliente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exibe detalhes de um cliente.
     */
    public function show(Customer $customer): JsonResponse
    {
        if ($customer->tenant_id !== auth()->user()->tenant_id) {
            return response()->json(['message' => 'Cliente não encontrado'], 404);
        }

        $customer->load([
            'address',
            'contact',
            'tags',
            'interactions' => function ($query) {
                $query->with('user')->orderBy('interaction_date', 'desc')->limit(10);
            },
        ]);

        return response()->json([
            'customer' => $customer,
        ]);
    }

    /**
     * Atualiza cliente.
     */
    public function update(Request $request, Customer $customer): JsonResponse
    {
        if ($customer->tenant_id !== auth()->user()->tenant_id) {
            return response()->json(['message' => 'Cliente não encontrado'], 404);
        }

        try {
            // Validar dados conforme tipo de cliente
            $isIndividual = ($customer->commonData?->type ?? CommonData::TYPE_INDIVIDUAL) === CommonData::TYPE_INDIVIDUAL;
            if ($isIndividual) {
                $request->validate((new CustomerPessoaFisicaRequest)->rules());
                $validatedData = $request->validated();
            } else {
                $request->validate((new CustomerPessoaJuridicaRequest)->rules());
                $validatedData = $request->validated();
            }

            $updatedResult = $this->customerService->updateCustomer($customer->id, $validatedData);
            if (! $updatedResult->isSuccess()) {
                return response()->json([
                    'message' => 'Erro ao atualizar cliente',
                    'error' => $updatedResult->getMessage(),
                ], 400);
            }
            $updatedCustomer = $updatedResult->getData();

            return response()->json([
                'message' => 'Cliente atualizado com sucesso',
                'customer' => $updatedCustomer->load(['address', 'contact', 'tags']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao atualizar cliente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove cliente.
     */
    public function destroy(Customer $customer): JsonResponse
    {
        if ($customer->tenant_id !== auth()->user()->tenant_id) {
            return response()->json(['message' => 'Cliente não encontrado'], 404);
        }

        try {
            $result = $this->customerService->deleteCustomer($customer->id);
            if (! $result->isSuccess()) {
                return response()->json([
                    'message' => 'Erro ao remover cliente',
                    'error' => $result->getMessage(),
                ], 400);
            }

            return response()->json([
                'message' => 'Cliente removido com sucesso',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao remover cliente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Adiciona endereço ao cliente.
     */
    public function addAddress(Request $request, Customer $customer): JsonResponse
    {
        if ($customer->tenant_id !== auth()->user()->tenant_id) {
            return response()->json(['message' => 'Cliente não encontrado'], 404);
        }

        $request->validate(CustomerAddress::businessRules());

        try {
            $addressData = $request->validated();
            $addressData['customer_id'] = $customer->id;

            $address = CustomerAddress::create($addressData);

            return response()->json([
                'message' => 'Endereço adicionado com sucesso',
                'address' => $address,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao adicionar endereço',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Atualiza endereço do cliente.
     */
    public function updateAddress(Request $request, Customer $customer, CustomerAddress $address): JsonResponse
    {
        if ($customer->tenant_id !== auth()->user()->tenant_id || $address->customer_id !== $customer->id) {
            return response()->json(['message' => 'Endereço não encontrado'], 404);
        }

        $request->validate(CustomerAddress::businessRules());

        try {
            $address->update($request->validated());

            return response()->json([
                'message' => 'Endereço atualizado com sucesso',
                'address' => $address,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao atualizar endereço',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove endereço do cliente.
     */
    public function removeAddress(Customer $customer, CustomerAddress $address): JsonResponse
    {
        if ($customer->tenant_id !== auth()->user()->tenant_id || $address->customer_id !== $customer->id) {
            return response()->json(['message' => 'Endereço não encontrado'], 404);
        }

        try {
            $address->delete();

            return response()->json([
                'message' => 'Endereço removido com sucesso',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao remover endereço',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Adiciona contato ao cliente.
     */
    public function addContact(Request $request, Customer $customer): JsonResponse
    {
        if ($customer->tenant_id !== auth()->user()->tenant_id) {
            return response()->json(['message' => 'Cliente não encontrado'], 404);
        }

        $request->validate(CustomerContact::businessRules());

        try {
            $contactData = $request->validated();
            $contactData['customer_id'] = $customer->id;

            $contact = CustomerContact::create($contactData);

            return response()->json([
                'message' => 'Contato adicionado com sucesso',
                'contact' => $contact,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao adicionar contato',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Atualiza contato do cliente.
     */
    public function updateContact(Request $request, Customer $customer, CustomerContact $contact): JsonResponse
    {
        if ($customer->tenant_id !== auth()->user()->tenant_id || $contact->customer_id !== $customer->id) {
            return response()->json(['message' => 'Contato não encontrado'], 404);
        }

        $request->validate(CustomerContact::businessRules());

        try {
            $contact->update($request->validated());

            return response()->json([
                'message' => 'Contato atualizado com sucesso',
                'contact' => $contact,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao atualizar contato',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove contato do cliente.
     */
    public function removeContact(Customer $customer, CustomerContact $contact): JsonResponse
    {
        if ($customer->tenant_id !== auth()->user()->tenant_id || $contact->customer_id !== $customer->id) {
            return response()->json(['message' => 'Contato não encontrado'], 404);
        }

        try {
            $contact->delete();

            return response()->json([
                'message' => 'Contato removido com sucesso',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao remover contato',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Busca interações do cliente.
     */
    public function getInteractions(Request $request, Customer $customer): JsonResponse
    {
        if ($customer->tenant_id !== auth()->user()->tenant_id) {
            return response()->json(['message' => 'Cliente não encontrado'], 404);
        }

        $filters = $request->only(['type', 'direction', 'start_date', 'end_date', 'user_id', 'pending_actions', 'per_page']);

        $interactions = $this->interactionService->getCustomerInteractions($customer, $filters);

        return response()->json([
            'interactions' => $interactions->items(),
            'pagination' => [
                'current_page' => $interactions->currentPage(),
                'per_page' => $interactions->perPage(),
                'total' => $interactions->total(),
                'last_page' => $interactions->lastPage(),
            ],
        ]);
    }

    /**
     * Adiciona interação ao cliente.
     */
    public function addInteraction(Request $request, Customer $customer): JsonResponse
    {
        if ($customer->tenant_id !== auth()->user()->tenant_id) {
            return response()->json(['message' => 'Cliente não encontrado'], 404);
        }

        $request->validate(CustomerInteraction::businessRules());

        try {
            $interactionData = $request->validated();
            $interactionData['customer_id'] = $customer->id;
            $interactionData['user_id'] = auth()->user()->id;

            $interaction = $this->interactionService->createInteraction($customer, $interactionData, auth()->user());

            return response()->json([
                'message' => 'Interação adicionada com sucesso',
                'interaction' => $interaction->load('user'),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao adicionar interação',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Atualiza interação do cliente.
     */
    public function updateInteraction(Request $request, Customer $customer, CustomerInteraction $interaction): JsonResponse
    {
        if ($customer->tenant_id !== auth()->user()->tenant_id || $interaction->customer_id !== $customer->id) {
            return response()->json(['message' => 'Interação não encontrada'], 404);
        }

        $request->validate(CustomerInteraction::businessRules());

        try {
            $updatedInteraction = $this->interactionService->updateInteraction($interaction, $request->validated(), auth()->user());

            return response()->json([
                'message' => 'Interação atualizada com sucesso',
                'interaction' => $updatedInteraction->load('user'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao atualizar interação',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Autocomplete para busca de clientes.
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2',
        ]);

        $customers = Customer::where('tenant_id', auth()->user()->tenant_id)
            ->where(function ($query) use ($request) {
                $query->where('company_name', 'like', "%{$request->query}%")
                    ->orWhere('fantasy_name', 'like', "%{$request->query}%")
                    ->orWhereHas('commonData', function ($q) use ($request) {
                        $q->where('first_name', 'like', "%{$request->query}%")
                            ->orWhere('last_name', 'like', "%{$request->query}%");
                    });
            })
            ->limit(10)
            ->get();

        return response()->json([
            'customers' => $customers->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'text' => $customer->name ?? $customer->company_name,
                    'email' => $customer->primary_email,
                    'phone' => $customer->primary_phone,
                ];
            }),
        ]);
    }

    /**
     * Busca clientes por tags.
     */
    public function filterByTags(Request $request): JsonResponse
    {
        $request->validate([
            'tags' => 'required|array',
            'tags.*' => 'integer|exists:customer_tags,id',
        ]);

        $customers = Customer::where('tenant_id', auth()->user()->tenant_id)
            ->withTags($request->tags)
            ->with(['address', 'contacts', 'tags'])
            ->paginate(15);

        return response()->json([
            'customers' => $customers->items(),
            'pagination' => [
                'current_page' => $customers->currentPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
                'last_page' => $customers->lastPage(),
            ],
        ]);
    }

    /**
     * Busca clientes próximos a uma localização.
     */
    public function findNearby(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|integer|min:1|max:100',
        ]);

        $nearbyCustomers = $this->customerService->findNearbyCustomers(
            $request->latitude,
            $request->longitude,
            $request->radius ?? 10,
            auth()->user(),
        );

        return response()->json([
            'customers' => $nearbyCustomers->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->name ?? $customer->company_name,
                    'primary_address' => $customer->primary_address?->full_address,
                    'distance' => $customer->primary_address?->getDistanceTo(
                        $request->latitude, $request->longitude,
                    ),
                ];
            }),
        ]);
    }

    /**
     * Obtém estatísticas de clientes.
     */
    public function getStats(): JsonResponse
    {
        $stats = $this->customerService->getCustomerStats(auth()->user());

        return response()->json([
            'stats' => $stats,
        ]);
    }

    /**
     * Importa clientes via arquivo.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:5120',
            'format' => 'required|string|in:csv,excel',
        ]);

        // TODO: Implementar lógica de importação
        return response()->json([
            'message' => 'Funcionalidade de importação será implementada em breve',
        ], 501);
    }

    /**
     * Busca clientes para tabela (retorna dados completos)
     */
    public function searchForTable(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        $page = $request->input('page', 1);
        $perPage = 20;

        $customers = Customer::with(['commonData', 'contact'])
            ->where('tenant_id', auth()->user()->tenant_id)
            ->when(! empty($query), function ($q) use ($query) {
                $q->where('id', 'like', "%{$query}%")
                    ->orWhereHas('commonData', function ($subQuery) use ($query) {
                        $subQuery->where('first_name', 'like', "%{$query}%")
                            ->orWhere('last_name', 'like', "%{$query}%")
                            ->orWhere('company_name', 'like', "%{$query}%");
                    });
            })
            ->paginate($perPage);

        $result = $customers->getCollection()->map(function ($customer) {
            $name = 'Cliente #'.$customer->id;
            if ($customer->commonData) {
                if ($customer->commonData->first_name || $customer->commonData->last_name) {
                    $name = trim($customer->commonData->first_name.' '.$customer->commonData->last_name);
                } elseif ($customer->commonData->company_name) {
                    $name = $customer->commonData->company_name;
                }
            }

            return [
                'id' => $customer->id,
                'customer_name' => $name,
                'email' => $customer->contact?->email ?? '',
                'email_business' => $customer->contact?->email_business ?? '',
                'phone' => $customer->contact?->phone ?? '',
                'phone_business' => $customer->contact?->phone_business ?? '',
                'cpf' => $customer->commonData?->cpf ?? '',
                'cnpj' => $customer->commonData?->cnpj ?? '',
                'created_at' => $customer->created_at->toISOString(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => 'Busca realizada com sucesso',
        ]);
    }

    /**
     * Exporta clientes.
     */
    public function export(Request $request): Response
    {
        $filters = $request->only([
            'search', 'status', 'customer_type', 'priority_level', 'tags',
        ]);

        $customers = $this->customerService->searchCustomers(
            array_merge($filters, ['per_page' => 1000]),
            auth()->user(),
        );

        // TODO: Implementar exportação para Excel/CSV
        // Por ora, retorna JSON
        return response()->json([
            'customers' => $customers->items(),
            'total' => $customers->total(),
        ]);
    }
}
