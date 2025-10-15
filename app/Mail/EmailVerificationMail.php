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
 * Mailable class para envio de e-mail de verificação de conta.
 *
 * Esta classe implementa o padrão ShouldQueue para processamento assíncrono,
 * garantindo melhor performance e confiabilidade no envio de e-mails.
 * Usa templates Markdown profissionais com internacionalização.
 */
class EmailVerificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Usuário que receberá o e-mail de verificação.
     */
    public User $user;

    /**
     * Token de verificação de e-mail.
     */
    public string $verificationToken;

    /**
     * URL de verificação personalizada.
     */
    public ?string $verificationUrl;

    /**
     * Tenant do usuário (opcional, para contexto multi-tenant).
     */
    public ?Tenant $tenant;

    /**
     * Dados adicionais da empresa para o template.
     */
    public array $company;

    /**
     * Locale para internacionalização (pt-BR, en, etc).
     */
    private string $emailLocale;

    /**
     * Cria uma nova instância da mailable.
     *
     * @param User $user Usuário que receberá o e-mail
     * @param string $verificationToken Token de verificação
     * @param string|null $verificationUrl URL de verificação personalizada (opcional)
     * @param Tenant|null $tenant Tenant do usuário (opcional)
     * @param array|null $company Dados da empresa (opcional)
     * @param string $locale Locale para internacionalização (opcional, padrão: pt-BR)
     */
    public function __construct(
        User $user,
        string $verificationToken,
        ?string $verificationUrl = null,
        ?Tenant $tenant = null,
        ?array $company = null,
        string $locale = 'pt-BR',
    ) {
        $this->user              = $user;
        $this->verificationToken = $verificationToken;
        $this->verificationUrl   = $verificationUrl;
        $this->tenant            = $tenant;
        $this->company           = $company ?? [];
        $this->emailLocale       = $locale;

        // Configurar locale para internacionalização
        app()->setLocale( $this->emailLocale );
    }

    /**
     * Define o envelope do e-mail (assunto e metadados).
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __( 'emails.verification.subject', [
                'app_name' => config( 'app.name', 'Easy Budget' )
            ], $this->emailLocale ),
            tags: [ 'email-verification', 'user-registration' ],
            metadata: [
                'user_id'            => $this->user->id,
                'tenant_id'          => $this->tenant?->id,
                'locale'             => $this->emailLocale,
                'verification_token' => substr( $this->verificationToken, 0, 8 ) . '...',
            ],
        );
    }

    /**
     * Define o conteúdo do e-mail usando Markdown.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.verification',
            with: [
                'user'              => $this->user,
                'first_name'        => $this->getUserFirstName(),
                'user_name'         => $this->getUserName(),
                'user_email'        => $this->getUserEmail(),
                'verificationToken' => $this->verificationToken,
                'verificationUrl'   => $this->generateVerificationUrl(),
                'expiresAt'         => now()->addMinutes( 30 )->format( 'd/m/Y H:i:s' ),
                'tenant'            => $this->tenant,
                'company'           => $this->getCompanyData(),
                'locale'            => $this->emailLocale,
                'appName'           => config( 'app.name', 'Easy Budget' ),
                'supportEmail'      => $this->getSupportEmail(),
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
     * Gera a URL de verificação segura.
     *
     * @return string URL completa de verificação
     */
    private function generateVerificationUrl(): string
    {
        if ( $this->verificationUrl ) {
            return $this->verificationUrl;
        }

        // Buscar token personalizado válido
        $token = $this->findValidConfirmationToken();

        if ( $token ) {
            return $this->buildConfirmationUrl( $token->token );
        }

        // Fallback para sistema Laravel built-in
        if ( $this->user->hasVerifiedEmail() ) {
            return config( 'app.url' ) . '/login';
        }

        return config( 'app.url' ) . '/email/verify';
    }

    /**
     * Busca token de confirmação válido de forma otimizada.
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

    /**
     * Obtém dados da empresa para o template.
     *
     * @return array Dados da empresa
     */
    private function getCompanyData(): array
    {
        if ( !empty( $this->company ) ) {
            return $this->company;
        }

        // Tentar obter dados da empresa através do tenant
        if ( $this->tenant ) {
            return [
                'company_name'   => $this->tenant->name,
                'email'          => null,
                'email_business' => null,
                'phone'          => null,
                'phone_business' => null,
            ];
        }

        return [
            'company_name'   => config( 'app.name', 'Easy Budget' ),
            'email'          => null,
            'email_business' => null,
            'phone'          => null,
            'phone_business' => null,
        ];
    }

    /**
     * Obtém o e-mail de suporte.
     *
     * @return string E-mail de suporte
     */
    private function getSupportEmail(): string
    {
        // Tentar obter e-mail de suporte do tenant
        if ( $this->tenant && isset( $this->tenant->settings[ 'support_email' ] ) ) {
            return $this->tenant->settings[ 'support_email' ];
        }

        // E-mail padrão de suporte
        return config( 'mail.support_email', 'suporte@easybudget.com.br' );
    }

    /**
     * Obtém o primeiro nome do usuário para personalização.
     *
     * @return string Primeiro nome do usuário
     */
    private function getUserFirstName(): string
    {
        if ( $this->user && $this->user->provider?->commonData ) {
            return $this->user->provider->commonData->first_name;
        }

        if ( $this->user && $this->user->email ) {
            return explode( '@', $this->user->email )[ 0 ];
        }

        return 'usuário';
    }

    /**
     * Obtém o e-mail do usuário.
     *
     * @return string E-mail do usuário
     */
    private function getUserEmail(): string
    {
        return $this->user?->email ?? 'usuario@exemplo.com';
    }

    /**
     * Obtém o nome do usuário para personalização.
     *
     * @return string Nome do usuário
     */
    private function getUserName(): string
    {
        return $this->getUserFirstName();
    }

}
