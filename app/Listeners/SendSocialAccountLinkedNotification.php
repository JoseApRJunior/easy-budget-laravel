<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SocialAccountLinked;
use App\Mail\SocialAccountLinkedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Listener para o evento de vinculação de conta social.
 *
 * Responsável por enviar e-mail de confirmação quando uma conta social
 * é vinculada a uma conta existente do usuário.
 */
class SendSocialAccountLinkedNotification implements ShouldQueue
{
    public $tries = 3;

    public $backoff = [ 10, 30, 60 ];

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
            Log::info( 'Processando vinculação de conta social', [
                'user_id'   => $event->user->id,
                'provider'  => $event->provider,
                'email'     => $event->user->email,
                'tenant_id' => $event->user->tenant_id,
            ] );

            // Validações de segurança
            if ( !$this->validateEventData( $event ) ) {
                Log::warning( 'Dados do evento de vinculação inválidos', [
                    'user_id'  => $event->user->id,
                    'provider' => $event->provider,
                ] );
                return;
            }

            // Envia e-mail de confirmação
            $this->sendConfirmationEmail( $event );

            Log::info( 'E-mail de confirmação de vinculação enviado com sucesso', [
                'user_id'  => $event->user->id,
                'provider' => $event->provider,
                'email'    => $event->user->email,
            ] );

        } catch ( Throwable $e ) {
            Log::error( 'Erro ao processar vinculação de conta social', [
                'user_id'  => $event->user->id ?? null,
                'provider' => $event->provider ?? null,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ] );

            // Re-throw para retry automático
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
            token: $event->token,
        );

        // Usa Laravel Mail facade diretamente para processamento assíncrono
        Mail::to( $event->user->email )->queue( $mail );
    }

    /**
     * Trata falhas no processamento.
     *
     * @param SocialAccountLinked $event Evento que falhou
     * @param Throwable $exception Exceção que causou a falha
     */
    public function failed( SocialAccountLinked $event, Throwable $exception ): void
    {
        Log::critical( 'Falha crítica no processamento de vinculação de conta social', [
            'user_id'  => $event->user->id ?? null,
            'provider' => $event->provider ?? null,
            'error'    => $exception->getMessage(),
            'trace'    => $exception->getTraceAsString(),
        ] );

        // TODO: Implementar notificação para administradores sobre falha crítica
    }

}
