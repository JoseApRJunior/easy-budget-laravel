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
     * Tamanho esperado do token de confirmação (64 caracteres).
     */
    private const EXPECTED_TOKEN_LENGTH = 64;

    /**
     * Padrão regex para validação de tokens alfanuméricos.
     */
    private const TOKEN_PATTERN = '/^[a-zA-Z0-9]{64}$/';

    /**
     * Constrói URL de confirmação segura.
     *
     * Estratégia de segurança implementada:
     * 1. Validação rigorosa do token (formato e comprimento)
     * 2. Sanitização completa para prevenir ataques
     * 3. Uso de urlencode para caracteres especiais
     * 4. Logging detalhado para auditoria
     * 5. Fallback seguro para cenários inválidos
     *
     * @param string|null $token Token de confirmação (64 caracteres)
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

        // Caso 2: Validação rigorosa do token
        if ( !$this->isValidToken( $token ) ) {
            Log::warning( 'Token de confirmação inválido detectado', [
                'token_length'    => strlen( $token ),
                'expected_length' => self::EXPECTED_TOKEN_LENGTH,
                'token_pattern'   => 'alphanumeric_64_chars',
                'action'          => 'redirect_to_fallback',
                'fallback_route'  => $fallbackRoute
            ] );
            return $this->buildBaseUrl() . $fallbackRoute;
        }

        // Caso 3: Token válido - construir URL segura
        $sanitizedToken  = $this->sanitizeToken( $token );
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
     * Valida se o token tem formato correto.
     *
     * @param string $token Token a ser validado
     * @return bool True se válido, false caso contrário
     */
    private function isValidToken( string $token ): bool
    {
        return strlen( $token ) === self::EXPECTED_TOKEN_LENGTH &&
            preg_match( self::TOKEN_PATTERN, $token );
    }

    /**
     * Sanitiza token para uso seguro em URLs.
     *
     * Usa múltiplas camadas de sanitização para máxima segurança:
     * 1. Sanitização HTML para prevenir ataques XSS
     * 2. Sanitização geral para caracteres especiais
     * 3. Validação final após sanitização
     *
     * @param string $token Token original
     * @return string Token sanitizado
     */
    private function sanitizeToken( string $token ): string
    {
        // Camada 1: Sanitização HTML
        $sanitized = htmlspecialchars( $token, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

        // Camada 2: Sanitização adicional para máxima segurança
        $sanitized = filter_var( $sanitized, FILTER_UNSAFE_RAW, FILTER_FLAG_NO_ENCODE_QUOTES );

        // Camada 3: Validação final após sanitização
        if ( strlen( $sanitized ) !== self::EXPECTED_TOKEN_LENGTH ) {
            Log::warning( 'Token corrompido após sanitização', [
                'original_length'  => strlen( $token ),
                'sanitized_length' => strlen( $sanitized ),
                'expected_length'  => self::EXPECTED_TOKEN_LENGTH
            ] );
            return '';
        }

        return $sanitized;
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

    /**
     * Verifica se um token é válido sem construir URL.
     *
     * Método utilitário para validações rápidas.
     *
     * @param string|null $token Token a ser verificado
     * @return bool True se válido, false caso contrário
     */
    public function isValidConfirmationToken( ?string $token ): bool
    {
        return !empty( $token ) && $this->isValidToken( $token );
    }

    /**
     * Obtém informações sobre um token para debugging.
     *
     * @param string|null $token Token a ser analisado
     * @return array Informações do token
     */
    public function getTokenInfo( ?string $token ): array
    {
        if ( empty( $token ) ) {
            return [
                'is_valid' => false,
                'length'   => 0,
                'reason'   => 'token_empty'
            ];
        }

        $length             = strlen( $token );
        $has_correct_length = $length === self::EXPECTED_TOKEN_LENGTH;
        $matches_pattern    = preg_match( self::TOKEN_PATTERN, $token );

        return [
            'is_valid'           => $has_correct_length && $matches_pattern,
            'length'             => $length,
            'expected_length'    => self::EXPECTED_TOKEN_LENGTH,
            'has_correct_length' => $has_correct_length,
            'matches_pattern'    => $matches_pattern,
            'reason'             => $has_correct_length && $matches_pattern ? 'valid' : 'invalid_format'
        ];
    }

}
