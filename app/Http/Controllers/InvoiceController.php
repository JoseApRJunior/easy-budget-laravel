<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\InvoiceStoreRequest;
use App\Http\Requests\InvoiceUpdateRequest;
use App\Models\Budget;
use App\Models\Invoice;
use App\Services\Domain\CustomerService;
use App\Services\Domain\InvoiceService;
use App\Services\Domain\ServiceService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Controller para gerenciamento de faturas.
 *
 * Implementa operações CRUD completas seguindo a arquitetura moderna:
 * Controller → Services → Repositories → Models
 */
class InvoiceController extends Controller
{
    private InvoiceService  $invoiceService;
    private CustomerService $customerService;
    private ServiceService  $serviceService;

    public function __construct(
        InvoiceService $invoiceService,
        CustomerService $customerService,
        ServiceService $serviceService,
    ) {
        $this->invoiceService  = $invoiceService;
        $this->customerService = $customerService;
        $this->serviceService  = $serviceService;
    }

    /**
     * Exibe lista de faturas.
     *
     * @param Request $request
     * @return View
     */
    public function index( Request $request ): View
    {
        try {
            $filters = $request->only( [ 'status', 'customer_id', 'date_from', 'date_to', 'search' ] );

            $result = $this->invoiceService->getFilteredInvoices( $filters, [
                'customer:id,name',
                'service:id,code,description',
                'invoiceStatus'
            ] );

            if ( !$result->isSuccess() ) {
                abort( 500, 'Erro ao carregar lista de faturas' );
            }

            $invoices = $result->getData();

            return view( 'invoices.index', [
                'invoices'      => $invoices,
                'filters'       => $filters,
                'statusOptions' => InvoiceStatus::cases(),
                'customers'     => $this->customerService->listCustomers()->isSuccess()
                    ? $this->customerService->listCustomers()->getData()
                    : []
            ] );

        } catch ( Exception $e ) {
            abort( 500, 'Erro ao carregar faturas' );
        }
    }

    /**
     * Exibe formulário de criação de fatura.
     *
     * @param string|null $serviceCode
     * @return View
     */
    public function create( ?string $serviceCode = null ): View
    {
        try {
            $service = null;

            if ( $serviceCode ) {
                $serviceResult = $this->serviceService->findByCode( $serviceCode );
                if ( $serviceResult->isSuccess() ) {
                    $service = $serviceResult->getData();
                }
            }

            return view( 'invoices.create', [
                'service'       => $service,
                'customers'     => $this->customerService->listCustomers()->isSuccess()
                    ? $this->customerService->listCustomers()->getData()
                    : [],
                'services'      => [], // TODO: Implementar getNotBilledServices no ServiceService
                'statusOptions' => InvoiceStatus::cases()
            ] );

        } catch ( Exception $e ) {
            abort( 500, 'Erro ao carregar formulário de criação de fatura' );
        }
    }

    /**
     * Armazena nova fatura no sistema.
     *
     * @param InvoiceStoreRequest $request
     * @return RedirectResponse
     */
    public function store( InvoiceStoreRequest $request ): RedirectResponse
    {
        try {
            $result = $this->invoiceService->createInvoice( $request->validated() );

            if ( !$result->isSuccess() ) {
                return redirect()->back()
                    ->withInput()
                    ->with( 'error', $result->getMessage() );
            }

            $invoice = $result->getData();

            return redirect()->route( 'invoices.show', $invoice->code )
                ->with( 'success', 'Fatura criada com sucesso!' );

        } catch ( Exception $e ) {
            return redirect()->back()
                ->withInput()
                ->with( 'error', 'Erro ao criar fatura: ' . $e->getMessage() );
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
        try {
            // Load relationships if not already loaded
            $invoice->load( [
                'customer.commonData',
                'service.budget',
                'invoiceItems.product',
                'invoiceStatus',
                'payments'
            ] );

            return view( 'invoices.show', [
                'invoice' => $invoice
            ] );

        } catch ( Exception $e ) {
            abort( 500, 'Erro ao carregar fatura' );
        }
    }

    /**
     * Exibe formulário de edição de fatura.
     *
     * @param Invoice $invoice
     * @return View
     */
    public function edit( Invoice $invoice ): View
    {
        try {
            // Load relationships if not already loaded
            $invoice->load( [
                'invoiceItems.product',
                'customer',
                'service'
            ] );

            // Verificar se pode editar (assumindo um método canEdit no Enum ou Model)
            // if (!$invoice->status->canEdit()) {
            //     abort(403, 'Fatura não pode ser editada no status atual');
            // }

            return view( 'invoices.edit', [
                'invoice'       => $invoice,
                'customers'     => $this->customerService->listCustomers()->isSuccess()
                    ? $this->customerService->listCustomers()->getData()
                    : [],
                'services'      => [], // TODO: Implementar getNotBilledServices no ServiceService
                'statusOptions' => InvoiceStatus::cases()
            ] );

        } catch ( Exception $e ) {
            abort( 500, 'Erro ao carregar formulário de edição de fatura' );
        }
    }

    /**
     * Atualiza fatura existente.
     *
     * @param Invoice $invoice
     * @param InvoiceUpdateRequest $request
     * @return RedirectResponse
     */
    public function update( Invoice $invoice, InvoiceUpdateRequest $request ): RedirectResponse
    {
        try {
            $result = $this->invoiceService->updateInvoice( $invoice, $request->validated() );

            if ( !$result->isSuccess() ) {
                return redirect()->back()
                    ->withInput()
                    ->with( 'error', $result->getMessage() );
            }

            return redirect()->route( 'invoices.show', $invoice->id )
                ->with( 'success', 'Fatura atualizada com sucesso!' );

        } catch ( Exception $e ) {
            return redirect()->back()
                ->withInput()
                ->with( 'error', 'Erro ao atualizar fatura: ' . $e->getMessage() );
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
            $result = $this->invoiceService->deleteInvoice( $invoice );

            if ( !$result->isSuccess() ) {
                return redirect()->back()
                    ->with( 'error', $result->getMessage() );
            }

            return redirect()->route( 'invoices.index' )
                ->with( 'success', 'Fatura excluída com sucesso!' );

        } catch ( Exception $e ) {
            return redirect()->back()
                ->with( 'error', 'Erro ao excluir fatura: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza status da fatura.
     *
     * @param Invoice $invoice
     * @param Request $request
     * @return RedirectResponse
     */
    public function change_status( Invoice $invoice, Request $request ): RedirectResponse
    {
        $request->validate( [
            'status' => [ 'required', 'string', 'in:' . implode( ',', array_map( fn( $case ) => $case->value, InvoiceStatus::cases() ) ) ]
        ] );

        try {
            $result = $this->invoiceService->changeStatus( $invoice, $request->status );

            if ( !$result->isSuccess() ) {
                return redirect()->back()
                    ->with( 'error', $result->getMessage() );
            }

            return redirect()->route( 'invoices.show', $invoice->id )
                ->with( 'success', 'Status da fatura alterado com sucesso!' );

        } catch ( Exception $e ) {
            return redirect()->back()
                ->with( 'error', 'Erro ao alterar status da fatura: ' . $e->getMessage() );
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
                ->with( [ 'customer', 'userConfirmationToken', 'service', 'tenant' ] )
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
                'invoice'       => $invoice,
                'invoiceStatus' => $invoice->status,
                'token'         => $token
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
                'invoice_status_id' => [ 'required', 'string', 'in:' . implode( ',', array_map( fn( $status ) => $status->value, InvoiceStatus::cases() ) ) ]
            ] );

            // Find the invoice by code and token
            $invoice = Invoice::where( 'code', $request->invoice_code )
                ->whereHas( 'userConfirmationToken', function ( $query ) use ( $request ) {
                    $query->where( 'token', $request->token )
                        ->where( 'expires_at', '>', now() );
                } )
                ->with( [ 'customer', 'userConfirmationToken' ] )
                ->first();

            if ( !$invoice ) {
                Log::warning( 'Invoice not found or token expired in choose status', [
                    'code'  => $request->invoice_code,
                    'token' => $request->token,
                    'ip'    => request()->ip()
                ] );
                return redirect()->route( 'error.not-found' );
            }

            // Validate that the selected status is allowed for public updates
            $allowedStatuses = [ InvoiceStatus::PAID->value, InvoiceStatus::CANCELLED->value, InvoiceStatus::OVERDUE->value ];
            if ( !in_array( $request->invoice_status_id, $allowedStatuses ) ) {
                Log::warning( 'Invalid invoice status selected', [
                    'invoice_code' => $request->invoice_code,
                    'status_id'    => $request->invoice_status_id,
                    'ip'           => request()->ip()
                ] );
                return redirect()->back()->with( 'error', 'Status inválido selecionado.' );
            }

            // Update invoice status using enum value
            $invoice->update( [
                'invoice_statuses_id' => $request->invoice_status_id,
                'updated_at'          => now()
            ] );

            // Log the action
            $newStatusEnum = InvoiceStatus::from( $request->invoice_status_id );
            $oldStatusEnum = $invoice->status;

            Log::info( 'Invoice status updated via public link', [
                'invoice_id'   => $invoice->id,
                'invoice_code' => $invoice->code,
                'old_status'   => $oldStatusEnum?->getDescription() ?? 'Unknown',
                'new_status'   => $newStatusEnum?->getDescription() ?? 'Unknown',
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
     * Download PDF invoice.
     *
     * @param string $code
     * @return Response
     */
    public function downloadPdf( string $code ): Response
    {
        try {
            $result = $this->invoiceService->generateInvoicePdf( $code );

            if ( !$result->isSuccess() ) {
                return redirect()->back()
                    ->with( 'error', $result->getMessage() );
            }

            $filePath = $result->getData(); // Ex: storage/invoices/tenant_id/invoice_CODE.pdf

            if ( !Storage::disk( 'public' )->exists( Str::after( $filePath, 'storage/' ) ) ) {
                abort( 404, 'PDF da fatura não encontrado.' );
            }

            return Storage::disk( 'public' )->download( Str::after( $filePath, 'storage/' ), 'fatura_' . $code . '.pdf' );

        } catch ( Exception $e ) {
            return redirect()->back()
                ->with( 'error', 'Erro ao baixar PDF da fatura: ' . $e->getMessage() );
        }
    }

    /**
     * Print invoice for admin/provider access.
     *
     * @param Invoice $invoice
     * @return View
     */
    public function print( Invoice $invoice ): View
    {
        try {
            // Load relationships for printing
            $invoice->load( [
                'customer.commonData',
                'service.budget',
                'invoiceItems.product',
                'invoiceStatus',
                'payments'
            ] );

            return view( 'invoices.print', [
                'invoice' => $invoice
            ] );

        } catch ( Exception $e ) {
            abort( 500, 'Erro ao carregar impressão da fatura' );
        }
    }

    /**
     * Create invoice from budget.
     *
     * @param Budget $budget
     * @return View
     */
    public function createFromBudget( Budget $budget ): View
    {
        try {
            // Load budget with relationships
            $budget->load( [
                'customer.commonData',
                'services.serviceItems.product'
            ] );

            return view( 'invoices.create-from-budget', [
                'budget'        => $budget,
                'customers'     => $this->customerService->listCustomers()->isSuccess()
                    ? $this->customerService->listCustomers()->getData()
                    : [],
                'statusOptions' => InvoiceStatus::cases()
            ] );

        } catch ( Exception $e ) {
            abort( 500, 'Erro ao carregar formulário de criação de fatura a partir do orçamento' );
        }
    }

    /**
     * Search invoices via AJAX.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search( Request $request ): JsonResponse
    {
        try {
            $query = $request->get( 'q', '' );
            $limit = $request->get( 'limit', 10 );

            $result = $this->invoiceService->searchInvoices( $query, $limit );

            if ( !$result->isSuccess() ) {
                return response()->json( [
                    'success' => false,
                    'message' => $result->getMessage()
                ], 400 );
            }

            return response()->json( [
                'success' => true,
                'data'    => $result->getData()
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao buscar faturas'
            ], 500 );
        }
    }

    /**
     * Export invoices to Excel/CSV.
     *
     * @param Request $request
     * @return BinaryFileResponse|JsonResponse
     */
    public function export( Request $request ): BinaryFileResponse|JsonResponse
    {
        try {
            $format  = $request->get( 'format', 'xlsx' );
            $filters = $request->only( [ 'status', 'customer_id', 'date_from', 'date_to' ] );

            $result = $this->invoiceService->exportInvoices( $filters, $format );

            if ( !$result->isSuccess() ) {
                return response()->json( [
                    'success' => false,
                    'message' => $result->getMessage()
                ], 400 );
            }

            $data = $result->getData();

            return response()->download( $data[ 'file_path' ], $data[ 'filename' ] )
                ->deleteFileAfterSend();

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao exportar faturas'
            ], 500 );
        }
    }

}
