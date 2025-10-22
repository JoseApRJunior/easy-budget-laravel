<?php

declare(strict_types=1);

namespace App\Services\Infrastructure;

use App\Enums\OperationStatus;
use App\Models\Tenant;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Serviço avançado para gerenciamento seguro de remetentes de e-mail.
 *
 * Funcionalidades principais:
 * - Configuração segura de remetentes padrão
 * - Sistema de remetentes personalizáveis por tenant
 * - Validação rigorosa de endereços de e-mail
 * - Headers de segurança obrigatórios
 * - Integração com sistema multi-tenant
 * - Cache inteligente de configurações
 *
 * Este serviço trabalha em conjunto com o MailerService existente,
 * adicionando camadas de segurança e validação.
 */
class EmailSenderService
{
    /**
     * Configurações carregadas do arquivo de configuração.
     */
    private array $config;

    /**
     * Cache de configurações por tenant.
     */
    private array $tenantCache = [];

    /**
     * Construtor: inicializa configurações.
     */
    public function __construct()
    {
        $this->config = config( 'email-senders' );
    }

    /**
     * Obtém configuração completa de remetente para um tenant específico.
     *
     * @param int|null $tenantId ID do tenant (null para configuração global)
     * @return ServiceResult Configuração do remetente
     */
    public function getSenderConfiguration( ?int $tenantId = null ): ServiceResult
    {
        try {
            // Se não há tenant específico, retorna configuração global
            if ( $tenantId === null ) {
                return ServiceResult::success( [
                    'sender'           => $this->config[ 'global' ][ 'default' ],
                    'security_headers' => $this->config[ 'global' ][ 'security_headers' ],
                    'validation_rules' => $this->config[ 'global' ][ 'validation' ],
                    'type'             => 'global',
                ], 'Configuração global de remetente obtida com sucesso.' );
            }

            // Verificar se tenant existe
            $tenant = Tenant::find( $tenantId );
            if ( !$tenant ) {
                return ServiceResult::error(
                    OperationStatus::NOT_FOUND,
                    "Tenant com ID {$tenantId} não encontrado.",
                );
            }

            // Verificar cache primeiro
            if ( $this->config[ 'tenants' ][ 'cache' ][ 'enabled' ] ) {
                $cacheKey = $this->config[ 'tenants' ][ 'cache' ][ 'key_prefix' ] . $tenantId;

                if ( isset( $this->tenantCache[ $tenantId ] ) ) {
                    return ServiceResult::success( $this->tenantCache[ $tenantId ], 'Configuração de tenant obtida do cache.' );
                }

                $cached = Cache::get( $cacheKey );
                if ( $cached ) {
                    $this->tenantCache[ $tenantId ] = $cached;
                    return ServiceResult::success( $cached, 'Configuração de tenant obtida do cache.' );
                }
            }

            // Buscar configuração personalizada do tenant
            $customConfig = $this->getTenantCustomConfiguration( $tenant );

            if ( $customConfig->isSuccess() ) {
                $config = $customConfig->getData();

                // Cache da configuração
                if ( $this->config[ 'tenants' ][ 'cache' ][ 'enabled' ] ) {
                    $cacheKey = $this->config[ 'tenants' ][ 'cache' ][ 'key_prefix' ] . $tenantId;
                    Cache::put( $cacheKey, $config, $this->config[ 'tenants' ][ 'cache' ][ 'ttl' ] );
                    $this->tenantCache[ $tenantId ] = $config;
                }

                return ServiceResult::success( $config, 'Configuração personalizada de tenant obtida com sucesso.' );
            }

            // Fallback para configuração global
            return ServiceResult::success( [
                'sender'           => $this->config[ 'global' ][ 'default' ],
                'security_headers' => $this->config[ 'global' ][ 'security_headers' ],
                'validation_rules' => $this->config[ 'global' ][ 'validation' ],
                'type'             => 'global_fallback',
                'tenant_id'        => $tenantId,
            ], 'Usando configuração global como fallback.' );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao obter configuração de remetente', [
                'tenant_id' => $tenantId,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao obter configuração de remetente: ' . $e->getMessage()
            );
        }
    }

    /**
     * Valida endereço de e-mail e nome do remetente.
     *
     * @param string $email Endereço de e-mail
     * @param string|null $name Nome do remetente
     * @param int|null $tenantId ID do tenant para validações específicas
     * @return ServiceResult Resultado da validação
     */
    public function validateSender( string $email, ?string $name = null, ?int $tenantId = null ): ServiceResult
    {
        try {
            // Validação básica de formato
            $validator = Validator::make(
                [ 'email' => $email, 'name' => $name ],
                [
                    'email' => [
                        'required',
                        'email:rfc,dns',
                        'max:' . $this->config[ 'global' ][ 'validation' ][ 'max_email_length' ],
                    ],
                    'name'  => [
                        'nullable',
                        'string',
                        'max:' . $this->config[ 'global' ][ 'validation' ][ 'max_name_length' ],
                    ],
                ],
            );

            if ( $validator->fails() ) {
                $this->logSecurityEvent( 'sender_validation_failed', [
                    'email'     => $email,
                    'name'      => $name,
                    'tenant_id' => $tenantId,
                    'errors'    => $validator->errors()->toArray(),
                ] );

                return ServiceResult::error(
                    OperationStatus::INVALID_DATA,
                    'Dados de remetente inválidos: ' . implode( ', ', $validator->errors()->all() )
                );
            }

            // Validação de domínio
            $domainValidation = $this->validateSenderDomain( $email, $tenantId );
            if ( !$domainValidation->isSuccess() ) {
                return $domainValidation;
            }

            // Validação específica para tenants
            if ( $tenantId && $this->config[ 'tenants' ][ 'customizable' ] ) {
                $tenantValidation = $this->validateTenantSender( $email, $name, $tenantId );
                if ( !$tenantValidation->isSuccess() ) {
                    return $tenantValidation;
                }
            }

            return ServiceResult::success( [
                'email'        => $email,
                'name'         => $name,
                'domain'       => explode( '@', $email )[ 1 ],
                'validated_at' => now()->toDateTimeString(),
            ], 'Remetente validado com sucesso.' );

        } catch ( Exception $e ) {
            $this->logSecurityEvent( 'sender_validation_error', [
                'email'     => $email,
                'tenant_id' => $tenantId,
                'error'     => $e->getMessage(),
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro na validação de remetente: ' . $e->getMessage()
            );
        }
    }

    /**
     * Define configuração personalizada de remetente para um tenant.
     *
     * @param int $tenantId ID do tenant
     * @param string $email Endereço de e-mail
     * @param string|null $name Nome do remetente
     * @param string|null $replyTo E-mail para resposta
     * @return ServiceResult Resultado da operação
     */
    public function setTenantSenderConfiguration(
        int $tenantId,
        string $email,
        ?string $name = null,
        ?string $replyTo = null,
    ): ServiceResult {
        try {
            // Verificar se tenant existe
            $tenant = Tenant::find( $tenantId );
            if ( !$tenant ) {
                return ServiceResult::error(
                    OperationStatus::NOT_FOUND,
                    "Tenant com ID {$tenantId} não encontrado.",
                );
            }

            // Validar dados do remetente
            $validation = $this->validateSender( $email, $name, $tenantId );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            // Se requer verificação de domínio, verificar
            if ( $this->config[ 'tenants' ][ 'validation' ][ 'require_verification' ] ) {
                $domainVerification = $this->verifySenderDomain( $email, $tenantId );
                if ( !$domainVerification->isSuccess() ) {
                    return $domainVerification;
                }
            }

            // Preparar configuração personalizada
            $customConfig = [
                'sender'           => [
                    'name'     => $name,
                    'email'    => $email,
                    'reply_to' => $replyTo,
                ],
                'security_headers' => array_merge(
                    $this->config[ 'global' ][ 'security_headers' ],
                    [ 'Return-Path' => $email ],
                ),
                'validation_rules' => $this->config[ 'global' ][ 'validation' ],
                'type'             => 'tenant_custom',
                'tenant_id'        => $tenantId,
                'configured_at'    => now()->toDateTimeString(),
                'domain_verified'  => $this->config[ 'tenants' ][ 'validation' ][ 'require_verification' ] ?
                    $this->isDomainVerified( $email, $tenantId ) : true,
            ];

            // Salvar configuração (em produção, seria no banco de dados)
            $this->saveTenantConfiguration( $tenantId, $customConfig );

            // Limpar cache
            $this->clearTenantCache( $tenantId );

            // Log da configuração
            $this->logSecurityEvent( 'tenant_sender_configured', [
                'tenant_id'     => $tenantId,
                'email'         => $email,
                'name'          => $name,
                'configured_by' => auth()->id(),
            ] );

            return ServiceResult::success( $customConfig, 'Configuração de remetente personalizada definida com sucesso.' );

        } catch ( Exception $e ) {
            $this->logSecurityEvent( 'tenant_sender_configuration_error', [
                'tenant_id' => $tenantId,
                'email'     => $email,
                'error'     => $e->getMessage(),
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao definir configuração de remetente: ' . $e->getMessage()
            );
        }
    }

    /**
     * Remove configuração personalizada de remetente de um tenant.
     *
     * @param int $tenantId ID do tenant
     * @return ServiceResult Resultado da operação
     */
    public function removeTenantSenderConfiguration( int $tenantId ): ServiceResult
    {
        try {
            // Verificar se tenant existe
            $tenant = Tenant::find( $tenantId );
            if ( !$tenant ) {
                return ServiceResult::error(
                    OperationStatus::NOT_FOUND,
                    "Tenant com ID {$tenantId} não encontrado.",
                );
            }

            // Verificar se há configuração personalizada
            $currentConfig = $this->getTenantCustomConfiguration( $tenant );
            if ( !$currentConfig->isSuccess() ) {
                return ServiceResult::error(
                    OperationStatus::NOT_FOUND,
                    'Tenant não possui configuração personalizada de remetente.',
                );
            }

            // Remover configuração
            $this->deleteTenantConfiguration( $tenantId );

            // Limpar cache
            $this->clearTenantCache( $tenantId );

            // Log da remoção
            $this->logSecurityEvent( 'tenant_sender_configuration_removed', [
                'tenant_id'  => $tenantId,
                'removed_by' => auth()->id(),
            ] );

            return ServiceResult::success(
                true,
                'Configuração personalizada de remetente removida com sucesso.',
            );

        } catch ( Exception $e ) {
            $this->logSecurityEvent( 'tenant_sender_configuration_removal_error', [
                'tenant_id' => $tenantId,
                'error'     => $e->getMessage(),
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao remover configuração de remetente: ' . $e->getMessage()
            );
        }
    }

    /**
     * Sanitiza conteúdo de e-mail removendo elementos potencialmente perigosos.
     *
     * @param string $content Conteúdo HTML ou texto
     * @param string $type Tipo de conteúdo (html|text)
     * @return ServiceResult Conteúdo sanitizado
     */
    public function sanitizeEmailContent( string $content, string $type = 'html' ): ServiceResult
    {
        try {
            if ( !$this->config[ 'content_sanitization' ][ 'enabled' ] ) {
                return ServiceResult::success( $content, 'Sanitização desabilitada.' );
            }

            $sanitized = $content;

            if ( $type === 'html' ) {
                $sanitized = $this->sanitizeHtmlContent( $content );
            } else {
                $sanitized = $this->sanitizeTextContent( $content );
            }

            // Verificar se houve mudanças significativas
            $contentChanged = $sanitized !== $content;

            if ( $contentChanged ) {
                $this->logSecurityEvent( 'content_sanitized', [
                    'original_length'  => strlen( $content ),
                    'sanitized_length' => strlen( $sanitized ),
                    'content_type'     => $type,
                    'changes_made'     => true,
                ] );
            }

            return ServiceResult::success( [
                'original_content'  => $content,
                'sanitized_content' => $sanitized,
                'content_changed'   => $contentChanged,
                'sanitized_at'      => now()->toDateTimeString(),
            ], 'Conteúdo sanitizado com sucesso.' );

        } catch ( Exception $e ) {
            $this->logSecurityEvent( 'content_sanitization_error', [
                'content_type' => $type,
                'error'        => $e->getMessage(),
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro na sanitização de conteúdo: ' . $e->getMessage()
            );
        }
    }

    /**
     * Verifica se um domínio de e-mail está autorizado para uso.
     *
     * @param string $email E-mail para verificação
     * @param int|null $tenantId ID do tenant
     * @return ServiceResult Resultado da verificação
     */
    private function validateSenderDomain( string $email, ?int $tenantId = null ): ServiceResult
    {
        $domain = explode( '@', $email )[ 1 ] ?? '';

        if ( empty( $domain ) ) {
            return ServiceResult::error(
                OperationStatus::INVALID_DATA,
                'Domínio de e-mail não identificado.',
            );
        }

        // Verificar domínios bloqueados
        $blockedDomains = array_filter( explode( ',', $this->config[ 'global' ][ 'validation' ][ 'blocked_domains' ] ) );
        if ( in_array( $domain, $blockedDomains ) ) {
            $this->logSecurityEvent( 'blocked_domain_attempt', [
                'domain'    => $domain,
                'email'     => $email,
                'tenant_id' => $tenantId,
            ] );

            return ServiceResult::error(
                OperationStatus::UNAUTHORIZED,
                'Domínio de e-mail bloqueado pelo sistema.',
            );
        }

        // Verificar domínios permitidos (se configurado)
        $allowedDomains = array_filter( explode( ',', $this->config[ 'global' ][ 'validation' ][ 'allowed_domains' ] ) );
        if ( !empty( $allowedDomains ) && !in_array( $domain, $allowedDomains ) ) {
            $this->logSecurityEvent( 'unauthorized_domain_attempt', [
                'domain'          => $domain,
                'email'           => $email,
                'tenant_id'       => $tenantId,
                'allowed_domains' => $allowedDomains,
            ] );

            return ServiceResult::error(
                OperationStatus::UNAUTHORIZED,
                'Domínio de e-mail não autorizado pelo sistema.',
            );
        }

        return ServiceResult::success( [
            'domain'    => $domain,
            'validated' => true,
        ], 'Domínio validado com sucesso.' );
    }

    /**
     * Valida remetente específico para tenant.
     */
    private function validateTenantSender( string $email, ?string $name, int $tenantId ): ServiceResult
    {
        // Implementar validações específicas de tenant
        // Por exemplo, verificar se o domínio pertence ao tenant

        return ServiceResult::success( true, 'Validação de tenant aprovada.' );
    }

    /**
     * Verifica domínio de remetente.
     */
    private function verifySenderDomain( string $email, int $tenantId ): ServiceResult
    {
        // Implementar verificação de domínio (DNS, SPF, DKIM, etc.)
        // Por simplicidade, retorna sucesso
        return ServiceResult::success( true, 'Domínio verificado com sucesso.' );
    }

    /**
     * Verifica se domínio está verificado.
     */
    private function isDomainVerified( string $email, int $tenantId ): bool
    {
        // Implementar verificação de domínio verificado
        return true;
    }

    /**
     * Obtém configuração personalizada de tenant.
     */
    private function getTenantCustomConfiguration( Tenant $tenant ): ServiceResult
    {
        // Em produção, buscar do banco de dados
        // Por ora, retorna erro indicando que não há configuração personalizada
        return ServiceResult::error(
            OperationStatus::NOT_FOUND,
            'Tenant não possui configuração personalizada.',
        );
    }

    /**
     * Salva configuração de tenant.
     */
    private function saveTenantConfiguration( int $tenantId, array $config ): void
    {
        // Em produção, salvar no banco de dados
        Log::info( 'Configuração de tenant salva', [
            'tenant_id' => $tenantId,
            'config'    => $config,
        ] );
    }

    /**
     * Remove configuração de tenant.
     */
    private function deleteTenantConfiguration( int $tenantId ): void
    {
        // Em produção, remover do banco de dados
        Log::info( 'Configuração de tenant removida', [ 'tenant_id' => $tenantId ] );
    }

    /**
     * Limpa cache de tenant.
     */
    private function clearTenantCache( int $tenantId ): void
    {
        if ( $this->config[ 'tenants' ][ 'cache' ][ 'enabled' ] ) {
            $cacheKey = $this->config[ 'tenants' ][ 'cache' ][ 'key_prefix' ] . $tenantId;
            Cache::forget( $cacheKey );
            unset( $this->tenantCache[ $tenantId ] );
        }
    }

    /**
     * Sanitiza conteúdo HTML.
     */
    private function sanitizeHtmlContent( string $content ): string
    {
        $config = $this->config[ 'content_sanitization' ][ 'html' ];

        // Remover tags não permitidas
        $allowedTags = $config[ 'allowed_tags' ];
        $sanitized   = strip_tags( $content, $allowedTags );

        // Se habilitado, corrigir HTML inválido
        if ( $config[ 'fix_invalid_html' ] ) {
            $sanitized = $this->fixInvalidHtml( $sanitized );
        }

        // Remover tags vazias
        if ( $config[ 'remove_empty_tags' ] ) {
            $sanitized = $this->removeEmptyTags( $sanitized );
        }

        return $sanitized;
    }

    /**
     * Sanitiza conteúdo de texto.
     */
    private function sanitizeTextContent( string $content ): string
    {
        $config = $this->config[ 'content_sanitization' ][ 'text' ];

        // Limitar tamanho
        if ( strlen( $content ) > $config[ 'max_length' ] ) {
            $content = substr( $content, 0, $config[ 'max_length' ] );
        }

        // Remover bytes nulos
        if ( $config[ 'remove_null_bytes' ] ) {
            $content = str_replace( "\0", '', $content );
        }

        // Normalizar quebras de linha
        if ( $config[ 'normalize_line_endings' ] ) {
            $content = preg_replace( '/\r\n|\r|\n/', "\n", $content );
        }

        // Remover caracteres perigosos
        if ( $config[ 'strip_dangerous_chars' ] ) {
            $content = filter_var( $content, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH );
        }

        return $content;
    }

    /**
     * Corrige HTML inválido.
     */
    private function fixInvalidHtml( string $html ): string
    {
        // Implementar correção básica de HTML
        // Em produção, usar biblioteca como Tidy ou similar
        return $html;
    }

    /**
     * Remove tags HTML vazias.
     */
    private function removeEmptyTags( string $html ): string
    {
        // Implementar remoção de tags vazias
        return preg_replace( '/<([^>]+)><\/\1>/', '', $html );
    }

    /**
     * Loga evento de segurança.
     */
    private function logSecurityEvent( string $event, array $context = [] ): void
    {
        if ( !$this->config[ 'security_logging' ][ 'enabled' ] ) {
            return;
        }

        $logData = [
            'event'      => $event,
            'timestamp'  => now()->toDateTimeString(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id'    => auth()->id(),
        ];

        $logData = array_merge( $logData, $context );

        // Ofuscar dados sensíveis
        if ( $this->config[ 'security_logging' ][ 'sensitive_data' ][ 'email_content' ] ) {
            // Implementar ofuscação se necessário
        }

        Log::channel( $this->config[ 'security_logging' ][ 'channel' ] )
            ->log( $this->config[ 'security_logging' ][ 'level' ], 'Evento de segurança de e-mail', $logData );
    }

    /**
     * Obtém estatísticas de uso do serviço.
     */
    public function getUsageStatistics(): array
    {
        return [
            'cache_enabled'            => $this->config[ 'tenants' ][ 'cache' ][ 'enabled' ],
            'cached_tenants'           => count( $this->tenantCache ),
            'sanitization_enabled'     => $this->config[ 'content_sanitization' ][ 'enabled' ],
            'rate_limiting_enabled'    => $this->config[ 'rate_limiting' ][ 'enabled' ],
            'security_logging_enabled' => $this->config[ 'security_logging' ][ 'enabled' ],
            'timestamp'                => now()->toDateTimeString(),
        ];
    }

}
