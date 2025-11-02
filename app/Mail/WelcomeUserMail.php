<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Concerns\AbstractBaseConfirmationEmail;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Infrastructure\LinkService;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Mailable class para envio de e-mail de boas-vindas a novos usuários.
 *
 * Esta classe herda de AbstractBaseConfirmationEmail, aproveitando toda a lógica comum
 * de geração de links, busca de tokens e tratamento de dados multi-tenant.
 * Implementa o padrão ShouldQueue para processamento assíncrono.
 */
class WelcomeUserMail extends AbstractBaseConfirmationEmail
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
     * Define o envelope do e-mail (assunto).
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bem-vindo ao ' . config( 'app.name', 'Easy Budget Laravel' ) . '!',
        );
    }

    /**
     * Define o conteúdo do e-mail.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.users.welcome',
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
