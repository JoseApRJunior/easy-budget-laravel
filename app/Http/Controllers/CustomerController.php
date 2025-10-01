<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CustomerPessoaFisicaRequest;
use App\Http\Requests\CustomerPessoaJuridicaRequest;
use App\Models\Customer;
use App\Models\CustomerTag;
use App\Services\CustomerService;
use App\Services\CustomerInteractionService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Controller para gestão de clientes - Interface Web
 *
 * Gerencia todas as operações relacionadas a clientes através
 * da interface web, incluindo CRUD, busca e filtros.
 */
class CustomerController extends Controller
{
    public function __construct(
        private CustomerService $customerService,
        private CustomerInteractionService $interactionService,
    ) {}

    /**
     * Lista de clientes com filtros e paginação.
     */
    public function index( Request $request ): View
    {
        $filters = $request->only( [
            'search', 'status', 'customer_type', 'priority_level',
            'tags', 'created_from', 'created_to', 'sort_by', 'sort_direction', 'per_page'
        ] );

        $customers = $this->customerService->searchCustomers( $filters, auth()->user() );

        // Dados adicionais para a view
        $stats         = $this->customerService->getCustomerStats( auth()->user() );
        $availableTags = CustomerTag::where( 'tenant_id', auth()->user()->tenant_id )
            ->active()
            ->ordered()
            ->get();

        return view( 'customers.index', compact( 'customers', 'stats', 'availableTags', 'filters' ) );
    }

    /**
     * Formulário de criação de cliente pessoa física.
     */
    public function createPessoaFisica(): View
    {
        $availableTags = CustomerTag::where( 'tenant_id', auth()->user()->tenant_id )
            ->active()
            ->ordered()
            ->get();

        return view( 'customers.create-pessoa-fisica', compact( 'availableTags' ) );
    }

    /**
     * Formulário de criação de cliente pessoa jurídica.
     */
    public function createPessoaJuridica(): View
    {
        $availableTags = CustomerTag::where( 'tenant_id', auth()->user()->tenant_id )
            ->active()
            ->ordered()
            ->get();

        return view( 'customers.create-pessoa-juridica', compact( 'availableTags' ) );
    }

    /**
     * Salva cliente pessoa física.
     */
    public function storePessoaFisica( CustomerPessoaFisicaRequest $request ): RedirectResponse
    {
        try {
            $customer = $this->customerService->createPessoaFisica( $request->validated(), auth()->user() );

            return redirect()->route( 'customers.show', $customer )
                ->with( 'success', 'Cliente pessoa física cadastrado com sucesso!' );

        } catch ( \Exception $e ) {
            return back()->withInput()
                ->with( 'error', 'Erro ao cadastrar cliente: ' . $e->getMessage() );
        }
    }

    /**
     * Salva cliente pessoa jurídica.
     */
    public function storePessoaJuridica( CustomerPessoaJuridicaRequest $request ): RedirectResponse
    {
        try {
            $customer = $this->customerService->createPessoaJuridica( $request->validated(), auth()->user() );

            return redirect()->route( 'customers.show', $customer )
                ->with( 'success', 'Cliente pessoa jurídica cadastrado com sucesso!' );

        } catch ( \Exception $e ) {
            return back()->withInput()
                ->with( 'error', 'Erro ao cadastrar cliente: ' . $e->getMessage() );
        }
    }

    /**
     * Exibe detalhes de um cliente.
     */
    public function show( Customer $customer ): View
    {
        // Verificar se o cliente pertence ao tenant do usuário
        if ( $customer->tenant_id !== auth()->user()->tenant_id ) {
            abort( 403 );
        }

        $customer->load( [
            'addresses',
            'contacts',
            'tags',
            'interactions' => function ( $query ) {
                $query->with( 'user' )->orderBy( 'interaction_date', 'desc' )->limit( 10 );
            }
        ] );

        // Dados adicionais
        $interactionTypes      = $this->interactionService->getInteractionTypes();
        $interactionDirections = $this->interactionService->getInteractionDirections();
        $interactionOutcomes   = $this->interactionService->getInteractionOutcomes();

        return view( 'customers.show', compact(
            'customer',
            'interactionTypes',
            'interactionDirections',
            'interactionOutcomes',
        ) );
    }

    /**
     * Formulário de edição de cliente.
     */
    public function edit( Customer $customer ): View
    {
        // Verificar se o cliente pertence ao tenant do usuário
        if ( $customer->tenant_id !== auth()->user()->tenant_id ) {
            abort( 403 );
        }

        $customer->load( [ 'addresses', 'contacts', 'tags' ] );

        $availableTags = CustomerTag::where( 'tenant_id', auth()->user()->tenant_id )
            ->active()
            ->ordered()
            ->get();

        return view( 'customers.edit', compact( 'customer', 'availableTags' ) );
    }

    /**
     * Atualiza cliente.
     */
    public function update( Request $request, Customer $customer ): RedirectResponse
    {
        // Verificar se o cliente pertence ao tenant do usuário
        if ( $customer->tenant_id !== auth()->user()->tenant_id ) {
            abort( 403 );
        }

        try {
            // Validar dados conforme tipo de cliente
            if ( $customer->customer_type === 'individual' ) {
                $request->validate( ( new CustomerPessoaFisicaRequest() )->rules() );
                $validatedData = $request->validated();
            } else {
                $request->validate( ( new CustomerPessoaJuridicaRequest() )->rules() );
                $validatedData = $request->validated();
            }

            $updatedCustomer = $this->customerService->updateCustomer( $customer, $validatedData, auth()->user() );

            return redirect()->route( 'customers.show', $updatedCustomer )
                ->with( 'success', 'Cliente atualizado com sucesso!' );

        } catch ( \Exception $e ) {
            return back()->withInput()
                ->with( 'error', 'Erro ao atualizar cliente: ' . $e->getMessage() );
        }
    }

    /**
     * Remove cliente (soft delete).
     */
    public function destroy( Customer $customer ): RedirectResponse
    {
        // Verificar se o cliente pertence ao tenant do usuário
        if ( $customer->tenant_id !== auth()->user()->tenant_id ) {
            abort( 403 );
        }

        try {
            $this->customerService->deleteCustomer( $customer, auth()->user() );

            return redirect()->route( 'customers.index' )
                ->with( 'success', 'Cliente removido com sucesso!' );

        } catch ( \Exception $e ) {
            return back()->with( 'error', 'Erro ao remover cliente: ' . $e->getMessage() );
        }
    }

    /**
     * Restaura cliente excluído.
     */
    public function restore( Customer $customer ): RedirectResponse
    {
        // Verificar se o cliente pertence ao tenant do usuário
        if ( $customer->tenant_id !== auth()->user()->tenant_id ) {
            abort( 403 );
        }

        try {
            $this->customerService->restoreCustomer( $customer, auth()->user() );

            return redirect()->route( 'customers.show', $customer )
                ->with( 'success', 'Cliente restaurado com sucesso!' );

        } catch ( \Exception $e ) {
            return back()->with( 'error', 'Erro ao restaurar cliente: ' . $e->getMessage() );
        }
    }

    /**
     * Duplica cliente.
     */
    public function duplicate( Customer $customer ): RedirectResponse
    {
        // Verificar se o cliente pertence ao tenant do usuário
        if ( $customer->tenant_id !== auth()->user()->tenant_id ) {
            abort( 403 );
        }

        try {
            $newCustomer = $this->customerService->duplicateCustomer( $customer, auth()->user() );

            return redirect()->route( 'customers.edit', $newCustomer )
                ->with( 'success', 'Cliente duplicado com sucesso!' );

        } catch ( \Exception $e ) {
            return back()->with( 'error', 'Erro ao duplicar cliente: ' . $e->getMessage() );
        }
    }

    /**
     * Busca clientes próximos via AJAX.
     */
    public function findNearby( Request $request ): \Illuminate\Http\JsonResponse
    {
        $request->validate( [
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius'    => 'nullable|integer|min:1|max:100',
        ] );

        $nearbyCustomers = $this->customerService->findNearbyCustomers(
            $request->latitude,
            $request->longitude,
            $request->radius ?? 10,
            auth()->user(),
        );

        return response()->json( [
            'customers' => $nearbyCustomers->map( function ( $customer ) {
                return [
                    'id'              => $customer->id,
                    'name'            => $customer->name ?? $customer->company_name,
                    'primary_address' => $customer->primary_address?->full_address,
                    'distance'        => $customer->primary_address?->getDistanceTo(
                        request( 'latitude' ), request( 'longitude' ),
                    ),
                ];
            } )
        ] );
    }

    /**
     * Autocomplete para busca de clientes.
     */
    public function autocomplete( Request $request ): \Illuminate\Http\JsonResponse
    {
        $request->validate( [
            'query' => 'required|string|min:2',
        ] );

        $customers = Customer::where( 'tenant_id', auth()->user()->tenant_id )
            ->where( function ( $query ) use ( $request ) {
                $query->where( 'company_name', 'like', "%{$request->query}%" )
                    ->orWhere( 'fantasy_name', 'like', "%{$request->query}%" )
                    ->orWhereHas( 'commonData', function ( $q ) use ( $request ) {
                        $q->where( 'first_name', 'like', "%{$request->query}%" )
                            ->orWhere( 'last_name', 'like', "%{$request->query}%" );
                    } );
            } )
            ->limit( 10 )
            ->get();

        return response()->json( [
            'customers' => $customers->map( function ( $customer ) {
                return [
                    'id'    => $customer->id,
                    'text'  => $customer->name ?? $customer->company_name,
                    'email' => $customer->primary_email,
                    'phone' => $customer->primary_phone,
                ];
            } )
        ] );
    }

    /**
     * Exporta lista de clientes.
     */
    public function export( Request $request )
    {
        $filters = $request->only( [
            'search', 'status', 'customer_type', 'priority_level', 'tags'
        ] );

        $customers = $this->customerService->searchCustomers(
            array_merge( $filters, [ 'per_page' => 1000 ] ),
            auth()->user(),
        );

        // TODO: Implementar exportação para Excel/CSV
        // Por ora, retorna JSON
        return response()->json( [
            'customers' => $customers->items(),
            'total'     => $customers->total(),
        ] );
    }

    /**
     * Dashboard de clientes.
     */
    public function dashboard(): View
    {
        $stats           = $this->customerService->getCustomerStats( auth()->user() );
        $recentCustomers = Customer::where( 'tenant_id', auth()->user()->tenant_id )
            ->withRecentInteractions( 7 )
            ->limit( 5 )
            ->get();

        $pendingActions      = $this->interactionService->getPendingActions( auth()->user(), 10 );
        $overdueInteractions = $this->interactionService->getOverdueInteractions( auth()->user() );

        return view( 'customers.dashboard', compact(
            'stats',
            'recentCustomers',
            'pendingActions',
            'overdueInteractions',
        ) );
    }

}
