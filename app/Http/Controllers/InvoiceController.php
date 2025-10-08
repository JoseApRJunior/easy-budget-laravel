<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceRequest;
use App\Models\Budget;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceStatus;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller para gestão de faturas - Interface Web
 *
 * Gerencia todas as operações relacionadas a faturas através
 * da interface web, incluindo CRUD, busca e filtros.
 */
class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService,
    ) {}

    /**
     * Lista de faturas com filtros e paginação.
     */
    public function index( Request $request ): View
    {
        $filters = $request->only( [
            'search', 'status', 'customer_id', 'due_from', 'due_to',
            'amount_min', 'amount_max', 'sort_by', 'sort_direction', 'per_page'
        ] );

        $invoices = $this->invoiceService->searchInvoices( $filters, auth()->user() );

        // Dados adicionais para a view
        $customers = Customer::where( 'tenant_id', auth()->user()->tenant_id )
            ->active()
            ->ordered()
            ->get();

        $statuses = InvoiceStatus::where( 'tenant_id', auth()->user()->tenant_id )
            ->active()
            ->ordered()
            ->get();

        $stats = $this->invoiceService->getInvoiceStats( auth()->user() );

        return view( 'invoices.index', compact( 'invoices', 'customers', 'statuses', 'stats', 'filters' ) );
    }

    /**
     * Formulário de criação de fatura.
     */
    public function create(): View
    {
        $customers = Customer::where( 'tenant_id', auth()->user()->tenant_id )
            ->active()
            ->ordered()
            ->get();

        $budgets = Budget::where( 'tenant_id', auth()->user()->tenant_id )
            ->where( 'status', 'approved' )
            ->ordered()
            ->get();

        return view( 'invoices.create', compact( 'customers', 'budgets' ) );
    }

    /**
     * Cria fatura a partir de um orçamento.
     */
    public function createFromBudget( Budget $budget ): View
    {
        // Verificar se o orçamento pertence ao tenant do usuário
        if ( $budget->tenant_id !== auth()->user()->tenant_id ) {
            abort( 403 );
        }

        // Verificar se o orçamento pode gerar fatura
        if ( !$budget->canGenerateInvoice() ) {
            abort( 403, 'Este orçamento não pode gerar fatura.' );
        }

        $customers = Customer::where( 'tenant_id', auth()->user()->tenant_id )
            ->active()
            ->ordered()
            ->get();

        return view( 'invoices.create-from-budget', compact( 'budget', 'customers' ) );
    }

    /**
     * Salva fatura.
     */
    public function store( InvoiceRequest $request ): RedirectResponse
    {
        try {
            $invoice = $this->invoiceService->createInvoice( $request->validated(), auth()->user() );

            return redirect()->route( 'invoices.show', $invoice->code )
                ->with( 'success', 'Fatura criada com sucesso!' );

        } catch ( \Exception $e ) {
            return back()->withInput()
                ->with( 'error', 'Erro ao criar fatura: ' . $e->getMessage() );
        }
    }

    /**
     * Exibe detalhes de uma fatura.
     */
    public function show( Invoice $invoice ): View
    {
        // Verificar se a fatura pertence ao tenant do usuário
        if ( $invoice->tenant_id !== auth()->user()->tenant_id ) {
            abort( 403 );
        }

        $invoice->load( [ 'customer', 'items', 'status', 'attachments', 'budget' ] );

        return view( 'invoices.show', compact( 'invoice' ) );
    }

    /**
     * Formulário de edição de fatura.
     */
    public function edit( Invoice $invoice ): View
    {
        // Verificar se a fatura pertence ao tenant do usuário
        if ( $invoice->tenant_id !== auth()->user()->tenant_id ) {
            abort( 403 );
        }

        // Verificar se a fatura pode ser editada
        if ( !$invoice->canBeEdited() ) {
            abort( 403, 'Esta fatura não pode ser editada.' );
        }

        $customers = Customer::where( 'tenant_id', auth()->user()->tenant_id )
            ->active()
            ->ordered()
            ->get();

        return view( 'invoices.edit', compact( 'invoice', 'customers' ) );
    }

    /**
     * Atualiza fatura.
     */
    public function update( InvoiceRequest $request, Invoice $invoice ): RedirectResponse
    {
        // Verificar se a fatura pertence ao tenant do usuário
        if ( $invoice->tenant_id !== auth()->user()->tenant_id ) {
            abort( 403 );
        }

        // Verificar se a fatura pode ser editada
        if ( !$invoice->canBeEdited() ) {
            abort( 403, 'Esta fatura não pode ser editada.' );
        }

        try {
            $updatedInvoice = $this->invoiceService->updateInvoice( $invoice, $request->validated(), auth()->user() );

            return redirect()->route( 'invoices.show', $updatedInvoice->code )
                ->with( 'success', 'Fatura atualizada com sucesso!' );

        } catch ( \Exception $e ) {
            return back()->withInput()
                ->with( 'error', 'Erro ao atualizar fatura: ' . $e->getMessage() );
        }
    }

    /**
     * Remove fatura (soft delete).
     */
    public function destroy( Invoice $invoice ): RedirectResponse
    {
        // Verificar se a fatura pertence ao tenant do usuário
        if ( $invoice->tenant_id !== auth()->user()->tenant_id ) {
            abort( 403 );
        }

        try {
            $this->invoiceService->deleteInvoice( $invoice, auth()->user() );

            return redirect()->route( 'invoices.index' )
                ->with( 'success', 'Fatura removida com sucesso!' );

        } catch ( \Exception $e ) {
            return back()->with( 'error', 'Erro ao remover fatura: ' . $e->getMessage() );
        }
    }

    /**
     * Busca faturas via AJAX.
     */
    public function search( Request $request ): \Illuminate\Http\JsonResponse
    {
        $request->validate( [
            'query' => 'required|string|min:2',
        ] );

        $invoices = Invoice::where( 'tenant_id', auth()->user()->tenant_id )
            ->where( function ( $query ) use ( $request ) {
                $query->where( 'title', 'like', "%{$request->query}%" )
                    ->orWhere( 'code', 'like', "%{$request->query}%" )
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
            'invoices' => $invoices->map( function ( $invoice ) {
                return [
                    'id'       => $invoice->id,
                    'code'     => $invoice->code,
                    'text'     => $invoice->title,
                    'customer' => $invoice->customer?->name ?? $invoice->customer?->company_name,
                    'amount'   => $invoice->total_amount,
                    'status'   => $invoice->status?->name,
                ];
            } )
        ] );
    }

    /**
     * Imprime fatura.
     */
    public function print( Invoice $invoice ): View
    {
        // Verificar se a fatura pertence ao tenant do usuário
        if ( $invoice->tenant_id !== auth()->user()->tenant_id ) {
            abort( 403 );
        }

        $invoice->load( [ 'customer', 'items', 'status' ] );

        return view( 'invoices.print', compact( 'invoice' ) );
    }

    /**
     * Exporta lista de faturas.
     */
    public function export( Request $request )
    {
        $filters = $request->only( [
            'search', 'status', 'customer_id', 'due_from', 'due_to'
        ] );

        $invoices = $this->invoiceService->searchInvoices(
            array_merge( $filters, [ 'per_page' => 1000 ] ),
            auth()->user(),
        );

        // TODO: Implementar exportação para Excel/CSV
        // Por ora, retorna JSON
        return response()->json( [
            'invoices' => $invoices->items(),
            'total'    => $invoices->total(),
        ] );
    }

    /**
     * Visualiza fatura pública (rota pública com hash).
     */
    public function viewPublic( string $hash ): View
    {
        $invoice = $this->invoiceService->getInvoiceByHash( $hash );

        if ( !$invoice ) {
            abort( 404, 'Fatura não encontrada.' );
        }

        // Verificar se a fatura pode ser visualizada publicamente
        if ( !$invoice->canBeViewedPublicly() ) {
            abort( 403, 'Esta fatura não pode ser visualizada publicamente.' );
        }

        $invoice->load( [ 'customer', 'items', 'status' ] );

        return view( 'invoices.public-view', compact( 'invoice' ) );
    }

    /**
     * Redireciona para pagamento (rota pública com hash).
     */
    public function redirectToPayment( string $hash ): RedirectResponse
    {
        $invoice = $this->invoiceService->getInvoiceByHash( $hash );

        if ( !$invoice ) {
            abort( 404, 'Fatura não encontrada.' );
        }

        // Verificar se a fatura pode ser paga
        if ( !$invoice->canBePaid() ) {
            abort( 403, 'Esta fatura não pode ser paga no momento.' );
        }

        try {
            $paymentUrl = $this->invoiceService->generatePaymentUrl( $invoice );

            return redirect()->away( $paymentUrl );

        } catch ( \Exception $e ) {
            return redirect()->route( 'invoices.public.error' )
                ->with( 'error', 'Erro ao processar pagamento: ' . $e->getMessage() );
        }
    }

    /**
     * Página de erro de pagamento público.
     */
    public function paymentError(): View
    {
        return view( 'invoices.public-error' );
    }

    /**
     * Verifica status do pagamento (API pública).
     */
    public function paymentStatus( Request $request )
    {
        $request->validate( [
            'invoice_hash' => 'required|string',
        ] );

        $invoice = $this->invoiceService->getInvoiceByHash( $request->invoice_hash );

        if ( !$invoice ) {
            return response()->json( [ 'error' => 'Fatura não encontrada' ], 404 );
        }

        return response()->json( [
            'status'         => $invoice->status->name,
            'paid_at'        => $invoice->paid_at,
            'payment_method' => $invoice->payment_method,
        ] );
    }

}
