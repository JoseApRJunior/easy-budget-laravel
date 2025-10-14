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
     * Este método implementa a estratégia de busca de token personalizada:
     * 1. Busca primeiro por UserConfirmationToken personalizado (sistema atual)
     * 2. Usa rota /confirm-account para compatibilidade com sistema antigo
     * 3. Fallback para sistema Laravel built-in se necessário
     * 4. Tratamento robusto para cenários sem token disponível
     *
     * @return string URL de confirmação funcional e segura
     */
    private function generateConfirmationLink(): string
    {
        // 1. Retorna URL personalizada se fornecida
        if ( $this->verificationUrl ) {
            return $this->verificationUrl;
        }

        // 2. Buscar token personalizado válido (otimizado para evitar N+1)
        $token = $this->findValidConfirmationToken();

        if ( $token ) {
            return $this->buildConfirmationUrl( $token->token );
        }

        // 3. Fallback para sistema Laravel built-in
        if ( $this->user->hasVerifiedEmail() ) {
            return config( 'app.url' ) . '/login';
        }

        // 4. Retorna URL padrão se nenhum token disponível
        return config( 'app.url' ) . '/email/verify';
    }

    /**
     * Busca token de confirmação válido de forma otimizada.
     *
     * Esta implementação evita problemas de N+1 queries através de:
     * - Query direta sem eager loading desnecessário
     * - Filtros aplicados no banco de dados
     * - Tratamento eficiente de resultados
     *
     * @return \App\Models\UserConfirmationToken|null Token válido ou null
     */
    private function findValidConfirmationToken(): ?\App\Models\UserConfirmationToken
    {
        return \App\Models\UserConfirmationToken::where( 'user_id', $this->user->id )
            ->where( 'expires_at', '>', now() )
            ->where( 'tenant_id', $this->user->tenant_id )
            ->latest( 'created_at' )
            ->first();
    }

    /**
     * Constrói URL de confirmação segura.
     *
     * @param string $token Token de confirmação
     * @return string URL completa e funcional
     */
    private function buildConfirmationUrl( string $token ): string
    {
        // Sanitizar token para evitar problemas de segurança
        $sanitizedToken = filter_var( $token, FILTER_SANITIZE_STRING );

        if ( empty( $sanitizedToken ) || strlen( $sanitizedToken ) !== 64 ) {
            return config( 'app.url' ) . '/login';
        }

        return config( 'app.url' ) . '/confirm-account?token=' . urlencode( $sanitizedToken );
    }

}
