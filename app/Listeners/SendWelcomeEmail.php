<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Services\Infrastructure\MailerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener responsável por enviar e-mail de boas-vindas quando um usuário se registra.
 *
 * Este listener é executado de forma assíncrona através da queue para melhorar
 * a performance e responsividade da aplicação.
 */
class SendWelcomeEmail implements ShouldQueue
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
     * @param UserRegistered $event
     * @return void
     */
    public function handle( UserRegistered $event ): void
    {
        try {
            Log::info( 'Processando evento UserRegistered para envio de e-mail de boas-vindas', [
                'user_id'   => $event->user->id,
                'email'     => $event->user->email,
                'tenant_id' => $event->tenant?->id,
            ] );

            $mailerService = app( MailerService::class);

            // Gera URL de verificação se necessário
            $verificationUrl = null;
            if ( method_exists( $event->user, 'getEmailForVerification' ) ) {
                $verificationUrl = $event->user->verification_url ?? null;
            }

            $result = $mailerService->sendWelcomeEmail(
                $event->user,
                $event->tenant,
                $verificationUrl,
            );

            if ( $result->isSuccess() ) {
                Log::info( 'E-mail de boas-vindas enviado com sucesso via evento', [
                    'user_id'   => $event->user->id,
                    'email'     => $event->user->email,
                    'queued_at' => $result->getData()[ 'queued_at' ] ?? null,
                ] );
            } else {
                Log::error( 'Falha ao enviar e-mail de boas-vindas via evento', [
                    'user_id' => $event->user->id,
                    'email'   => $event->user->email,
                    'error'   => $result->getMessage(),
                ] );

                // Relança a exceção para que seja tratada pela queue
                throw new \Exception( 'Falha no envio de e-mail de boas-vindas: ' . $result->getMessage() );
            }

        } catch ( \Throwable $e ) {
            Log::error( 'Erro crítico no listener SendWelcomeEmail', [
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
     * @param UserRegistered $event
     * @param \Throwable $exception
     * @return void
     */
    public function failed( UserRegistered $event, \Throwable $exception ): void
    {
        Log::critical( 'Listener SendWelcomeEmail falhou após todas as tentativas', [
            'user_id'  => $event->user->id,
            'email'    => $event->user->email,
            'error'    => $exception->getMessage(),
            'attempts' => $this->tries,
        ] );

        // Em produção, poderia notificar administradores sobre a falha
        // ou implementar lógica de fallback
    }

}
