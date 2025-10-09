<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ServiceRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceStatus;
use App\Services\ServiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller para gestão de serviços - Interface Web
 *
 * Gerencia todas as operações relacionadas a serviços através
 * da interface web, incluindo CRUD, busca e filtros.
 */
class ServiceController extends Controller
{
    public function __construct(
        private ServiceService $serviceService,
    ) {}

    /**
     * Lista de serviços com filtros e paginação.
     */
    public function index( Request $request ): View
    {
        $filters = $request->only( [
            'search', 'status', 'customer_id', 'priority_level',
            'created_from', 'created_to', 'sort_by', 'sort_direction', 'per_page'
        ] );

        $services = $this->serviceService->searchServices( $filters, auth()->user() );

        // Dados adicionais para a view
        $customers = Customer::where( 'tenant_id', auth()->user()->tenant_id )
            ->active()
            ->ordered()
            ->get();

        $statuses = ServiceStatus::active()
            ->ordered()
            ->get();

        $stats = $this->serviceService->getServiceStats( auth()->user() );

        return view( 'pages.service.index', compact( 'services', 'customers', 'statuses', 'stats', 'filters' ) );
    }

    /**
     * Formulário de criação de serviço.
     */
    public function create(): View
    {
        $customers = Customer::where( 'tenant_id', auth()->user()->tenant_id )
            ->active()
            ->ordered()
            ->get();

        $products = Product::where( 'tenant_id', auth()->user()->tenant_id )
            ->active()
            ->ordered()
            ->get();

        $statuses = ServiceStatus::active()
            ->ordered()
            ->get();

        return view( 'services.create', compact( 'customers', 'products', 'statuses' ) );
    }

    /**
     * Salva serviço.
     */
    public function store( ServiceRequest $request ): RedirectResponse
    {
        try {
            $service = $this->serviceService->createService( $request->validated(), auth()->user() );

            return redirect()->route( 'services.show', $service->code )
                ->with( 'success', 'Serviço cadastrado com sucesso!' );

        } catch ( \Exception $e ) {
            return back()->withInput()
                ->with( 'error', 'Erro ao cadastrar serviço: ' . $e->getMessage() );
        }
    }

    /**
     * Exibe detalhes de um serviço.
     */
    public function show( Service $service ): View
    {
        // Verificar se o serviço pertence ao tenant do usuário
        if ( $service->tenant_id !== auth()->user()->tenant_id ) {
            abort( 403 );
        }

        $service->load( [ 'customer', 'items.product', 'status', 'attachments' ] );

        return view( 'services.show', compact( 'service' ) );
    }

    /**
     * Formulário de edição de serviço.
     */
    public function edit( Service $service ): View
    {
        // Verificar se o serviço pertence ao tenant do usuário
        if ( $service->tenant_id !== auth()->user()->tenant_id ) {
            abort( 403 );
        }

        $customers = Customer::where( 'tenant_id', auth()->user()->tenant_id )
            ->active()
            ->ordered()
            ->get();

        $products = Product::where( 'tenant_id', auth()->user()->tenant_id )
            ->active()
            ->ordered()
            ->get();

        $statuses = ServiceStatus::active()
            ->ordered()
            ->get();

        return view( 'services.edit', compact( 'service', 'customers', 'products', 'statuses' ) );
    }

    /**
     * Atualiza serviço.
     */
    public function update( ServiceRequest $request, Service $service ): RedirectResponse
    {
        // Verificar se o serviço pertence ao tenant do usuário
        if ( $service->tenant_id !== auth()->user()->tenant_id ) {
            abort( 403 );
        }

        try {
            $updatedService = $this->serviceService->updateService( $service, $request->validated(), auth()->user() );

            return redirect()->route( 'services.show', $updatedService->code )
                ->with( 'success', 'Serviço atualizado com sucesso!' );

        } catch ( \Exception $e ) {
            return back()->withInput()
                ->with( 'error', 'Erro ao atualizar serviço: ' . $e->getMessage() );
        }
    }

    /**
     * Altera status do serviço.
     */
    public function changeStatus( Request $request, Service $service ): RedirectResponse
    {
        // Verificar se o serviço pertence ao tenant do usuário
        if ( $service->tenant_id !== auth()->user()->tenant_id ) {
            abort( 403 );
        }

        $request->validate( [
            'status_id' => 'required|exists:service_statuses,id',
            'notes'     => 'nullable|string|max:1000',
        ] );

        try {
            $updatedService = $this->serviceService->changeServiceStatus(
                $service,
                $request->status_id,
                $request->notes,
                auth()->user(),
            );

            return redirect()->route( 'services.show', $updatedService->code )
                ->with( 'success', 'Status do serviço atualizado com sucesso!' );

        } catch ( \Exception $e ) {
            return back()->with( 'error', 'Erro ao alterar status: ' . $e->getMessage() );
        }
    }

    /**
     * Cancela serviço.
     */
    public function cancel( Service $service ): RedirectResponse
    {
        // Verificar se o serviço pertence ao tenant do usuário
        if ( $service->tenant_id !== auth()->user()->tenant_id ) {
            abort( 403 );
        }

        try {
            $this->serviceService->cancelService( $service, auth()->user() );

            return redirect()->route( 'services.show', $service->code )
                ->with( 'success', 'Serviço cancelado com sucesso!' );

        } catch ( \Exception $e ) {
            return back()->with( 'error', 'Erro ao cancelar serviço: ' . $e->getMessage() );
        }
    }

    /**
     * Remove serviço (soft delete).
     */
    public function destroy( Service $service ): RedirectResponse
    {
        // Verificar se o serviço pertence ao tenant do usuário
        if ( $service->tenant_id !== auth()->user()->tenant_id ) {
            abort( 403 );
        }

        try {
            $this->serviceService->deleteService( $service, auth()->user() );

            return redirect()->route( 'services.index' )
                ->with( 'success', 'Serviço removido com sucesso!' );

        } catch ( \Exception $e ) {
            return back()->with( 'error', 'Erro ao remover serviço: ' . $e->getMessage() );
        }
    }

    /**
     * Busca serviços via AJAX.
     */
    public function search( Request $request ): \Illuminate\Http\JsonResponse
    {
        $request->validate( [
            'query' => 'required|string|min:2',
        ] );

        $services = Service::where( 'tenant_id', auth()->user()->tenant_id )
            ->where( function ( $query ) use ( $request ) {
                $query->where( 'title', 'like', "%{$request->query}%" )
                    ->orWhere( 'code', 'like', "%{$request->query}%" )
                    ->orWhere( 'description', 'like', "%{$request->query}%" )
                    ->orWhereHas( 'customer', function ( $q ) use ( $request ) {
                        $q->where( 'company_name', 'like', "%{$request->query}%" )
                            ->orWhereHas( 'commonData', function ( $subQ ) use ( $request ) {
                                $subQ->where( 'first_name', 'like', "%{$request->query}%" )
                                    ->orWhere( 'last_name', 'like', "%{$request->query}%" );
                            } );
                    } );
            } )
            ->limit( 10 )
            ->get();

        return response()->json( [
            'services' => $services->map( function ( $service ) {
                return [
                    'id'       => $service->id,
                    'code'     => $service->code,
                    'text'     => $service->title,
                    'customer' => $service->customer?->name ?? $service->customer?->company_name,
                    'status'   => $service->status?->name,
                ];
            } )
        ] );
    }

    /**
     * Imprime serviço.
     */
    public function print( Service $service ): View
    {
        // Verificar se o serviço pertence ao tenant do usuário
        if ( $service->tenant_id !== auth()->user()->tenant_id ) {
            abort( 403 );
        }

        $service->load( [ 'customer', 'items.product', 'status' ] );

        return view( 'services.print', compact( 'service' ) );
    }

    /**
     * Visualiza status do serviço (rota pública com token).
     */
    public function viewServiceStatus( string $code, string $token ): View
    {
        $service = $this->serviceService->getServiceByCodeAndToken( $code, $token );

        if ( !$service ) {
            abort( 404, 'Serviço não encontrado ou token inválido.' );
        }

        return view( 'services.public-status', compact( 'service' ) );
    }

    /**
     * Altera status do serviço (rota pública com token).
     */
    public function chooseServiceStatus( Request $request ): RedirectResponse
    {
        $request->validate( [
            'code'      => 'required|string',
            'token'     => 'required|string',
            'status_id' => 'required|exists:service_statuses,id',
            'notes'     => 'nullable|string|max:1000',
        ] );

        try {
            $service = $this->serviceService->changeServiceStatusByToken(
                $request->code,
                $request->token,
                $request->status_id,
                $request->notes,
            );

            return redirect()->back()
                ->with( 'success', 'Status do serviço atualizado com sucesso!' );

        } catch ( \Exception $e ) {
            return back()->with( 'error', 'Erro ao alterar status: ' . $e->getMessage() );
        }
    }

}
