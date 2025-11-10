<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\CustomerPessoaFisicaRequest;
use App\Http\Requests\CustomerPessoaJuridicaRequest;
use App\Http\Requests\CustomerUpdateRequest;
use App\Models\AreaOfActivity;
use App\Models\Customer;
use App\Models\Profession;
use App\Services\Domain\CustomerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Controller para gestão de clientes - Interface Web
 *
 * Gerencia todas as operações relacionadas a clientes através
 * da interface web, incluindo CRUD, busca e filtros.
 * Implementa arquitetura Controller → Services → Repositories → Models.
 *
 * Métodos específicos para Pessoa Física e Pessoa Jurídica
 * conforme especificação de migração do módulo Customer.
 */
class CustomerController extends Controller
{
    public function __construct(
        private CustomerService $customerService,
    ) {}

    /**
     * Lista de clientes com filtros e paginação
     */
    public function index( Request $request ): View
    {
        $filters   = $request->only( [ 'search', 'status', 'type', 'area_of_activity_id' ] );
        $tenantId  = Auth::user()->tenant_id;
        $customers = $this->customerService->getFilteredCustomers( $filters, $tenantId );

        if ( !$customers->isSuccess() ) {
            return view( 'pages.customer.index', [
                'customers' => collect(),
                'error'     => $customers->getMessage()
            ] );
        }

        return view( 'pages.customer.index', [
            'customers'         => $customers->getData(),
            'filters'           => $filters,
            'areas_of_activity' => AreaOfActivity::active()->get(),
        ] );
    }

    /**
     * Formulário de criação de cliente - Pessoa Física
     */
    public function createPessoaFisica(): View
    {
        $areasOfActivity = AreaOfActivity::active()->get();
        $professions     = Profession::active()->get();

        return view( 'customers.create-pessoa-fisica', [
            'areas_of_activity' => $areasOfActivity,
            'professions'       => $professions,
            'customer'          => new Customer(),
        ] );
    }

    /**
     * Formulário de criação de cliente - Pessoa Jurídica
     */
    public function createPessoaJuridica(): View
    {
        $areasOfActivity = AreaOfActivity::active()->get();
        $professions     = Profession::active()->get();

        return view( 'customers.create-pessoa-juridica', [
            'areas_of_activity' => $areasOfActivity,
            'professions'       => $professions,
            'customer'          => new Customer(),
        ] );
    }

    /**
     * Formulário de criação de cliente (método legado para compatibilidade)
     */
    public function create(): View
    {
        $areasOfActivity = AreaOfActivity::active()->get();
        $professions     = Profession::active()->get();

        return view( 'pages.customer.create', [
            'areas_of_activity' => $areasOfActivity,
            'professions'       => $professions,
            'customer'          => new Customer(),
        ] );
    }

    /**
     * Criar cliente - Pessoa Física
     */
    public function storePessoaFisica( CustomerPessoaFisicaRequest $request ): RedirectResponse
    {
        $validated = $request->validated();
        $tenantId  = Auth::user()->tenant_id;
        $result    = $this->customerService->createPessoaFisica( $validated, $tenantId );

        if ( !$result->isSuccess() ) {
            return back()->withInput()->with( 'error', $result->getMessage() );
        }

        $this->logOperation( 'customer_created', [
            'customer_id' => $result->getData()->id,
            'type'        => 'pf'
        ] );

        return redirect()
            ->route( 'provider.customers.show', $result->getData() )
            ->with( 'success', $result->getMessage() );
    }

    /**
     * Criar cliente - Pessoa Jurídica
     */
    public function storePessoaJuridica( CustomerPessoaJuridicaRequest $request ): RedirectResponse
    {
        $validated = $request->validated();
        $tenantId  = Auth::user()->tenant_id;
        $result    = $this->customerService->createPessoaJuridica( $validated, $tenantId );

        if ( !$result->isSuccess() ) {
            return back()->withInput()->with( 'error', $result->getMessage() );
        }

        $this->logOperation( 'customer_created', [
            'customer_id' => $result->getData()->id,
            'type'        => 'pj'
        ] );

        return redirect()
            ->route( 'provider.customers.show', $result->getData() )
            ->with( 'success', $result->getMessage() );
    }

    /**
     * Criar cliente (método legado para compatibilidade)
     * Detecta automaticamente o tipo baseado no documento
     */
    public function store( Request $request ): RedirectResponse
    {
        // Detecta tipo baseado no campo enviado (cnpj ou cpf)
        $cnpj = $request->input( 'cnpj', '' );
        $cpf  = $request->input( 'cpf', '' );
        $isPJ = !empty( $cnpj );

        if ( $isPJ ) {
            $formRequest = app( CustomerPessoaJuridicaRequest::class);
        } else {
            $formRequest = app( CustomerPessoaFisicaRequest::class);
        }

        $formRequest->setContainer( app() )
            ->setRedirector( app( 'redirect' ) )
            ->replace( $request->all() );

        $formRequest->validateResolved();
        $validated = $formRequest->validated();
        $tenantId  = Auth::user()->tenant_id;

        // Chama método específico baseado no tipo detectado
        $result = $isPJ
            ? $this->customerService->createPessoaJuridica( $validated, $tenantId )
            : $this->customerService->createPessoaFisica( $validated, $tenantId );

        if ( !$result->isSuccess() ) {
            return back()->withInput()->with( 'error', $result->getMessage() );
        }

        $this->logOperation( 'customer_created', [
            'customer_id' => $result->getData()->id,
            'type'        => $isPJ ? 'pj' : 'pf'
        ] );

        return redirect()
            ->route( 'provider.customers.show', $result->getData() )
            ->with( 'success', $result->getMessage() );
    }

    /**
     * Detalhes do cliente
     */
    public function show( Customer $customer ): View
    {
        // Verificar se o cliente pertence ao tenant do usuário
        if ( $customer->tenant_id !== Auth::user()->tenant_id ) {
            abort( 403, 'Cliente não encontrado.' );
        }

        $customer->load( [ 'commonData', 'contact', 'address', 'budgets', 'invoices' ] );

        return view( 'pages.customer.show', [
            'customer' => $customer,
        ] );
    }

    /**
     * Formulário de edição - Pessoa Física
     */
    public function editPessoaFisica( Customer $customer ): View
    {
        // Verificar se o cliente pertence ao tenant do usuário
        if ( $customer->tenant_id !== Auth::user()->tenant_id ) {
            abort( 403, 'Cliente não encontrado.' );
        }

        $customer->load( [ 'commonData', 'contact', 'address' ] );
        $areasOfActivity = AreaOfActivity::active()->get();
        $professions     = Profession::active()->get();

        return view( 'customers.edit-pessoa-fisica', [
            'customer'          => $customer,
            'areas_of_activity' => $areasOfActivity,
            'professions'       => $professions,
        ] );
    }

    /**
     * Formulário de edição - Pessoa Jurídica
     */
    public function editPessoaJuridica( Customer $customer ): View
    {
        // Verificar se o cliente pertence ao tenant do usuário
        if ( $customer->tenant_id !== Auth::user()->tenant_id ) {
            abort( 403, 'Cliente não encontrado.' );
        }

        $customer->load( [ 'commonData', 'contact', 'address' ] );
        $areasOfActivity = AreaOfActivity::active()->get();
        $professions     = Profession::active()->get();

        return view( 'customers.edit-pessoa-juridica', [
            'customer'          => $customer,
            'areas_of_activity' => $areasOfActivity,
            'professions'       => $professions,
        ] );
    }

    /**
     * Formulário de edição (método legado)
     */
    public function edit( Customer $customer ): View
    {
        // Verificar se o cliente pertence ao tenant do usuário
        if ( $customer->tenant_id !== Auth::user()->tenant_id ) {
            abort( 403, 'Cliente não encontrado.' );
        }

        $customer->load( [ 'commonData', 'contact', 'address' ] );
        $areasOfActivity = AreaOfActivity::active()->get();
        $professions     = Profession::active()->get();

        return view( 'pages.customer.edit', [
            'customer'          => $customer,
            'areas_of_activity' => $areasOfActivity,
            'professions'       => $professions,
        ] );
    }

    /**
     * Atualizar cliente - Pessoa Física
     */
    public function updatePessoaFisica( Customer $customer, CustomerPessoaFisicaRequest $request ): RedirectResponse
    {
        if ( $customer->tenant_id !== Auth::user()->tenant_id ) {
            abort( 403, 'Cliente não encontrado.' );
        }

        $validated = $request->validated();
        $tenantId  = Auth::user()->tenant_id;
        $result    = $this->customerService->updateCustomer( $customer->id, $validated, $tenantId );

        if ( !$result->isSuccess() ) {
            return back()->withInput()->with( 'error', $result->getMessage() );
        }

        $this->logOperation( 'customer_updated', [ 'customer_id' => $customer->id ] );

        return redirect()
            ->route( 'provider.customers.show', $customer )
            ->with( 'success', $result->getMessage() );
    }

    /**
     * Atualizar cliente - Pessoa Jurídica
     */
    public function updatePessoaJuridica( Customer $customer, CustomerPessoaJuridicaRequest $request ): RedirectResponse
    {
        if ( $customer->tenant_id !== Auth::user()->tenant_id ) {
            abort( 403, 'Cliente não encontrado.' );
        }

        $validated = $request->validated();
        $tenantId  = Auth::user()->tenant_id;
        $result    = $this->customerService->updateCustomer( $customer->id, $validated, $tenantId );

        if ( !$result->isSuccess() ) {
            return back()->withInput()->with( 'error', $result->getMessage() );
        }

        $this->logOperation( 'customer_updated', [ 'customer_id' => $customer->id ] );

        return redirect()
            ->route( 'provider.customers.show', $customer )
            ->with( 'success', $result->getMessage() );
    }

    /**
     * Atualizar cliente (método legado)
     */
    public function update( Customer $customer, Request $request ): RedirectResponse
    {
        if ( $customer->tenant_id !== Auth::user()->tenant_id ) {
            abort( 403, 'Cliente não encontrado.' );
        }

        // Detecta tipo baseado no campo enviado (cnpj ou cpf)
        $cnpj = $request->input( 'cnpj', '' );
        $cpf  = $request->input( 'cpf', '' );
        $isPJ = !empty( $cnpj );

        $formRequest = $isPJ
            ? app( CustomerPessoaJuridicaRequest::class)
            : app( CustomerPessoaFisicaRequest::class);

        $formRequest->setContainer( app() )
            ->setRedirector( app( 'redirect' ) )
            ->replace( $request->all() );

        $formRequest->validateResolved();
        $validated = $formRequest->validated();
        $tenantId  = Auth::user()->tenant_id;

        $result = $this->customerService->updateCustomer( $customer->id, $validated, $tenantId );

        if ( !$result->isSuccess() ) {
            return back()->withInput()->with( 'error', $result->getMessage() );
        }

        $this->logOperation( 'customer_updated', [ 'customer_id' => $customer->id ] );

        return redirect()
            ->route( 'provider.customers.show', $customer )
            ->with( 'success', $result->getMessage() );
    }

    /**
     * Remover cliente
     */
    public function destroy( Customer $customer ): RedirectResponse
    {
        // Verificar se o cliente pertence ao tenant do usuário
        if ( $customer->tenant_id !== Auth::user()->tenant_id ) {
            abort( 403, 'Cliente não encontrado.' );
        }

        $tenantId = Auth::user()->tenant_id;
        $result   = $this->customerService->deleteCustomer( $customer->id, $tenantId );

        if ( !$result->isSuccess() ) {
            return redirect()
                ->route( 'provider.customers.show', $customer )
                ->with( 'error', $result->getMessage() );
        }

        $this->logOperation( 'customer_deleted', [
            'customer_id' => $customer->id
        ] );

        return redirect()
            ->route( 'provider.customers.index' )
            ->with( 'success', $result->getMessage() );
    }

    /**
     * Restaurar cliente (soft delete)
     */
    public function restore( Customer $customer ): RedirectResponse
    {
        // Verificar se o cliente pertence ao tenant do usuário
        if ( $customer->tenant_id !== Auth::user()->tenant_id ) {
            abort( 403, 'Cliente não encontrado.' );
        }

        $customer->restore();

        $this->logOperation( 'customer_restored', [
            'customer_id' => $customer->id
        ] );

        return redirect()
            ->route( 'provider.customers.show', $customer )
            ->with( 'success', 'Cliente restaurado com sucesso.' );
    }

    /**
     * Alternar status do cliente (ativo/inativo)
     */
    public function toggleStatus( Customer $customer ): RedirectResponse
    {
        // Verificar se o cliente pertence ao tenant do usuário
        if ( $customer->tenant_id !== Auth::user()->tenant_id ) {
            abort( 403, 'Cliente não encontrado.' );
        }

        $tenantId = Auth::user()->tenant_id;
        $result   = $this->customerService->toggleStatus( $customer->id, $tenantId );

        if ( !$result->isSuccess() ) {
            return redirect()
                ->route( 'provider.customers.show', $customer )
                ->with( 'error', $result->getMessage() );
        }

        $updatedCustomer = $result->getData();
        $newStatus       = $updatedCustomer->status;
        $statusText      = $newStatus === 'active' ? 'ativado' : 'desativado';

        $this->logOperation( 'customer_status_toggled', [
            'customer_id' => $customer->id,
            'old_status'  => $customer->status,
            'new_status'  => $newStatus,
        ] );

        return redirect()
            ->route( 'provider.customers.show', $customer )
            ->with( 'success', "Cliente {$statusText} com sucesso." );
    }

    /**
     * Autocomplete para busca de clientes
     */
    public function autocomplete( Request $request ): \Illuminate\Http\JsonResponse
    {
        $request->validate( [
            'q' => 'required|string|min:2|max:50',
        ] );

        $customers = $this->customerService->listCustomers( [
            'search' => $request->q,
            'limit'  => 10,
        ] );

        $results = $customers->isSuccess() ? $customers->getData()->map( function ( $customer ) {
            return [
                'id'    => $customer->id,
                'text'  => $customer->full_name . ' (' . $customer->email . ')',
                'email' => $customer->email,
                'phone' => $customer->contact?->phone,
            ];
        } ) : collect();

        return response()->json( [ 'results' => $results ] );
    }

    /**
     * Dashboard de clientes
     */
    public function dashboard(): View
    {
        // Implementar métricas e estatísticas
        $stats = [
            'total_customers'          => 0,
            'active_customers'         => 0,
            'new_customers_this_month' => 0,
            'top_customers'            => collect(),
        ];

        return view( 'pages.customer.dashboard', [
            'stats' => $stats,
        ] );
    }

    /**
     * Busca AJAX de clientes
     */
    public function search( Request $request ): \Illuminate\Http\JsonResponse
    {
        Log::info( 'Método search foi chamado', [
            'user_id'     => Auth::id(),
            'tenant_id'   => Auth::user()->tenant_id,
            'search_term' => $request->input( 'search', '' ),
            'uri'         => $request->getUri()
        ] );

        try {
            $searchTerm = $request->input( 'search', '' );

            // Realiza a busca simplificada por ID
            return $this->simpleSearch( $searchTerm );

        } catch ( \Exception $e ) {
            Log::error( 'Erro na busca de clientes', [
                'user_id'     => Auth::id(),
                'search_term' => $request->input( 'search', '' ),
                'error'       => $e->getMessage()
            ] );

            return response()->json( [
                'success' => false,
                'message' => 'Erro na busca de clientes',
                'error'   => config( 'app.debug' ) ? $e->getMessage() : 'Erro interno do servidor'
            ], 500 );
        }
    }

    /**
     * Método de busca simplificado
     */
    private function simpleSearch( string $searchTerm ): \Illuminate\Http\JsonResponse
    {
        // Busca por ID com dados relacionados
        $customers = Customer::with( [ 'commonData', 'contact' ] )
            ->where( 'tenant_id', Auth::user()->tenant_id )
            ->when( !empty( $searchTerm ), function ( $query ) use ( $searchTerm ) {
                $query->where( 'id', 'like', "%{$searchTerm}%" );
            } )
            ->limit( 20 )
            ->get();

        $result = $customers->map( function ( $customer ) {
            $name = 'Cliente #' . $customer->id;
            if ( $customer->commonData ) {
                if ( $customer->commonData->first_name || $customer->commonData->last_name ) {
                    $name = trim( $customer->commonData->first_name . ' ' . $customer->commonData->last_name );
                } elseif ( $customer->commonData->company_name ) {
                    $name = $customer->commonData->company_name;
                }
            }

            return [
                'id'             => $customer->id,
                'customer_name'  => $name,
                'email'          => $customer->contact?->email ?? '',
                'email_business' => $customer->contact?->email_business ?? '',
                'phone'          => $customer->contact?->phone ?? '',
                'phone_business' => $customer->contact?->phone_business ?? '',
                'cpf'            => $customer->commonData?->cpf ?? '',
                'cnpj'           => $customer->commonData?->cnpj ?? '',
                'created_at'     => $customer->created_at->toISOString(),
            ];
        } );

        return response()->json( [
            'success' => true,
            'data'    => $result,
            'message' => 'Busca realizada com sucesso'
        ] );
    }

    /**
     * Log de operações
     */
    protected function logOperation( string $action, array $context = [] ): void
    {
        Log::info( "Customer {$action}", [
            'tenant_id'  => Auth::user()->tenant_id,
            'user_id'    => Auth::id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'context'    => $context,
        ] );
    }

}
