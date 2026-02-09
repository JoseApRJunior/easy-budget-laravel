<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Jobs\ProcessMercadoPagoWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

/**
 * Handles Mercado Pago webhook notifications.
 *
 * Processes payment notifications from Mercado Pago for both plan subscriptions
 * and invoice payments. Implements asynchronous processing via queues for
 * reliability and performance.
 *
 *
 * @example Webhook endpoints:
 * ```
 * POST /webhooks/mercadopago/plans - Plan subscription payments
 * POST /webhooks/mercadopago/invoices - Invoice payments
 * ```
 */
class MercadoPagoWebhookController extends Controller
{
    /**
     * Processes plan subscription payment webhooks.
     *
     * @param  Request  $request  Webhook notification data
     * @return JsonResponse Acknowledgment response
     */
    public function handlePlanWebhook(Request $request): JsonResponse
    {
        return $this->processWebhook($request, 'plan');
    }

    /**
     * Processes invoice payment webhooks.
     *
     * @param  Request  $request  Webhook notification data
     * @return JsonResponse Acknowledgment response
     */
    public function handleInvoiceWebhook(Request $request): JsonResponse
    {
        Log::info('mp_webhook_received', ['payload' => $request->all()]);
        return $this->processWebhook($request, 'invoice');
    }

    /**
     * Processes webhook notification asynchronously.
     *
     * @param  Request  $request  Webhook data
     * @param  string  $type  Payment type (plan|invoice)
     * @return JsonResponse Acknowledgment response
     */
    private function processWebhook(Request $request, string $type): JsonResponse
    {
        $webhookData = $request->all();

        // Mercado Pago pode enviar ID no corpo ou como query param
        $requestId = $request->header('X-Request-Id') 
            ?? $webhookData['id'] 
            ?? $webhookData['data']['id'] 
            ?? uniqid('mp_', true);

        $signature = $request->header('X-Signature');
        $secret = config('services.mercadopago.webhook_secret');
        
        if ($signature && $secret) {
            // Tenta validar a assinatura (suporta formato simples e v1/v2 básico)
            $isValid = false;
            
            // 1. Tenta hash direto (legado/simples)
            $computed = hash_hmac('sha256', $request->getContent(), $secret);
            if (hash_equals($computed, $signature)) {
                $isValid = true;
            } else {
                // 2. Tenta parsear formato ts=...,v1=...
                parse_str(str_replace(',', '&', $signature), $sigParts);
                if (isset($sigParts['ts']) && isset($sigParts['v1'])) {
                    $resourceId = $webhookData['data']['id'] ?? $webhookData['id'] ?? '';
                    $mpRequestId = $request->header('X-Request-Id') ?? '';
                    $template = "id:{$resourceId};request-id:{$mpRequestId};ts:{$sigParts['ts']};";
                    $computedV1 = hash_hmac('sha256', $template, $secret);
                    if (hash_equals($computedV1, $sigParts['v1'])) {
                        $isValid = true;
                    }
                }
            }

            if (! $isValid) {
                Log::warning('mp_webhook_invalid_signature', [
                    'request_id' => $requestId,
                    'type' => $type,
                    'signature_received' => $signature,
                    'headers' => $request->headers->all(),
                    'payload' => $webhookData
                ]);

                // Em ambiente de dev, vamos permitir o processamento mesmo com assinatura inválida
                // mas logamos o aviso para investigação.
                if (config('app.env') === 'production') {
                    return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 403);
                }
            }
        }

        $cacheKey = 'mp_webhook_req_'.$requestId;
        if (Cache::has($cacheKey)) {
            return response()->json(['status' => 'ignored', 'reason' => 'duplicate_cache'], 200);
        }
        Cache::put($cacheKey, true, now()->addMinutes(10));

        $payloadHash = hash('sha256', $request->getContent());
        $existing = \App\Models\WebhookRequest::where('request_id', (string)$requestId)->where('type', $type)->first();
        
        if ($existing) {
            return response()->json(['status' => 'ignored', 'reason' => 'duplicate_db'], 200);
        }

        \App\Models\WebhookRequest::create([
            'request_id' => (string)$requestId,
            'type' => $type,
            'payload_hash' => $payloadHash,
            'status' => 'received',
            'received_at' => now(),
        ]);

        Log::info('Mercado Pago webhook received', [
            'type' => $type,
            'request_id' => $requestId,
            'payload' => $webhookData,
        ]);

        // Validate webhook structure (MP v2 uses 'type' and 'data.id')
        $id = $webhookData['data']['id'] ?? $webhookData['id'] ?? null;
        $mpType = $webhookData['type'] ?? $webhookData['topic'] ?? null;

        if (! $id || ! $mpType) {
            Log::warning('Invalid webhook structure', ['data' => $webhookData]);
            return response()->json(['status' => 'error', 'message' => 'Invalid webhook structure'], 400);
        }

        // Only process payment notifications
        if ($mpType !== 'payment') {
            return response()->json(['status' => 'ignored', 'reason' => 'not_a_payment'], 200);
        }

        // Dispatch to default queue for async processing
        ProcessMercadoPagoWebhook::dispatch($webhookData, $type, (string)$requestId);

        return response()->json(['status' => 'accepted'], 200);
    }
}
