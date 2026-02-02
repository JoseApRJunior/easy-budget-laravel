<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Models\Invoice;
use App\Services\Domain\InvoiceShareService;
use App\Services\Infrastructure\PaymentMercadoPagoInvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Controller para visualização pública de faturas
 *
 * Permite que clientes visualizem e paguem faturas através de links públicos seguros.
 */
class PublicInvoiceController extends Controller
{
    public function __construct(
        private InvoiceShareService $invoiceShareService
    ) {}

    public function show(string $hash): View|RedirectResponse
    {
        // 1. Tenta buscar pelo novo sistema de compartilhamento (InvoiceShare)
        $result = $this->invoiceShareService->getInvoiceByToken($hash);

        if ($result->isSuccess()) {
            $invoice = $result->getData();
        } else {
            $invoice = null;
        }

        if (! $invoice) {
            return redirect()->route('error.not-found');
        }

        $invoice->load(['customer.commonData', 'customer.contact', 'service', 'userConfirmationToken', 'invoiceItems.product']);

        return view('pages.public.invoice.show', [
            'invoice' => $invoice,
            'invoiceStatus' => $invoice->status,
            'token' => $invoice->userConfirmationToken?->token ?? '',
        ]);
    }

    public function redirectToPayment(string $hash): RedirectResponse
    {
        // 1. Tenta buscar pelo novo sistema de compartilhamento (InvoiceShare)
        $result = $this->invoiceShareService->getInvoiceByToken($hash);
        $invoice = $result->isSuccess() ? $result->getData() : null;

        if (! $invoice) {
            return redirect()->route('error.not-found');
        }

        $service = app(PaymentMercadoPagoInvoiceService::class);
        $result = $service->createMercadoPagoPreference($invoice->code);

        if (! $result->isSuccess()) {
            return redirect()->route('invoices.public.status')->with('error', $result->getMessage());
        }

        $initPoint = $result->getData()['init_point'] ?? null;
        if (! $initPoint) {
            return redirect()->route('invoices.public.status')->with('error', 'Link de pagamento indisponível');
        }

        return redirect()->away($initPoint);
    }

    public function paymentStatus(): View
    {
        return view('pages.public.invoice.show', [
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
