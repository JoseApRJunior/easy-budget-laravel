<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\CustomerPessoaFisicaRequest;
use App\Http\Requests\CustomerPessoaJuridicaRequest;
use App\Models\AreaOfActivity;
use App\Models\Customer;
use App\Models\Profession;
use App\Services\Domain\CustomerService;
use Illuminate\Http\JsonResponse;
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

        $customerCollection = $customers->getData();
        // Verificar se é uma collection ou paginator
        if ( method_exists( $customerCollection, 'total' ) ) {
            // É um paginator
            $customerData = $customerCollection->items();
        } else {
            // É uma collection simples
            $customerData = $customerCollection;
        }

        return view( 'pages.customer.index', [
            'customers'         => $customerData,
            'customerPaginator' => method_exists( $customerCollection, 'total' ) ? $customerCollection : null,
            'filters'           => $filters,
            'areas_of_activity' => AreaOfActivity::active()->get(),
            'totalCustomers'    => method_exists( $customerCollection, 'total' ) ? $customerCollection->total() : $customerCollection->count(),
        ] );
    }

    /**
     * Formulário de criação de cliente - Pessoa Física
     */
    public function createPessoaFisica(): View
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
     * Formulário de criação de cliente - Pessoa Jurídica
     */
    public function createPessoaJuridica(): View
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

        return view( 'pages.customer.edit', [
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

        return view( 'pages.customer.edit', [
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

        $tenantId = Auth::user()->tenant_id;

        // Usa getFilteredCustomers garantindo uso explícito do tenantId
        $serviceResult = $this->customerService->getFilteredCustomers(
            [ 'search' => $request->q, 'limit' => 10 ],
            $tenantId,
        );

        if ( !$serviceResult->isSuccess() ) {
            return response()->json( [
                'results' => [],
                'error'   => $serviceResult->getMessage(),
            ], 422 );
        }

        $customers = $serviceResult->getData();

        // Normaliza paginator/collection para iterável simples
        if ( method_exists( $customers, 'items' ) ) {
            $customers = collect( $customers->items() );
        }

        $results = $customers->map( function ( $customer ) {
            $common  = $customer->commonData ?? $customer->common_data ?? null;
            $contact = $customer->contact ?? null;

            $name = $common?->first_name || $common?->last_name
                ? trim( ( $common->first_name ?? '' ) . ' ' . ( $common->last_name ?? '' ) )
                : ( $common->company_name ?? 'Cliente' );

            $email = $contact->email_personal
                ?? $contact->email_business
                ?? null;

            $phone = $contact->phone_personal
                ?? $contact->phone_business
                ?? null;

            return [
                'id'    => $customer->id,
                'text'  => $name . ( $email ? " ({$email})" : '' ),
                'email' => $email,
                'phone' => $phone,
            ];
        } );

        return response()->json( [ 'results' => $results ] );
    }

    /**
     * Dashboard de clientes
     */
    public function dashboard(): View
    {
        $tenantId    = Auth::user()->tenant_id;
        $statsResult = $this->customerService->getCustomerStats( $tenantId );

        $stats = $statsResult->isSuccess()
            ? $statsResult->getData()
            : [
                'total_customers'    => 0,
                'active_customers'   => 0,
                'inactive_customers' => 0,
                'recent_customers'   => collect(),
            ];

        return view( 'pages.customer.dashboard', [
            'stats' => $stats,
        ] );
    }

    /**
     * Busca AJAX avançada de clientes com múltiplos filtros
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * Campos pesquisados:
     * - ID do cliente
     * - Dados pessoais (nome, sobrenome, empresa, CPF, CNPJ)
     * - Dados de contato (emails, telefones)
     *
     * @example /provider/customers/search?search=joao
     * @example /provider/customers/search?search=12345678901
     * @example /provider/customers/search?search=empresa@email.com
     */
    public function search( Request $request ): \Illuminate\Http\JsonResponse
    {
        try {
            $searchTerm = trim( $request->input( 'search', '' ) );

            Log::info( 'Busca de clientes realizada', [
                'user_id'     => Auth::id(),
                'tenant_id'   => Auth::user()->tenant_id,
                'search_term' => $searchTerm,
                'uri'         => $request->getUri()
            ] );

            // Se busca estiver vazia, retorna todos os clientes
            if ( empty( $searchTerm ) || $searchTerm === '' ) {
                Log::info( 'Executando busca vazia - retornando todos os clientes' );
                return $this->getAllCustomers();
            }

            // Realiza busca avançada em múltiplos campos
            Log::info( 'Executando busca avançada com termo', [ 'search_term' => $searchTerm ] );
            return $this->advancedSearch( $searchTerm );

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
     * Busca avançada em múltiplos campos
     */
    private function advancedSearch( string $searchTerm ): \Illuminate\Http\JsonResponse
    {
        $tenantId = Auth::user()->tenant_id;

        // Busca em múltiplos campos
        $customers = Customer::with( [ 'commonData', 'contact' ] )
            ->where( 'tenant_id', $tenantId )
            ->where( function ( $query ) use ( $searchTerm ) {
                // Busca por ID do cliente
                $query->where( 'id', 'like', "%{$searchTerm}%" );

                // Busca nos dados pessoais (se existirem)
                $query->orWhereHas( 'commonData', function ( $subQuery ) use ( $searchTerm ) {
                    $subQuery->where( 'first_name', 'like', "%{$searchTerm}%" )
                        ->orWhere( 'last_name', 'like', "%{$searchTerm}%" )
                        ->orWhere( 'company_name', 'like', "%{$searchTerm}%" )
                        ->orWhere( 'cpf', 'like', "%{$searchTerm}%" )
                        ->orWhere( 'cnpj', 'like', "%{$searchTerm}%" );
                } );

                // Busca nos dados de contato (se existirem)
                $query->orWhereHas( 'contact', function ( $subQuery ) use ( $searchTerm ) {
                    $subQuery->where( 'email_personal', 'like', "%{$searchTerm}%" )
                        ->orWhere( 'email_business', 'like', "%{$searchTerm}%" )
                        ->orWhere( 'phone_personal', 'like', "%{$searchTerm}%" )
                        ->orWhere( 'phone_business', 'like', "%{$searchTerm}%" );
                } );
            } )
            ->limit( 20 )
            ->get();

        $result = $customers->map( function ( $customer ) {
            $name = 'Cliente #' . $customer->id;

            // Acessar os dados relacionados diretamente (hasOne)
            $commonData = $customer->commonData;
            $contact = $customer->contact;

            if ( $commonData ) {
                if ( $commonData->first_name || $commonData->last_name ) {
                    $name = trim( $commonData->first_name . ' ' . $commonData->last_name );
                } elseif ( $commonData->company_name ) {
                    $name = $commonData->company_name;
                }
            }

            return [
                'id'             => $customer->id,
                'customer_name'  => $name,
                'email'          => $contact?->email_personal ?? '',
                'email_business' => $contact?->email_business ?? '',
                'phone'          => $contact?->phone_personal ?? '',
                'phone_business' => $contact?->phone_business ?? '',
                'cpf'            => $commonData?->cpf ?? '',
                'cnpj'           => $commonData?->cnpj ?? '',
                'created_at'     => $customer->created_at->toISOString(),
            ];
        } );

        return response()->json( [
            'success' => true,
            'data'    => $result,
            'message' => sprintf( 'Busca avançada realizada com sucesso (%d resultados)', count( $result ) )
        ] );
    }

    /**
     * Busca todos os clientes (para busca vazia)
     */
    private function getAllCustomers(): \Illuminate\Http\JsonResponse
    {
        try {
            $tenantId = Auth::user()->tenant_id;

            Log::info( 'Iniciando busca vazia de todos os clientes', [
                'tenant_id' => $tenantId,
                'user_id'   => Auth::id()
            ] );

            // Carrega clientes com dados relacionados de forma segura
            $customers = Customer::where( 'tenant_id', $tenantId )
                ->with( [
                    'commonData' => function ( $query ) {
                        $query->select( 'id', 'tenant_id', 'customer_id', 'first_name', 'last_name', 'company_name', 'cpf', 'cnpj' );
                    },
                    'contact'    => function ( $query ) {
                        $query->select( 'id', 'tenant_id', 'customer_id', 'email_personal', 'email_business', 'phone_personal', 'phone_business' );
                    }
                ] )
                ->select( 'id', 'tenant_id', 'status', 'created_at' )
                ->limit( 20 )
                ->get();

            Log::info( 'Clientes encontrados na busca vazia', [
                'total_clientes' => $customers->count(),
                'tenant_id'      => $tenantId
            ] );

            $result = $customers->map( function ( $customer ) {
                $name = 'Cliente #' . $customer->id;

                // Acessar os dados relacionados diretamente (hasOne)
                $commonData = $customer->commonData;
                $contact = $customer->contact;

                if ( $commonData ) {
                    if ( $commonData->first_name || $commonData->last_name ) {
                        $name = trim( $commonData->first_name . ' ' . $commonData->last_name );
                    } elseif ( $commonData->company_name ) {
                        $name = $commonData->company_name;
                    }
                }

                return [
                    'id'             => $customer->id,
                    'customer_name'  => $name,
                    'email'          => $contact?->email_personal ?? '',
                    'email_business' => $contact?->email_business ?? '',
                    'phone'          => $contact?->phone_personal ?? '',
                    'phone_business' => $contact?->phone_business ?? '',
                    'cpf'            => $commonData?->cpf ?? '',
                    'cnpj'           => $commonData?->cnpj ?? '',
                    'created_at'     => $customer->created_at->toISOString(),
                ];
            } );

            return response()->json( [
                'success' => true,
                'data'    => $result,
                'message' => sprintf( 'Mostrando todos os clientes (%d resultados)', count( $result ) )
            ] );

        } catch ( \Exception $e ) {
            Log::error( 'Erro na busca de todos os clientes', [
                'tenant_id' => Auth::user()->tenant_id,
                'error'     => $e->getMessage()
            ] );

            return response()->json( [
                'success' => false,
                'message' => 'Erro ao carregar todos os clientes',
                'error'   => config( 'app.debug' ) ? $e->getMessage() : 'Erro interno do servidor'
            ], 500 );
        }
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

    /**
     * AJAX endpoint para buscar clientes com filtros.
     */
    public function ajaxSearch( Request $request ): JsonResponse
    {
        $filters = $request->only(['search','status','date_from','date_to']);
        $tenantId = auth()->user()->tenant_id;
        $result = $this->customerService->getFilteredCustomers($filters, $tenantId);
        return $result->isSuccess()
            ? response()->json(['success' => true, 'data' => $result->getData()])
            : response()->json(['success' => false, 'message' => $result->getMessage()], 400);
    }

}
