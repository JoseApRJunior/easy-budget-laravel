<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\CustomerRequest;
use App\Http\Requests\CustomerUpdateRequest;
use App\Models\AreaOfActivity;
use App\Models\AreasOfActivity;
use App\Models\Profession;
use App\Models\User;
use App\Services\Domain\CustomerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
    ) {}

    /**
     * Mostrar lista de clientes.
     */
    public function index( Request $request ): View
    {
        /** @var User $user */
        $user = Auth::user();

        $filters = $request->only( [ 'search', 'status', 'type' ] );
        $result  = $this->customerService->getFilteredCustomers( $filters, $user->tenant_id );

        if ( !$result->isSuccess() ) {
            Log::error( 'Erro ao carregar clientes', [
                'user_id'   => $user->id,
                'tenant_id' => $user->tenant_id,
                'error'     => $result->getMessage()
            ] );

            return view( 'pages.customer.index', [
                'customers' => collect( [] ),
                'filters'   => $filters,
                'error'     => $result->getMessage()
            ] );
        }

        return view( 'pages.customer.index', [
            'customers' => $result->getData(),
            'filters'   => $filters
        ] );
    }

    /**
     * Mostrar formulário de criação de cliente.
     */
    public function create(): View
    {
        /** @var User $user */
        $user = Auth::user();

        // Dados necessários para o formulário
        $areasOfActivity = AreaOfActivity::where( 'is_active', true )
            ->orderBy( 'name' )
            ->get();

        $professions = Profession::where( 'is_active', true )
            ->orderBy( 'name' )
            ->get();

        return view( 'pages.customer.create', [
            'areas_of_activity' => $areasOfActivity,
            'professions'       => $professions,
            'customer'          => new \App\Models\Customer(),
        ] );
    }

    /**
     * Criar cliente (Pessoa Física ou Jurídica) - Método unificado
     */
    public function store( CustomerRequest $request ): RedirectResponse
    {
        try {
            /** @var User $user */
            $user      = Auth::user();
            $validated = $request->validated();

            // Usar o serviço para criar cliente
            $result = $this->customerService->create( $validated );

            // Verificar resultado do serviço
            if ( !$result->isSuccess() ) {
                return redirect()
                    ->route( 'provider.customers.create' )
                    ->with( 'error', $result->getMessage() )
                    ->withInput();
            }

            return redirect()->route( 'provider.customers.index' )
                ->with( 'success', 'Cliente criado com sucesso!' );
        } catch ( \Exception $e ) {
            Log::error( 'Erro inesperado ao criar cliente', [
                'user_id'   => auth()->id(),
                'tenant_id' => auth()->user()->tenant_id,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString()
            ] );

            return redirect()
                ->route( 'provider.customers.create' )
                ->with( 'error', 'Erro interno ao criar cliente. Tente novamente.' )
                ->withInput();
        }
    }

    /**
     * Mostrar cliente específico.
     */
    public function show( string $id ): View
    {
        /** @var User $user */
        $user = Auth::user();

        $result = $this->customerService->findCustomer( (int) $id );

        if ( !$result->isSuccess() ) {
            abort( 404, $result->getMessage() );
        }

        return view( 'pages.customer.show', [
            'customer' => $result->getData()
        ] );
    }

    /**
     * Mostrar formulário de edição de cliente.
     */
    public function edit( string $id ): View
    {
        /** @var User $user */
        $user = Auth::user();

        $result = $this->customerService->findCustomer( (int) $id );

        if ( !$result->isSuccess() ) {
            abort( 404, $result->getMessage() );
        }

        $customer = $result->getData();

        // Dados necessários para o formulário
        $areasOfActivity = \App\Models\AreasOfActivity::where( 'is_active', true )
            ->orderBy( 'name' )
            ->get();

        $professions = \App\Models\Profession::where( 'is_active', true )
            ->orderBy( 'name' )
            ->get();

        return view( 'pages.customer.edit', [
            'customer'          => $customer,
            'areas_of_activity' => $areasOfActivity,
            'professions'       => $professions,
        ] );
    }

    /**
     * Atualizar cliente (Pessoa Física ou Jurídica) - Método unificado
     */
    public function update( CustomerUpdateRequest $request, string $id ): RedirectResponse
    {
        try {
            /** @var User $user */
            $user      = Auth::user();
            $validated = $request->validated();

            // Usar o serviço para atualizar cliente
            $result = $this->customerService->updateCustomer( (int) $id, $validated );

            // Verificar resultado do serviço
            if ( !$result->isSuccess() ) {
                return redirect()
                    ->route( 'provider.customers.edit', $id )
                    ->with( 'error', $result->getMessage() )
                    ->withInput();
            }

            return redirect()->route( 'provider.customers.index' )
                ->with( 'success', 'Cliente atualizado com sucesso!' );
        } catch ( \Exception $e ) {
            Log::error( 'Erro inesperado ao atualizar cliente', [
                'customer_id' => $id,
                'user_id'     => auth()->id(),
                'tenant_id'   => auth()->user()->tenant_id,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString()
            ] );

            return redirect()
                ->route( 'provider.customers.edit', $id )
                ->with( 'error', 'Erro interno ao atualizar cliente. Tente novamente.' )
                ->withInput();
        }
    }

    /**
     * Excluir cliente.
     */
    public function destroy( string $id ): RedirectResponse
    {
        try {
            /** @var User $user */
            $user = Auth::user();

            $result = $this->customerService->deleteCustomer( (int) $id );

            if ( !$result->isSuccess() ) {
                return redirect()
                    ->route( 'provider.customers.index' )
                    ->with( 'error', $result->getMessage() );
            }

            return redirect()->route( 'provider.customers.index' )
                ->with( 'success', 'Cliente excluído com sucesso!' );
        } catch ( \Exception $e ) {
            Log::error( 'Erro inesperado ao excluir cliente', [
                'customer_id' => $id,
                'user_id'     => auth()->id(),
                'tenant_id'   => auth()->user()->tenant_id,
                'error'       => $e->getMessage()
            ] );

            return redirect()
                ->route( 'provider.customers.index' )
                ->with( 'error', 'Erro interno ao excluir cliente. Tente novamente.' );
        }
    }

    /**
     * Alterar status do cliente (ativo/inativo).
     */
    public function toggleStatus( string $id ): RedirectResponse
    {
        try {
            /** @var User $user */
            $user = Auth::user();

            $result = $this->customerService->toggleStatus( (int) $id, $user->tenant_id );

            $status  = $result->isSuccess() ? 'success' : 'error';
            $message = $result->isSuccess()
                ? 'Status do cliente alterado com sucesso!'
                : $result->getMessage();

            return redirect()
                ->route( 'provider.customers.index' )
                ->with( $status, $message );
        } catch ( \Exception $e ) {
            Log::error( 'Erro inesperado ao alterar status do cliente', [
                'customer_id' => $id,
                'user_id'     => auth()->id(),
                'tenant_id'   => auth()->user()->tenant_id,
                'error'       => $e->getMessage()
            ] );

            return redirect()
                ->route( 'provider.customers.index' )
                ->with( 'error', 'Erro interno ao alterar status. Tente novamente.' );
        }
    }

    /**
     * Restaurar cliente excluído.
     */
    public function restore( string $id ): RedirectResponse
    {
        try {
            /** @var User $user */
            $user = Auth::user();

            $result = $this->customerService->restoreCustomer( (int) $id, $user->tenant_id );

            $status  = $result->isSuccess() ? 'success' : 'error';
            $message = $result->isSuccess()
                ? 'Cliente restaurado com sucesso!'
                : $result->getMessage();

            return redirect()
                ->route( 'provider.customers.index' )
                ->with( $status, $message );
        } catch ( \Exception $e ) {
            Log::error( 'Erro inesperado ao restaurar cliente', [
                'customer_id' => $id,
                'user_id'     => auth()->id(),
                'tenant_id'   => auth()->user()->tenant_id,
                'error'       => $e->getMessage()
            ] );

            return redirect()
                ->route( 'provider.customers.index' )
                ->with( 'error', 'Erro interno ao restaurar cliente. Tente novamente.' );
        }
    }

    /**
     * Buscar clientes próximos (por CEP).
     */
    public function findNearby( Request $request ): View
    {
        $cep = $request->get( 'cep' );

        if ( !$cep ) {
            return redirect()->route( 'provider.customers.index' )
                ->with( 'error', 'CEP é obrigatório para busca por proximidade.' );
        }

        /** @var User $user */
        $user = Auth::user();

        $result = $this->customerService->findNearbyCustomers( $cep, $user->tenant_id );

        if ( !$result->isSuccess() ) {
            return redirect()->route( 'provider.customers.index' )
                ->with( 'error', $result->getMessage() );
        }

        // Por enquanto, redirecionar para a página principal com dados filtrados
        // TODO: Implementar view de busca por proximidade
        return redirect()->route( 'provider.customers.index' )
            ->with( 'info', 'Busca por proximidade não implementada. Use os filtros da página principal.' );
    }

    /**
     * Buscar clientes (AJAX).
     */
    public function search( Request $request ): \Illuminate\Http\JsonResponse
    {
        /** @var User $user */
        $user    = Auth::user();
        $search  = $request->get( 'search' );
        $type    = $request->get( 'type' );

        $query = \App\Models\Customer::with( [ 'commonData', 'contact' ] )
            ->where( 'tenant_id', $user->tenant_id );

        if ( !empty( $search ) ) {
            $query->where( function ( $q ) use ( $search ) {
                $q->whereHas( 'commonData', function ( $cq ) use ( $search ) {
                    $cq->where( 'first_name', 'like', "%{$search}%" )
                        ->orWhere( 'last_name', 'like', "%{$search}%" )
                        ->orWhere( 'company_name', 'like', "%{$search}%" )
                        ->orWhere( 'cpf', 'like', "%{$search}%" )
                        ->orWhere( 'cnpj', 'like', "%{$search}%" );
                } )->orWhereHas( 'contact', function ( $cq ) use ( $search ) {
                    $cq->where( 'email_personal', 'like', "%{$search}%" )
                        ->orWhere( 'email_business', 'like', "%{$search}%" )
                        ->orWhere( 'phone_personal', 'like', "%{$search}%" )
                        ->orWhere( 'phone_business', 'like', "%{$search}%" );
                } );
            } );
        }

        if ( !empty( $type ) ) {
            $query->whereHas( 'commonData', function ( $q ) use ( $type ) {
                if ( strtolower( $type ) === 'pessoa_fisica' || strtolower( $type ) === 'pf' || strtolower( $type ) === 'individual' ) {
                    $q->whereNotNull( 'cpf' );
                } else {
                    $q->whereNotNull( 'cnpj' );
                }
            } );
        }

        $customers = $query->orderBy( 'created_at', 'desc' )->limit( 100 )->get();

        $data = $customers->map( function ( $customer ) {
            $commonData = $customer->commonData;
            $contact    = $customer->contact;

            return [
                'id'             => $customer->id,
                'customer_name'  => $commonData ? ( $commonData->company_name ?: trim( ( $commonData->first_name ?? '' ) . ' ' . ( $commonData->last_name ?? '' ) ) ) : 'Nome não informado',
                'cpf'            => $commonData->cpf ?? '',
                'cnpj'           => $commonData->cnpj ?? '',
                'email'          => $contact->email_personal ?? '',
                'email_business' => $contact->email_business ?? '',
                'phone'          => $contact->phone_personal ?? '',
                'phone_business' => $contact->phone_business ?? '',
                'created_at'     => $customer->created_at?->toISOString(),
            ];
        } );

        return response()->json( [ 'data' => $data ] );
    }

    /**
     * Autocompletar clientes (AJAX).
     */
    public function autocomplete( Request $request ): \Illuminate\Http\JsonResponse
    {
        $query = $request->get( 'q' );

        /** @var User $user */
        $user = Auth::user();

        $result = $this->customerService->searchForAutocomplete( $query, $user->tenant_id );

        if ( !$result->isSuccess() ) {
            return response()->json( [
                'error' => $result->getMessage()
            ], 400 );
        }

        return response()->json( [
            'suggestions' => $result->getData()
        ] );
    }

    /**
     * Exportar clientes.
     */
    public function export( Request $request ): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $filters = $request->only( [ 'search', 'status', 'type' ] );
        $format  = $request->get( 'format', 'excel' );

        $result = $this->customerService->exportCustomers( $filters, $format, $user->tenant_id );

        if ( !$result->isSuccess() ) {
            return response()->json( [
                'error' => $result->getMessage()
            ], 400 );
        }

        return $result->getData();
    }

    /**
     * Dashboard de clientes.
     */
    public function dashboard(): View
    {
        /** @var User $user */
        $user = Auth::user();

        $result = $this->customerService->getCustomerStats( $user->tenant_id );

        if ( !$result->isSuccess() ) {
            return view( 'pages.customer.dashboard', [
                'stats' => [],
                'error' => $result->getMessage()
            ] );
        }

        return view( 'pages.customer.dashboard', [
            'stats' => $result->getData()
        ] );
    }

}
