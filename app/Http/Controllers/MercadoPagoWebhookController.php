<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\ProcessMercadoPagoWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Handles Mercado Pago webhook notifications.
 *
 * Processes payment notifications from Mercado Pago for both plan subscriptions
 * and invoice payments. Implements asynchronous processing via queues for
 * reliability and performance.
 *
 * @package App\Http\Controllers
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
     * @param Request $request Webhook notification data
     * @return JsonResponse Acknowledgment response
     */
    public function handlePlanWebhook(Request $request): JsonResponse
    {
        return $this->processWebhook($request, 'plan');
    }

    /**
     * Processes invoice payment webhooks.
     *
     * @param Request $request Webhook notification data
     * @return JsonResponse Acknowledgment response
     */
    public function handleInvoiceWebhook(Request $request): JsonResponse
    {
        return $this->processWebhook($request, 'invoice');
    }

    /**
     * Processes webhook notification asynchronously.
     *
     * @param Request $request Webhook data
     * @param string $type Payment type (plan|invoice)
     * @return JsonResponse Acknowledgment response
     */
    private function processWebhook(Request $request, string $type): JsonResponse
    {
        $webhookData = $request->all();
        
        Log::info("Mercado Pago webhook received", [
            'type' => $type,
            'topic' => $webhookData['topic'] ?? null,
            'id' => $webhookData['id'] ?? null,
        ]);

        // Validate webhook structure
        if (!isset($webhookData['type']) || !isset($webhookData['data']['id'])) {
            Log::warning("Invalid webhook structure", ['data' => $webhookData]);
            return response()->json(['status' => 'error', 'message' => 'Invalid webhook structure'], 400);
        }

        // Only process payment notifications
        if ($webhookData['type'] !== 'payment') {
            Log::info("Ignoring non-payment webhook", ['type' => $webhookData['type']]);
            return response()->json(['status' => 'ignored'], 200);
        }

        // Dispatch to queue for async processing
        ProcessMercadoPagoWebhook::dispatch($webhookData, $type);

        return response()->json(['status' => 'accepted'], 200);
    }
}
