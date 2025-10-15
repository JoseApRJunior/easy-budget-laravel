<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\EmailVerificationRequested;
use App\Services\Infrastructure\MailerService;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Listener para envio de e-mail de verificação.
 *
 * Este listener captura o evento EmailVerificationRequested e utiliza
 * o MailerService para envio efetivo do e-mail de verificação.
 *
 * Segue o padrão estabelecido no sistema de usar eventos + listeners
 * para desacoplamento entre lógica de negócio e envio de e-mails.
 */
class SendEmailVerification
{
    protected MailerService $mailerService;

    public function __construct( MailerService $mailerService )
    {
        $this->mailerService = $mailerService;
    }

    /**
     * Trata o evento EmailVerificationRequested.
     *
     * @param EmailVerificationRequested $event
     * @return void
     */
    public function handle( EmailVerificationRequested $event ): void
    {
        try {
            Log::info( 'Processando evento EmailVerificationRequested para envio de e-mail de verificação', [
                'user_id'   => $event->user->id,
                'tenant_id' => $event->user->tenant_id,
                'email'     => $event->user->email,
            ] );

            $mailerService = app( MailerService::class);

            // Gera URL de verificação se necessário
            $verificationUrl = null;
            if ( method_exists( $event->user, 'getEmailForVerification' ) ) {
                $verificationUrl = $event->user->verification_url ?? null;
            }

            $result = $mailerService->sendEmailVerificationMail(
                $event->user,
                $event->tenant,
                $verificationUrl,
            );

            if ( $result->isSuccess() ) {
                Log::info( 'E-mail de verificação enviado com sucesso', [
                    'user_id'   => $event->user->id,
                    'email'     => $event->user->email,
                    'tenant_id' => $event->user->tenant_id,
                ] );
            } else {
                Log::error( 'Falha no envio do e-mail de verificação via MailerService', [
                    'user_id'   => $event->user->id,
                    'email'     => $event->user->email,
                    'tenant_id' => $event->user->tenant_id,
                    'error'     => $result->getMessage(),
                ] );
            }

        } catch ( Exception $e ) {
            Log::error( 'Erro crítico no listener SendEmailVerificationNotification', [
                'user_id'   => $event->user->id,
                'email'     => $event->user->email,
                'tenant_id' => $event->user->tenant_id,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ] );

            // Não relançar exceção para evitar quebrar a cadeia de eventos
        }
    }

    /**
     * Trata falhas no processamento do evento.
     *
     * @param EmailVerificationRequested $event
     * @param \Throwable $exception
     * @return void
     */
    public function failed( EmailVerificationRequested $event, \Throwable $exception ): void
    {
        Log::error( 'Falha crítica no processamento do evento EmailVerificationRequested', [
            'user_id'   => $event->user->id,
            'email'     => $event->user->email,
            'tenant_id' => $event->user->tenant_id,
            'error'     => $exception->getMessage(),
            'trace'     => $exception->getTraceAsString(),
        ] );

        // Em caso de falha crítica, poderíamos implementar:
        // - Notificação para administradores
        // - Retry automático
        // - Fallback para método alternativo de envio
    }

}
