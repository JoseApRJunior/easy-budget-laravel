<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\EmailVerificationRequested;
use App\Services\Infrastructure\MailerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener responsável por enviar e-mail de verificação quando um usuário solicita verificação de e-mail.
 *
 * Este listener processa o evento de registro de usuário e envia o e-mail de verificação
 * contendo o link para ativação da conta. É executado de forma assíncrona através da queue
 * para melhorar a performance e responsividade da aplicação.
 */
class SendEmailVerification implements ShouldQueue
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
     * @param EmailVerificationRequested $event
     * @return void
     */
    public function handle( EmailVerificationRequested $event ): void
    {
        try {
            Log::info( 'Processando evento EmailVerificationRequested para envio de e-mail de verificação', [
                'user_id'            => $event->user->id,
                'email'              => $event->user->email,
                'tenant_id'          => $event->tenant?->id,
                'verification_token' => substr( $event->verificationToken, 0, 10 ) . '...',
            ] );

            $mailerService = app( MailerService::class);

            // Gera URL de verificação usando o token do evento
            $confirmationLink = config( 'app.url' ) . '/confirm-account?token=' . $event->verificationToken;

            $result = $mailerService->sendEmailVerificationMail(
                $event->user,
                $event->tenant,
                $confirmationLink,
            );

            if ( $result->isSuccess() ) {
                Log::info( 'E-mail de verificação enviado com sucesso via evento', [
                    'user_id'   => $event->user->id,
                    'email'     => $event->user->email,
                    'queued_at' => $result->getData()[ 'queued_at' ] ?? null,
                ] );
            } else {
                Log::error( 'Falha ao enviar e-mail de verificação via evento', [
                    'user_id' => $event->user->id,
                    'email'   => $event->user->email,
                    'error'   => $result->getMessage(),
                ] );

                // Relança a exceção para que seja tratada pela queue
                throw new \Exception( 'Falha no envio de e-mail de verificação: ' . $result->getMessage() );
            }

        } catch ( \Throwable $e ) {
            Log::error( 'Erro crítico no listener SendEmailVerification', [
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
     * @param EmailVerificationRequested $event
     * @param \Throwable $exception
     * @return void
     */
    public function failed( EmailVerificationRequested $event, \Throwable $exception ): void
    {
        Log::critical( 'Listener SendEmailVerification falhou após todas as tentativas', [
            'user_id'  => $event->user->id,
            'email'    => $event->user->email,
            'error'    => $exception->getMessage(),
            'attempts' => $this->tries,
        ] );

        // Em produção, poderia notificar administradores sobre a falha
        // ou implementar lógica de fallback
    }

}
