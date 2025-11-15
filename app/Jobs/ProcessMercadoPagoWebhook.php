<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Infrastructure\Payment\MercadoPagoWebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Processes Mercado Pago webhook notifications asynchronously.
 *
 * Handles payment notifications from Mercado Pago with retry logic
 * and error handling. Delegates processing to specialized services
 * based on payment type.
 *
 * @package App\Jobs
 */
class ProcessMercadoPagoWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of retry attempts.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * Seconds to wait before retrying.
     *
     * @var int
     */
    public int $backoff = 60;

    /**
     * Creates a new job instance.
     *
     * @param array $webhookData Webhook notification data
     * @param string $type Payment type (plan|invoice)
     */
    public function __construct(
        public array $webhookData,
        public string $type,
        private ?string $requestId = null
    ) {}

    /**
     * Executes the job.
     *
     * @param MercadoPagoWebhookService $webhookService Webhook processing service
     * @return void
     */
    public function handle(MercadoPagoWebhookService $webhookService): void
    {
        try {
            $paymentId = (string) ($this->webhookData['data']['id'] ?? '');

            Log::info("Processing Mercado Pago webhook", [
                'type' => $this->type,
                'payment_id' => $paymentId,
                'attempt' => $this->attempts(),
            ]);

            $result = match ($this->type) {
                'plan' => $webhookService->processPlanPayment($paymentId),
                'invoice' => $webhookService->processInvoicePayment($paymentId),
                default => throw new \InvalidArgumentException("Invalid payment type: {$this->type}"),
            };

            if (!$result['success']) {
                throw new \Exception($result['message'] ?? 'Payment processing failed');
            }

            Log::info("Webhook processed successfully", [
                'type' => $this->type,
                'payment_id' => $paymentId,
            ]);

            if ($this->requestId) {
                \App\Models\WebhookRequest::where('request_id', $this->requestId)
                    ->where('type', $this->type)
                    ->update([
                        'status' => 'processed',
                        'processed_at' => now(),
                    ]);
            }
        } catch (\Exception $e) {
            Log::error("Webhook processing failed", [
                'type' => $this->type,
                'payment_id' => $this->webhookData['data']['id'] ?? null,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            throw $e;
        }
    }

    /**
     * Handles job failure.
     *
     * @param \Throwable $exception Exception that caused failure
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical("Webhook processing failed permanently", [
            'type' => $this->type,
            'payment_id' => $this->webhookData['data']['id'] ?? null,
            'error' => $exception->getMessage(),
            'attempts' => $this->tries,
        ]);

        if ($this->requestId) {
            \App\Models\WebhookRequest::where('request_id', $this->requestId)
                ->where('type', $this->type)
                ->update([
                    'status' => 'failed',
                    'processed_at' => now(),
                ]);
        }
    }
}
