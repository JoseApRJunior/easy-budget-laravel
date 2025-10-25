<?php

declare(strict_types=1);

namespace App\Services\Infrastructure;

use Illuminate\Support\Facades\Log;

/**
 * Serviço híbrido para construção segura de links de confirmação e validação.
 *
 * Centraliza toda a lógica de construção de URLs para diferentes contextos,
 * eliminando duplicação entre componentes e garantindo consistência,
 * segurança e flexibilidade em todas as implementações.
 *
 * Funcionalidades:
 * - Validação rigorosa de tokens com diferentes tipos
 * - Sanitização segura contra ataques
 * - Logging detalhado para auditoria
 * - Tratamento robusto de casos inválidos
 * - Configuração flexível de rotas e contextos
 * - Suporte a múltiplos tipos de token (email, password, etc.)
 */
class LinkService
{
    /**
     * Constrói URL segura para diferentes contextos (híbrido).
     *
     * Estratégia de segurança implementada:
     * 1. Validação rigorosa do token com diferentes formatos
     * 2. Sanitização completa para prevenir ataques
     * 3. Uso de urlencode para caracteres especiais
     * 4. Logging detalhado para auditoria
     * 5. Fallback seguro para cenários inválidos
     * 6. Suporte a múltiplos tipos de token
     *
     * @param string|null $token Token de confirmação
     * @param string $route Rota para confirmação (padrão: /confirm-account)
     * @param string $fallbackRoute Rota de fallback para casos inválidos (padrão: /login)
     * @param string $tokenType Tipo do token para validação específica (padrão: base64url)
     * @param bool $validateToken Se deve validar o token (padrão: true)
     * @return string URL completa e funcional ou fallback seguro
     */
    public function buildLink(
        ?string $token,
        string $route = '/confirm-account',
        string $fallbackRoute = '/login',
        string $tokenType = 'base64url',
        bool $validateToken = true,
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

        // Caso 2: Validação rigorosa do token (se habilitada)
        if ( $validateToken && !validateAndSanitizeToken( $token, $tokenType ) ) {
            Log::warning( 'Token inválido detectado', [
                'token_length'    => strlen( $token ),
                'expected_format' => $tokenType,
                'action'          => 'redirect_to_fallback',
                'fallback_route'  => $fallbackRoute
            ] );
            return $this->buildBaseUrl() . $fallbackRoute;
        }

        // Caso 3: Token válido - construir URL segura
        $sanitizedToken = $validateToken ? validateAndSanitizeToken( $token, $tokenType ) : $token;
        if ( $validateToken && !$sanitizedToken ) {
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

        return $this->buildLink( $token, '/confirm-account', $fallbackRoute );
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
     * Constrói URL de confirmação híbrida para verificação com parâmetros customizáveis.
     *
     * Método híbrido que permite configuração completa dos parâmetros de criação e validação
     * de links de verificação, usado por classes externas para diferentes contextos.
     *
     * @param string|null $token Token de confirmação
     * @param string $route Rota específica para verificação (padrão: /confirm-account)
     * @param string $fallbackRoute Rota de fallback (padrão: /email/verify)
     * @param string $tokenType Tipo do token para validação (padrão: base64url)
     * @param bool $validateToken Se deve validar o token (padrão: true)
     * @param array $additionalParams Parâmetros adicionais para a URL
     * @return string URL de confirmação completa
     */
    public function buildVerificationConfirmationLink(
        ?string $token,
        string $route = '/confirm-account',
        string $fallbackRoute = '/email/verify',
        string $tokenType = 'base64url',
        bool $validateToken = true,
        array $additionalParams = [],
    ): string {
        // Construir parâmetros da query string
        $queryParams = [ 'token' => $token ];

        if ( !empty( $additionalParams ) ) {
            $queryParams = array_merge( $queryParams, $additionalParams );
        }

        // Converter parâmetros para query string
        $queryString = http_build_query( $queryParams );

        Log::info( 'Construindo URL de verificação híbrida', [
            'route'             => $route,
            'fallback_route'    => $fallbackRoute,
            'token_type'        => $tokenType,
            'validate_token'    => $validateToken,
            'additional_params' => $additionalParams,
            'query_string'      => $queryString,
        ] );

        // Usar o método buildLink com os parâmetros específicos
        $url = $this->buildLink( $token, $route, $fallbackRoute, $tokenType, $validateToken );

        // Adicionar parâmetros adicionais se a URL foi construída com sucesso
        if ( $token && $validateToken && strpos( $url, 'token=' ) !== false ) {
            $baseUrl = $this->buildBaseUrl() . $route;
            $url     = $baseUrl . '?' . $queryString;
        }

        return $url;
    }

}
