<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PasswordResetRequested;
use App\Services\Infrastructure\MailerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener responsável por enviar e-mail de redefinição de senha.
 *
 * Este listener é executado de forma assíncrona através da queue para melhorar
 * a performance e responsividade da aplicação.
 */
class SendPasswordResetNotification implements ShouldQueue
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
     * @param PasswordResetRequested $event
     * @return void
     */
    public function handle( PasswordResetRequested $event ): void
    {
        try {
            Log::info( 'Processando evento PasswordResetRequested para envio de e-mail de redefinição', [
                'user_id'   => $event->user->id,
                'email'     => $event->user->email,
                'tenant_id' => $event->tenant?->id,
            ] );

            $mailerService = app( MailerService::class);

            $result = $mailerService->sendPasswordResetNotification(
                $event->user,
                $event->resetToken,
                $event->tenant,
            );

            if ( $result->isSuccess() ) {
                Log::info( 'E-mail de redefinição de senha enviado com sucesso via evento', [
                    'user_id' => $event->user->id,
                    'email'   => $event->user->email,
                    'sent_at' => $result->getData()[ 'sent_at' ] ?? null,
                ] );
            } else {
                Log::error( 'Falha ao enviar e-mail de redefinição de senha via evento', [
                    'user_id' => $event->user->id,
                    'email'   => $event->user->email,
                    'error'   => $result->getMessage(),
                ] );

                // Relança a exceção para que seja tratada pela queue
                throw new \Exception( 'Falha no envio de e-mail de redefinição de senha: ' . $result->getMessage() );
            }

        } catch ( \Throwable $e ) {
            Log::error( 'Erro crítico no listener SendPasswordResetNotification', [
                'user_id' => $event->user->id,
                'email'   => $event->user->email,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ] );

            // Relança a exceção para que seja tratada pela queue
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param PasswordResetRequested $event
     * @param \Throwable $exception
     * @return void
     */
    public function failed( PasswordResetRequested $event, \Throwable $exception ): void
    {
        Log::critical( 'Listener SendPasswordResetNotification falhou após todas as tentativas', [
            'user_id'  => $event->user->id,
            'email'    => $event->user->email,
            'error'    => $exception->getMessage(),
            'attempts' => $this->tries,
        ] );

        // Em produção, poderia notificar administradores sobre a falha
        // ou implementar lógica de fallback
    }

}
