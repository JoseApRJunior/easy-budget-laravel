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
use Illuminate\Support\Facades\Cache;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\MercadoPagoConfig;

class MercadoPagoWebhookService
{
    public function __construct(private EncryptionService $encryption) {}

    public function processInvoicePayment(array $webhookData, ?int $forceTenantId = null): array
    {
        $paymentId = (string) ($webhookData['data']['id'] ?? $webhookData['id'] ?? '');
        if (empty($paymentId)) {
            return ['success' => false, 'message' => 'ID do pagamento não encontrado no webhook'];
        }

        try {
            $accessToken = $this->resolveAccessTokenFromWebhook($webhookData, $forceTenantId);
            if (empty($accessToken)) {
                Log::error('could_not_resolve_token_for_webhook', ['webhook' => $webhookData, 'forced_tenant' => $forceTenantId]);
                return ['success' => false, 'message' => 'Não foi possível resolver o token de acesso'];
            }

            MercadoPagoConfig::setAccessToken($accessToken);
            $paymentClient = new PaymentClient();
            $payment = $paymentClient->get((int) $paymentId);
            
            $status = $payment->status ?? 'pending';
            $amount = (float) ($payment->transaction_amount ?? 0);
            $date = $payment->date_approved ?? $payment->date_created ?? null;
            $externalRef = $payment->external_reference ?? '';
            $invoiceCode = $this->parseExternalReference($externalRef, 'invoice');

            $invoice = $invoiceCode ? Invoice::where('code', $invoiceCode)->first() : null;
            if (! $invoice) {
                Log::warning('invoice_not_found_for_payment', [
                    'payment_id' => $paymentId, 
                    'external_reference' => $externalRef,
                    'webhook_user_id' => $webhookData['user_id'] ?? 'N/A'
                ]);

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
                $invoice->update([
                    'status' => InvoiceStatus::PAID->value,
                    'payment_id' => $paymentId,
                    'transaction_amount' => $payment->transaction_amount,
                    'transaction_date' => now(),
                    'payment_method' => $payment->payment_method_id,
                ]);

                // 1. Limpa o cache para que novos cliques no sistema não gerem o link antigo
                Cache::forget('mp_preference_'.$invoice->code);

                // 2. Tenta invalidar a preferência no Mercado Pago para fechar outras abas abertas
                $this->invalidateMercadoPagoPreference($payment->preference_id, $accessToken);

                Log::info('Invoice marked as PAID and preference invalidated', [
                    'invoice' => $invoice->code,
                    'payment_id' => $paymentId,
                    'preference_id' => $payment->preference_id
                ]);
            }

            return ['success' => true];
        } catch (\Throwable $e) {
            Log::error('process_invoice_payment_error', ['payment_id' => $paymentId, 'error' => $e->getMessage()]);

            return ['success' => false, 'message' => 'Erro ao processar pagamento'];
        }
    }

    public function processPlanPayment(array $webhookData): array
    {
        $paymentId = (string) ($webhookData['data']['id'] ?? $webhookData['id'] ?? '');
        if (empty($paymentId)) {
            return ['success' => false, 'message' => 'ID do pagamento não encontrado no webhook'];
        }

        try {
            // Plan payments usually use the global app token
            $accessToken = $this->resolveAccessTokenForPlan($paymentId);
            if (empty($accessToken)) {
                return ['success' => false, 'message' => 'Token para plano não configurado'];
            }

            MercadoPagoConfig::setAccessToken($accessToken);
            $paymentClient = new PaymentClient();
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

    private function resolveAccessTokenFromWebhook(array $webhookData, ?int $forceTenantId = null): string
    {
        if ($forceTenantId) {
            $credential = ProviderCredential::withoutGlobalScopes()
                ->where('tenant_id', $forceTenantId)
                ->where('payment_gateway', 'mercadopago')
                ->first();

            if ($credential) {
                $res = $this->encryption->decryptStringLaravel($credential->access_token_encrypted);
                if ($res->isSuccess()) {
                    return (string) ($res->getData()['decrypted'] ?? '');
                }
            }
        }

        $gatewayUserId = (string) ($webhookData['user_id'] ?? '');
        
        if (!empty($gatewayUserId)) {
            $credential = ProviderCredential::withoutGlobalScopes()
                ->where('user_id_gateway', $gatewayUserId)
                ->where('payment_gateway', 'mercadopago')
                ->first();

            if ($credential) {
                $res = $this->encryption->decryptStringLaravel($credential->access_token_encrypted);
                if ($res->isSuccess()) {
                    return (string) ($res->getData()['decrypted'] ?? '');
                }
            }
        }

        // Fallback 1: try using payment_id if we already have it in DB
        $paymentId = (string) ($webhookData['data']['id'] ?? $webhookData['id'] ?? '');
        if (!empty($paymentId)) {
            $token = $this->resolveAccessTokenForInvoice($paymentId);
            if (!empty($token)) {
                return $token;
            }
        }

        // Fallback 2: if there is only one provider credential, use it (common in dev/small installs)
        $count = ProviderCredential::withoutGlobalScopes()->count();
        if ($count === 1) {
            $credential = ProviderCredential::withoutGlobalScopes()->first();
            if ($credential) {
                $res = $this->encryption->decryptStringLaravel($credential->access_token_encrypted);
                if ($res->isSuccess()) {
                    Log::info('using_single_provider_credential_fallback', ['tenant_id' => $credential->tenant_id]);
                    return (string) ($res->getData()['decrypted'] ?? '');
                }
            }
        }

        return '';
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

    /**
     * Invalida uma preferência no Mercado Pago para evitar pagamentos duplicados em abas abertas
     */
    private function invalidateMercadoPagoPreference(?string $preferenceId, string $token): void
    {
        if (!$preferenceId) return;

        try {
            MercadoPagoConfig::setAccessToken($token);

            // Usamos o Guzzle ou o próprio client do MP para dar um PUT na preferência
            // definindo que ela expira "ontem"
            $mpClient = new \MercadoPago\Net\MPHttpClient();
            $mpClient->send("/checkout/preferences/{$preferenceId}", "PUT", json_encode([
                "expires" => true,
                "expiration_date_to" => now()->subMinutes(1)->format('Y-m-d\TH:i:s.vP')
            ]), [
                "Authorization" => "Bearer {$token}",
                "Content-Type" => "application/json"
            ]);

            Log::info('Mercado Pago preference invalidated successfully', ['preference_id' => $preferenceId]);
        } catch (\Exception $e) {
            Log::warning('Failed to invalidate Mercado Pago preference', [
                'preference_id' => $preferenceId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
