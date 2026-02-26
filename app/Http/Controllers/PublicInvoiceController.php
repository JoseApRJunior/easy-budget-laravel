<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
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

        // Processamento de callback do Mercado Pago (fallback para quando o webhook atrasa)
        if (request()->has('collection_status') || request()->has('status')) {
            $paymentId = request()->get('collection_id') ?? request()->get('payment_id');
            $status = request()->get('collection_status') ?? request()->get('status');

            if ($paymentId && $status === 'approved') {
                try {
                    $webhookService = app(\App\Services\Infrastructure\Payment\MercadoPagoWebhookService::class);
                    // Simula um payload de webhook para processar o status
                    $webhookService->processInvoicePayment([
                        'id' => $paymentId,
                        'type' => 'payment',
                        'data' => ['id' => $paymentId],
                    ], (int) $invoice->tenant_id);
                    // Recarrega a fatura para garantir que o status atualizado seja exibido
                    $invoice->refresh();
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('error_processing_callback_fallback', [
                        'invoice' => $invoice->code,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $invoice->load([
            'customer.commonData',
            'customer.contact',
            'service',
            'userConfirmationToken',
            'invoiceItems.product',
            'tenant.provider.commonData',
            'tenant.provider.contact',
        ]);

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

        // Impedir geração de pagamento para faturas já pagas
        if ($invoice->status === \App\Enums\InvoiceStatus::PAID->value || $invoice->status === 'paid') {
            return redirect()->route('services.public.invoices.public.show', ['hash' => $hash])
                ->with('warning', 'Esta fatura já consta como paga em nosso sistema.');
        }

        $service = app(PaymentMercadoPagoInvoiceService::class);
        $result = $service->createMercadoPagoPreference($invoice->code);

        if (! $result->isSuccess()) {
            return redirect()->route('services.public.invoices.public.status')->with('error', $result->getMessage());
        }

        $initPoint = $result->getData()['init_point'] ?? null;
        if (! $initPoint) {
            return redirect()->route('services.public.invoices.public.status')->with('error', 'Link de pagamento indisponível');
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
