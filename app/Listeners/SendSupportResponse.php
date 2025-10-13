<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SupportTicketResponded;
use App\Services\Infrastructure\MailerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener responsável por enviar resposta de suporte quando um ticket recebe resposta.
 *
 * Este listener é executado de forma assíncrona através da queue para melhorar
 * a performance e responsividade da aplicação.
 */
class SendSupportResponse implements ShouldQueue
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
     *
     * @param SupportTicketResponded $event
     * @return void
     */
    public function handle( SupportTicketResponded $event ): void
    {
        try {
            Log::info( 'Processando evento SupportTicketResponded para envio de resposta de suporte', [
                'ticket_id'      => $event->ticket[ 'id' ] ?? null,
                'ticket_subject' => $event->ticket[ 'subject' ] ?? 'Sem assunto',
                'tenant_id'      => $event->tenant?->id,
            ] );

            $mailerService = app( MailerService::class);

            $result = $mailerService->sendSupportResponse(
                $event->ticket,
                $event->response,
                $event->tenant,
            );

            if ( $result->isSuccess() ) {
                Log::info( 'Resposta de suporte enviada com sucesso via evento', [
                    'ticket_id'      => $event->ticket[ 'id' ] ?? null,
                    'ticket_subject' => $event->ticket[ 'subject' ] ?? 'Sem assunto',
                    'sent_at'        => $result->getData()[ 'sent_at' ] ?? null,
                ] );
            } else {
                Log::error( 'Falha ao enviar resposta de suporte via evento', [
                    'ticket_id'      => $event->ticket[ 'id' ] ?? null,
                    'ticket_subject' => $event->ticket[ 'subject' ] ?? 'Sem assunto',
                    'error'          => $result->getMessage(),
                ] );

                // Relança a exceção para que seja tratada pela queue
                throw new \Exception( 'Falha no envio de resposta de suporte: ' . $result->getMessage() );
            }

        } catch ( \Throwable $e ) {
            Log::error( 'Erro crítico no listener SendSupportResponse', [
                'ticket_id'      => $event->ticket[ 'id' ] ?? null,
                'ticket_subject' => $event->ticket[ 'subject' ] ?? 'Sem assunto',
                'error'          => $e->getMessage(),
                'trace'          => $e->getTraceAsString(),
            ] );

            // Relança a exceção para que seja tratada pela queue
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param SupportTicketResponded $event
     * @param \Throwable $exception
     * @return void
     */
    public function failed( SupportTicketResponded $event, \Throwable $exception ): void
    {
        Log::critical( 'Listener SendSupportResponse falhou após todas as tentativas', [
            'ticket_id'      => $event->ticket[ 'id' ] ?? null,
            'ticket_subject' => $event->ticket[ 'subject' ] ?? 'Sem assunto',
            'error'          => $exception->getMessage(),
            'attempts'       => $this->tries,
        ] );

        // Em produção, poderia notificar administradores sobre a falha
        // ou implementar lógica de fallback
    }

}
