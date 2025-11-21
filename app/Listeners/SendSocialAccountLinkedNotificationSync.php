<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SocialAccountLinked;
use App\Mail\SocialAccountLinkedMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Listener síncrono para o evento de vinculação de conta social.
 *
 * Versão síncrona do SendSocialAccountLinkedNotification para desenvolvimento.
 * Executa imediatamente sem usar queue.
 */
class SendSocialAccountLinkedNotificationSync
{
    /**
     * Cria uma nova instância do listener.
     */
    public function __construct() {}

    /**
     * Trata o evento de vinculação de conta social.
     *
     * @param SocialAccountLinked $event Evento disparado
     */
    public function handle( SocialAccountLinked $event ): void
    {
        try {
            Log::info( 'Processando vinculação de conta social (SYNC)', [
                'user_id'   => $event->user->id,
                'provider'  => $event->provider,
                'email'     => $event->user->email,
                'tenant_id' => $event->user->tenant_id,
            ] );

            // Validações de segurança
            if ( !$this->validateEventData( $event ) ) {
                Log::warning( 'Dados do evento de vinculação inválidos (SYNC)', [
                    'user_id'  => $event->user->id,
                    'provider' => $event->provider,
                ] );
                return;
            }

            // Envia e-mail de confirmação
            $this->sendConfirmationEmail( $event );

            Log::info( 'E-mail de confirmação de vinculação enviado com sucesso (SYNC)', [
                'user_id'  => $event->user->id,
                'provider' => $event->provider,
                'email'    => $event->user->email,
            ] );

        } catch ( Throwable $e ) {
            Log::error( 'Erro ao processar vinculação de conta social (SYNC)', [
                'user_id'  => $event->user->id ?? null,
                'provider' => $event->provider ?? null,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ] );

            // Re-throw para que seja tratado pelo Laravel
            throw $e;
        }
    }

    /**
     * Valida os dados do evento.
     *
     * @param SocialAccountLinked $event Evento a ser validado
     * @return bool Verdadeiro se os dados são válidos
     */
    private function validateEventData( SocialAccountLinked $event ): bool
    {
        return $event->user &&
            $event->user->exists &&
            !empty( $event->provider ) &&
            !empty( $event->user->email );
    }

    /**
     * Envia o e-mail de confirmação de vinculação.
     *
     * @param SocialAccountLinked $event Evento com dados da vinculação
     */
    private function sendConfirmationEmail( SocialAccountLinked $event ): void
    {
        $mail = new SocialAccountLinkedMail(
            user: $event->user,
            tenant: $event->user->tenant,
            provider: $event->provider,
        );

        // Envia imediatamente (síncrono)
        Mail::to( $event->user->email )->send( $mail );
    }

}
