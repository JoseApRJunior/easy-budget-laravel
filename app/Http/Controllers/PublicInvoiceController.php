<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller para visualização pública de faturas
 *
 * Permite que clientes visualizem e paguem faturas através de links públicos seguros.
 */
class PublicInvoiceController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService,
    ) {}

    /**
     * Exibe fatura pública através de hash seguro.
     */
    public function show( string $hash ): View
    {
        $invoice = $this->invoiceService->getInvoiceByHash( $hash );

        if ( !$invoice ) {
            abort( 404, 'Fatura não encontrada.' );
        }

        // Verificar se a fatura pode ser visualizada publicamente
        if ( !$invoice->canBeViewedPublicly() ) {
            abort( 403, 'Esta fatura não está disponível para visualização pública.' );
        }

        $invoice->load( [ 'customer', 'items', 'status' ] );

        return view( 'invoices.public-show', compact( 'invoice' ) );
    }

    /**
     * Redireciona para gateway de pagamento.
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
     * Página de erro de pagamento.
     */
    public function error(): View
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
            'amount'         => $invoice->total_amount,
        ] );
    }

}
