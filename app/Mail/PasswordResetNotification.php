<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Concerns\AbstractBaseSimpleEmail;
use App\Models\User;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Mailable class para envio de e-mail de redefinição de senha.
 *
 * Esta classe herda de BaseSimpleEmail, aproveitando toda a lógica comum
 * de tratamento de dados do usuário, empresa e multi-tenant.
 * Implementa o padrão ShouldQueue para processamento assíncrono.
 */
class PasswordResetNotification extends AbstractBaseSimpleEmail
{
    /**
     * Token de redefinição de senha.
     */
    private string $token;

    /**
     * Cria uma nova instância da mailable.
     *
     * @param  User  $user  Usuário que receberá o e-mail
     * @param  string  $token  Token de redefinição de senha
     */
    public function __construct(
        User $user,
        string $token,
    ) {
        parent::__construct($user, null, [
            'token' => $token,
        ]);

        $this->token = $token;

        // Log da operação de criação do e-mail
        $this->logEmailOperation('password_reset_notification_created', [
            'token_length' => strlen($token),
            'user_email' => $this->getUserEmail(),
        ]);
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
        try {
            $templateData = $this->getTemplateData();

            $resetLink = $this->generateResetLink();
            $expiresAt = now()->addHours(1)->format('d/m/Y H:i:s');
            $appName = config('app.name', 'Easy Budget');

            // Log dos dados do template para auditoria
            $this->logEmailOperation('password_reset_content_generated', [
                'has_reset_link' => ! empty($resetLink),
                'expires_at' => $expiresAt,
                'app_name' => $appName,
            ]);

            return new Content(
                view: 'emails.users.forgot-password',
                with: array_merge($templateData, [
                    'reset_link' => $resetLink,
                    'expires_at' => $expiresAt,
                    'app_name' => $appName,
                ]),
            );

        } catch (\Throwable $e) {
            $this->handleEmailError($e, 'generate_password_reset_content', [
                'user_email' => $this->getUserEmail(),
                'token' => substr($this->token, 0, 10).'...', // Log parcial do token por segurança
            ]);

            // Em caso de erro, fornecer dados mínimos para o template não quebrar
            return new Content(
                view: 'emails.users.forgot-password',
                with: [
                    'first_name' => $this->getUserFirstName(),
                    'name' => $this->getUserName(),
                    'email' => $this->getUserEmail(),
                    'reset_link' => route('password.reset', ['token' => $this->token], true),
                    'expires_at' => now()->addHours(1)->format('d/m/Y H:i:s'),
                    'app_name' => config('app.name', 'Easy Budget'),
                ],
            );
        }
    }

    /**
     * Define os anexos do e-mail (nenhum por padrão).
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Gera o link de redefinição de senha.
     *
     * Estratégia baseada no sistema real do projeto Easy Budget Laravel:
     * 1. Usa route() helper para gerar URL com rota nomeada
     * 2. Passa token como parâmetro de rota
     * 3. Tratamento específico para desenvolvimento local
     * 4. Construção segura da URL com parâmetros
     * 5. Logging detalhado para auditoria e debugging
     *
     * @return string URL de redefinição de senha funcional e segura
     */
    private function generateResetLink(): string
    {
        try {
            // Usar route() helper para gerar URL com rota nomeada
            // A rota 'password.reset' espera um parâmetro 'token'
            $resetUrl = route('password.reset', ['token' => $this->token], true);

            // Log da geração do link para auditoria
            $this->logEmailOperation('password_reset_link_generated', [
                'route_name' => 'password.reset',
                'token_length' => strlen($this->token),
                'user_email' => $this->getUserEmail(),
                'reset_url' => $resetUrl,
            ]);

            return $resetUrl;

        } catch (\Throwable $e) {
            $this->handleEmailError($e, 'generate_password_reset_link', [
                'user_email' => $this->getUserEmail(),
                'token_length' => strlen($this->token),
            ]);

            // Fallback para URL padrão em caso de erro
            return route('password.reset', ['token' => $this->token], true);
        }
    }
}
