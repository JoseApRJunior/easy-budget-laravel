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
    public ?string $confirmationLink;

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
     * @param Tenant|null $tenant Tenant do usuário (opcional)
     * @param string|null $confirmationLink URL de verificação de e-mail (opcional)
     */
    public function __construct( User $user, ?Tenant $tenant = null, ?string $confirmationLink = null )
    {
        $this->user             = $user;
        $this->tenant           = $tenant;
        $this->confirmationLink = $confirmationLink;
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
            with: [
                'first_name'       => $this->getUserFirstName(),
                'confirmationLink' => $this->confirmationLink ?? $this->generateConfirmationLink(),
                'tenant_name'      => $this->tenant?->name ?? 'Easy Budget',
                'user'             => $this->user,
                'tenant'           => $this->tenant,
                'supportEmail'     => $this->getSupportEmail()
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
     * Gera o link de confirmação de conta.
     *
     * Este método implementa a estratégia de busca de token personalizada:
     * 1. Usa o confirmationLink fornecido (prioridade máxima)
     * 2. Busca primeiro por UserConfirmationToken personalizado (sistema atual)
     * 3. Usa rota /confirm-account para compatibilidade com sistema antigo
     * 4. Fallback para sistema Laravel built-in se necessário
     * 5. Tratamento robusto para cenários sem token disponível
     *
     * @return string URL de confirmação funcional e segura
     */
    private function generateConfirmationLink(): string
    {
        // 1. Retorna URL personalizada se fornecida (prioridade máxima)
        if ( $this->confirmationLink ) {
            return $this->confirmationLink;
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
        // Validar token - deve ter formato alfanumérico e comprimento adequado
        if ( empty( $token ) || !preg_match( '/^[a-zA-Z0-9]{64}$/', $token ) ) {
            return config( 'app.url' ) . '/login';
        }

        // Sanitizar token para uso seguro em URL
        $sanitizedToken = htmlspecialchars( $token, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

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
