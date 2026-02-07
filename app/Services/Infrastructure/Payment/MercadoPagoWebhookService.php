<?php

declare(strict_types=1);

namespace App\Services\Infrastructure\Payment;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\PaymentMercadoPagoInvoice;
use App\Models\PaymentMercadoPagoPlan;
use App\Models\PlanSubscription;
use App\Models\ProviderCredential;
use App\Services\Infrastructure\EncryptionService;
use Illuminate\Support\Facades\Log;
use MercadoPago\Client\Payment\PaymentClient;

class MercadoPagoWebhookService
{
    public function __construct(private EncryptionService $encryption) {}

    public function processInvoicePayment(string $paymentId): array
    {
        try {
            $paymentClient = new PaymentClient($this->resolveAccessTokenForInvoice($paymentId));
            $payment = $paymentClient->get((int) $paymentId);
            $status = $payment->status ?? 'pending';
            $amount = (float) ($payment->transaction_amount ?? 0);
            $date = $payment->date_approved ?? $payment->date_created ?? null;
            $externalRef = $payment->external_reference ?? '';
            $invoiceCode = $this->parseExternalReference($externalRef, 'invoice');

            $invoice = $invoiceCode ? Invoice::where('code', $invoiceCode)->first() : null;
            if (! $invoice) {
                Log::warning('invoice_not_found_for_payment', ['payment_id' => $paymentId, 'external_reference' => $externalRef]);

                return ['success' => false, 'message' => 'Fatura não encontrada'];
            }

            $mappedStatus = $this->mapMercadoPagoStatus($status);
            $method = $payment->payment_method_id ?? 'ticket';

            $existingInv = PaymentMercadoPagoInvoice::where('payment_id', (string) $paymentId)->first();
            if ($existingInv && $existingInv->status === $mappedStatus) {
                Log::info('mp_invoice_webhook_no_change', ['payment_id' => $paymentId, 'status' => $mappedStatus]);

                return ['success' => true];
            }

            PaymentMercadoPagoInvoice::updateOrCreate(
                ['payment_id' => (string) $paymentId, 'tenant_id' => $invoice->tenant_id, 'invoice_id' => $invoice->id],
                [
                    'status' => $mappedStatus,
                    'payment_method' => (string) $method,
                    'transaction_amount' => $amount,
                    'transaction_date' => $date,
                ],
            );

            if ($mappedStatus === PaymentMercadoPagoInvoice::STATUS_APPROVED) {
                $invoice->update(['status' => InvoiceStatus::PAID->value]);
            }

            return ['success' => true];
        } catch (\Throwable $e) {
            Log::error('process_invoice_payment_error', ['payment_id' => $paymentId, 'error' => $e->getMessage()]);

            return ['success' => false, 'message' => 'Erro ao processar pagamento'];
        }
    }

    public function processPlanPayment(string $paymentId): array
    {
        try {
            $paymentClient = new PaymentClient($this->resolveAccessTokenForPlan($paymentId));
            $payment = $paymentClient->get((int) $paymentId);
            $status = $payment->status ?? 'pending';
            $amount = (float) ($payment->transaction_amount ?? 0);
            $date = $payment->date_approved ?? $payment->date_created ?? null;
            $externalRef = $payment->external_reference ?? '';
            $planSubId = $this->parseExternalReference($externalRef, 'plan_subscription_id');

            $subscription = $planSubId ? PlanSubscription::find((int) $planSubId) : null;
            $mappedStatus = $this->mapMercadoPagoStatus($status);

            $existingPlan = PaymentMercadoPagoPlan::where('payment_id', (string) $paymentId)->first();
            if ($existingPlan && $existingPlan->status === $mappedStatus) {
                Log::info('mp_plan_webhook_no_change', ['payment_id' => $paymentId, 'status' => $mappedStatus]);

                return ['success' => true];
            }

            PaymentMercadoPagoPlan::updateOrCreate(
                ['payment_id' => (string) $paymentId, 'plan_subscription_id' => (int) ($planSubId ?? 0)],
                [
                    'tenant_id' => $subscription?->tenant_id,
                    'provider_id' => $subscription?->provider_id,
                    'status' => $mappedStatus,
                    'payment_method' => (string) ($payment->payment_method_id ?? 'ticket'),
                    'transaction_amount' => $amount,
                    'transaction_date' => $date,
                ],
            );

            if ($subscription) {
                if ($mappedStatus === PaymentMercadoPagoPlan::STATUS_APPROVED) {
                    $subscription->update([
                        'status' => PlanSubscription::STATUS_ACTIVE,
                        'transaction_amount' => $amount,
                        'payment_method' => (string) ($payment->payment_method_id ?? 'ticket'),
                        'payment_id' => (string) $paymentId,
                        'transaction_date' => $date,
                        'last_payment_date' => now(),
                        'next_payment_date' => now()->addMonth(),
                    ]);
                } elseif (in_array($mappedStatus, [PaymentMercadoPagoPlan::STATUS_REJECTED, PaymentMercadoPagoPlan::STATUS_CANCELLED, PaymentMercadoPagoPlan::STATUS_REFUNDED], true)) {
                    $subscription->update(['status' => PlanSubscription::STATUS_CANCELLED]);
                }
            }

            return ['success' => true];
        } catch (\Throwable $e) {
            Log::error('process_plan_payment_error', ['payment_id' => $paymentId, 'error' => $e->getMessage()]);

            return ['success' => false, 'message' => 'Erro ao processar pagamento de plano'];
        }
    }

    private function mapMercadoPagoStatus(string $status): string
    {
        return match ($status) {
            'approved' => PaymentMercadoPagoInvoice::STATUS_APPROVED,
            'pending', 'authorized', 'in_process', 'in_mediation' => PaymentMercadoPagoInvoice::STATUS_PENDING,
            'rejected' => PaymentMercadoPagoInvoice::STATUS_REJECTED,
            'cancelled' => PaymentMercadoPagoInvoice::STATUS_CANCELLED,
            'refunded', 'charged_back' => PaymentMercadoPagoInvoice::STATUS_REFUNDED,
            default => PaymentMercadoPagoInvoice::STATUS_PENDING,
        };
    }

    private function parseExternalReference(?string $externalRef, string $key): ?string
    {
        if (! $externalRef) {
            return null;
        }
        // Expected formats: "invoice:INV123:tenant:1" or "plan:...:plan_subscription_id:123"
        $parts = explode(':', $externalRef);
        $idx = array_search($key, $parts, true);
        if ($idx !== false && isset($parts[$idx + 1])) {
            return $parts[$idx + 1];
        }
        if ($key === 'invoice' && str_starts_with($externalRef, 'invoice:')) {
            return $parts[1] ?? null;
        }

        return null;
    }

    private function resolveAccessTokenForInvoice(string $paymentId): string
    {
        try {
            // Fallback strategy: attempt to parse invoice code from external reference via a lightweight fetch
            // If not possible, use provider credential by locating invoice later
            // Here we lookup the latest invoice payments to find tenant and provider credential
            $invoicePayment = PaymentMercadoPagoInvoice::where('payment_id', $paymentId)->first();
            if ($invoicePayment) {
                $invoice = Invoice::find($invoicePayment->invoice_id);
                if ($invoice) {
                    $cred = ProviderCredential::where('tenant_id', $invoice->tenant_id)
                        ->where('payment_gateway', 'mercadopago')
                        ->first();
                    if ($cred) {
                        $res = $this->encryption->decryptStringLaravel($cred->access_token_encrypted);
                        if ($res->isSuccess()) {
                            return (string) $res->getData()['decrypted'];
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('resolve_token_invoice_error', ['payment_id' => $paymentId, 'error' => $e->getMessage()]);
        }

        return '';
    }

    private function resolveAccessTokenForPlan(string $paymentId): string
    {
        try {
            // Attempt to parse plan_subscription_id from a previously stored payment or by external reference lookups
            $planPayment = PaymentMercadoPagoPlan::where('payment_id', $paymentId)->first();
            $planSubId = $planPayment?->plan_subscription_id;
            if ($planSubId) {
                $subscription = \App\Models\PlanSubscription::find($planSubId);
                if ($subscription) {
                    $cred = \App\Models\ProviderCredential::where('tenant_id', $subscription->tenant_id)
                        ->where('payment_gateway', 'mercadopago')
                        ->first();
                    if ($cred) {
                        $res = $this->encryption->decryptStringLaravel((string) $cred->access_token_encrypted);
                        if ($res->isSuccess()) {
                            $access = (string) ($res->getData()['decrypted'] ?? '');
                            if ($access !== '') {
                                return $access;
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('resolve_token_plan_error', ['payment_id' => $paymentId, 'error' => $e->getMessage()]);
        }

        $token = (string) (config('services.mercadopago.access_token') ?? '');
        if ($token !== '') {
            return $token;
        }

        return (string) env('MERCADOPAGO_GLOBAL_ACCESS_TOKEN', '');
    }
}
