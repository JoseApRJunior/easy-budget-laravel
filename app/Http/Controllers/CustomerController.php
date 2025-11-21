<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\CustomerPessoaFisicaRequest;
use App\Http\Requests\CustomerPessoaJuridicaRequest;
use App\Models\AreaOfActivity;
use App\Models\Customer;
use App\Models\Profession;
use App\Models\User;
use App\Services\Domain\CustomerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Controller para gestão de clientes - Interface Web
 * Versão simplificada para resolver problemas de sintaxe
 */
class CustomerController extends Controller
{
    public function __construct(
        private CustomerService $customerService,
    ) {}

    /**
     * Lista todos os clientes
     */
    public function index( Request $request ): View
    {
        /** @var User $user */
        $user = Auth::user();

        $customers = Customer::query()
            ->where( 'tenant_id', $user->tenant_id )
            ->orderBy( 'name' )
            ->paginate( 20 );

        return view( 'pages.customer.index', [
            'customers' => $customers,
        ] );
    }

    /**
     * Formulário de criação de cliente
     */
    public function create(): View
    {
        $areasOfActivity = AreaOfActivity::query()
            ->where( 'is_active', true )
            ->orderBy( 'name' )
            ->get();

        $professions = Profession::query()
            ->where( 'is_active', true )
            ->orderBy( 'name' )
            ->get();

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
        try {
            /** @var User $user */
            $user = Auth::user();
            $this->customerService->createPessoaFisica( $request->validated(), $user->tenant_id );

            return redirect()->route( 'customers.index' )
                ->with( 'success', 'Cliente criado com sucesso!' );
        } catch ( \Exception $e ) {
            return redirect()->back()
                ->withInput()
                ->with( 'error', 'Erro ao criar cliente: ' . $e->getMessage() );
        }
    }

    /**
     * Mostrar detalhes do cliente
     */
    public function show( Customer $customer ): View
    {
        $this->authorize( 'view', $customer );

        return view( 'pages.customer.show', [
            'customer' => $customer->load( [ 'commonData', 'contact', 'address' ] ),
        ] );
    }

    /**
     * Dashboard de clientes
     */
    public function dashboard( Request $request ): View
    {
        /** @var User $user */
        $user     = Auth::user();
        $tenantId = (int) ( $user->tenant_id ?? 0 );

        // Usa o CustomerService para obter estatísticas de forma consistente
        $result = $this->customerService->getCustomerStats( $tenantId );

        if ( !$result->isSuccess() ) {
            // Em caso de erro, retorna estatísticas vazias
            $stats = [
                'total_customers'    => 0,
                'active_customers'   => 0,
                'inactive_customers' => 0,
                'recent_customers'   => collect(),
            ];
        } else {
            $stats = $result->getData();
        }

        return view( 'pages.customer.dashboard', compact( 'stats' ) );
    }

    public function createPessoaFisica(): View
    {
        $areasOfActivity = AreaOfActivity::query()
            ->where( 'is_active', true )
            ->orderBy( 'name' )
            ->get();

        $professions = Profession::query()
            ->where( 'is_active', true )
            ->orderBy( 'name' )
            ->get();

        return view( 'pages.customer.create', [
            'areas_of_activity' => $areasOfActivity,
            'professions'       => $professions,
            'customer'          => new Customer(),
        ] );
    }

    public function createPessoaJuridica(): View
    {
        $areasOfActivity = AreaOfActivity::query()
            ->where( 'is_active', true )
            ->orderBy( 'name' )
            ->get();

        $professions = Profession::query()
            ->where( 'is_active', true )
            ->orderBy( 'name' )
            ->get();

        return view( 'pages.customer.create', [
            'areas_of_activity' => $areasOfActivity,
            'professions'       => $professions,
            'customer'          => new Customer(),
        ] );
    }

    public function storePessoaJuridica( CustomerPessoaJuridicaRequest $request ): RedirectResponse
    {
        try {
            /** @var User $user */
            $user = Auth::user();
            $this->customerService->createPessoaJuridica( $request->validated(), $user->tenant_id );

            return redirect()->route( 'customers.index' )
                ->with( 'success', 'Cliente criado com sucesso!' );
        } catch ( \Exception $e ) {
            return redirect()->back()
                ->withInput()
                ->with( 'error', 'Erro ao criar cliente: ' . $e->getMessage() );
        }
    }

    public function store( Request $request ): RedirectResponse
    {
        try {
            /** @var User $user */
            $user      = Auth::user();
            $tenantId  = (int) ( $user->tenant_id ?? 0 );
            $isCompany = filled( $request->input( 'cnpj' ) )
                || in_array( $request->input( 'type' ), [ 'company', 'pj' ], true )
                || in_array( $request->input( 'customer_type' ), [ 'company', 'pj' ], true );

            if ( $isCompany ) {
                $validated = $request->validate( [
                    'first_name'          => 'required|string|max:100',
                    'last_name'           => 'required|string|max:100',
                    'company_name'        => 'required|string|max:255',
                    'area_of_activity_id' => 'required|integer|exists:areas_of_activity,id',
                    'profession_id'       => 'nullable|integer|exists:professions,id',
                    'description'         => 'nullable|string|max:500',
                    'website'             => 'nullable|url|max:255',
                    'email_personal'      => 'required|email|max:255',
                    'phone_personal'      => 'required|string|regex:/^\(\d{2}\) \d{4,5}-\d{4}$/',
                    'email_business'      => 'required|email|max:255',
                    'phone_business'      => 'nullable|string|regex:/^\(\d{2}\) \d{4,5}-\d{4}$/',
                    'cnpj'                => 'required|string|regex:/^(?:\d{14}|\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2})$/',
                    'cep'                 => 'required|string|regex:/^\d{5}-?\d{3}$/',
                    'address'             => 'required|string|max:255',
                    'address_number'      => 'nullable|string|max:20',
                    'neighborhood'        => 'required|string|max:100',
                    'city'                => 'required|string|max:100',
                    'state'               => 'required|string|size:2|alpha',
                ] );

                $result = $this->customerService->createPessoaJuridica( $validated, $tenantId );
            } else {
                $validated = $request->validate( [
                    'first_name'          => 'required|string|max:100',
                    'last_name'           => 'required|string|max:100',
                    'birth_date'          => 'nullable|string',
                    'area_of_activity_id' => 'nullable|integer|exists:areas_of_activity,id',
                    'profession_id'       => 'nullable|integer|exists:professions,id',
                    'description'         => 'nullable|string|max:500',
                    'website'             => 'nullable|url|max:255',
                    'email_personal'      => 'required|email|max:255',
                    'phone_personal'      => 'required|string|regex:/^\(\d{2}\) \d{4,5}-\d{4}$/',
                    'cpf'                 => 'required|string|regex:/^(?:\d{11}|\d{3}\.\d{3}\.\d{3}-\d{2})$/',
                    'cep'                 => 'required|string|regex:/^\d{5}-?\d{3}$/',
                    'address'             => 'required|string|max:255',
                    'address_number'      => 'nullable|string|max:20',
                    'neighborhood'        => 'required|string|max:100',
                    'city'                => 'required|string|max:100',
                    'state'               => 'required|string|size:2|alpha',
                ] );

                $result = $this->customerService->createPessoaFisica( $validated, $tenantId );
            }

            if ( !$result->isSuccess() ) {
                return redirect()->back()->withInput()->with( 'error', $result->getMessage() );
            }

            return redirect()->route( 'customers.index' )
                ->with( 'success', 'Cliente criado com sucesso!' );
        } catch ( \Exception $e ) {
            return redirect()->back()
                ->withInput()
                ->with( 'error', 'Erro ao criar cliente: ' . $e->getMessage() );
        }
    }

}
