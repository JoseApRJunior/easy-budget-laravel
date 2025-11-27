<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Concerns\BaseEmail;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Mailable para notificação de vinculação de conta social.
 *
 * Envia e-mail confirmando que uma conta social foi vinculada
 * a uma conta existente do usuário.
 */
class SocialAccountLinkedMail extends BaseEmail
{
    public string $provider;

    /**
     * Cria uma nova instância da mailable.
     *
     * @param  User  $user  Usuário que teve a conta vinculada
     * @param  Tenant|null  $tenant  Tenant do usuário
     * @param  string  $provider  Provedor social vinculado
     */
    public function __construct(User $user, ?Tenant $tenant = null, string $provider = 'google')
    {
        parent::__construct($user, $tenant);
        $this->provider = $provider;
    }

    /**
     * Define o envelope do e-mail.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Conta '.ucfirst($this->provider).' vinculada com sucesso!',
        );
    }

    /**
     * Define o conteúdo do e-mail.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.users.social-account-linked',
            with: array_merge($this->getUserBasicData(), [
                'provider' => $this->provider,
            ]),
        );
    }

    /**
     * Define os anexos do e-mail.
     */
    public function attachments(): array
    {
        return [];
    }
}
