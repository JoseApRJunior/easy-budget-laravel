<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Mailable class para envio de e-mail de boas-vindas para login social.
 *
 * Esta classe envia um e-mail de boas-vindas específico para usuários
 * que fazem login via provedores sociais como Google.
 */
class SocialLoginWelcomeMail extends \Illuminate\Mail\Mailable
{
    public User $user;

    public ?Tenant $tenant;

    public string $provider;

    /**
     * Cria uma nova instância da mailable.
     *
     * @param  User  $user  Usuário que receberá o e-mail
     * @param  Tenant|null  $tenant  Tenant do usuário (opcional)
     * @param  string  $provider  Provedor social usado (ex: 'google')
     */
    public function __construct(
        User $user,
        ?Tenant $tenant = null,
        string $provider = 'google',
    ) {
        $this->user = $user;
        $this->tenant = $tenant;
        $this->provider = $provider;
    }

    /**
     * Define o envelope do e-mail (assunto).
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bem-vindo ao '.config('app.name', 'Easy Budget Laravel').' via '.ucfirst($this->provider).'!',
        );
    }

    /**
     * Define o conteúdo do e-mail.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.users.social-login-welcome',
            with: [
                'user' => $this->user,
                'tenant' => $this->tenant,
                'provider' => $this->provider,
                'first_name' => $this->user->first_name ?? explode(' ', $this->user->name)[0] ?? 'usuário',
            ],
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
