<?php

namespace App\Mail\Concerns;

use App\Mail\Concerns\BaseEmail;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserConfirmationToken;
use App\Services\Infrastructure\ConfirmationLinkService;

/**
 * Classe base abstrata para e-mails que envolvem tokens de confirmação.
 *
 * Especializada em lidar com UserConfirmationToken e geração de links seguros,
 * seguindo o padrão identificado nos e-mails de verificação e boas-vindas.
 */
abstract class AbstractBaseConfirmationEmail extends BaseEmail
{
    /**
     * URL de verificação personalizada.
     */
    protected ?string $confirmationLink;

    /**
     * Token personalizado fornecido externamente.
     */
    protected ?string $verificationToken;

    /**
     * Cria uma nova instância da mailable de confirmação.
     */
    public function __construct(
        User $user,
        ?Tenant $tenant = null,
        ?string $confirmationLink = null,
        ?string $verificationToken = null,
        ?ConfirmationLinkService $confirmationLinkService = null,
    ) {
        parent::__construct( $user, $tenant, $confirmationLinkService );

        $this->confirmationLink  = $confirmationLink;
        $this->verificationToken = $verificationToken;
    }

    /**
     * Busca token de confirmação válido de forma otimizada.
     *
     * Esta implementação evita problemas de N+1 queries através de:
     * - Query direta sem eager loading desnecessário
     * - Filtros aplicados no banco de dados
     * - Tratamento eficiente de resultados
     *
     * @return UserConfirmationToken|null Token válido ou null
     */
    protected function findValidConfirmationToken(): ?UserConfirmationToken
    {
        return UserConfirmationToken::where( 'user_id', $this->user->id )
            ->where( 'expires_at', '>', now() )
            ->where( 'tenant_id', $this->user->tenant_id )
            ->latest( 'created_at' )
            ->first();
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
    protected function generateConfirmationLink(): string
    {
        // 1. Usar token fornecido diretamente (prioridade máxima)
        if ( !empty( $this->verificationToken ) ) {
            try {
                $confirmationLink = $this->confirmationLinkService->buildConfirmationLink(
                    $this->verificationToken,
                    '/confirm-account',
                    '/email/verify',
                );

                $this->logEmailOperation( 'confirmation_link_generated_with_provided_token', [
                    'token_length'     => strlen( $this->verificationToken ),
                    'confirmationLink' => $confirmationLink
                ] );

                return $confirmationLink;
            } catch ( \Throwable $e ) {
                $this->handleEmailError( $e, 'generate_confirmation_link_with_provided_token', [
                    'action' => 'fallback_to_token_search'
                ] );
            }
        }

        // 2. Buscar token personalizado válido usando sistema do projeto (fallback)
        $token = $this->findValidConfirmationToken();

        if ( $token ) {
            try {
                $confirmationLink = $this->confirmationLinkService->buildConfirmationLink(
                    $token->token,
                    '/confirm-account',
                    '/email/verify',
                );

                $this->logEmailOperation( 'confirmation_link_generated_with_db_token', [
                    'token_id'         => $token->id,
                    'expires_at'       => $token->expires_at,
                    'confirmationLink' => $confirmationLink
                ] );

                return $confirmationLink;
            } catch ( \Throwable $e ) {
                $this->handleEmailError( $e, 'generate_confirmation_link_with_db_token', [
                    'token_id' => $token->id,
                    'action'   => 'fallback_to_verification_page'
                ] );
            }
        }

        // 3. Cenário sem token disponível - redirecionar para página de verificação
        // onde o usuário pode solicitar um novo token
        $this->logEmailOperation( 'no_confirmation_token_available', [
            'action' => 'redirecting_to_verification_page'
        ] );

        return config( 'app.url' ) . '/email/verify';
    }

    /**
     * Obtém dados específicos para e-mails de confirmação.
     *
     * @return array Dados para template de confirmação
     */
    protected function getConfirmationData(): array
    {
        return array_merge( $this->getUserBasicData(), [
            'confirmationLink' => $this->confirmationLink ?? $this->generateConfirmationLink(),
            'tenant_name'      => $this->tenant?->name ?? 'Easy Budget',
        ] );
    }

}
