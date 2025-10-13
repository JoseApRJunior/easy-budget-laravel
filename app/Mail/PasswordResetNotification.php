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
 * Mailable class para envio de e-mail de redefinição de senha.
 *
 * Esta classe implementa o padrão ShouldQueue para processamento assíncrono,
 * garantindo melhor performance e confiabilidade no envio de e-mails.
 */
class PasswordResetNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Usuário que receberá o e-mail de redefinição de senha.
     */
    public User $user;

    /**
     * Token de redefinição de senha.
     */
    public string $token;

    /**
     * Tenant do usuário (opcional, para contexto multi-tenant).
     */
    public ?Tenant $tenant;

    /**
     * Dados adicionais da empresa para o template.
     */
    public array $company;

    /**
     * Cria uma nova instância da mailable.
     *
     * @param User $user Usuário que receberá o e-mail
     * @param string $token Token de redefinição de senha
     * @param Tenant|null $tenant Tenant do usuário (opcional)
     * @param array|null $company Dados da empresa (opcional)
     */
    public function __construct(
        User $user,
        string $token,
        ?Tenant $tenant = null,
        ?array $company = null,
    ) {
        $this->user    = $user;
        $this->token   = $token;
        $this->tenant  = $tenant;
        $this->company = $company ?? [];
    }

    /**
     * Define o envelope do e-mail (assunto).
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Redefinição de Senha - Easy Budget',
        );
    }

    /**
     * Define o conteúdo do e-mail.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.forgot-password',
            with: [
                'first_name' => $this->getUserFirstName(),
                'reset_link' => $this->generateResetLink(),
                'token'      => $this->token,
                'expires_at' => now()->addHours( 1 )->format( 'd/m/Y H:i:s' ),
                'app_name'   => config( 'app.name', 'Easy Budget' ),
                'company'    => $this->getCompanyData(),
                'tenant'     => $this->tenant,
                'user'       => $this->user,
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
     * Gera o link de redefinição de senha.
     *
     * @return string URL de redefinição de senha
     */
    private function generateResetLink(): string
    {
        $baseUrl = config( 'app.url' );

        // Para desenvolvimento local, usar localhost
        if ( app()->environment( 'local' ) ) {
            $baseUrl = 'http://localhost:8000';
        }

        return $baseUrl . '/reset-password?token=' . $this->token . '&email=' . urlencode( $this->user->email );
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
            'company_name'   => 'Easy Budget',
            'email'          => null,
            'email_business' => null,
            'phone'          => null,
            'phone_business' => null,
        ];
    }

}
