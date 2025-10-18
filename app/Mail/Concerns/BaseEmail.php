<?php

declare(strict_types=1);

namespace App\Mail\Concerns;

use App\Models\Tenant;
use App\Models\User;
use App\Services\Infrastructure\ConfirmationLinkService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Classe base abstrata unificada para e-mails do sistema Easy Budget Laravel.
 *
 * Esta classe consolida toda a lógica comum identificada nos e-mails existentes,
 * seguindo as melhores práticas da arquitetura do projeto (ServiceResult, TenantScoped, etc.).
 *
 * Funcionalidades implementadas:
 * - Métodos auxiliares comuns para obtenção de dados do usuário e empresa
 * - Tratamento padronizado de dados multi-tenant
 * - Logging automático de operações críticas
 * - Tratamento robusto de erros com fallbacks seguros
 * - Integração com serviços de infraestrutura existentes
 */
abstract class BaseEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Usuário que receberá o e-mail.
     */
    protected User $user;

    /**
     * Tenant do usuário (opcional, para contexto multi-tenant).
     */
    protected ?Tenant $tenant;

    /**
     * Serviço para construção segura de links de confirmação.
     */
    protected ConfirmationLinkService $confirmationLinkService;

    /**
     * Cria uma nova instância da mailable base.
     */
    public function __construct(
        User $user,
        ?Tenant $tenant = null,
        ?ConfirmationLinkService $confirmationLinkService = null,
    ) {
        $this->user                    = $user;
        $this->tenant                  = $tenant;
        $this->confirmationLinkService = $confirmationLinkService ?? app( ConfirmationLinkService::class);
    }

    /**
     * Obtém o primeiro nome do usuário para personalização.
     *
     * Estratégia de busca baseada na estrutura real do projeto:
     * 1. Busca através do relacionamento User → Provider → CommonData
     * 2. Usa o campo first_name do CommonData
     * 3. Fallback para parte do e-mail se nome não disponível
     *
     * @return string Primeiro nome do usuário ou parte do e-mail como fallback
     */
    protected function getUserFirstName(): string
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
     * Obtém o nome completo do usuário para personalização.
     *
     * @return string Nome completo do usuário
     */
    protected function getUserName(): string
    {
        return $this->getUserFirstName();
    }

    /**
     * Obtém o e-mail do usuário.
     *
     * @return string E-mail do usuário
     */
    protected function getUserEmail(): string
    {
        return $this->user?->email ?? 'usuario@exemplo.com';
    }

    /**
     * Obtém dados da empresa para o template.
     *
     * @return array Dados da empresa
     */
    protected function getCompanyData(): array
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
     * Obtém o e-mail de suporte.
     *
     * Estratégia de busca otimizada:
     * 1. Tentar obter e-mail de suporte do tenant (settings)
     * 2. Tentar obter e-mail de contato do tenant (settings)
     * 3. E-mail padrão de suporte do sistema
     *
     * @return string E-mail de suporte
     */
    protected function getSupportEmail(): string
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
     * Obtém dados básicos do usuário para templates.
     *
     * @return array Dados básicos do usuário
     */
    protected function getUserBasicData(): array
    {
        return [
            'first_name'    => $this->getUserFirstName(),
            'name'          => $this->getUserName(),
            'email'         => $this->getUserEmail(),
            'company_data'  => $this->getCompanyData(),
            'support_email' => $this->getSupportEmail(),
        ];
    }

    /**
     * Log de operações críticas do e-mail.
     *
     * @param string $action Ação realizada
     * @param array $context Contexto adicional
     */
    protected function logEmailOperation( string $action, array $context = [] ): void
    {
        Log::info( "Email operation: {$action}", [
            'email_class' => static::class,
            'user_id'     => $this->user->id,
            'tenant_id'   => $this->tenant?->id,
            'context'     => $context,
        ] );
    }

    /**
     * Tratamento padronizado de erros em operações de e-mail.
     *
     * @param \Throwable $e Exceção capturada
     * @param string $operation Operação que falhou
     * @param array $context Contexto adicional
     */
    protected function handleEmailError( \Throwable $e, string $operation, array $context = [] ): void
    {
        Log::warning( "Email error in {$operation}", [
            'email_class' => static::class,
            'user_id'     => $this->user->id,
            'tenant_id'   => $this->tenant?->id,
            'error'       => $e->getMessage(),
            'context'     => $context,
        ] );
    }

}
