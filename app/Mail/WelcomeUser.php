<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable class para envio de e-mail de boas-vindas a novos usuários.
 *
 * Esta classe implementa o padrão ShouldQueue para processamento assíncrono,
 * garantindo melhor performance e confiabilidade no envio de e-mails.
 */
class WelcomeUser extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Usuário que receberá o e-mail de boas-vindas.
     */
    public User $user;

    /**
     * Tenant do usuário (opcional, para contexto multi-tenant).
     */
    public ?Tenant $tenant;

    /**
     * URL de verificação de e-mail (opcional).
     */
    public ?string $verificationUrl;

    /**
     * Cria uma nova instância da mailable.
     *
     * @param User $user Usuário que receberá o e-mail
     * @param Tenant|null $tenant Tenant do usuário (opcional)
     * @param string|null $verificationUrl URL de verificação de e-mail (opcional)
     */
    public function __construct( User $user, ?Tenant $tenant = null, ?string $verificationUrl = null )
    {
        $this->user            = $user;
        $this->tenant          = $tenant;
        $this->verificationUrl = $verificationUrl;
    }

    /**
     * Define o envelope do e-mail (assunto).
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bem-vindo ao Easy Budget!',
        );
    }

    /**
     * Define o conteúdo do e-mail.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.new-user',
            with: [
                'first_name'       => $this->getUserFirstName(),
                'confirmationLink' => $this->verificationUrl ?? $this->generateConfirmationLink(),
                'tenant_name'      => $this->tenant?->name ?? 'Easy Budget',
                'user'             => $this->user,
                'tenant'           => $this->tenant,
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

    /**
     * Obtém o primeiro nome do usuário.
     *
     * @return string Primeiro nome do usuário ou e-mail se nome não disponível
     */
    private function getUserFirstName(): string
    {
        if ( $this->user->provider?->commonData ) {
            return $this->user->provider->commonData->first_name;
        }

        return explode( '@', $this->user->email )[ 0 ];
    }

    /**
     * Gera o link de confirmação de conta.
     *
     * @return string URL de confirmação
     */
    private function generateConfirmationLink(): string
    {
        if ( $this->verificationUrl ) {
            return $this->verificationUrl;
        }

        // Gera link padrão baseado no token de confirmação mais recente
        $token = $this->user->userConfirmationTokens()
            ->where( 'expires_at', '>', now() )
            ->latest()
            ->first();

        if ( $token ) {
            return config( 'app.url' ) . '/confirm-account?token=' . $token->token;
        }

        return config( 'app.url' ) . '/login';
    }

}
