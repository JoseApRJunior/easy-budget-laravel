<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Concerns\AbstractBaseConfirmationEmail;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Mailable class para envio de e-mail de verificação de conta.
 *
 * Esta classe herda de AbstractBaseConfirmationEmail, aproveitando toda a lógica comum
 * de geração de links, busca de tokens e tratamento de dados multi-tenant.
 * Implementa o padrão ShouldQueue para processamento assíncrono.
 */
class EmailVerificationMail extends AbstractBaseConfirmationEmail
{
    /**
     * Cria uma nova instância da mailable.
     *
     * @param User $user Usuário que receberá o e-mail
     * @param Tenant|null $tenant Tenant do usuário (opcional)
     * @param string|null $confirmationLink URL de verificação de e-mail (opcional)
     */
    public function __construct(
        User $user,
        ?Tenant $tenant = null,
        ?string $confirmationLink = null,
    ) {
        parent::__construct( $user, $tenant, $confirmationLink );
    }

    /**
     * Define o envelope do e-mail (assunto e metadados).
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bem-vindo ao Easy Budget!',
        );
    }

    /**
     * Define o conteúdo do e-mail usando Markdown.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.users.verification',
            with: $this->getConfirmationData(),
        );
    }

    /**
     * Define os anexos do e-mail (nenhum por padrão).
     */
    public function attachments(): array
    {
        return [];
    }

}
