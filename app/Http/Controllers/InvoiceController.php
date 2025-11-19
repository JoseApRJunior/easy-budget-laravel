<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\InvoiceStoreFromBudgetRequest;
use App\Http\Requests\InvoiceStoreRequest;
use App\Http\Requests\InvoiceUpdateRequest;
use App\Models\Budget;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use App\Services\Domain\CustomerService;
use App\Services\Domain\InvoiceService;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Controller para gerenciamento de faturas.
 *
 * Versão final mínima para resolver problemas de sintaxe
 */
class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService,
        private CustomerService $customerService,
    ) {}

    /**
     * Exibe lista de faturas.
     */
    public function index( Request $request ): View
    {
        /** @var User $user */
        $user    = Auth::user();
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

        return view( 'pages.invoice.index', [
            'invoices'      => $invoices,
            'filters'       => $filters,
            'statusOptions' => InvoiceStatus::cases(),
            'customers'     => $this->customerService->listCustomers( $user->tenant_id )->isSuccess()
                ? $this->customerService->listCustomers( $user->tenant_id )->getData()
                : []
        ] );
    }

    public function dashboard( Request $request ): View
    {
        /** @var User $user */
        $user     = Auth::user();
        $tenantId = (int) ( $user->tenant_id ?? 0 );

        $total     = Invoice::where( 'tenant_id', $tenantId )->count();
        $paid      = Invoice::where( 'tenant_id', $tenantId )->where( 'status', InvoiceStatus::PAID->value )->count();
        $pending   = Invoice::where( 'tenant_id', $tenantId )->where( 'status', InvoiceStatus::PENDING->value )->count();
        $overdue   = Invoice::where( 'tenant_id', $tenantId )->where( 'status', InvoiceStatus::OVERDUE->value )->count();
        $cancelled = Invoice::where( 'tenant_id', $tenantId )->where( 'status', InvoiceStatus::CANCELLED->value )->count();

        $totalBilled   = (float) Invoice::where( 'tenant_id', $tenantId )->sum( 'total' );
        $totalReceived = (float) Invoice::where( 'tenant_id', $tenantId )->where( 'status', InvoiceStatus::PAID->value )->sum( 'transaction_amount' );
        $totalPending  = (float) Invoice::where( 'tenant_id', $tenantId )->whereIn( 'status', [ InvoiceStatus::PENDING->value, InvoiceStatus::OVERDUE->value ] )->sum( 'total' );

        $statusBreakdown = [
            'PENDENTE'  => [ 'count' => $pending, 'color' => InvoiceStatus::PENDING->getColor() ],
            'VENCIDA'   => [ 'count' => $overdue, 'color' => InvoiceStatus::OVERDUE->getColor() ],
            'PAGA'      => [ 'count' => $paid, 'color' => InvoiceStatus::PAID->getColor() ],
            'CANCELADA' => [ 'count' => $cancelled, 'color' => InvoiceStatus::CANCELLED->getColor() ],
        ];

        $recent = Invoice::where( 'tenant_id', $tenantId )
            ->latest( 'created_at' )
            ->limit( 10 )
            ->with( [ 'customer.commonData', 'service' ] )
            ->get();

        $stats = [
            'total_invoices'     => $total,
            'paid_invoices'      => $paid,
            'pending_invoices'   => $pending,
            'overdue_invoices'   => $overdue,
            'cancelled_invoices' => $cancelled,
            'total_billed'       => $totalBilled,
            'total_received'     => $totalReceived,
            'total_pending'      => $totalPending,
            'status_breakdown'   => $statusBreakdown,
            'recent_invoices'    => $recent,
        ];

        return view( 'pages.invoice.dashboard', compact( 'stats' ) );
    }

    /**
     * Exibe formulário de criação de fatura.
     */
    public function create( Request $request ): View
    {
        /** @var User $user */
        $user     = Auth::user();
        $products = Product::byTenant( (int) ( $user->tenant_id ?? 0 ) )->active()->orderBy( 'name' )->get();
        return view( 'pages.invoice.create', [
            'service'       => null,
            'customers'     => $this->customerService->listCustomers( $user->tenant_id )->isSuccess()
                ? $this->customerService->listCustomers( $user->tenant_id )->getData()
                : [],
            'services'      => [],
            'products'      => $products,
            'statusOptions' => InvoiceStatus::cases()
        ] );
    }

    /**
     * Armazena nova fatura no sistema.
     */
    public function store( InvoiceStoreRequest $request ): RedirectResponse
    {
        $result = $this->invoiceService->createInvoice( $request->validated() );

        if ( !$result->isSuccess() ) {
            return redirect()->back()
                ->withInput()
                ->with( 'error', $result->getMessage() );
        }

        $invoice = $result->getData();

        return redirect()->route( 'provider.invoices.show', $invoice->code )
            ->with( 'success', 'Fatura criada com sucesso!' );
    }

    /**
     * Exibe detalhes de uma fatura específica.
     */
    public function show( Invoice $invoice ): View
    {
        $invoice->load( [
            'customer.commonData',
            'service.budget',
            'invoiceItems.product',
            'invoiceStatus',
            'payments'
        ] );

        return view( 'pages.invoice.show', [
            'invoice' => $invoice
        ] );
    }

    /**
     * Exibe formulário de edição de fatura.
     */
    public function edit( Invoice $invoice ): View
    {
        /** @var User $user */
        $user = Auth::user();

        $invoice->load( [
            'invoiceItems.product',
            'customer',
            'service'
        ] );

        return view( 'pages.invoice.edit', [
            'invoice'       => $invoice,
            'customers'     => $this->customerService->listCustomers( $user->tenant_id )->isSuccess()
                ? $this->customerService->listCustomers( $user->tenant_id )->getData()
                : [],
            'services'      => [],
            'statusOptions' => InvoiceStatus::cases()
        ] );
    }

    /**
     * Atualiza fatura existente.
     */
    public function update( InvoiceUpdateRequest $request, Invoice $invoice ): RedirectResponse
    {
        $data = $request->validated();
        $invoice->update( $data );

        return redirect()->route( 'provider.invoices.show', $invoice->code )
            ->with( 'success', 'Fatura atualizada com sucesso!' );
    }

    /**
     * Remove fatura do sistema.
     */
    public function destroy( Invoice $invoice ): RedirectResponse
    {
        $invoice->delete();

        return redirect()->route( 'provider.invoices.index' )
            ->with( 'success', 'Fatura excluída com sucesso!' );
    }

    /**
     * Atualiza status da fatura.
     */
    public function change_status( Invoice $invoice, Request $request ): RedirectResponse
    {
        $request->validate( [
            'status' => [ 'required', 'string', 'in:' . implode( ',', array_map( fn( $case ) => $case->value, InvoiceStatus::cases() ) ) ]
        ] );

        $invoice->update( [ 'status' => $request->input( 'status' ) ] );

        return redirect()->route( 'provider.invoices.show', $invoice->code )
            ->with( 'success', 'Status da fatura alterado com sucesso!' );
    }

    /**
     * Create invoice from budget.
     */
    public function createFromBudget( Budget $budget ): View
    {
        /** @var User $user */
        $user = Auth::user();

        $budget->load( [
            'customer.commonData',
            'services.serviceItems.product'
        ] );

        return view( 'pages.invoice.create-from-budget', [
            'budget'           => $budget,
            'alreadyBilled'    => 0,
            'remainingBalance' => 0,
            'customers'        => $this->customerService->listCustomers( $user->tenant_id )->isSuccess()
                ? $this->customerService->listCustomers( $user->tenant_id )->getData()
                : [],
            'statusOptions'    => InvoiceStatus::cases()
        ] );
    }

    /**
     * Create partial invoice from service (Interface para faturas parciais)
     */
    public function createPartialFromService( string $serviceCode ): View|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            // Gerar dados da fatura a partir do serviço
            $result = $this->invoiceService->generateInvoiceDataFromService( $serviceCode );

            if ( !$result->isSuccess() ) {
                return redirect()->back()
                    ->with( 'error', $result->getMessage() );
            }

            $invoiceData = $result->getData();

            return view( 'pages.invoice.create-partial-from-service', [
                'invoiceData'   => $invoiceData,
                'serviceCode'   => $serviceCode,
                'customers'     => $this->customerService->listCustomers( $user->tenant_id )->isSuccess()
                    ? $this->customerService->listCustomers( $user->tenant_id )->getData()
                    : [],
                'statusOptions' => InvoiceStatus::cases()
            ] );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao criar interface de fatura parcial a partir do serviço', [
                'service_code' => $serviceCode,
                'error'        => $e->getMessage()
            ] );

            return redirect()->back()
                ->with( 'error', 'Erro ao processar solicitação. Tente novamente.' );
        }
    }

    /**
     * Create invoice from service (Nova funcionalidade do sistema antigo)
     */
    public function createFromService( string $serviceCode ): View|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            // Gerar dados da fatura a partir do serviço
            $result = $this->invoiceService->generateInvoiceDataFromService( $serviceCode );

            if ( !$result->isSuccess() ) {
                return redirect()->back()
                    ->with( 'error', $result->getMessage() );
            }

            $invoiceData = $result->getData();

            // Verificar se já existe fatura para este serviço
            if ( $this->invoiceService->checkExistingInvoiceForService( $invoiceData[ 'service_id' ] ) ) {
                return redirect()->route( 'provider.invoices.index' )
                    ->with( 'error', 'Já existe uma fatura para este serviço.' );
            }

            return view( 'pages.invoice.create-from-service', [
                'invoiceData'   => $invoiceData,
                'serviceCode'   => $serviceCode,
                'customers'     => $this->customerService->listCustomers( $user->tenant_id )->isSuccess()
                    ? $this->customerService->listCustomers( $user->tenant_id )->getData()
                    : [],
                'statusOptions' => InvoiceStatus::cases()
            ] );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao criar fatura a partir do serviço', [
                'service_code' => $serviceCode,
                'error'        => $e->getMessage()
            ] );

            return redirect()->back()
                ->with( 'error', 'Erro ao processar solicitação. Tente novamente.' );
        }
    }

    /**
     * Store manual invoice from service (Nova funcionalidade para faturas manuais)
     */
    public function storeManualFromService( Request $request, string $serviceCode ): RedirectResponse
    {
        $request->validate( [
            'issue_date'         => 'required|date',
            'due_date'           => 'required|date|after_or_equal:issue_date',
            'notes'              => 'nullable|string|max:1000',
            'items'              => 'nullable|array',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity'   => 'required|numeric|min:0.01',
            'items.*.unit_value' => 'required|numeric|min:0.01',
        ] );

        try {
            // Preparar dados adicionais para fatura manual
            $additionalData = array_merge(
                $request->only( [ 'issue_date', 'due_date', 'notes' ] ),
                [ 'is_automatic' => false ] // Marcar como fatura manual
            );

            // Se houver itens específicos, incluir
            if ( $request->has( 'items' ) ) {
                $additionalData[ 'items' ] = $request->input( 'items' );
            }

            $result = $this->invoiceService->createInvoiceFromService( $serviceCode, $additionalData );

            if ( !$result->isSuccess() ) {
                return redirect()->back()
                    ->withInput()
                    ->with( 'error', $result->getMessage() );
            }

            $invoice = $result->getData();

            return redirect()->route( 'provider.invoices.show', $invoice->code )
                ->with( 'success', 'Fatura manual criada com sucesso!' );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao criar fatura manual a partir do serviço', [
                'service_code' => $serviceCode,
                'error'        => $e->getMessage()
            ] );

            return redirect()->back()
                ->withInput()
                ->with( 'error', 'Erro ao processar fatura manual. Tente novamente.' );
        }
    }

    /**
     * Store invoice from service (Nova funcionalidade do sistema antigo)
     */
    public function storeFromService( Request $request ): RedirectResponse
    {
        $request->validate( [
            'service_code' => 'required|string|exists:services,code',
            'issue_date'   => 'required|date',
            'due_date'     => 'required|date|after_or_equal:issue_date',
        ] );

        try {
            $result = $this->invoiceService->createInvoiceFromService(
                $request->input( 'service_code' ),
                $request->only( [ 'issue_date', 'due_date' ] ),
            );

            if ( !$result->isSuccess() ) {
                return redirect()->back()
                    ->withInput()
                    ->with( 'error', $result->getMessage() );
            }

            $invoice = $result->getData();

            return redirect()->route( 'provider.invoices.show', $invoice->code )
                ->with( 'success', 'Fatura gerada com sucesso a partir do serviço!' );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao salvar fatura a partir do serviço', [
                'service_code' => $request->input( 'service_code' ),
                'error'        => $e->getMessage()
            ] );

            return redirect()->back()
                ->withInput()
                ->with( 'error', 'Erro ao processar fatura. Tente novamente.' );
        }
    }

    /**
     * Store invoice from budget.
     */
    public function storeFromBudget( Budget $budget, InvoiceStoreFromBudgetRequest $request ): RedirectResponse
    {
        $payload = $request->validated();
        $result  = $this->invoiceService->createPartialInvoiceFromBudget( $budget->code, $payload );

        if ( !$result->isSuccess() ) {
            return redirect()->back()
                ->withInput()
                ->with( 'error', $result->getMessage() );
        }

        $invoice = $result->getData();
        return redirect()->route( 'provider.invoices.show', $invoice->code )
            ->with( 'success', 'Fatura criada a partir do orçamento!' );
    }

    /**
     * Search invoices via AJAX.
     */
    public function search( Request $request ): JsonResponse
    {
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
    }

    /**
     * Export invoices to Excel/CSV.
     */
    public function export( Request $request ): BinaryFileResponse|JsonResponse
    {
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
    }

    /**
     * AJAX endpoint para filtrar faturas.
     */
    public function ajaxFilter( Request $request ): JsonResponse
    {
        $filters = $request->only( [ 'status', 'customer_id', 'service_id', 'date_from', 'date_to', 'due_date_from', 'due_date_to', 'min_amount', 'max_amount', 'search', 'sort_by', 'sort_direction' ] );
        $result  = $this->invoiceService->getFilteredInvoices( $filters, [ 'customer:id,name', 'service:id,code,description', 'invoiceStatus' ] );
        return $result->isSuccess()
            ? response()->json( [ 'success' => true, 'data' => $result->getData() ] )
            : response()->json( [ 'success' => false, 'message' => $result->getMessage() ], 400 );
    }

}
