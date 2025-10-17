<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Tenant;
use App\Models\User;
use App\Services\Infrastructure\ConfirmationLinkService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Mailable class para envio de e-mail de boas-vindas a novos usuários.
 *
 * Esta classe implementa o padrão ShouldQueue para processamento assíncrono,
 * garantindo melhor performance e confiabilidade no envio de e-mails.
 *
 * Características específicas do projeto Easy Budget Laravel:
 * - Integração completa com sistema multi-tenant
 * - Uso de UserConfirmationToken personalizado
 * - Rota /confirm-account para verificação
 * - Logs detalhados para auditoria
 * - Tratamento robusto de erros e fallbacks seguros
 */
class WelcomeUserMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Usuário que receberá o e-mail de boas-vindas.
     */
    public User $user;

    /**
     * URL de verificação personalizada.
     */
    public string $confirmationLink;

    /**
     * Tenant do usuário (opcional, para contexto multi-tenant).
     */
    public ?Tenant $tenant;

    /**
     * Serviço para construção segura de links de confirmação.
     */
    private ConfirmationLinkService $confirmationLinkService;

    /**
     * Cria uma nova instância da mailable.
     *
     * @param User $user Usuário que receberá o e-mail
     * @param Tenant|null $tenant Tenant do usuário (opcional)
     * @param string $confirmationLink URL de verificação de e-mail
     * @param ConfirmationLinkService $confirmationLinkService Serviço de construção de links
     */
    public function __construct(
        User $user,
        ?Tenant $tenant = null,
        string $confirmationLink,
        ConfirmationLinkService $confirmationLinkService,
    ) {
        $this->user                    = $user;
        $this->tenant                  = $tenant;
        $this->confirmationLink        = $confirmationLink;
        $this->confirmationLinkService = $confirmationLinkService;
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
     * Obtém o primeiro nome do usuário.
     *
     * Estratégia de busca baseada na estrutura real do projeto:
     * 1. Busca através do relacionamento User → Provider → CommonData
     * 2. Usa o campo first_name do CommonData
     * 3. Fallback para parte do e-mail se nome não disponível
     *
     * @return string Primeiro nome do usuário ou parte do e-mail como fallback
     */
    private function getUserFirstName(): string
    {
        // Estratégia 1: Buscar nome através da estrutura completa User → Provider → CommonData
        if ( $this->user->provider && $this->user->provider->commonData ) {
            $firstName = $this->user->provider->commonData->first_name;

            if ( !empty( trim( $firstName ) ) ) {
                return trim( $firstName );
            }
        }

        // Estratégia 2: Fallback para parte do e-mail (antes do @)
        // Remove caracteres especiais e números para melhorar apresentação
        $emailPrefix = explode( '@', $this->user->email )[ 0 ];
        $cleanName   = preg_replace( '/[^a-zA-ZÀ-ÿ\s]/', '', $emailPrefix );

        if ( !empty( trim( $cleanName ) ) ) {
            return ucfirst( strtolower( trim( $cleanName ) ) );
        }

        // Estratégia 3: Último recurso - nome genérico
        return 'Usuário';
    }

    /**
     * Gera o link de confirmação de conta.
     *
     * Estratégia baseada no sistema real do projeto Easy Budget Laravel:
     * 1. Usa UserConfirmationToken personalizado (sistema atual do projeto)
     * 2. Rota /confirm-account conforme implementação existente
     * 3. Tratamento robusto para cenários sem token disponível
     * 4. Logs detalhados para auditoria e debugging
     *
     * @return string URL de confirmação funcional e segura
     */
    private function generateConfirmationLink(): string
    {
        // 1. Usar token fornecido diretamente (prioridade máxima)
        if ( !empty( $this->verificationToken ) ) {
            try {
                $confirmationLink = $this->confirmationLinkService->buildConfirmationLink( $this->verificationToken, '/confirm-account', '/email/verify' );

                Log::info( 'URL de confirmação gerada usando token fornecido', [
                    'user_id'          => $this->user->id,
                    'token_length'     => strlen( $this->verificationToken ),
                    'confirmationLink' => $confirmationLink
                ] );

                return $confirmationLink;
            } catch ( \Throwable $e ) {
                Log::warning( 'Erro ao gerar link de confirmação com token fornecido', [
                    'user_id' => $this->user->id,
                    'error'   => $e->getMessage(),
                    'action'  => 'fallback_to_token_search'
                ] );
            }
        }

        // 2. Buscar token personalizado válido usando sistema do projeto (fallback)
        $token = $this->findValidConfirmationToken();

        if ( $token ) {
            try {
                $confirmationLink = $this->confirmationLinkService->buildConfirmationLink( $token->token, '/confirm-account', '/email/verify' );

                Log::info( 'Token de confirmação encontrado no banco e URL gerada', [
                    'user_id'          => $this->user->id,
                    'token_id'         => $token->id,
                    'expires_at'       => $token->expires_at,
                    'confirmationLink' => $confirmationLink
                ] );

                return $confirmationLink;
            } catch ( \Throwable $e ) {
                Log::warning( 'Erro ao gerar link de confirmação com token do banco', [
                    'user_id'  => $this->user->id,
                    'token_id' => $token->id,
                    'error'    => $e->getMessage(),
                    'action'   => 'fallback_to_verification_page'
                ] );
            }
        }

        // 3. Cenário sem token disponível - redirecionar para página de verificação
        // onde o usuário pode solicitar um novo token
        Log::warning( 'Nenhum token de confirmação disponível - redirecionando para página de verificação', [
            'user_id'   => $this->user->id,
            'email'     => $this->user->email,
            'tenant_id' => $this->user->tenant_id,
            'action'    => 'redirecting_to_verification_page'
        ] );

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
     * Obtém o e-mail de suporte.
     *
     * @return string E-mail de suporte
     */
    private function getSupportEmail(): string
    {
        // Tentar obter e-mail de suporte do tenant
        if ( $this->tenant && isset( $this->tenant->settings[ 'support_email' ] ) && !empty( $this->tenant->settings[ 'support_email' ] ) ) {
            return $this->tenant->settings[ 'support_email' ];
        }

        // Tentar obter e-mail de contato do tenant
        if ( $this->tenant && isset( $this->tenant->settings[ 'contact_email' ] ) && !empty( $this->tenant->settings[ 'contact_email' ] ) ) {
            return $this->tenant->settings[ 'contact_email' ];
        }

        // E-mail padrão de suporte
        return config( 'mail.support_email', 'suporte@easybudget.com.br' );
    }

    /**
     * Obtém dados da empresa para o template.
     *
     * @return array Dados da empresa
     */
    private function getCompanyData(): array
    {
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
