<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Models\Invoice;
use App\Services\Infrastructure\PaymentMercadoPagoInvoiceService;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Controller para visualização pública de faturas
 *
 * Permite que clientes visualizem e paguem faturas através de links públicos seguros.
 */
class PublicInvoiceController extends Controller
{
    public function show(string $hash): View|RedirectResponse
    {
        try {
            $invoice = Invoice::where('public_hash', $hash)
                ->with(['customer.commonData','customer.contact','service','userConfirmationToken'])
                ->first();

            if (!$invoice) {
                return redirect()->route('error.not-found');
            }

            return view('invoices.public.view-status', [
                'invoice' => $invoice,
                'invoiceStatus' => $invoice->status,
                'token' => $invoice->userConfirmationToken?->token ?? '',
            ]);
        } catch ( \Exception $e ) {
            Log::error('public_invoice_show_error', ['hash' => $hash, 'error' => $e->getMessage()]);
            return redirect()->route('error.internal');
        }
    }

    public function redirectToPayment(string $hash): RedirectResponse
    {
        try {
            $invoice = Invoice::where('public_hash', $hash)->first();
            if (!$invoice) {
                return redirect()->route('error.not-found');
            }

            $service = app(PaymentMercadoPagoInvoiceService::class);
            $result = $service->createMercadoPagoPreference($invoice->code);

            if (!$result->isSuccess()) {
                return redirect()->route('invoices.public.status')->with('error', $result->getMessage());
            }

            $initPoint = $result->getData()['init_point'] ?? null;
            if (!$initPoint) {
                return redirect()->route('invoices.public.status')->with('error', 'Link de pagamento indisponível');
            }

            return redirect()->away($initPoint);
        } catch ( \Exception $e ) {
            return redirect()->route('invoices.public.error')->with('error', 'Erro ao redirecionar pagamento.');
        }
    }

    public function paymentStatus(): View
    {
        $status = request('status');
        return view('invoices.public.view-status', [
            'invoice' => null,
            'invoiceStatus' => null,
            'token' => '',
        ]);
    }

    public function error(): View
    {
        return view('error.internal');
    }
}
