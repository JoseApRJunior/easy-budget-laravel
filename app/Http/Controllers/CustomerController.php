<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
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
        $customers = $this->customerService->listCustomers( $filters );

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
     * Formulário de criação de cliente
     */
    public function create(): View
    {
        $areasOfActivity = AreaOfActivity::active()->get();
        $professions     = Profession::active()->get();

        return view( 'pages.customer.create', [
            'areas_of_activity' => $areasOfActivity,
            'professions'       => $professions,
            'customer'          => new Customer(), // Instância vazia para criação
        ] );
    }

    /**
     * Criar cliente (Pessoa Física ou Jurídica)
     */
    public function store( Request $request ): RedirectResponse
    {
        // Determinar tipo baseado nos dados enviados
        $customerType = $this->determineCustomerType( $request );

        Log::info( 'Iniciando criação de cliente', [
            'customer_type' => $customerType,
            'data'          => $request->all(),
            'user_id'       => Auth::id(),
            'tenant_id'     => Auth::user()?->tenant_id
        ] );

        try {
            $result = $this->customerService->createCustomer( $request->all(), $customerType );

            if ( !$result->isSuccess() ) {
                Log::error( 'Erro ao criar cliente', [
                    'customer_type' => $customerType,
                    'error'         => $result->getMessage(),
                    'data'          => $request->all(),
                    'user_id'       => Auth::id()
                ] );

                return redirect()
                    ->route( 'provider.customers.create' )
                    ->with( 'error', $result->getMessage() );
            }

            Log::info( 'Cliente criado com sucesso', [
                'customer_type' => $customerType,
                'customer_id'   => $result->getData()->id,
                'user_id'       => Auth::id()
            ] );

            $this->logOperation( 'customer_created', [
                'customer_id' => $result->getData()->id,
                'type'        => $customerType
            ] );

            return redirect()
                ->route( 'provider.customers.show', $result->getData() )
                ->with( 'success', $result->getMessage() );

        } catch ( \Throwable $e ) {
            Log::error( 'Exceção ao criar cliente', [
                'customer_type' => $customerType,
                'exception'     => $e->getMessage(),
                'trace'         => $e->getTraceAsString(),
                'data'          => $request->all(),
                'user_id'       => Auth::id()
            ] );

            return redirect()
                ->route( 'provider.customers.create' )
                ->with( 'error', 'Erro interno do servidor. Tente novamente mais tarde.' );
        }
    }

    /**
     * Determinar tipo de cliente baseado nos dados enviados
     */
    private function determineCustomerType( Request $request ): string
    {
        // Se tem CNPJ, é pessoa jurídica
        if ( $request->filled( 'document' ) && strlen( preg_replace( '/\D/', '', $request->document ) ) === 14 ) {
            return 'pessoa_juridica';
        }

        // Se tem CPF, é pessoa física
        if ( $request->filled( 'document' ) && strlen( preg_replace( '/\D/', '', $request->document ) ) === 11 ) {
            return 'pessoa_fisica';
        }

        // Fallback: verificar se tem dados empresariais
        if ( $request->filled( [ 'company_name', 'company_email' ] ) ) {
            return 'pessoa_juridica';
        }

        // Default: pessoa física
        return 'pessoa_fisica';
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
     * Formulário de edição
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
     * Atualizar cliente
     */
    public function update( Customer $customer, Request $request ): RedirectResponse
    {
        // Verificar se o cliente pertence ao tenant do usuário
        if ( $customer->tenant_id !== Auth::user()->tenant_id ) {
            abort( 403, 'Cliente não encontrado.' );
        }

        // Usar validação apropriada baseada no tipo de cliente
        if ( $customer->isCompany() ) {
            $request->validate( [
                // Regras para pessoa jurídica
                'company_name'  => 'required|string|max:255',
                'document'      => 'required|string|size:14|unique:customers,document,' . $customer->id,
                'company_email' => 'required|email|unique:customers,email,' . $customer->id,
                // ... outras regras
            ] );
        } else {
            $request->validate( [
                // Regras para pessoa física
                'name'     => 'required|string|max:255',
                'document' => 'required|string|size:11|unique:customers,document,' . $customer->id,
                'email'    => 'required|email|unique:customers,email,' . $customer->id,
                // ... outras regras
            ] );
        }

        $result = $this->customerService->updateCustomer( $customer->id, $request->all() );

        if ( !$result->isSuccess() ) {
            return redirect()
                ->route( 'provider.customers.edit', $customer )
                ->with( 'error', $result->getMessage() );
        }

        $this->logOperation( 'customer_updated', [
            'customer_id' => $customer->id,
            'changes'     => $request->all()
        ] );

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

        $result = $this->customerService->deleteCustomer( $customer->id );

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
     * Duplicar cliente
     */
    public function duplicate( Customer $customer ): RedirectResponse
    {
        // Verificar se o cliente pertence ao tenant do usuário
        if ( $customer->tenant_id !== Auth::user()->tenant_id ) {
            abort( 403, 'Cliente não encontrado.' );
        }

        // Implementar lógica de duplicação
        $duplicateData = $customer->toArray();
        unset( $duplicateData[ 'id' ], $duplicateData[ 'created_at' ], $duplicateData[ 'updated_at' ] );

        // Modificar campos únicos
        $duplicateData[ 'email' ]    = 'copia-' . $duplicateData[ 'email' ];
        $duplicateData[ 'document' ] = 'copia-' . $duplicateData[ 'document' ];

        $result = $this->customerService->createCustomer( $duplicateData );

        if ( !$result->isSuccess() ) {
            return redirect()
                ->route( 'provider.customers.show', $customer )
                ->with( 'error', 'Erro ao duplicar cliente: ' . $result->getMessage() );
        }

        $this->logOperation( 'customer_duplicated', [
            'original_customer_id' => $customer->id,
            'new_customer_id'      => $result->getData()->id
        ] );

        return redirect()
            ->route( 'provider.customers.show', $result->getData() )
            ->with( 'success', 'Cliente duplicado com sucesso.' );
    }

    /**
     * Buscar clientes próximos (geolocalização)
     */
    public function findNearby( Request $request ): View
    {
        $request->validate( [
            'lat'    => 'required|numeric|between:-90,90',
            'lng'    => 'required|numeric|between:-180,180',
            'radius' => 'nullable|integer|min:1|max:100',
        ] );

        // Implementar busca por geolocalização
        $customers = collect(); // Placeholder

        return view( 'pages.customer.nearby', [
            'customers' => $customers,
            'lat'       => $request->lat,
            'lng'       => $request->lng,
            'radius'    => $request->radius ?? 10,
        ] );
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
     * Exportar clientes
     */
    public function export( Request $request ): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        // Implementar exportação CSV/Excel
        $filename = 'clientes_' . now()->format( 'Y-m-d_H-i-s' ) . '.csv';

        // Placeholder - implementar lógica real
        $content = "Nome,Email,Telefone\nJoão Silva,joao@email.com,(11) 99999-9999\n";

        return response()->streamDownload( function () use ($content) {
            echo $content;
        }, $filename, [ 'Content-Type' => 'text/csv' ] );
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
     * Log de operações para auditoria
     */
    protected function logOperation( string $action, array $context = [] ): void
    {
        Log::info( "Customer operation: {$action}", [
            'user_id'    => Auth::id(),
            'tenant_id'  => Auth::user()?->tenant_id,
            'action'     => $action,
            'context'    => $context,
            'ip'         => request()->ip(),
            'user_agent' => request()->userAgent(),
        ] );
    }

}
