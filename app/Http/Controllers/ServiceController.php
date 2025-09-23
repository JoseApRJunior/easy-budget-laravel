<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ServiceChangeStatusFormRequest;
use App\Http\Requests\ServiceChooseStatusFormRequest;
use App\Http\Requests\ServiceFormRequest;
use App\Services\NotificationService;
use App\Services\PdfService;
use App\Services\ServiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ServiceController extends BaseController
{
    public function __construct(
        private readonly ServiceService $serviceService,
        private readonly PdfService $pdfService,
        private readonly NotificationService $notificationService,
    ) {
        parent::__construct();
    }

    public function index( Request $request ): View
    {
        $user       = Auth::user();
        $tenantId   = $user->tenant_id ?? 1;
        $providerId = $user->provider_id ?? $user->id;

        $this->logActivity(
            action: 'view_services',
            entity: 'services',
            metadata: [ 
                'tenant_id'   => $tenantId,
                'provider_id' => $providerId
            ],
        );

        $filters  = $request->only( [ 'status', 'budget_code', 'date_from', 'date_to' ] );
        $services = $this->serviceService->getServicesForProvider( $providerId, $filters );

        return $this->renderView( 'pages.service.index', compact( 'services', 'filters' ) );
    }

    public function create( ?string $budgetCode = null ): View
    {
        $user       = Auth::user();
        $tenantId   = $user->tenant_id ?? 1;
        $providerId = $user->provider_id ?? $user->id;

        $this->logActivity(
            action: 'view_create_service',
            entity: 'services',
            metadata: [ 
                'tenant_id'   => $tenantId,
                'provider_id' => $providerId
            ],
        );

        $categories = []; // Assume getAllCategories from service
        $units      = []; // Assume findAllByTenant from service
        $products   = []; // Assume getAllProductsActive from service
        $budgets    = $this->serviceService->getBudgetsForServiceCreation( $tenantId, $providerId );

        return $this->renderView( 'pages.service.create', compact( 'budgetCode', 'categories', 'units', 'products', 'budgets' ) );
    }

    public function store( ServiceFormRequest $request ): RedirectResponse
    {
        $user       = Auth::user();
        $tenantId   = $user->tenant_id ?? 1;
        $providerId = $user->provider_id ?? $user->id;

        try {
            $result = $this->serviceService->create( $request->validated(), $tenantId, $providerId );

            return $this->handleServiceResult(
                result: $result,
                request: $request,
                successMessage: 'Serviço criado com sucesso.',
                errorMessage: 'Erro ao criar serviço.',
            );
        } catch ( \Exception $e ) {
            return $this->errorRedirect( 'Erro ao criar serviço: ' . $e->getMessage() );
        }
    }

    public function show( string $code ): View
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id ?? 1;

        $serviceData = $this->serviceService->getServiceFullByCode( $code, $tenantId );

        if ( !$serviceData ) {
            return $this->errorRedirect( 'Serviço não encontrado.' );
        }

        $this->logActivity(
            action: 'view_service',
            entity: 'services',
            metadata: [ 'tenant_id' => $tenantId, 'code' => $code ],
        );

        return $this->renderView( 'pages.service.show', $serviceData );
    }

    public function edit( string $code ): View
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id ?? 1;

        $serviceData = $this->serviceService->getServiceUpdateData( $code, $tenantId );

        if ( !$serviceData ) {
            return $this->errorRedirect( 'Serviço não encontrado.' );
        }

        $this->logActivity(
            action: 'view_edit_service',
            entity: 'services',
            metadata: [ 'tenant_id' => $tenantId, 'code' => $code ],
        );

        return $this->renderView( 'pages.service.edit', $serviceData );
    }

    public function update( ServiceFormRequest $request, string $code ): RedirectResponse
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id ?? 1;

        try {
            $result = $this->serviceService->updateByCode( $code, $request->validated(), $tenantId );

            return $this->handleServiceResult(
                result: $result,
                request: $request,
                successMessage: 'Serviço atualizado com sucesso.',
                errorMessage: 'Erro ao atualizar serviço.',
            );
        } catch ( \Exception $e ) {
            return $this->errorRedirect( 'Erro ao atualizar serviço: ' . $e->getMessage() );
        }
    }

    public function changeStatus( ServiceChangeStatusFormRequest $request, string $code ): RedirectResponse
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id ?? 1;

        try {
            $result = $this->serviceService->changeStatusByCode( $code, $request->validated(), $tenantId );

            return $this->handleServiceResult(
                result: $result,
                request: $request,
                successMessage: 'Status do serviço alterado com sucesso!',
                errorMessage: 'Erro ao alterar status.',
            );
        } catch ( \Exception $e ) {
            return $this->errorRedirect( 'Erro ao alterar status: ' . $e->getMessage() );
        }
    }

    public function viewServiceStatus( string $code, string $token ): View
    {
        $serviceData = $this->serviceService->getServiceForStatusView( $code, $token );

        if ( !$serviceData ) {
            return $this->errorRedirect( 'Serviço não encontrado ou token inválido.' );
        }

        return $this->renderView( 'pages.service.view-status', $serviceData );
    }

    public function chooseServiceStatusStore( ServiceChooseStatusFormRequest $request ): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $result = $this->serviceService->handleCustomerStatusChange( $validated[ 'service_id' ], $validated[ 'status' ], $validated[ 'token' ], $validated[ 'comment' ] ?? null );

            return $this->handleServiceResult(
                result: $result,
                request: $request,
                successMessage: 'Status do serviço atualizado com sucesso!',
                errorMessage: 'Erro ao atualizar status.',
            );
        } catch ( \Exception $e ) {
            return $this->errorRedirect( 'Erro ao atualizar status: ' . $e->getMessage() );
        }
    }

    public function destroy( string $code ): RedirectResponse
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id ?? 1;

        try {
            $result = $this->serviceService->deleteByCode( $code, $tenantId );

            return $this->handleServiceResult(
                result: $result,
                request: request(),
                successMessage: 'Serviço deletado com sucesso!',
                errorMessage: 'Erro ao deletar serviço.',
            );
        } catch ( \Exception $e ) {
            return $this->errorRedirect( 'Erro ao deletar serviço: ' . $e->getMessage() );
        }
    }

    /**
     * Cancela serviço.
     *
     * @param string $code
     * @return RedirectResponse
     */
    public function cancel( string $code ): RedirectResponse
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id ?? 1;

        $this->logActivity(
            action: 'cancel_service',
            entity: 'services',
            metadata: [ 'tenant_id' => $tenantId, 'code' => $code ],
        );

        try {
            $result = $this->serviceService->cancelByCode( $code, $tenantId, 'Cancelado pelo usuário' );

            return $this->handleServiceResult(
                result: $result,
                request: request(),
                successMessage: 'Serviço cancelado com sucesso!',
                errorMessage: 'Erro ao cancelar serviço.',
            );
        } catch ( \Exception $e ) {
            return $this->errorRedirect( 'Erro ao cancelar serviço: ' . $e->getMessage() );
        }
    }

    /**
     * Imprime serviço.
     *
     * @param string $code
     * @param string|null $token
     * @return \Illuminate\Http\Response
     */
    public function print( string $code, ?string $token = null ): \Illuminate\Http\Response
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id ?? 1;

        $serviceData = $this->serviceService->getServicePrintData( $code, $token, $tenantId );

        if ( !$serviceData ) {
            return $this->errorRedirect( 'Serviço não encontrado.' );
        }

        $this->logActivity(
            action: 'print_service',
            entity: 'services',
            metadata: [ 'tenant_id' => $tenantId, 'code' => $code ],
        );

        $pdfPath    = 'services/' . $serviceData[ 'service' ]->id . '.pdf';
        $pdfContent = $this->pdfService->generateServicePdf( $serviceData[ 'service' ], $pdfPath );

        $filename = "servico_{$code}.pdf";

        return response( $pdfContent )
            ->header( 'Content-Type', 'application/pdf' )
            ->header( 'Content-Disposition', "inline; filename=\"{$filename}\"" );
    }

}
