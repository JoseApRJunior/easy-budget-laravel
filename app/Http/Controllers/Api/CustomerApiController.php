<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\DTOs\Customer\CustomerDTO;
use App\DTOs\Customer\CustomerFilterDTO;
use App\DTOs\Customer\CustomerInteractionDTO;
use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\CustomerPessoaFisicaRequest;
use App\Http\Requests\CustomerPessoaJuridicaRequest;
use App\Models\Address;
use App\Models\CommonData;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\CustomerInteraction;
use App\Services\Application\CustomerInteractionService;
use App\Services\Domain\AddressService;
use App\Services\Domain\ContactService;
use App\Services\Domain\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        private AddressService $addressService,
        private ContactService $contactService,
    ) {}

    /**
     * Lista de clientes com paginação e filtros.
     */
    public function index(Request $request): JsonResponse
    {
        $filterDTO = CustomerFilterDTO::fromRequest($request->all());
        $result = $this->customerService->getFilteredCustomers($filterDTO);

        return $this->jsonResponse($result);
    }

    /**
     * Cria novo cliente pessoa física.
     */
    public function storePessoaFisica(CustomerPessoaFisicaRequest $request): JsonResponse
    {
        $dto = CustomerDTO::fromRequest(array_merge($request->validated(), ['type' => CommonData::TYPE_INDIVIDUAL]));
        $result = $this->customerService->createCustomer($dto);

        return $this->jsonResponse($result, 201);
    }

    /**
     * Cria novo cliente pessoa jurídica.
     */
    public function storePessoaJuridica(CustomerPessoaJuridicaRequest $request): JsonResponse
    {
        $dto = CustomerDTO::fromRequest(array_merge($request->validated(), ['type' => CommonData::TYPE_BUSINESS]));
        $result = $this->customerService->createCustomer($dto);

        return $this->jsonResponse($result, 201);
    }

    /**
     * Exibe detalhes de um cliente.
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->customerService->findCustomer($id);

        if ($result->isSuccess()) {
            $customer = $result->getData();
            $customer->load([
                'address',
                'contact',
                'tags',
                'interactions' => function ($query) {
                    $query->with('user')->orderBy('interaction_date', 'desc')->limit(10);
                },
            ]);
        }

        return $this->jsonResponse($result);
    }

    /**
     * Atualiza cliente.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $result = $this->customerService->findCustomer($id);

        if (! $result->isSuccess()) {
            return $this->jsonResponse($result);
        }

        $customer = $result->getData();

        // Validar dados conforme tipo de cliente
        $isIndividual = ($customer->commonData?->type ?? CommonData::TYPE_INDIVIDUAL) === CommonData::TYPE_INDIVIDUAL;

        if ($isIndividual) {
            $rules = (new CustomerPessoaFisicaRequest)->rules();
        } else {
            $rules = (new CustomerPessoaJuridicaRequest)->rules();
        }

        $validatedData = $request->validate($rules);

        $dto = CustomerDTO::fromRequest($validatedData);
        $updatedResult = $this->customerService->updateCustomer($id, $dto);

        if ($updatedResult->isSuccess()) {
            $updatedResult->getData()->load(['address', 'contact', 'tags']);
        }

        return $this->jsonResponse($updatedResult);
    }

    /**
     * Remove cliente.
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->customerService->deleteCustomer($id);

        return $this->jsonResponse($result);
    }

    /**
     * Adiciona endereço ao cliente.
     */
    public function addAddress(Request $request, Customer $customer): JsonResponse
    {
        if ($customer->tenant_id !== auth()->user()->tenant_id) {
            return response()->json(['message' => 'Cliente não encontrado'], 404);
        }

        $validatedData = $request->validate(Address::businessRules());
        $validatedData['customer_id'] = $customer->id;

        $dto = AddressDTO::fromRequest($validatedData);
        $result = $this->addressService->createAddress($dto);

        return $this->jsonResponse($result);
    }

    /**
     * Atualiza endereço do cliente.
     */
    public function updateAddress(Request $request, Customer $customer, Address $address): JsonResponse
    {
        if ($customer->tenant_id !== auth()->user()->tenant_id || $address->customer_id !== $customer->id) {
            return response()->json(['message' => 'Endereço não encontrado'], 404);
        }

        $validatedData = $request->validate(Address::businessRules());
        $validatedData['customer_id'] = $customer->id;

        $dto = AddressDTO::fromRequest($validatedData);
        $result = $this->addressService->updateAddress($address->id, $dto);

        return $this->jsonResponse($result);
    }

    /**
     * Remove endereço do cliente.
     */
    public function removeAddress(Customer $customer, Address $address): JsonResponse
    {
        if ($customer->tenant_id !== auth()->user()->tenant_id || $address->customer_id !== $customer->id) {
            return response()->json(['message' => 'Endereço não encontrado'], 404);
        }

        $result = $this->addressService->deleteAddress($address->id);

        return $this->jsonResponse($result);
    }

    /**
     * Adiciona contato ao cliente.
     */
    public function addContact(Request $request, Customer $customer): JsonResponse
    {
        if ($customer->tenant_id !== auth()->user()->tenant_id) {
            return response()->json(['message' => 'Cliente não encontrado'], 404);
        }

        $validatedData = $request->validate(Contact::businessRules());
        $validatedData['customer_id'] = $customer->id;

        $dto = ContactDTO::fromRequest($validatedData);
        $result = $this->contactService->createContact($dto);

        return $this->jsonResponse($result);
    }

    /**
     * Atualiza contato do cliente.
     */
    public function updateContact(Request $request, Customer $customer, Contact $contact): JsonResponse
    {
        if ($customer->tenant_id !== auth()->user()->tenant_id || $contact->customer_id !== $customer->id) {
            return response()->json(['message' => 'Contato não encontrado'], 404);
        }

        $validatedData = $request->validate(Contact::businessRules());
        $validatedData['customer_id'] = $customer->id;

        $dto = ContactDTO::fromRequest($validatedData);
        $result = $this->contactService->updateContact($contact->id, $dto);

        return $this->jsonResponse($result);
    }

    /**
     * Remove contato do cliente.
     */
    public function removeContact(Customer $customer, Contact $contact): JsonResponse
    {
        if ($customer->tenant_id !== auth()->user()->tenant_id || $contact->customer_id !== $customer->id) {
            return response()->json(['message' => 'Contato não encontrado'], 404);
        }

        $result = $this->contactService->deleteContact($contact->id);

        return $this->jsonResponse($result);
    }

    /**
     * Busca interações do cliente.
     */
    public function getInteractions(Request $request, int $id): JsonResponse
    {
        $result = $this->customerService->findCustomer($id);

        if (! $result->isSuccess()) {
            return $this->jsonResponse($result);
        }

        $customer = $result->getData();

        $filters = $request->only(['type', 'direction', 'start_date', 'end_date', 'user_id', 'pending_actions', 'per_page']);

        $interactions = $this->interactionService->getCustomerInteractions($customer, $filters);

        return $this->jsonSuccess($interactions);
    }

    /**
     * Adiciona interação ao cliente.
     */
    public function addInteraction(Request $request, int $id): JsonResponse
    {
        $dto = CustomerInteractionDTO::fromRequest($request->all());

        $result = $this->customerService->createInteraction($id, $dto);

        if ($result->isSuccess()) {
            $result->getData()->load('user');
        }

        return $this->jsonResponse($result, 201);
    }

    /**
     * Atualiza interação do cliente.
     */
    public function updateInteraction(Request $request, int $customerId, int $interactionId): JsonResponse
    {
        $interaction = CustomerInteraction::find($interactionId);

        if (! $interaction || $interaction->customer_id !== $customerId) {
            return $this->jsonError('Interação não encontrada', null, 404);
        }

        $request->validate(CustomerInteraction::businessRules());

        $updatedInteraction = $this->interactionService->updateInteraction($interaction, $request->validated(), auth()->user());

        return $this->jsonSuccess($updatedInteraction->load('user'), 'Interação atualizada com sucesso');
    }

    /**
     * Autocomplete para busca de clientes.
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2',
        ]);

        $result = $this->customerService->searchForAutocomplete($request->get('query'));

        return $this->jsonResponse($result);
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

        $filterDTO = CustomerFilterDTO::fromRequest(['tags' => $request->tags]);
        $result = $this->customerService->getFilteredCustomers($filterDTO);

        return $this->jsonResponse($result);
    }

    /**
     * Busca clientes próximos a uma localização.
     */
    public function findNearby(Request $request): JsonResponse
    {
        // Se houver CEP, busca por CEP
        if ($request->has('cep')) {
            $result = $this->customerService->findNearbyCustomers($request->get('cep'));

            return $this->jsonResponse($result);
        }

        // Caso contrário, tenta por latitude/longitude se o repositório suportar
        return $this->jsonError('Busca por latitude/longitude não implementada. Use CEP.', null, 400);
    }

    /**
     * Obtém estatísticas de clientes.
     */
    public function getStats(): JsonResponse
    {
        $result = $this->customerService->getDashboardData();

        return $this->jsonResponse($result);
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

        return $this->jsonError('Funcionalidade de importação será implementada em breve', null, 501);
    }

    /**
     * Busca clientes para tabela (retorna dados completos)
     */
    public function searchForTable(Request $request): JsonResponse
    {
        $filterDTO = CustomerFilterDTO::fromRequest($request->all());
        $result = $this->customerService->getFilteredCustomers($filterDTO);

        if (! $result->isSuccess()) {
            return $this->jsonResponse($result);
        }

        /** @var \Illuminate\Pagination\LengthAwarePaginator $customers */
        $customers = $result->getData();

        $formatted = collect($customers->items())->map(function ($customer) {
            $commonData = $customer->commonData;
            $contact = $customer->contact;

            $name = $commonData ? ($commonData->company_name ?: trim(($commonData->first_name ?? '').' '.($commonData->last_name ?? ''))) : 'Nome não informado';

            return [
                'id' => $customer->id,
                'customer_name' => $name,
                'email' => $contact?->email_personal ?? '',
                'email_business' => $contact?->email_business ?? '',
                'phone' => $contact?->phone_personal ?? '',
                'phone_business' => $contact?->phone_business ?? '',
                'cpf' => $commonData?->cpf ?? '',
                'cnpj' => $commonData?->cnpj ?? '',
                'created_at' => $customer->created_at->toISOString(),
            ];
        });

        return $this->jsonSuccess($formatted, 'Busca realizada com sucesso');
    }

    /**
     * Exporta clientes.
     */
    public function export(Request $request): JsonResponse
    {
        $filterDTO = CustomerFilterDTO::fromRequest($request->all());
        $result = $this->customerService->exportCustomers($filterDTO);

        return $this->jsonResponse($result);
    }
}
