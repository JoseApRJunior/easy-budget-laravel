<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SettingsService;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Controlador para gerenciamento de configurações da aplicação.
 * Implementa configurações tenant-aware: informações da empresa, preferências, integrações, notificações.
 * Migração do sistema legacy app/controllers/SettingsController.php.
 *
 * @package App\Http\Controllers
 * @author IA
 */
class SettingsController extends BaseController
{
    /**
     * @var SettingsService
     */
    protected SettingsService $settingsService;

    /**
     * @var TenantService
     */
    protected TenantService $tenantService;

    /**
     * Construtor da classe SettingsController.
     *
     * @param SettingsService $settingsService
     * @param TenantService $tenantService
     */
    public function __construct(
        SettingsService $settingsService,
        TenantService $tenantService,
    ) {
        parent::__construct();
        $this->settingsService = $settingsService;
        $this->tenantService   = $tenantService;
    }

    /**
     * Exibe a página principal de configurações.
     *
     * @return View
     */
    public function index(): View
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $this->logActivity(
            action: 'view_settings',
            entity: 'settings',
            metadata: [ 'tenant_id' => $tenantId ],
        );

        $settings = $this->settingsService->getAllSettings( $tenantId );
        $tenant   = $this->tenantService->getTenantById( $tenantId );

        return $this->renderView( 'settings.index', [
            'settings'      => $settings,
            'tenant'        => $tenant,
            'tenantId'      => $tenantId,
            'settingGroups' => $this->getSettingGroups()
        ] );
    }

    /**
     * Exibe configurações de informações da empresa.
     *
     * @return View
     */
    public function company(): View
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $this->logActivity(
            action: 'view_company_settings',
            entity: 'settings',
            metadata: [ 'tenant_id' => $tenantId ],
        );

        $companySettings = $this->settingsService->getCompanySettings( $tenantId );
        $tenant          = $this->tenantService->getTenantById( $tenantId );

        return $this->renderView( 'settings.company', [
            'companySettings' => $companySettings,
            'tenant'          => $tenant,
            'tenantId'        => $tenantId,
            'states'          => $this->getBrazilianStates()
        ] );
    }

    /**
     * Atualiza configurações de informações da empresa.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateCompany( Request $request ): RedirectResponse
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $request->validate( [
            'company_name'         => 'required|string|max:255',
            'company_cnpj'         => 'nullable|string|size:18|regex:/^\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}$/',
            'company_email'        => 'required|email|max:255',
            'company_phone'        => 'nullable|string|max:15|regex:/^\(\d{2}\)\s?\d{4,5}-\d{4}$/',
            'company_website'      => 'nullable|url|max:255',
            'address_street'       => 'nullable|string|max:255',
            'address_number'       => 'nullable|string|max:10',
            'address_complement'   => 'nullable|string|max:100',
            'address_neighborhood' => 'nullable|string|max:100',
            'address_city'         => 'nullable|string|max:100',
            'address_state'        => 'nullable|size:2|in:AC,AL,AP,AM,BA,CE,DF,ES,GO,MA,MT,MS,MG,PA,PB,PR,PE,PI,RJ,RN,RS,RO,RR,SC,SP,SE,TO',
            'address_cep'          => 'nullable|string|size:9|regex:/^[0-9]{5}-[0-9]{3}$/',
            'logo'                 => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'fiscal_number'        => 'nullable|string|max:50',
            'tax_regime'           => 'nullable|in:simples_nacional,lucro_presumido,lucro_real',
            'accounting_email'     => 'nullable|email|max:255'
        ] );

        try {
            $companyData              = $request->only( [
                'company_name',
                'company_cnpj',
                'company_email',
                'company_phone',
                'company_website',
                'address_street',
                'address_number',
                'address_complement',
                'address_neighborhood',
                'address_city',
                'address_state',
                'address_cep',
                'fiscal_number',
                'tax_regime',
                'accounting_email'
            ] );
            $companyData[ 'tenant_id' ] = $tenantId;

            // Upload de logo
            if ( $request->hasFile( 'logo' ) ) {
                // Deletar logo anterior
                $currentSettings = $this->settingsService->getCompanySettings( $tenantId );
                if ( $currentSettings->logo && Storage::disk( 'public' )->exists( $currentSettings->logo ) ) {
                    Storage::disk( 'public' )->delete( $currentSettings->logo );
                }

                $logoPath            = $request->file( 'logo' )->store( 'company_logos', 'public' );
                $companyData[ 'logo' ] = $logoPath;
            }

            $result = $this->settingsService->updateCompanySettings( $tenantId, $companyData );

            if ( $result->isSuccess() ) {
                $this->logActivity(
                    action: 'update_company_settings',
                    entity: 'settings',
                    metadata: [ 'tenant_id' => $tenantId ],
                );

                return $this->successRedirect(
                    message: 'Configurações da empresa atualizadas com sucesso.',
                    route: 'settings.company',
                );
            }

            return $this->errorRedirect( $result->getError() ?? 'Erro ao atualizar configurações da empresa.' );

        } catch ( \Exception $e ) {
            return $this->errorRedirect( 'Erro interno ao atualizar configurações: ' . $e->getMessage() );
        }
    }

    /**
     * Exibe configurações de preferências da aplicação.
     *
     * @return View
     */
    public function preferences(): View
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $this->logActivity(
            action: 'view_preferences_settings',
            entity: 'settings',
            metadata: [ 'tenant_id' => $tenantId ],
        );

        $preferences = $this->settingsService->getPreferencesSettings( $tenantId );

        return $this->renderView( 'settings.preferences', [
            'preferences' => $preferences,
            'tenantId'    => $tenantId,
            'timezones'   => timezone_identifiers_list(),
            'languages'   => [ 'pt-BR' => 'Português (Brasil)', 'en' => 'English' ],
            'currencies'  => [ 'BRL' => 'Real Brasileiro (R$)', 'USD' => 'Dólar Americano (US$)' ]
        ] );
    }

    /**
     * Atualiza preferências da aplicação.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function updatePreferences( Request $request ): RedirectResponse
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $request->validate( [
            'default_timezone'      => 'required|timezone',
            'default_language'      => 'required|in:pt-BR,en',
            'default_currency'      => 'required|in:BRL,USD',
            'date_format'           => 'required|in:d/m/Y,m/d/Y,Y-m-d',
            'decimal_separator'     => 'required|in:.,,',
            'thousands_separator'   => 'required|string|max:1',
            'theme'                 => 'required|in:light,dark,auto',
            'dashboard_layout'      => 'required|in:default,compact,extended',
            'notifications_enabled' => 'boolean',
            'email_notifications'   => 'boolean',
            'sms_notifications'     => 'boolean',
            'auto_backup_enabled'   => 'boolean',
            'backup_retention_days' => 'nullable|integer|min:1|max:365'
        ] );

        try {
            $preferencesData              = $request->only( [
                'default_timezone',
                'default_language',
                'default_currency',
                'date_format',
                'decimal_separator',
                'thousands_separator',
                'theme',
                'dashboard_layout',
                'notifications_enabled',
                'email_notifications',
                'sms_notifications',
                'auto_backup_enabled',
                'backup_retention_days'
            ] );
            $preferencesData[ 'tenant_id' ] = $tenantId;

            $result = $this->settingsService->updatePreferencesSettings( $tenantId, $preferencesData );

            if ( $result->isSuccess() ) {
                $this->logActivity(
                    action: 'update_preferences_settings',
                    entity: 'settings',
                    metadata: [ 'tenant_id' => $tenantId ],
                );

                return $this->successRedirect(
                    message: 'Preferências atualizadas com sucesso.',
                    route: 'settings.preferences',
                );
            }

            return $this->errorRedirect( $result->getError() ?? 'Erro ao atualizar preferências.' );

        } catch ( \Exception $e ) {
            return $this->errorRedirect( 'Erro interno ao atualizar preferências: ' . $e->getMessage() );
        }
    }

    /**
     * Exibe configurações de integrações.
     *
     * @return View
     */
    public function integrations(): View
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $this->logActivity(
            action: 'view_integrations_settings',
            entity: 'settings',
            metadata: [ 'tenant_id' => $tenantId ],
        );

        $integrations = $this->settingsService->getIntegrationsSettings( $tenantId );

        return $this->renderView( 'settings.integrations', [
            'integrations'          => $integrations,
            'tenantId'              => $tenantId,
            'availableIntegrations' => $this->getAvailableIntegrations()
        ] );
    }

    /**
     * Atualiza configurações de integrações.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateIntegrations( Request $request ): RedirectResponse
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $request->validate( [
            'mercado_pago.access_token' => 'nullable|string|max:255',
            'mercado_pago.public_key'   => 'nullable|string|max:255',
            'email_smtp.host'           => 'nullable|string|max:255',
            'email_smtp.port'           => 'nullable|integer|min:1|max:65535',
            'email_smtp.username'       => 'nullable|string|max:255',
            'email_smtp.password'       => 'nullable|string|max:255',
            'email_smtp.encryption'     => 'nullable|in:tls,ssl',
            'email_smtp.from_name'      => 'nullable|string|max:255',
            'email_smtp.from_address'   => 'nullable|email|max:255',
            'sms_api.enabled'           => 'boolean',
            'sms_api.provider'          => 'nullable|in:twilio,nexmo,generic',
            'sms_api.account_sid'       => 'nullable|string|max:255',
            'sms_api.auth_token'        => 'nullable|string|max:255',
            'sms_api.from_number'       => 'nullable|string|max:20',
            'backup.storage'            => 'nullable|in:local,s3,database',
            'backup.s3.bucket'          => 'nullable|string|max:255',
            'backup.s3.key'             => 'nullable|string|max:255',
            'backup.s3.secret'          => 'nullable|string|max:255',
            'backup.s3.region'          => 'nullable|string|max:50',
            'api_integrations.enabled'  => 'boolean',
            'webhook.enabled'           => 'boolean',
            'webhook.url'               => 'nullable|url|max:500'
        ] );

        try {
            $integrationsData              = $request->only( [
                'mercado_pago',
                'email_smtp',
                'sms_api',
                'backup',
                'api_integrations',
                'webhook'
            ] );
            $integrationsData[ 'tenant_id' ] = $tenantId;

            // Criptografar senhas sensíveis
            if ( isset( $integrationsData[ 'email_smtp' ][ 'password' ] ) && $integrationsData[ 'email_smtp' ][ 'password' ] ) {
                $integrationsData[ 'email_smtp' ][ 'password' ] = encrypt( $integrationsData[ 'email_smtp' ][ 'password' ] );
            }

            if ( isset( $integrationsData[ 'sms_api' ][ 'auth_token' ] ) && $integrationsData[ 'sms_api' ][ 'auth_token' ] ) {
                $integrationsData[ 'sms_api' ][ 'auth_token' ] = encrypt( $integrationsData[ 'sms_api' ][ 'auth_token' ] );
            }

            if ( isset( $integrationsData[ 'backup' ][ 's3' ][ 'secret' ] ) && $integrationsData[ 'backup' ][ 's3' ][ 'secret' ] ) {
                $integrationsData[ 'backup' ][ 's3' ][ 'secret' ] = encrypt( $integrationsData[ 'backup' ][ 's3' ][ 'secret' ] );
            }

            $result = $this->settingsService->updateIntegrationsSettings( $tenantId, $integrationsData );

            if ( $result->isSuccess() ) {
                $this->logActivity(
                    action: 'update_integrations_settings',
                    entity: 'settings',
                    metadata: [ 'tenant_id' => $tenantId ],
                );

                // Testar conexões se solicitado
                if ( $request->boolean( 'test_connections' ) ) {
                    $testResults = $this->settingsService->testIntegrations( $tenantId );
                    if ( $testResults[ 'errors' ] ) {
                        return $this->errorRedirect( 'Algumas integrações falharam no teste. Verifique as configurações.' );
                    }
                }

                return $this->successRedirect(
                    message: 'Integrações atualizadas com sucesso.',
                    route: 'settings.integrations',
                );
            }

            return $this->errorRedirect( $result->getError() ?? 'Erro ao atualizar configurações de integrações.' );

        } catch ( \Exception $e ) {
            return $this->errorRedirect( 'Erro interno ao atualizar integrações: ' . $e->getMessage() );
        }
    }

    /**
     * Exibe configurações de notificações.
     *
     * @return View
     */
    public function notifications(): View
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $this->logActivity(
            action: 'view_notifications_settings',
            entity: 'settings',
            metadata: [ 'tenant_id' => $tenantId ],
        );

        $notificationSettings  = $this->settingsService->getNotificationSettings( $tenantId );
        $notificationTemplates = $this->settingsService->getNotificationTemplates( $tenantId );

        return $this->renderView( 'settings.notifications', [
            'notificationSettings'  => $notificationSettings,
            'templates'             => $notificationTemplates,
            'tenantId'              => $tenantId,
            'notificationProviders' => [ 'email' => 'Email', 'sms' => 'SMS', 'push' => 'Push Notifications' ]
        ] );
    }

    /**
     * Atualiza configurações de notificações.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateNotifications( Request $request ): RedirectResponse
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $request->validate( [
            'notification_email'                => 'nullable|email|max:255',
            'notification_phone'                => 'nullable|string|max:15|regex:/^\(\d{2}\)\s?\d{4,5}-\d{4}$/',
            'notification_level'                => 'required|in:low,medium,high',
            'daily_limit'                       => 'nullable|integer|min:1|max:1000',
            'email_notifications'               => 'required|boolean',
            'sms_notifications'                 => 'required|boolean',
            'push_notifications'                => 'required|boolean',
            'template_budget_approved.subject'  => 'nullable|string|max:255',
            'template_budget_approved.body'     => 'nullable|string|max:5000',
            'template_invoice_sent.subject'     => 'nullable|string|max:255',
            'template_invoice_sent.body'        => 'nullable|string|max:5000',
            'template_payment_received.subject' => 'nullable|string|max:255',
            'template_payment_received.body'    => 'nullable|string|max:5000',
            'template_account_locked.subject'   => 'nullable|string|max:255',
            'template_account_locked.body'      => 'nullable|string|max:5000'
        ] );

        try {
            $notificationData              = $request->only( [
                'notification_email',
                'notification_phone',
                'notification_level',
                'daily_limit',
                'email_notifications',
                'sms_notifications',
                'push_notifications',
                'template_budget_approved',
                'template_invoice_sent',
                'template_payment_received',
                'template_account_locked'
            ] );
            $notificationData[ 'tenant_id' ] = $tenantId;

            $result = $this->settingsService->updateNotificationSettings( $tenantId, $notificationData );

            if ( $result->isSuccess() ) {
                $this->logActivity(
                    action: 'update_notification_settings',
                    entity: 'settings',
                    metadata: [ 'tenant_id' => $tenantId ],
                );

                return $this->successRedirect(
                    message: 'Configurações de notificações atualizadas com sucesso.',
                    route: 'settings.notifications',
                );
            }

            return $this->errorRedirect( $result->getError() ?? 'Erro ao atualizar configurações de notificações.' );

        } catch ( \Exception $e ) {
            return $this->errorRedirect( 'Erro interno ao atualizar notificações: ' . $e->getMessage() );
        }
    }

    /**
     * Testa configurações de email.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function testEmail( Request $request ): JsonResponse
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->jsonError( 'Tenant não encontrado.', statusCode: 403 );
        }

        $request->validate( [
            'test_email' => 'required|email|max:255',
            'test_name'  => 'nullable|string|max:255'
        ] );

        $testResult = $this->settingsService->testEmailConfiguration(
            tenantId: $tenantId,
            testEmail: $request->test_email,
            testName: $request->test_name ?? 'Teste do Sistema'
        );

        if ( $testResult[ 'success' ] ) {
            $this->logActivity(
                action: 'test_email_config',
                entity: 'settings',
                metadata: [ 'tenant_id' => $tenantId, 'success' => true ],
            );

            return $this->jsonSuccess(
                message: 'Email de teste enviado com sucesso.',
                data: [ 'email_sent' => true ],
            );
        }

        $this->logActivity(
            action: 'test_email_config',
            entity: 'settings',
            metadata: [ 'tenant_id' => $tenantId, 'success' => false, 'error' => $testResult[ 'error' ] ],
        );

        return $this->jsonError(
            message: $testResult[ 'error' ] ?? 'Erro ao enviar email de teste.',
            statusCode: 422,
        );
    }

    /**
     * Obtém grupos de configurações disponíveis.
     *
     * @return array
     */
    private function getSettingGroups(): array
    {
        return [
            'general'       => 'Configurações Gerais',
            'company'       => 'Informações da Empresa',
            'preferences'   => 'Preferências',
            'notifications' => 'Notificações',
            'integrations'  => 'Integrações',
            'security'      => 'Segurança',
            'backup'        => 'Backup',
            'advanced'      => 'Avançado'
        ];
    }

    /**
     * Obtém integrações disponíveis.
     *
     * @return array
     */
    private function getAvailableIntegrations(): array
    {
        return [
            'mercado_pago' => [
                'name'        => 'Mercado Pago',
                'description' => 'Processamento de pagamentos via Mercado Pago',
                'enabled'     => true
            ],
            'email_smtp'   => [
                'name'        => 'Email SMTP',
                'description' => 'Configuração de servidor SMTP para envio de emails',
                'enabled'     => true
            ],
            'sms_api'      => [
                'name'        => 'SMS API',
                'description' => 'Integração com serviços de SMS (Twilio, Nexmo)',
                'enabled'     => true
            ],
            'backup_s3'    => [
                'name'        => 'Amazon S3 Backup',
                'description' => 'Armazenamento de backups em Amazon S3',
                'enabled'     => true
            ],
            'webhook'      => [
                'name'        => 'Webhooks',
                'description' => 'Notificações via webhook para sistemas externos',
                'enabled'     => true
            ]
        ];
    }

    /**
     * Obtém lista de estados brasileiros.
     *
     * @return array
     */
    private function getBrazilianStates(): array
    {
        return [
            'AC' => 'Acre',
            'AL' => 'Alagoas',
            'AP' => 'Amapá',
            'AM' => 'Amazonas',
            'BA' => 'Bahia',
            'CE' => 'Ceará',
            'DF' => 'Distrito Federal',
            'ES' => 'Espírito Santo',
            'GO' => 'Goiás',
            'MA' => 'Maranhão',
            'MT' => 'Mato Grosso',
            'MS' => 'Mato Grosso do Sul',
            'MG' => 'Minas Gerais',
            'PA' => 'Pará',
            'PB' => 'Paraíba',
            'PR' => 'Paraná',
            'PE' => 'Pernambuco',
            'PI' => 'Piauí',
            'RJ' => 'Rio de Janeiro',
            'RN' => 'Rio Grande do Norte',
            'RS' => 'Rio Grande do Sul',
            'RO' => 'Rondônia',
            'RR' => 'Roraima',
            'SC' => 'Santa Catarina',
            'SP' => 'São Paulo',
            'SE' => 'Sergipe',
            'TO' => 'Tocantins'
        ];
    }

}
