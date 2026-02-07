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

        $requestId = $request->header('X-Request-Id');
        if (! $requestId) {
            Log::warning('mp_webhook_missing_request_id');

            return response()->json(['status' => 'error', 'message' => 'Missing X-Request-Id'], 400);
        }

        $signature = $request->header('X-Signature');
        $secret = config('services.mercadopago.webhook_secret');
        if ($signature && $secret) {
            $computed = hash_hmac('sha256', $request->getContent(), $secret);
            if (! hash_equals($computed, $signature)) {
                Log::warning('mp_webhook_invalid_signature', ['request_id' => $requestId]);

                return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 403);
            }
        }

        $cacheKey = 'mp_webhook_req_'.$requestId;
        if (Cache::has($cacheKey)) {
            Log::info('mp_webhook_duplicate', ['request_id' => $requestId]);

            return response()->json(['status' => 'ignored'], 200);
        }
        Cache::put($cacheKey, true, now()->addMinutes(10));

        $payloadHash = hash('sha256', $request->getContent());
        $existing = \App\Models\WebhookRequest::where('request_id', $requestId)->where('type', $type)->first();
        if ($existing) {
            Log::info('mp_webhook_duplicate_db', ['request_id' => $requestId]);

            return response()->json(['status' => 'ignored'], 200);
        }
        \App\Models\WebhookRequest::create([
            'request_id' => $requestId,
            'type' => $type,
            'payload_hash' => $payloadHash,
            'status' => 'received',
            'received_at' => now(),
        ]);

        Log::info('Mercado Pago webhook received', [
            'type' => $type,
            'topic' => $webhookData['topic'] ?? null,
            'id' => $webhookData['id'] ?? null,
        ]);

        // Validate webhook structure
        if (! isset($webhookData['type']) || ! isset($webhookData['data']['id'])) {
            Log::warning('Invalid webhook structure', ['data' => $webhookData]);

            return response()->json(['status' => 'error', 'message' => 'Invalid webhook structure'], 400);
        }

        // Only process payment notifications
        if ($webhookData['type'] !== 'payment') {
            Log::info('Ignoring non-payment webhook', ['type' => $webhookData['type']]);

            return response()->json(['status' => 'ignored'], 200);
        }

        // Dispatch to queue for async processing
        Queue::connection('null')->push(new ProcessMercadoPagoWebhook($webhookData, $type, $requestId));

        return response()->json(['status' => 'accepted'], 200);
    }
}
