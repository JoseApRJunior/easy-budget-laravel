<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailBase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmailBase implements ShouldQueue
{
    use Queueable;

    /**
     * Get the mail representation of the notification.
     */
    public function toMail( $notifiable )
    {
        $verificationUrl = $this->verificationUrl( $notifiable );

        return ( new MailMessage )
            ->subject( 'Confirme seu endereço de e-mail' )
            ->greeting( 'Olá ' . ( $notifiable->provider?->commonData?->first_name ?? 'usuário' ) . '!' )
            ->line( 'Clique no botão abaixo para confirmar seu endereço de e-mail e ativar sua conta no Easy Budget.' )
            ->action( 'Confirmar E-mail', $verificationUrl )
            ->line( 'Se você não criou uma conta no Easy Budget, ignore este e-mail.' )
            ->salutation( 'Atenciosamente, Equipe Easy Budget' );
    }

    /**
     * Get the verification URL for the given notifiable.
     */
    protected function verificationUrl( $notifiable ): string
    {
        return route( 'verification.verify', [
            'id'   => $notifiable->getKey(),
            'hash' => sha1( $notifiable->getKey() . $notifiable->getEmailForVerification() . $notifiable->created_at ),
        ] );
    }

}
