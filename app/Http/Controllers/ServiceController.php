<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ServiceStatus;
use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\ServiceStoreRequest;
use App\Http\Requests\ServiceUpdateRequest;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Service;
use App\Models\UserConfirmationToken;
use App\Services\Domain\BudgetService;
use App\Services\Domain\CategoryService;
use App\Services\Domain\ProductService;
use App\Services\Domain\ScheduleService;
use App\Services\Domain\ServiceService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
    protected ScheduleService $scheduleService;

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

            return view( 'service.create', [
                'budget'        => $budget,
                'categories'    => $this->categoryService->getActive(),
                'products'      => $this->productService->getActive(),
                'budgets'       => $this->budgetService->getNotCompleted(),
                'statusOptions' => ServiceStatus::cases()
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
     * Display a listing of the service.
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

            return view( 'service.index', [
                'services'      => $services,
                'filters'       => $filters,
                'statusOptions' => ServiceStatus::cases(),
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

            return view( 'service.public.view-status', [
                'service' => $service,
                'token'   => $token
            ] );

        } catch ( Exception $e ) {
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
            $validated = $request->validate( [
                'service_code'      => 'required|string',
                'token'             => 'required|string|size:43',
                'service_status_id' => [
                    'required',
                    'string',
                    'in:' . implode( ',', [
                        ServiceStatus::APPROVED->value,
                        ServiceStatus::REJECTED->value,
                        ServiceStatus::CANCELLED->value
                    ] )
                ],
                'reason'            => 'nullable|string|max:500'
            ] );

            $result = $this->serviceService->updateStatusByToken(
                $validated[ 'service_code' ],
                $validated[ 'token' ],
                $validated[ 'service_status_id' ],
                $validated[ 'reason' ] ?? null
            );

            if ( !$result->isSuccess() ) {
                return redirect()->back()
                    ->with( 'error', $result->getMessage() );
            }

            return redirect()->route( 'service.public.view-status', [
                'code'  => $validated[ 'service_code' ],
                'token' => $validated[ 'token' ]
            ] )->with( 'success', 'Status do serviço atualizado com sucesso!' );

        } catch ( Exception $e ) {
            Log::error( 'Error in chooseServiceStatus', [
                'error'   => $e->getMessage(),
                'request' => $request->all()
            ] );
            return redirect()->route( 'error.internal' );
        }
    }

    /**
     * Print service for public access.
     */
    public function print( string $code, string $token = '' ): View|RedirectResponse
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

            $pdfService = app(\App\Services\Infrastructure\ServicePdfService::class);

            $verificationUrl = $service->pdf_verification_hash ? route('documents.verify', ['hash' => $service->pdf_verification_hash]) : null;
            $qrDataUri = null;
            if ($verificationUrl) {
                $qrDataUri = app(\App\Services\Infrastructure\QrCodeService::class)->generateDataUri($verificationUrl, 160);
            }

            $pdfPath = $pdfService->generatePdf($service, [
                'verificationUrl' => $verificationUrl,
                'qrDataUri' => $qrDataUri,
            ]);

            $hash = $pdfService->generateHash($pdfPath);
            $service->update(['pdf_verification_hash' => $hash]);

            $pdfContent = \Illuminate\Support\Facades\Storage::get($pdfPath);
            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "inline; filename=\"servico_{$service->code}.pdf\"",
                'Cache-Control' => 'public, max-age=86400'
            ]);

        } catch ( Exception $e ) {
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

            return redirect()->route( 'provider.services.show', $service->code )
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
                'budget.customer.commonData',
                'budget.customer.contacts',
                'category',
                'serviceItems.product',
                'serviceStatus',
                'schedules' => function ( $q ) {
                    $q->latest()->limit( 1 );
                }
            ] );

            if ( !$result->isSuccess() ) {
                abort( 404, 'Serviço não encontrado' );
            }

            $service = $result->getData();

            return view( 'service.show', [
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

    /**
     * Show the form for editing the specified service.
     */
    public function edit( string $code ): View
    {
        try {
            $result = $this->serviceService->findByCode( $code, [
                'serviceItems.product',
                'budget'
            ] );

            if ( !$result->isSuccess() ) {
                abort( 404, 'Serviço não encontrado' );
            }

            $service = $result->getData();

            // Verificar se pode editar
            if ( !$service->serviceStatus->canEdit() ) {
                abort( 403, 'Serviço não pode ser editado no status atual' );
            }

            return view( 'service.edit', [
                'service'       => $service,
                'categories'    => $this->categoryService->getActive(),
                'products'      => $this->productService->getActive(),
                'budgets'       => $this->budgetService->getNotCompleted(),
                'statusOptions' => ServiceStatus::cases()
            ] );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao carregar formulário de edição', [
                'error' => $e->getMessage(),
                'code'  => $code
            ] );
            abort( 500, 'Erro ao carregar formulário de edição' );
        }

    }

    /**
     * Update the specified service in storage.
     */
    public function update( string $code, ServiceUpdateRequest $request ): RedirectResponse
    {
        try {
            $result = $this->serviceService->updateServiceByCode( $code, $request->getValidatedData() );

            if ( !$result->isSuccess() ) {
                return redirect()->back()
                    ->withInput()
                    ->with( 'error', $result->getMessage() );
            }

            $service = $result->getData();

            return redirect()->route( 'provider.services.show', $service->code )
                ->with( 'success', 'Serviço atualizado com sucesso!' );

        } catch ( Exception $e ) {
            return redirect()->back()
                ->withInput()
                ->with( 'error', 'Erro ao atualizar serviço: ' . $e->getMessage() );
        }
    }

    /**
     * Altera o status do serviço com validação de transições permitidas.
     * Quando o status for SCHEDULED, cria um agendamento.
     */
    public function change_status( string $code, Request $request ): RedirectResponse
    {
        $request->validate( [
            'status' => [ 'required', 'string', 'in:' . implode( ',', array_map( fn( $status ) => $status->value, ServiceStatus::cases() ) ) ]
        ] );

        try {
            // Se for agendamento, validar dados adicionais
            if ($request->status === 'SCHEDULED') {
                $request->validate( [
                    'start_date_time' => 'required|date|after:now',
                    'end_date_time' => 'required|date|after:start_date_time',
                    'location' => 'nullable|string|max:500',
                ] );

                // Obter o serviço
                $serviceResult = $this->serviceService->findByCode( $code );
                if ( !$serviceResult->isSuccess() ) {
                    return redirect()->back()
                        ->with( 'error', 'Serviço não encontrado.' );
                }
                $service = $serviceResult->getData();

                // Criar agendamento
                $scheduleData = [
                    'start_date_time' => $request->start_date_time,
                    'end_date_time' => $request->end_date_time,
                    'location' => $request->location,
                ];

                $scheduleResult = $this->scheduleService->handleScheduledStatus( $service, $scheduleData );
                if ( !$scheduleResult->isSuccess() ) {
                    return redirect()->back()
                        ->with( 'error', $scheduleResult->getMessage() );
                }
            }

            // Mudar status do serviço
            $result = $this->serviceService->changeStatus( $code, $request->status );

            if ( !$result->isSuccess() ) {
                return redirect()->back()
                    ->with( 'error', $result->getMessage() );
            }

            return redirect()->route( 'provider.services.show', $code )
                ->with( 'success', 'Status alterado com sucesso!' );

        } catch ( Exception $e ) {
            return redirect()->back()
                ->with( 'error', 'Erro ao alterar status: ' . $e->getMessage() );
        }
    }

    /**
     * Remove serviço do sistema com validação de dependências.
     *
     * Verifica relacionamentos que impedem exclusão (agendamentos, faturas)
     * e deleta itens do serviço primeiro antes de deletar o serviço.
     */
    public function delete_store( string $code ): RedirectResponse
    {
        try {
            $result = $this->serviceService->deleteByCode( $code );

            if ( !$result->isSuccess() ) {
                return redirect()->back()
                    ->with( 'error', $result->getMessage() );
            }

            return redirect()->route( 'provider.services.index' )
                ->with( 'success', 'Serviço excluído com sucesso!' );

        } catch ( Exception $e ) {
            return redirect()->back()
                ->with( 'error', 'Erro ao excluir serviço: ' . $e->getMessage() );
        }
    }

    /**
     * Cancela um serviço alterando o status para CANCELLED.
     */
    public function cancel( string $code ): RedirectResponse
    {
        try {
            $result = $this->serviceService->cancelService( $code );

            if ( !$result->isSuccess() ) {
                return redirect()->back()
                    ->with( 'error', $result->getMessage() );
            }

            $service = $result->getData();

            return redirect()->route( 'provider.services.show', $service->code )
                ->with( 'success', 'Serviço cancelado com sucesso!' );

        } catch ( Exception $e ) {
            return redirect()->back()
                ->with( 'error', 'Erro ao cancelar serviço: ' . $e->getMessage() );
        }
    }

    /**
     * Dashboard de serviços com estatísticas e dados recentes.
     */
    public function dashboard(): View
    {
        try {
            $user = Auth::user();

            if ( !$user || !$user->tenant_id ) {
                abort( 403, 'Acesso negado.' );
            }

            // Buscar dados do dashboard através do ServiceService
            $stats = $this->serviceService->getDashboardData( $user->tenant_id );

            return view( 'pages.service.dashboard', [
                'stats' => $stats
            ] );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao carregar dashboard de serviços', [
                'error'   => $e->getMessage(),
                'user_id' => Auth::id()
            ] );
            abort( 500, 'Erro ao carregar dashboard' );
        }
    }

    /**
     * AJAX endpoint para filtrar serviços.
     */
    public function ajaxFilter( Request $request ): JsonResponse
    {
        $filters = $request->only(['status','category_id','date_from','date_to','search']);
        $result = $this->serviceService->getFilteredServices($filters);
        return $result->isSuccess()
            ? response()->json(['success' => true, 'data' => $result->getData()])
            : response()->json(['success' => false, 'message' => $result->getMessage()], 400);
    }

}
