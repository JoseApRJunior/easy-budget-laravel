<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification
{
    /**
     * Handle the email sending directly without Laravel Notification system.
     */
    public function handle( $notifiable ): void
    {
        $mailMessage = $this->buildMailMessage( $notifiable );

        // Send email directly using Laravel Mail facade
        Mail::send( [], [], function ( $message ) use ( $notifiable, $mailMessage ) {
            $message->to( $notifiable->email )
                ->subject( $mailMessage->subject )
                ->html( $this->buildHtmlContent( $notifiable, $mailMessage ) );
        } );
    }

    /**
     * Build the mail message content.
     */
    private function buildMailMessage( $notifiable ): MailMessage
    {
        $verificationUrl = $this->verificationUrl( $notifiable );

        return ( new MailMessage() )
            ->subject( 'Confirmação de E-mail' )
            ->greeting( 'Olá ' . ( $notifiable->first_name ?? 'usuário' ) . '!' )
            ->line( 'Clique no botão abaixo para confirmar seu endereço de e-mail.' )
            ->action( 'Confirmar E-mail', $verificationUrl )
            ->line( 'Se estiver tendo problemas para clicar no botão "Confirmar E-mail", copie e cole a URL abaixo no seu navegador:' )
            ->line( $verificationUrl )
            ->line( 'Se você não solicitou esta verificação, pode ignorar este e-mail com segurança.' )
            ->salutation( 'Atenciosamente, Equipe Easy Budget' );
    }

    /**
     * Build the complete HTML content for the email.
     */
    private function buildHtmlContent( $notifiable, MailMessage $mailMessage ): string
    {
        $verificationUrl = $this->verificationUrl( $notifiable );

        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . $mailMessage->subject . '</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
                .button { display: inline-block; padding: 12px 24px; background-color: #3498db; color: white; text-decoration: none; border-radius: 4px; }
                .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <h1>' . $mailMessage->greeting . '</h1>

            <p>Clique no botão abaixo para confirmar seu endereço de e-mail.</p>

            <p><a href="' . $verificationUrl . '" class="button">Confirmar E-mail</a></p>

            <p>Se estiver tendo problemas para clicar no botão "Confirmar E-mail", copie e cole a URL abaixo no seu navegador:</p>

            <p><a href="' . $verificationUrl . '">' . $verificationUrl . '</a></p>

            <p>Se você não solicitou esta verificação, pode ignorar este e-mail com segurança.</p>

            <div class="footer">
                Atenciosamente,<br>
                Equipe Easy Budget
            </div>
        </body>
        </html>';
    }

    /**
     * Get the verification URL for the given notifiable.
     */
    public function verificationUrl( $notifiable ): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes( 60 ),
            [
                'id'   => $notifiable->getKey(),
                'hash' => sha1( $notifiable->getEmailForVerification() ),
            ],
        );
    }

}
