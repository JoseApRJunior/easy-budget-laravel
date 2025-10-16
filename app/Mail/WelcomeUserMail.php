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
     * Tamanho esperado do token de confirmação (64 caracteres).
     */
    private const EXPECTED_TOKEN_LENGTH = 64;

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
    public ?string $confirmationLink;

    /**
     * Serviço para construção segura de links de confirmação.
     */
    private ConfirmationLinkService $confirmationLinkService;

    /**
     * Cria uma nova instância da mailable.
     *
     * @param User $user Usuário que receberá o e-mail
     * @param Tenant|null $tenant Tenant do usuário (opcional)
     * @param string|null $confirmationLink URL de verificação de e-mail (opcional)
     * @param ConfirmationLinkService $confirmationLinkService Serviço de construção de links
     */
    public function __construct(
        User $user,
        ?Tenant $tenant = null,
        ?string $confirmationLink = null,
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
        // Usar link personalizado se fornecido, caso contrário gerar automaticamente
        $confirmationLink = $this->confirmationLink ?? $this->generateConfirmationLink();

        Log::info( 'Preparando conteúdo do e-mail de boas-vindas', [
            'user_id'           => $this->user->id,
            'email'             => $this->user->email,
            'has_custom_link'   => !empty( $this->confirmationLink ),
            'confirmation_link' => substr( $confirmationLink, 0, 50 ) . '...',
        ] );

        return new Content(
            view: 'emails.users.welcome',
            with: [
                'first_name'       => $this->getUserFirstName(),
                'confirmationLink' => $confirmationLink,
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
        // 1. Retorna URL personalizada se fornecida (prioridade máxima)
        if ( $this->confirmationLink ) {
            Log::info( 'Usando URL de verificação personalizada', [
                'user_id'          => $this->user->id,
                'confirmationLink' => $this->confirmationLink
            ] );
            return $this->confirmationLink;
        }

        // 2. Buscar token personalizado válido usando sistema do projeto
        $token = $this->findValidConfirmationToken();

        if ( $token ) {
            $confirmationLink = $this->confirmationLinkService->buildWelcomeConfirmationLink( $token->token );

            Log::info( 'Token de confirmação encontrado e URL gerada', [
                'user_id'          => $this->user->id,
                'token_id'         => $token->id,
                'expires_at'       => $token->expires_at,
                'confirmationLink' => $confirmationLink
            ] );

            return $confirmationLink;
        }

        // 3. Cenário sem token disponível - redirecionar para página de verificação
        // onde o usuário pode solicitar um novo token
        Log::warning( 'Nenhum token de confirmação válido encontrado - redirecionando para página de verificação', [
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

}
