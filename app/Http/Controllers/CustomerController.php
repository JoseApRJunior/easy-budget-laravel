<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CustomerFormRequest;
use App\Services\CustomerService;
use App\Services\ActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CustomerController extends BaseController
{
    public function __construct(
        private readonly CustomerService $customerService,
        private readonly ActivityService $activityService,
    ) {
        parent::__construct($activityService);
    }

    public function index( Request $request ): View
    {
        $user       = Auth::user();
        $tenantId   = $user->tenant_id ?? 1;
        $providerId = $user->provider_id ?? $user->id;

        $this->logActivity(
            action: 'view_customers',
            entity: 'customers',
            metadata: [ 
                'tenant_id'   => $tenantId,
                'provider_id' => $providerId
            ],
        );

        $filters   = $request->only( [ 'name', 'email', 'status' ] );
        $customers = $this->customerService->search( $filters[ 'name' ] ?? '', $tenantId, $providerId );

        return $this->renderView( 'pages.customer.index', compact( 'customers', 'filters' ) );
    }

    public function create(): View
    {
        $user       = Auth::user();
        $tenantId   = $user->tenant_id ?? 1;
        $providerId = $user->provider_id ?? $user->id;

        $this->logActivity(
            action: 'view_create_customer',
            entity: 'customers',
            metadata: [ 
                'tenant_id'   => $tenantId,
                'provider_id' => $providerId
            ],
        );

        return $this->renderView( 'pages.customer.create' );
    }

    public function store( CustomerFormRequest $request ): RedirectResponse
    {
        $user       = Auth::user();
        $tenantId   = $user->tenant_id ?? 1;
        $providerId = $user->provider_id ?? $user->id;

        try {
            $result = $this->customerService->create( $request->validated(), $tenantId, $providerId );

            return $this->handleServiceResult(
                result: $result,
                request: $request,
                successMessage: 'Cliente criado com sucesso.',
                errorMessage: 'Erro ao criar cliente.',
            );
        } catch ( \Exception $e ) {
            return $this->errorRedirect( 'Erro ao criar cliente: ' . $e->getMessage() );
        }
    }

    public function show( int $id ): View
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id ?? 1;

        $customer = $this->customerService->getById( $id, $tenantId );

        if ( !$customer ) {
            return $this->errorRedirect( 'Cliente não encontrado.' );
        }

        $this->logActivity(
            action: 'view_customer',
            entity: 'customers',
            entityId: $id,
            metadata: [ 'tenant_id' => $tenantId ],
        );

        return $this->renderView( 'pages.customer.show', compact( 'customer' ) );
    }

    public function edit( int $id ): View
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id ?? 1;

        $customer = $this->customerService->getById( $id, $tenantId );

        if ( !$customer ) {
            return $this->errorRedirect( 'Cliente não encontrado.' );
        }

        $this->logActivity(
            action: 'view_edit_customer',
            entity: 'customers',
            entityId: $id,
            metadata: [ 'tenant_id' => $tenantId ],
        );

        return $this->renderView( 'pages.customer.edit', compact( 'customer' ) );
    }

    public function update( CustomerFormRequest $request, int $id ): RedirectResponse
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id ?? 1;

        try {
            $result = $this->customerService->update( $id, $request->validated(), $tenantId );

            return $this->handleServiceResult(
                result: $result,
                request: $request,
                successMessage: 'Cliente atualizado com sucesso.',
                errorMessage: 'Erro ao atualizar cliente.',
            );
        } catch ( \Exception $e ) {
            return $this->errorRedirect( 'Erro ao atualizar cliente: ' . $e->getMessage() );
        }
    }

    public function destroy( int $id ): RedirectResponse
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id ?? 1;

        try {
            $result = $this->customerService->delete( $id, $tenantId );

            return $this->handleServiceResult(
                result: $result,
                request: request(),
                successMessage: 'Cliente deletado com sucesso.',
                errorMessage: 'Erro ao deletar cliente.',
            );
        } catch ( \Exception $e ) {
            return $this->errorRedirect( 'Erro ao deletar cliente: ' . $e->getMessage() );
        }
    }

    /**
     * Exibe serviços e orçamentos relacionados ao cliente.
     *
     * @param int $id
     * @return View
     */
    public function servicesAndQuotes( int $id ): View
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id ?? 1;

        $this->logActivity(
            action: 'view_customer_services_quotes',
            entity: 'customers',
            entityId: $id,
            metadata: [ 'tenant_id' => $tenantId ],
        );

        $data = $this->customerService->getServicesAndQuotes( $id, $tenantId );

        if ( empty( $data ) ) {
            return $this->errorRedirect( 'Dados não encontrados.' );
        }

        $customer = $this->customerService->getById( $id, $tenantId );

        if ( !$customer ) {
            return $this->errorRedirect( 'Cliente não encontrado.' );
        }

        return $this->renderView( 'pages.customer.services-quotes', compact( 'data', 'customer' ) );
    }

    /**
     * Busca clientes via AJAX.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search( Request $request ): JsonResponse
    {
        $user       = Auth::user();
        $tenantId   = $user->tenant_id ?? 1;
        $providerId = $user->provider_id ?? $user->id;

        $this->logActivity(
            action: 'search_customers',
            entity: 'customers',
            metadata: [ 
                'tenant_id'   => $tenantId,
                'provider_id' => $providerId
            ],
        );

        $query     = $request->get( 'q', '' );
        $customers = $this->customerService->search( $query, $tenantId, $providerId );

        return response()->json( $customers );
    }

}
