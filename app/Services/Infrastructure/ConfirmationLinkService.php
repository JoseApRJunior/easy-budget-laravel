<?php

declare(strict_types=1);

namespace App\Services\Infrastructure;

use Illuminate\Support\Facades\Log;

/**
 * Serviço dedicado para construção segura de links de confirmação.
 *
 * Centraliza toda a lógica de construção de URLs de confirmação,
 * eliminando duplicação entre componentes e garantindo consistência
 * e segurança em todas as implementações.
 *
 * Funcionalidades:
 * - Validação rigorosa de tokens
 * - Sanitização segura contra ataques
 * - Logging detalhado para auditoria
 * - Tratamento robusto de casos inválidos
 * - Configuração flexível de rotas
 */
class ConfirmationLinkService
{
    /**
     * Constrói URL de confirmação segura.
     *
     * Estratégia de segurança implementada:
     * 1. Validação rigorosa do token (formato base64url)
     * 2. Sanitização completa para prevenir ataques
     * 3. Uso de urlencode para caracteres especiais
     * 4. Logging detalhado para auditoria
     * 5. Fallback seguro para cenários inválidos
     *
     * @param string|null $token Token de confirmação (43 caracteres em base64url)
     * @param string $route Rota para confirmação (padrão: /confirm-account)
     * @param string $fallbackRoute Rota de fallback para casos inválidos (padrão: /login)
     * @return string URL completa e funcional ou fallback seguro
     */
    public function buildConfirmationLink(
        ?string $token,
        string $route = '/confirm-account',
        string $fallbackRoute = '/login',
    ): string {
        // Caso 1: Token vazio - redirecionar para página de verificação
        if ( empty( $token ) ) {
            Log::warning( 'Token de confirmação vazio - redirecionando para página de verificação', [
                'action'         => 'redirect_to_verification_page',
                'route'          => $route,
                'fallback_route' => $fallbackRoute
            ] );
            return $this->buildBaseUrl() . '/email/verify';
        }

        // Caso 2: Validação rigorosa do token usando formato base64url
        if ( !validateAndSanitizeToken( $token, 'base64url' ) ) {
            Log::warning( 'Token de confirmação inválido detectado', [
                'token_length'    => strlen( $token ),
                'expected_format' => 'base64url',
                'action'          => 'redirect_to_fallback',
                'fallback_route'  => $fallbackRoute
            ] );
            return $this->buildBaseUrl() . $fallbackRoute;
        }

        // Caso 3: Token válido - construir URL segura
        $sanitizedToken = validateAndSanitizeToken( $token, 'base64url' );
        if ( !$sanitizedToken ) {
            Log::warning( 'Token inválido após sanitização', [
                'token_length'   => strlen( $token ),
                'action'         => 'redirect_to_fallback',
                'fallback_route' => $fallbackRoute
            ] );
            return $this->buildBaseUrl() . $fallbackRoute;
        }
        $confirmationUrl = $this->buildBaseUrl() . $route . '?token=' . urlencode( $sanitizedToken );

        Log::info( 'URL de confirmação construída com sucesso', [
            'route'                => $route,
            'token_length'         => strlen( $sanitizedToken ),
            'confirmation_url'     => $confirmationUrl,
            'security_validations' => [
                'length_check'         => true,
                'pattern_check'        => true,
                'sanitization_applied' => true,
                'url_encoding_applied' => true
            ]
        ] );

        return $confirmationUrl;
    }

    /**
     * Constrói URL base da aplicação.
     *
     * @return string URL base sem barra final
     */
    private function buildBaseUrl(): string
    {
        return rtrim( config( 'app.url' ), '/' );
    }

    /**
     * Constrói URL de confirmação unificada para diferentes contextos de e-mail.
     *
     * Método unificado que elimina duplicação entre métodos específicos,
     * permitindo configuração flexível do contexto de confirmação.
     *
     * @param string|null $token Token de confirmação
     * @param string $context Contexto do e-mail ('welcome' ou 'verification')
     * @return string URL de confirmação
     */
    public function buildConfirmationLinkByContext( ?string $token, string $context = 'welcome' ): string
    {
        $fallbackRoutes = [
            'welcome'      => '/login',
            'verification' => '/email/verify',
        ];

        $fallbackRoute = $fallbackRoutes[ $context ] ?? '/login';

        Log::info( 'Construindo URL de confirmação por contexto', [
            'context'        => $context,
            'fallback_route' => $fallbackRoute,
            'token_length'   => strlen( $token ?? '' ),
        ] );

        return $this->buildConfirmationLink( $token, '/confirm-account', $fallbackRoute );
    }

    /**
     * Constrói URL de confirmação para e-mails de boas-vindas.
     *
     * @deprecated Use buildConfirmationLinkByContext() com contexto 'welcome'
     * @param string|null $token Token de confirmação
     * @return string URL de confirmação
     */
    public function buildWelcomeConfirmationLink( ?string $token ): string
    {
        return $this->buildConfirmationLinkByContext( $token, 'welcome' );
    }

    /**
     * Constrói URL de confirmação para e-mails de verificação.
     *
     * @deprecated Use buildConfirmationLinkByContext() com contexto 'verification'
     * @param string|null $token Token de confirmação
     * @return string URL de confirmação
     */
    public function buildVerificationConfirmationLink( ?string $token ): string
    {
        return $this->buildConfirmationLinkByContext( $token, 'verification' );
    }

}
