<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable para envio de email de confirmação de contato para o usuário.
 *
 * Esta classe envia um email automático confirmando que a mensagem
 * de contato foi recebida e será processada em breve.
 */
class SupportConfirmationEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Dados da confirmação.
     */
    public array $confirmationData;

    /**
     * Tenant do usuário (opcional).
     */
    public ?Tenant $tenant;

    /**
     * Cria uma nova instância da mailable.
     *
     * @param array $confirmationData Dados da confirmação
     * @param Tenant|null $tenant Tenant do usuário (opcional)
     */
    public function __construct(
        array $confirmationData,
        ?Tenant $tenant = null,
    ) {
        $this->confirmationData = $confirmationData;
        $this->tenant           = $tenant;
    }

    /**
     * Obtém o envelope da mensagem.
     */
    public function envelope(): Envelope
    {
        $subject = 'Confirmação de recebimento - ' . ( $this->confirmationData[ 'subject' ] ?? 'Mensagem de contato' );

        return new Envelope(
            to: $this->confirmationData[ 'email' ],
            subject: $subject,
        );
    }

    /**
     * Obtém a definição do conteúdo da mensagem.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.support_confirmation',
            with: [
                'confirmationData' => $this->confirmationData,
                'tenant'           => $this->tenant,
                'appName'          => config( 'app.name', 'Easy Budget' ),
                'appUrl'           => config( 'app.url' ),
            ],
        );
    }

    /**
     * Obtém os anexos da mensagem.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

}
