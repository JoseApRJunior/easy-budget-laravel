<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\StatusUpdated;
use App\Services\Infrastructure\MailerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Listener responsável por enviar notificação de atualização de status.
 *
 * Este listener é executado de forma assíncrona através da queue para melhorar
 * a performance e responsividade da aplicação.
 */
class SendStatusUpdateNotification implements ShouldQueue
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
    public function handle(StatusUpdated $event): void
    {
        try {
            // 1. Deduplicação para evitar envios duplicados
            $entityType = class_basename($event->entity);
            $dedupeKey = "email:status_update:{$entityType}:{$event->entity->id}:{$event->newStatus}";
            if (! Cache::add($dedupeKey, true, now()->addMinutes(30))) {
                Log::warning('Notificação de status ignorada por deduplicação', [
                    'entity_type' => $entityType,
                    'entity_id' => $event->entity->id,
                    'new_status' => $event->newStatus,
                    'dedupe_key' => $dedupeKey
                ]);
                return;
            }

            Log::info('Processando evento StatusUpdated para envio de notificação', [
                'entity_type' => class_basename($event->entity),
                'entity_id' => $event->entity->id,
                'old_status' => $event->oldStatus,
                'new_status' => $event->newStatus,
                'status_name' => $event->statusName,
                'tenant_id' => $event->tenant?->id,
            ]);

            $mailerService = app(MailerService::class);

            // Gera URL da entidade se disponível
            $entityUrl = null;
            if (method_exists($event->entity, 'getUrl')) {
                $entityUrl = $event->entity->getUrl();
            }

            $result = $mailerService->sendStatusUpdateNotification(
                $event->entity,
                $event->newStatus,
                $event->statusName,
                $event->tenant,
                null, // company data - pode ser adicionado posteriormente
                $entityUrl,
            );

            if ($result->isSuccess()) {
                Log::info('Notificação de atualização de status enviada com sucesso via evento', [
                    'entity_type' => class_basename($event->entity),
                    'entity_id' => $event->entity->id,
                    'old_status' => $event->oldStatus,
                    'new_status' => $event->newStatus,
                    'status_name' => $event->statusName,
                    'sent_at' => $result->getData()['sent_at'] ?? null,
                ]);
            } else {
                Log::error('Falha ao enviar notificação de atualização de status via evento', [
                    'entity_type' => class_basename($event->entity),
                    'entity_id' => $event->entity->id,
                    'old_status' => $event->oldStatus,
                    'new_status' => $event->newStatus,
                    'status_name' => $event->statusName,
                    'error' => $result->getMessage(),
                ]);

                // Relança a exceção para que seja tratada pela queue
                throw new \Exception('Falha no envio de notificação de atualização de status: '.$result->getMessage());
            }

        } catch (\Throwable $e) {
            Log::error('Erro crítico no listener SendStatusUpdateNotification', [
                'entity_type' => class_basename($event->entity),
                'entity_id' => $event->entity->id,
                'old_status' => $event->oldStatus,
                'new_status' => $event->newStatus,
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
    public function failed(StatusUpdated $event, \Throwable $exception): void
    {
        Log::critical('Listener SendStatusUpdateNotification falhou após todas as tentativas', [
            'entity_type' => class_basename($event->entity),
            'entity_id' => $event->entity->id,
            'old_status' => $event->oldStatus,
            'new_status' => $event->newStatus,
            'error' => $exception->getMessage(),
            'attempts' => $this->tries,
        ]);

        // Em produção, poderia notificar administradores sobre a falha
        // ou implementar lógica de fallback
    }
}
