<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ServiceStatusEnum;
use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\ServiceRequest;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Service;
use App\Models\UserConfirmationToken;
use App\Services\Domain\BudgetService;
use App\Services\Domain\CategoryService;
use App\Services\Domain\ProductService;
use App\Services\Domain\ServiceService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Controller para gestão de serviços - Interface Web
 *
 * Gerencia todas as operações relacionadas a serviços através
 * da interface web, incluindo CRUD, busca e filtros.
 */
class ServiceController extends Controller
{
    protected ServiceService  $serviceService;
    protected CategoryService $categoryService;
    protected BudgetService   $budgetService;
    protected ProductService  $productService;

    public function __construct(
        ServiceService $serviceService,
        CategoryService $categoryService,
        BudgetService $budgetService,
        ProductService $productService,
    ) {
        $this->serviceService  = $serviceService;
        $this->categoryService = $categoryService;
        $this->budgetService   = $budgetService;
        $this->productService  = $productService;
    }

    /**
     * Show the form for creating a new service.
     */
    public function create( ?string $budgetCode = null ): View
    {
        try {
            $budget = null;

            if ( $budgetCode ) {
                $budgetResult = $this->budgetService->findByCode( $budgetCode );
                if ( $budgetResult->isSuccess() ) {
                    $budget = $budgetResult->getData();
                }
            }

            return view( 'services.create', [
                'budget'        => $budget,
                'categories'    => $this->categoryService->getActive(),
                'products'      => $this->productService->getActive(),
                'budgets'       => $this->budgetService->getNotCompleted(),
                'statusOptions' => ServiceStatusEnum::cases()
            ] );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao carregar formulário de criação', [
                'error'      => $e->getMessage(),
                'budgetCode' => $budgetCode
            ] );
            abort( 500, 'Erro ao carregar formulário de criação' );
        }
    }

    /**
     * Display a listing of the services.
     */
    public function index( Request $request ): View
    {
        try {
            $filters = $request->only( [ 'status', 'category_id', 'date_from', 'date_to', 'search' ] );

            $result = $this->serviceService->getFilteredServices( $filters, [
                'category:id,name',
                'budget.customer.commonData',
                'serviceStatus'
            ] );

            if ( !$result->isSuccess() ) {
                abort( 500, 'Erro ao carregar lista de serviços' );
            }

            $services = $result->getData();

            return view( 'services.index', [
                'services'      => $services,
                'filters'       => $filters,
                'statusOptions' => ServiceStatusEnum::cases(),
                'categories'    => $this->categoryService->getActive()
            ] );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao carregar serviços', [
                'error'   => $e->getMessage(),
                'filters' => $request->only( [ 'status', 'category_id', 'date_from', 'date_to', 'search' ] )
            ] );
            abort( 500, 'Erro ao carregar serviços' );
        }
    }

    /**
     * Display the service status page for public access.
     */
    public function viewServiceStatus( string $code, string $token ): View|RedirectResponse
    {
        try {
            // Find the service by code and token
            $service = Service::where( 'code', $code )
                ->whereHas( 'userConfirmationToken', function ( $query ) use ( $token ) {
                    $query->where( 'token', $token )
                        ->where( 'expires_at', '>', now() );
                } )
                ->with( [ 'customer', 'serviceStatus', 'userConfirmationToken', 'budget' ] )
                ->first();

            if ( !$service ) {
                Log::warning( 'Service not found or token expired', [
                    'code'  => $code,
                    'token' => $token,
                    'ip'    => request()->ip()
                ] );
                return redirect()->route( 'error.not-found' );
            }

            return view( 'services.public.view-status', [
                'service' => $service,
                'token'   => $token
            ] );

        } catch ( \Exception $e ) {
            Log::error( 'Error in viewServiceStatus', [
                'code'  => $code,
                'token' => $token,
                'error' => $e->getMessage(),
                'ip'    => request()->ip()
            ] );
            return redirect()->route( 'error.internal' );
        }
    }

    /**
     * Process the service status selection for public access.
     */
    public function chooseServiceStatus( Request $request ): RedirectResponse
    {
        try {
            $request->validate( [
                'service_code'      => 'required|string',
                'token'             => 'required|string|size:43', // base64url format: 32 bytes = 43 caracteres
                'service_status_id' => [ 'required', 'string', 'in:' . implode( ',', array_map( fn( $status ) => $status->value, ServiceStatusEnum::cases() ) ) ]
            ] );

            // Find the service by code and token
            $service = Service::where( 'code', $request->service_code )
                ->whereHas( 'userConfirmationToken', function ( $query ) use ( $request ) {
                    $query->where( 'token', $request->token )
                        ->where( 'expires_at', '>', now() );
                } )
                ->with( [ 'customer', 'serviceStatus', 'userConfirmationToken' ] )
                ->first();

            if ( !$service ) {
                Log::warning( 'Service not found or token expired in choose status', [
                    'code'  => $request->service_code,
                    'token' => $request->token,
                    'ip'    => request()->ip()
                ] );
                return redirect()->route( 'error.not-found' );
            }

            // Validate that the selected status is allowed
            $allowedStatuses = [ ServiceStatusEnum::APPROVED->value, ServiceStatusEnum::REJECTED->value, ServiceStatusEnum::CANCELLED->value ];
            if ( !in_array( $request->service_status_id, $allowedStatuses ) ) {
                Log::warning( 'Invalid service status selected', [
                    'service_code' => $request->service_code,
                    'status_id'    => $request->service_status_id,
                    'ip'           => request()->ip()
                ] );
                return redirect()->back()->with( 'error', 'Status inválido selecionado.' );
            }

            // Update service status
            $service->update( [
                'service_statuses_id' => $request->service_status_id,
                'updated_at'          => now()
            ] );

            // Log the action
            $newStatusEnum = ServiceStatusEnum::tryFrom( $request->service_status_id );
            $oldStatusEnum = $service->serviceStatus; // Uses the accessor to get the enum
            Log::info( 'Service status updated via public link', [
                'service_id'   => $service->id,
                'service_code' => $service->code,
                'old_status'   => $oldStatusEnum?->getName() ?? 'Unknown',
                'new_status'   => $newStatusEnum?->getName() ?? 'Unknown',
                'ip'           => request()->ip()
            ] );

            return redirect()->route( 'services.public.view-status', [
                'code'  => $service->code,
                'token' => $request->token
            ] )->with( 'success', 'Status do serviço atualizado com sucesso!' );

        } catch ( \Exception $e ) {
            Log::error( 'Error in chooseServiceStatus', [
                'error'   => $e->getMessage(),
                'request' => $request->all(),
                'ip'      => request()->ip()
            ] );
            return redirect()->route( 'error.internal' );
        }
    }

    /**
     * Print service for public access.
     */
    public function print( string $code, string $token ): View|RedirectResponse
    {
        try {
            // Find the service by code and token
            $service = Service::where( 'code', $code )
                ->whereHas( 'userConfirmationToken', function ( $query ) use ( $token ) {
                    $query->where( 'token', $token )
                        ->where( 'expires_at', '>', now() );
                } )
                ->with( [
                    'customer',
                    'serviceStatus',
                    'items.product',
                    'userConfirmationToken',
                    'budget.tenant'
                ] )
                ->first();

            if ( !$service ) {
                Log::warning( 'Service not found or token expired for print', [
                    'code'  => $code,
                    'token' => $token,
                    'ip'    => request()->ip()
                ] );
                return redirect()->route( 'error.not-found' );
            }

            return view( 'services.public.print', [
                'service' => $service
            ] );

        } catch ( \Exception $e ) {
            Log::error( 'Error in service print', [
                'code'  => $code,
                'token' => $token,
                'error' => $e->getMessage(),
                'ip'    => request()->ip()
            ] );
            return redirect()->route( 'error.internal' );
        }
    }

    /**
     * Store a newly created service in storage.
     */
    public function store( ServiceStoreRequest $request ): RedirectResponse
    {
        try {
            $result = $this->serviceService->createService( $request->getValidatedData() );

            if ( !$result->isSuccess() ) {
                return redirect()->back()
                    ->withInput()
                    ->with( 'error', $result->getMessage() );
            }

            $service = $result->getData();

            return redirect()->route( 'services.show', $service->code )
                ->with( 'success', 'Serviço criado com sucesso!' );

        } catch ( Exception $e ) {
            return redirect()->back()
                ->withInput()
                ->with( 'error', 'Erro ao criar serviço: ' . $e->getMessage() );
        }
    }

    /**
     * Display the specified service.
     */
    public function show( string $code ): View
    {
        try {
            $result = $this->serviceService->findByCode( $code, [
                'category',
                'budget.customer.commonData',
                'serviceStatus',
                'serviceItems.product',
                'userConfirmationToken'
            ] );

            if ( !$result->isSuccess() ) {
                abort( 404, 'Serviço não encontrado' );
            }

            $service = $result->getData();

            return view( 'services.show', [
                'service' => $service
            ] );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao carregar serviço', [
                'error' => $e->getMessage(),
                'code'  => $code
            ] );
            abort( 500, 'Erro ao carregar serviço' );
        }
    }

}
