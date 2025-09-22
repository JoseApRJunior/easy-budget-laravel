<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceFormRequest;
use App\Services\InvoiceService;
use App\Services\PdfService;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Controlador para gerenciamento de faturas.
 * Implementa operações CRUD completas com geração de PDF e processamento de pagamentos.
 * Migração do sistema legacy app/controllers/InvoiceController.php.
 *
 * @package App\Http\Controllers
 * @author IA
 */
class InvoiceController extends BaseController
{
    /**
     * @var InvoiceService
     */
    protected InvoiceService $invoiceService;

    /**
     * @var PdfService
     */
    protected PdfService $pdfService;

    /**
     * Construtor da classe InvoiceController.
     *
     * @param InvoiceService $invoiceService
     * @param PdfService $pdfService
     */
    public function __construct( InvoiceService $invoiceService, PdfService $pdfService )
    {
        parent::__construct();
        $this->invoiceService = $invoiceService;
        $this->pdfService     = $pdfService;
    }

    /**
     * Exibe uma listagem das faturas do tenant atual com filtros.
     *
     * @param Request $request
     * @return View
     */
    public function index( Request $request ): View
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $this->logActivity(
            action: 'view_invoices',
            entity: 'invoices',
            metadata: [ 'tenant_id' => $tenantId ],
        );

        $filters = [
            'status'         => $request->get( 'status' ),
            'payment_method' => $request->get( 'payment_method' ),
            'date_from'      => $request->get( 'date_from' ),
            'date_to'        => $request->get( 'date_to' ),
            'customer_id'    => $request->get( 'customer_id' ),
            'search'         => $request->get( 'search' ),
        ];

        $invoices = $this->invoiceService->getInvoicesByTenant(
            tenantId: $tenantId,
            filters: $filters,
            perPage: 20,
            orderBy: $request->get( 'order_by', 'created_at' ),
            orderDirection: $request->get( 'order_direction', 'desc' ),
        );

        $customers      = $this->invoiceService->getCustomersForFilter( $tenantId );
        $paymentMethods = [ 'cash', 'credit_card', 'debit_card', 'bank_transfer', 'pix', 'boleto' ];
        $statuses       = [ 'draft', 'sent', 'paid', 'overdue', 'cancelled' ];

        return $this->renderView( 'invoices.index', [
            'invoices'       => $invoices,
            'filters'        => $filters,
            'customers'      => $customers,
            'paymentMethods' => $paymentMethods,
            'statuses'       => $statuses,
            'tenantId'       => $tenantId,
            'stats'          => $this->invoiceService->getInvoiceStats( $tenantId )
        ] );
    }

    /**
     * Mostra o formulário para criação de uma nova fatura.
     *
     * @param Request $request
     * @return View
     */
    public function create( Request $request ): View
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $this->logActivity(
            action: 'view_create_invoice',
            entity: 'invoices',
            metadata: [ 'tenant_id' => $tenantId ],
        );

        $serviceId  = $request->get( 'service_id' );
        $customerId = $request->get( 'customer_id' );

        $services  = $this->invoiceService->getAvailableServices( $tenantId );
        $customers = $this->invoiceService->getCustomersForSelection( $tenantId );

        return $this->renderView( 'invoices.create', [
            'tenantId'       => $tenantId,
            'services'       => $services,
            'customers'      => $customers,
            'serviceId'      => $serviceId,
            'customerId'     => $customerId,
            'paymentMethods' => [ 'cash', 'credit_card', 'debit_card', 'bank_transfer', 'pix', 'boleto' ]
        ] );
    }

    /**
     * Armazena uma nova fatura no banco de dados.
     *
     * @param InvoiceFormRequest $request
     * @return RedirectResponse
     */
    public function store( InvoiceFormRequest $request ): RedirectResponse
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $request->merge( [ 'tenant_id' => $tenantId ] );

        $result = $this->invoiceService->createInvoice( $request->validated() );

        return $this->handleServiceResult(
            result: $result,
            request: $request,
            successMessage: 'Fatura criada com sucesso.',
            errorMessage: 'Erro ao criar fatura.',
        );
    }

    /**
     * Exibe a fatura específica com detalhes completos.
     *
     * @param int $id
     * @return View
     */
    public function show( int $id ): View
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $invoice = $this->invoiceService->getInvoiceById( $id, $tenantId );

        if ( !$invoice ) {
            return $this->errorRedirect( 'Fatura não encontrada.' );
        }

        $this->logActivity(
            action: 'view_invoice',
            entity: 'invoices',
            entityId: $id,
            metadata: [ 'tenant_id' => $tenantId ],
        );

        $customer = $this->invoiceService->getCustomerDetails( $invoice->customer_id );
        $service  = $this->invoiceService->getServiceDetails( $invoice->service_id );

        return $this->renderView( 'invoices.show', [
            'invoice'  => $invoice,
            'customer' => $customer,
            'service'  => $service,
            'tenantId' => $tenantId
        ] );
    }

    /**
     * Mostra o formulário para edição da fatura.
     *
     * @param int $id
     * @return View
     */
    public function edit( int $id ): View
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $invoice = $this->invoiceService->getInvoiceById( $id, $tenantId );

        if ( !$invoice ) {
            return $this->errorRedirect( 'Fatura não encontrada.' );
        }

        $this->logActivity(
            action: 'view_edit_invoice',
            entity: 'invoices',
            entityId: $id,
            metadata: [ 'tenant_id' => $tenantId ],
        );

        $services  = $this->invoiceService->getAvailableServices( $tenantId );
        $customers = $this->invoiceService->getCustomersForSelection( $tenantId );

        return $this->renderView( 'invoices.edit', [
            'invoice'        => $invoice,
            'services'       => $services,
            'customers'      => $customers,
            'tenantId'       => $tenantId,
            'paymentMethods' => [ 'cash', 'credit_card', 'debit_card', 'bank_transfer', 'pix', 'boleto' ]
        ] );
    }

    /**
     * Atualiza a fatura no banco de dados.
     *
     * @param InvoiceFormRequest $request
     * @param int $id
     * @return RedirectResponse
     */
    public function update( InvoiceFormRequest $request, int $id ): RedirectResponse
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $existingInvoice = $this->invoiceService->getInvoiceById( $id, $tenantId );

        if ( !$existingInvoice ) {
            return $this->errorRedirect( 'Fatura não encontrada.' );
        }

        $request->merge( [ 'tenant_id' => $tenantId ] );

        $result = $this->invoiceService->updateInvoice( $id, $request->validated() );

        return $this->handleServiceResult(
            result: $result,
            request: $request,
            successMessage: 'Fatura atualizada com sucesso.',
            errorMessage: 'Erro ao atualizar fatura.',
        );
    }

    /**
     * Remove a fatura do banco de dados.
     *
     * @param int $id
     * @return RedirectResponse
     */
    public function destroy( int $id ): RedirectResponse
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $existingInvoice = $this->invoiceService->getInvoiceById( $id, $tenantId );

        if ( !$existingInvoice ) {
            return $this->errorRedirect( 'Fatura não encontrada.' );
        }

        // Verifica se a fatura já foi paga
        if ( $existingInvoice->status === 'paid' ) {
            return $this->errorRedirect( 'Faturas pagas não podem ser excluídas.' );
        }

        $result = $this->invoiceService->deleteInvoice( $id );

        return $this->handleServiceResult(
            result: $result,
            request: request(),
            successMessage: 'Fatura excluída com sucesso.',
            errorMessage: 'Erro ao excluir fatura.',
        );
    }

    /**
     * Gera e baixa PDF da fatura.
     *
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|RedirectResponse
     */
    public function generatePdf( int $id ): \Symfony\Component\HttpFoundation\BinaryFileResponse|RedirectResponse
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $invoice = $this->invoiceService->getInvoiceById( $id, $tenantId );

        if ( !$invoice ) {
            return $this->errorRedirect( 'Fatura não encontrada.' );
        }

        $this->logActivity(
            action: 'generate_invoice_pdf',
            entity: 'invoices',
            entityId: $id,
            metadata: [ 'tenant_id' => $tenantId ],
        );

        try {
            $pdf = $this->pdfService->generateInvoicePdf( $invoice );

            $filename = 'fatura_' . $invoice->code . '_' . date( 'Y-m-d_H-i-s' ) . '.pdf';
            $path     = 'invoices/' . $filename;

            Storage::disk( 'public' )->put( $path, $pdf->output() );

            $this->invoiceService->updatePdfPath( $id, $path );

            return response()->download(
                public_path( 'storage/' . $path ),
                $filename,
                [ 'Content-Type' => 'application/pdf' ],
            );
        } catch ( \Exception $e ) {
            return $this->errorRedirect( 'Erro ao gerar PDF da fatura: ' . $e->getMessage() );
        }
    }

    /**
     * Envia fatura por email para o cliente.
     *
     * @param int $id
     * @return RedirectResponse|JsonResponse
     */
    public function sendEmail( int $id ): RedirectResponse|JsonResponse
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            if ( request()->expectsJson() ) {
                return $this->jsonError( 'Tenant não encontrado.', statusCode: 403 );
            }
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $invoice = $this->invoiceService->getInvoiceById( $id, $tenantId );

        if ( !$invoice ) {
            if ( request()->expectsJson() ) {
                return $this->jsonError( 'Fatura não encontrada.', statusCode: 404 );
            }
            return $this->errorRedirect( 'Fatura não encontrada.' );
        }

        $result = $this->invoiceService->sendInvoiceEmail( $invoice );

        if ( request()->expectsJson() ) {
            if ( $result->isSuccess() ) {
                $this->logActivity(
                    action: 'send_invoice_email',
                    entity: 'invoices',
                    entityId: $id,
                    metadata: [ 'tenant_id' => $tenantId ],
                );

                return $this->jsonSuccess(
                    message: 'Fatura enviada por email com sucesso.',
                );
            }

            return $this->jsonError(
                message: $result->getError() ?? 'Erro ao enviar email da fatura.',
                statusCode: 422,
            );
        }

        return $this->handleServiceResult(
            result: $result,
            request: request(),
            successMessage: 'Fatura enviada por email com sucesso.',
            errorMessage: 'Erro ao enviar email da fatura.',
        );
    }

    /**
     * Marca fatura como paga.
     *
     * @param int $id
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    public function markAsPaid( int $id, Request $request ): RedirectResponse|JsonResponse
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            if ( request()->expectsJson() ) {
                return $this->jsonError( 'Tenant não encontrado.', statusCode: 403 );
            }
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $invoice = $this->invoiceService->getInvoiceById( $id, $tenantId );

        if ( !$invoice ) {
            if ( request()->expectsJson() ) {
                return $this->jsonError( 'Fatura não encontrada.', statusCode: 404 );
            }
            return $this->errorRedirect( 'Fatura não encontrada.' );
        }

        if ( $invoice->status === 'paid' ) {
            if ( request()->expectsJson() ) {
                return $this->jsonError( 'Esta fatura já está marcada como paga.', statusCode: 400 );
            }
            return $this->errorRedirect( 'Esta fatura já está marcada como paga.' );
        }

        $paymentData = [
            'payment_date'    => $request->input( 'payment_date' ),
            'payment_method'  => $request->input( 'payment_method' ),
            'received_amount' => $request->input( 'received_amount' ),
            'notes'           => $request->input( 'payment_notes' )
        ];

        $result = $this->invoiceService->markAsPaid( $id, $paymentData );

        if ( request()->expectsJson() ) {
            if ( $result->isSuccess() ) {
                $this->logActivity(
                    action: 'mark_invoice_paid',
                    entity: 'invoices',
                    entityId: $id,
                    metadata: [ 'tenant_id' => $tenantId, 'payment_method' => $paymentData[ 'payment_method' ] ],
                );

                return $this->jsonSuccess(
                    data: $result->getData(),
                    message: 'Fatura marcada como paga com sucesso.',
                );
            }

            return $this->jsonError(
                message: $result->getError() ?? 'Erro ao marcar fatura como paga.',
                statusCode: 422,
            );
        }

        return $this->handleServiceResult(
            result: $result,
            request: $request,
            successMessage: 'Fatura marcada como paga com sucesso.',
            errorMessage: 'Erro ao marcar fatura como paga.',
        );
    }

    /**
     * Atualiza status da fatura.
     *
     * @param int $id
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    public function updateStatus( int $id, Request $request ): RedirectResponse|JsonResponse
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            if ( request()->expectsJson() ) {
                return $this->jsonError( 'Tenant não encontrado.', statusCode: 403 );
            }
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $request->validate( [
            'status' => 'required|in:draft,sent,paid,overdue,cancelled'
        ] );

        $invoice = $this->invoiceService->getInvoiceById( $id, $tenantId );

        if ( !$invoice ) {
            if ( request()->expectsJson() ) {
                return $this->jsonError( 'Fatura não encontrada.', statusCode: 404 );
            }
            return $this->errorRedirect( 'Fatura não encontrada.' );
        }

        $result = $this->invoiceService->updateInvoiceStatus( $id, $request->status, [
            'comment' => $request->input( 'comment' ),
            'reason'  => $request->input( 'reason' )
        ] );

        if ( request()->expectsJson() ) {
            if ( $result->isSuccess() ) {
                $this->logActivity(
                    action: 'update_invoice_status',
                    entity: 'invoices',
                    entityId: $id,
                    metadata: [
                        'tenant_id'  => $tenantId,
                        'new_status' => $request->status,
                        'old_status' => $invoice->status
                    ],
                );

                return $this->jsonSuccess(
                    data: $result->getData(),
                    message: 'Status da fatura atualizado com sucesso.',
                );
            }

            return $this->jsonError(
                message: $result->getError() ?? 'Erro ao atualizar status da fatura.',
                statusCode: 422,
            );
        }

        return $this->handleServiceResult(
            result: $result,
            request: $request,
            successMessage: 'Status da fatura atualizado com sucesso.',
            errorMessage: 'Erro ao atualizar status da fatura.',
        );
    }

    /**
     * Busca faturas por código via AJAX.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchByCode( Request $request ): JsonResponse
    {
        $request->validate( [
            'code'  => 'required|string|max:50',
            'limit' => 'nullable|integer|min:1|max:50'
        ] );

        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->jsonError( 'Tenant não encontrado.', statusCode: 403 );
        }

        $invoices = $this->invoiceService->searchInvoicesByCode(
            code: $request->code,
            tenantId: $tenantId,
            limit: $request->get( 'limit', 10 ),
        );

        $this->logActivity(
            action: 'search_invoices_by_code',
            entity: 'invoices',
            metadata: [
                'tenant_id'     => $tenantId,
                'search_code'   => $request->code,
                'results_count' => $invoices->count()
            ],
        );

        return $this->jsonSuccess(
            data: $invoices,
            message: 'Faturas encontradas com sucesso.',
        );
    }

    /**
     * Gera relatório de faturas por período.
     *
     * @param Request $request
     * @return View|\Illuminate\Http\Response
     */
    public function report( Request $request ): View|\Illuminate\Http\Response
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $request->validate( [
            'date_from' => 'required|date',
            'date_to'   => 'required|date|after_or_equal:date_from',
            'format'    => 'nullable|in:html,pdf,excel'
        ] );

        $this->logActivity(
            action: 'generate_invoice_report',
            entity: 'invoices',
            metadata: [
                'tenant_id' => $tenantId,
                'date_from' => $request->date_from,
                'date_to'   => $request->date_to,
                'format'    => $request->get('format', 'html')
            ],
        );

        $reportData = $this->invoiceService->generateInvoiceReport(
            tenantId: $tenantId,
            dateFrom: $request->date_from,
            dateTo: $request->date_to,
            filters: $request->only( [ 'status', 'payment_method', 'customer_id' ] ),
        );

        if ( $request->get( 'format' ) === 'pdf' ) {
            $pdf      = PDF::loadView( 'reports.invoices', $reportData );
            $filename = 'relatorio_faturas_' . date( 'Y-m-d' ) . '.pdf';

            return $pdf->download( $filename );
        }

        return $this->renderView( 'reports.invoices', $reportData );
    }

}
