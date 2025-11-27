<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\InvoiceCreated;
use App\Services\Infrastructure\MailerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener responsável por enviar notificação de fatura quando uma fatura é criada.
 *
 * Este listener é executado de forma assíncrona através da queue para melhorar
 * a performance e responsividade da aplicação.
 */
class SendInvoiceNotification implements ShouldQueue
{
    /**
     * O número de vezes que o job pode ser executado novamente em caso de falha.
     */
    public int $tries = 3;

    /**
     * O tempo em segundos antes de tentar executar o job novamente.
     */
    public int $backoff = 30;

    /**
     * Handle the event.
     */
    public function handle(InvoiceCreated $event): void
    {
        try {
            Log::info('Processando evento InvoiceCreated para envio de notificação de fatura', [
                'invoice_id' => $event->invoice->id,
                'invoice_code' => $event->invoice->code,
                'customer_id' => $event->customer->id,
                'tenant_id' => $event->tenant?->id,
            ]);

            $mailerService = app(MailerService::class);

            $result = $mailerService->sendInvoiceNotification(
                $event->invoice,
                $event->customer,
                $event->tenant,
            );

            if ($result->isSuccess()) {
                Log::info('Notificação de fatura enviada com sucesso via evento', [
                    'invoice_id' => $event->invoice->id,
                    'invoice_code' => $event->invoice->code,
                    'customer_id' => $event->customer->id,
                    'queued_at' => $result->getData()['queued_at'] ?? null,
                ]);
            } else {
                Log::error('Falha ao enviar notificação de fatura via evento', [
                    'invoice_id' => $event->invoice->id,
                    'invoice_code' => $event->invoice->code,
                    'customer_id' => $event->customer->id,
                    'error' => $result->getMessage(),
                ]);

                // Relança a exceção para que seja tratada pela queue
                throw new \Exception('Falha no envio de notificação de fatura: '.$result->getMessage());
            }

        } catch (\Throwable $e) {
            Log::error('Erro crítico no listener SendInvoiceNotification', [
                'invoice_id' => $event->invoice->id,
                'customer_id' => $event->customer->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Relança a exceção para que seja tratada pela queue
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(InvoiceCreated $event, \Throwable $exception): void
    {
        Log::critical('Listener SendInvoiceNotification falhou após todas as tentativas', [
            'invoice_id' => $event->invoice->id,
            'invoice_code' => $event->invoice->code,
            'customer_id' => $event->customer->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->tries,
        ]);

        // Em produção, poderia notificar administradores sobre a falha
        // ou implementar lógica de fallback
    }
}
