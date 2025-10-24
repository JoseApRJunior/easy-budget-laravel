<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\ServiceRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceStatus;
use App\Models\UserConfirmationToken;
use App\Services\ServiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

/**
 * Controller para gestão de serviços - Interface Web
 *
 * Gerencia todas as operações relacionadas a serviços através
 * da interface web, incluindo CRUD, busca e filtros.
 */
class ServiceController extends Controller
{
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
                'token'             => 'required|string|size:64',
                'service_status_id' => 'required|integer|exists:service_statuses,id'
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
            $selectedStatus = ServiceStatus::find( $request->service_status_id );
            if ( !$selectedStatus || !in_array( $selectedStatus->slug, [ 'aprovado', 'rejeitado', 'cancelado' ] ) ) {
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
            Log::info( 'Service status updated via public link', [
                'service_id'   => $service->id,
                'service_code' => $service->code,
                'old_status'   => $service->serviceStatus->name,
                'new_status'   => $selectedStatus->name,
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

}
