<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\InvoiceCreated;
use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\InvoiceRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceStatus;
use App\Models\Tenant;
use App\Models\UserConfirmationToken;
use App\Services\Domain\InvoiceService;
use App\Support\ServiceResult;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Controller para gerenciamento de faturas.
 *
 * Este controller demonstra como usar eventos customizados para notificações
 * ao invés de chamar MailerService diretamente.
 *
 * Exemplo de migração:
 * ANTES: $mailerService->sendInvoiceNotification($invoice, $customer, $tenant);
 * DEPOIS: Event::dispatch(new InvoiceCreated($invoice, $customer, $tenant));
 */
class InvoiceController extends Controller
{
    private InvoiceService $invoiceService;

    public function __construct( InvoiceService $invoiceService )
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Exibe lista de faturas.
     *
     * @param Request $request
     * @return View
     */
    public function index( Request $request ): View
    {
        $filters = $request->only( [ 'status', 'customer_id', 'date_from', 'date_to' ] );
        $result  = $this->invoiceService->listInvoices( $filters );

        return $this->view( 'invoices.index', $result );
    }

    /**
     * Exibe formulário de criação de fatura.
     *
     * @return View
     */
    public function create(): View
    {
        $customersResult = $this->invoiceService->getAvailableCustomers();

        return view( 'invoices.create', [
            'customers' => $customersResult->isSuccess() ? $customersResult->getData() : []
        ] );
    }

    /**
     * Armazena nova fatura no sistema.
     *
     * Este método demonstra o uso de eventos para notificações:
     * - Dispara evento InvoiceCreated para processamento assíncrono
     * - Não chama MailerService diretamente
     * - Mantém compatibilidade com código existente
     *
     * @param InvoiceRequest $request
     * @return RedirectResponse
     */
    public function store( InvoiceRequest $request ): RedirectResponse
    {
        try {
            // Criar fatura usando o service
            $result = $this->invoiceService->createInvoice( $request->validated() );

            if ( $result->isSuccess() ) {
                $invoice  = $result->getData()[ 'invoice' ];
                $customer = $result->getData()[ 'customer' ];
                $tenant   = $result->getData()[ 'tenant' ];

                // Disparar evento para envio de notificação por e-mail
                // AO INVÉS de chamar MailerService diretamente
                Event::dispatch( new InvoiceCreated( $invoice, $customer, $tenant ) );

                Log::info( 'Evento InvoiceCreated disparado para nova fatura', [
                    'invoice_id'   => $invoice->id,
                    'invoice_code' => $invoice->code,
                    'customer_id'  => $customer->id,
                    'tenant_id'    => $tenant->id,
                ] );

                return $this->redirectSuccess(
                    route( 'invoices.show', $invoice->id ),
                    'Fatura criada com sucesso. Notificação será enviada em segundo plano.',
                );
            }

            return $this->redirectError(
                route( 'invoices.create' ),
                $this->getServiceErrorMessage( $result ),
            );

        } catch ( \Exception $e ) {
            Log::error( 'Erro ao criar fatura', [
                'error'        => $e->getMessage(),
                'request_data' => $request->validated(),
            ] );

            return $this->redirectError(
                route( 'invoices.create' ),
                'Erro interno ao criar fatura. Tente novamente.',
            );
        }
    }

    /**
     * Exibe detalhes de uma fatura específica.
     *
     * @param Invoice $invoice
     * @return View
     */
    public function show( Invoice $invoice ): View
    {
        $result = $this->invoiceService->getInvoiceDetails( $invoice->id );

        return $this->view( 'invoices.show', $result );
    }

    /**
     * Exibe formulário de edição de fatura.
     *
     * @param Invoice $invoice
     * @return View
     */
    public function edit( Invoice $invoice ): View
    {
        $customersResult = $this->invoiceService->getAvailableCustomers();

        return view( 'invoices.edit', [
            'invoice'   => $invoice,
            'customers' => $customersResult->isSuccess() ? $customersResult->getData() : []
        ] );
    }

    /**
     * Atualiza fatura existente.
     *
     * @param InvoiceRequest $request
     * @param Invoice $invoice
     * @return RedirectResponse
     */
    public function update( InvoiceRequest $request, Invoice $invoice ): RedirectResponse
    {
        try {
            $result = $this->invoiceService->updateInvoice( $invoice->id, $request->validated() );

            if ( $result->isSuccess() ) {
                return $this->redirectSuccess(
                    route( 'invoices.show', $invoice->id ),
                    'Fatura atualizada com sucesso.',
                );
            }

            return $this->redirectError(
                route( 'invoices.edit', $invoice->id ),
                $this->getServiceErrorMessage( $result ),
            );

        } catch ( \Exception $e ) {
            Log::error( 'Erro ao atualizar fatura', [
                'invoice_id'   => $invoice->id,
                'error'        => $e->getMessage(),
                'request_data' => $request->validated(),
            ] );

            return $this->redirectError(
                route( 'invoices.edit', $invoice->id ),
                'Erro interno ao atualizar fatura. Tente novamente.',
            );
        }
    }

    /**
     * Remove fatura do sistema.
     *
     * @param Invoice $invoice
     * @return RedirectResponse
     */
    public function destroy( Invoice $invoice ): RedirectResponse
    {
        try {
            $result = $this->invoiceService->deleteInvoice( $invoice->id );

            if ( $result->isSuccess() ) {
                return $this->redirectSuccess(
                    route( 'invoices.index' ),
                    'Fatura removida com sucesso.',
                );
            }

            return $this->redirectError(
                route( 'invoices.show', $invoice->id ),
                $this->getServiceErrorMessage( $result ),
            );

        } catch ( \Exception $e ) {
            Log::error( 'Erro ao remover fatura', [
                'invoice_id' => $invoice->id,
                'error'      => $e->getMessage(),
            ] );

            return $this->redirectError(
                route( 'invoices.show', $invoice->id ),
                'Erro interno ao remover fatura. Tente novamente.',
            );
        }
    }

    /**
     * Atualiza status da fatura.
     *
     * Este método demonstra como disparar evento StatusUpdated
     * quando o status de uma entidade é alterado.
     *
     * @param Request $request
     * @param Invoice $invoice
     * @return RedirectResponse
     */
    public function updateStatus( Request $request, Invoice $invoice ): RedirectResponse
    {
        try {
            $oldStatus  = $invoice->status;
            $newStatus  = $request->input( 'status' );
            $statusName = $request->input( 'status_name', ucfirst( $newStatus ) );

            // Atualizar status usando o service
            $result = $this->invoiceService->updateInvoiceStatus( $invoice->id, $newStatus );

            if ( $result->isSuccess() ) {
                // Disparar evento para envio de notificação de atualização de status
                // AO INVÉS de chamar MailerService diretamente
                Event::dispatch( new StatusUpdated(
                    $invoice,
                    $oldStatus,
                    $newStatus,
                    $statusName,
                    $invoice->tenant,
                ) );

                Log::info( 'Evento StatusUpdated disparado para fatura', [
                    'invoice_id'  => $invoice->id,
                    'old_status'  => $oldStatus,
                    'new_status'  => $newStatus,
                    'status_name' => $statusName,
                ] );

                return $this->redirectSuccess(
                    route( 'invoices.show', $invoice->id ),
                    'Status da fatura atualizado com sucesso. Notificação será enviada em segundo plano.',
                );
            }

            return $this->redirectError(
                route( 'invoices.show', $invoice->id ),
                $this->getServiceErrorMessage( $result ),
            );

        } catch ( \Exception $e ) {
            Log::error( 'Erro ao atualizar status da fatura', [
                'invoice_id'   => $invoice->id,
                'error'        => $e->getMessage(),
                'request_data' => $request->all(),
            ] );

            return $this->redirectError(
                route( 'invoices.show', $invoice->id ),
                'Erro interno ao atualizar status da fatura. Tente novamente.',
            );
        }
    }

    /**
     * Display the invoice status page for public access.
     */
    public function viewInvoiceStatus( string $code, string $token ): View|RedirectResponse
    {
        try {
            // Find the invoice by code and token
            $invoice = Invoice::where( 'code', $code )
                ->whereHas( 'userConfirmationToken', function ( $query ) use ( $token ) {
                    $query->where( 'token', $token )
                        ->where( 'expires_at', '>', now() );
                } )
                ->with( [ 'customer', 'invoiceStatus', 'userConfirmationToken', 'service', 'tenant' ] )
                ->first();

            if ( !$invoice ) {
                Log::warning( 'Invoice not found or token expired', [
                    'code'  => $code,
                    'token' => $token,
                    'ip'    => request()->ip()
                ] );
                return redirect()->route( 'error.not-found' );
            }

            return view( 'invoices.public.view-status', [
                'invoice' => $invoice,
                'token'   => $token
            ] );

        } catch ( \Exception $e ) {
            Log::error( 'Error in viewInvoiceStatus', [
                'code'  => $code,
                'token' => $token,
                'error' => $e->getMessage(),
                'ip'    => request()->ip()
            ] );
            return redirect()->route( 'error.internal' );
        }
    }

    /**
     * Process the invoice status selection for public access.
     */
    public function chooseInvoiceStatus( Request $request ): RedirectResponse
    {
        try {
            $request->validate( [
                'invoice_code'      => 'required|string',
                'token'             => 'required|string|size:43', // base64url format: 32 bytes = 43 caracteres
                'invoice_status_id' => 'required|integer|exists:invoice_statuses,id'
            ] );

            // Find the invoice by code and token
            $invoice = Invoice::where( 'code', $request->invoice_code )
                ->whereHas( 'userConfirmationToken', function ( $query ) use ( $request ) {
                    $query->where( 'token', $request->token )
                        ->where( 'expires_at', '>', now() );
                } )
                ->with( [ 'customer', 'invoiceStatus', 'userConfirmationToken' ] )
                ->first();

            if ( !$invoice ) {
                Log::warning( 'Invoice not found or token expired in choose status', [
                    'code'  => $request->invoice_code,
                    'token' => $request->token,
                    'ip'    => request()->ip()
                ] );
                return redirect()->route( 'error.not-found' );
            }

            // Validate that the selected status is allowed
            $selectedStatus = InvoiceStatus::find( $request->invoice_status_id );
            if ( !$selectedStatus || !in_array( $selectedStatus->slug, [ 'pago', 'cancelado', 'vencido' ] ) ) {
                Log::warning( 'Invalid invoice status selected', [
                    'invoice_code' => $request->invoice_code,
                    'status_id'    => $request->invoice_status_id,
                    'ip'           => request()->ip()
                ] );
                return redirect()->back()->with( 'error', 'Status inválido selecionado.' );
            }

            // Update invoice status
            $invoice->update( [
                'invoice_statuses_id' => $request->invoice_status_id,
                'updated_at'          => now()
            ] );

            // Log the action
            Log::info( 'Invoice status updated via public link', [
                'invoice_id'   => $invoice->id,
                'invoice_code' => $invoice->code,
                'old_status'   => $invoice->invoiceStatus->name,
                'new_status'   => $selectedStatus->name,
                'ip'           => request()->ip()
            ] );

            return redirect()->route( 'invoices.public.view-status', [
                'code'  => $invoice->code,
                'token' => $request->token
            ] )->with( 'success', 'Status da fatura atualizado com sucesso!' );

        } catch ( \Exception $e ) {
            Log::error( 'Error in chooseInvoiceStatus', [
                'error'   => $e->getMessage(),
                'request' => $request->all(),
                'ip'      => request()->ip()
            ] );
            return redirect()->route( 'error.internal' );
        }
    }

    /**
     * Print invoice for public access.
     */
    public function print( string $code, string $token ): View|RedirectResponse
    {
        try {
            // Find the invoice by code and token
            $invoice = Invoice::where( 'code', $code )
                ->whereHas( 'userConfirmationToken', function ( $query ) use ( $token ) {
                    $query->where( 'token', $token )
                        ->where( 'expires_at', '>', now() );
                } )
                ->with( [
                    'customer',
                    'invoiceStatus',
                    'items.product',
                    'userConfirmationToken',
                    'service.budget.tenant'
                ] )
                ->first();

            if ( !$invoice ) {
                Log::warning( 'Invoice not found or token expired for print', [
                    'code'  => $code,
                    'token' => $token,
                    'ip'    => request()->ip()
                ] );
                return redirect()->route( 'error.not-found' );
            }

            return view( 'invoices.public.print', [
                'invoice' => $invoice
            ] );

        } catch ( \Exception $e ) {
            Log::error( 'Error in invoice print', [
                'code'  => $code,
                'token' => $token,
                'error' => $e->getMessage(),
                'ip'    => request()->ip()
            ] );
            return redirect()->route( 'error.internal' );
        }
    }

}
