<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\FileUploadService;
use App\Services\SettingsBackupService;
use App\Services\SettingsService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller para gerenciamento de configurações
 */
class SettingsController extends Controller
{
    public function __construct(
        private SettingsService $settingsService,
        private SettingsBackupService $backupService,
        private FileUploadService $fileUploadService,
    ) {}

    /**
     * Exibe página principal de configurações
     */
    public function index( Request $request ): View
    {
        $activeTab = $request->get( 'tab', 'general' );

        // Obtém configurações completas
        $userSettings   = $this->settingsService->getCompleteUserSettings();
        $systemSettings = $this->settingsService->getCompleteSystemSettings();

        // Dados para as abas
        $tabs = [
            'general'       => [
                'label' => 'Geral',
                'icon'  => 'building',
                'data'  => [
                    'system_settings' => $systemSettings,
                ],
            ],
            'profile'       => [
                'label' => 'Perfil',
                'icon'  => 'person',
                'data'  => [
                    'user_settings' => $userSettings,
                ],
            ],
            'security'      => [
                'label' => 'Segurança',
                'icon'  => 'shield-check',
                'data'  => [
                    'user_settings' => $userSettings,
                    'sessions'      => $this->getActiveSessions(),
                ],
            ],
            'notifications' => [
                'label' => 'Notificações',
                'icon'  => 'bell',
                'data'  => [
                    'user_settings' => $userSettings,
                ],
            ],
            'integrations'  => [
                'label' => 'Integrações',
                'icon'  => 'link',
                'data'  => [
                    'user_settings' => $userSettings,
                    'integrations'  => $this->getIntegrations(),
                ],
            ],
            'customization' => [
                'label' => 'Personalização',
                'icon'  => 'palette',
                'data'  => [
                    'user_settings' => $userSettings,
                ],
            ],
        ];

        return view( 'settings.index', [
            'activeTab'      => $activeTab,
            'tabs'           => $tabs,
            'userSettings'   => $userSettings,
            'systemSettings' => $systemSettings,
        ] );
    }

    /**
     * Atualiza configurações gerais
     */
    public function updateGeneral( Request $request ): RedirectResponse
    {
        try {
            $validated = $request->validate( [
                'company_name'         => 'required|string|max:255',
                'contact_email'        => 'required|email:rfc,dns|max:255',
                'phone'                => 'nullable|string|max:20',
                'website'              => 'nullable|url|max:255',
                'currency'             => 'required|in:BRL,USD,EUR',
                'timezone'             => 'required|timezone',
                'language'             => 'required|in:pt-BR,en-US,es-ES',
                'address_street'       => 'nullable|string|max:255',
                'address_number'       => 'nullable|string|max:20',
                'address_complement'   => 'nullable|string|max:100',
                'address_neighborhood' => 'nullable|string|max:100',
                'address_city'         => 'nullable|string|max:100',
                'address_state'        => 'nullable|string|max:50',
                'address_zip_code'     => 'nullable|string|max:10',
                'address_country'      => 'nullable|string|max:50',
            ] );

            $this->settingsService->updateGeneralSettings( $validated );

            return back()->with( 'success', 'Configurações gerais atualizadas com sucesso!' );

        } catch ( Exception $e ) {
            return back()->with( 'error', 'Erro ao atualizar configurações gerais: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza configurações de perfil
     */
    public function updateProfile( Request $request ): RedirectResponse
    {
        try {
            $validated = $request->validate( [
                'full_name'        => 'nullable|string|max:255',
                'bio'              => 'nullable|string|max:1000',
                'phone'            => 'nullable|string|max:20',
                'birth_date'       => 'nullable|date|before:today',
                'social_facebook'  => 'nullable|url|max:255',
                'social_twitter'   => 'nullable|url|max:255',
                'social_linkedin'  => 'nullable|url|max:255',
                'social_instagram' => 'nullable|url|max:255',
            ] );

            $this->settingsService->updateProfileSettings( $validated );

            return back()->with( 'success', 'Perfil atualizado com sucesso!' );

        } catch ( Exception $e ) {
            return back()->with( 'error', 'Erro ao atualizar perfil: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza configurações de segurança
     */
    public function updateSecurity( Request $request ): RedirectResponse
    {
        try {
            $validated = $request->validate( [
                'current_password'          => 'required_with:new_password|string',
                'new_password'              => 'nullable|string|min:8|max:255|confirmed',
                'email_notifications'       => 'boolean',
                'transaction_notifications' => 'boolean',
                'weekly_reports'            => 'boolean',
                'security_alerts'           => 'boolean',
                'newsletter_subscription'   => 'boolean',
                'push_notifications'        => 'boolean',
            ] );

            $this->settingsService->updateSecuritySettings( $validated );

            return back()->with( 'success', 'Configurações de segurança atualizadas com sucesso!' );

        } catch ( Exception $e ) {
            return back()->with( 'error', 'Erro ao atualizar configurações de segurança: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza configurações de notificação
     */
    public function updateNotifications( Request $request ): RedirectResponse
    {
        try {
            $validated = $request->validate( [
                'email_notifications'       => 'boolean',
                'transaction_notifications' => 'boolean',
                'weekly_reports'            => 'boolean',
                'security_alerts'           => 'boolean',
                'newsletter_subscription'   => 'boolean',
                'push_notifications'        => 'boolean',
            ] );

            $this->settingsService->updateNotificationSettings( $validated );

            return back()->with( 'success', 'Configurações de notificação atualizadas com sucesso!' );

        } catch ( Exception $e ) {
            return back()->with( 'error', 'Erro ao atualizar configurações de notificação: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza configurações de integração
     */
    public function updateIntegrations( Request $request ): RedirectResponse
    {
        try {
            $validated = $request->validate( [
                'api_keys'             => 'nullable|array',
                'webhook_urls'         => 'nullable|array',
                'integration_settings' => 'nullable|array',
            ] );

            $this->settingsService->updateIntegrationSettings( $validated );

            return back()->with( 'success', 'Configurações de integração atualizadas com sucesso!' );

        } catch ( Exception $e ) {
            return back()->with( 'error', 'Erro ao atualizar configurações de integração: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza configurações de personalização
     */
    public function updateCustomization( Request $request ): RedirectResponse
    {
        try {
            $validated = $request->validate( [
                'theme'              => 'required|in:light,dark,auto',
                'primary_color'      => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'layout_density'     => 'required|in:compact,normal,spacious',
                'sidebar_position'   => 'required|in:left,right',
                'animations_enabled' => 'boolean',
                'sound_enabled'      => 'boolean',
            ] );

            $this->settingsService->updateCustomizationSettings( $validated );

            return back()->with( 'success', 'Configurações de personalização atualizadas com sucesso!' );

        } catch ( Exception $e ) {
            return back()->with( 'error', 'Erro ao atualizar configurações de personalização: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza avatar do usuário
     */
    public function updateAvatar( Request $request ): RedirectResponse
    {
        try {
            $request->validate( [
                'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            ] );

            $this->settingsService->updateAvatar( $request->file( 'avatar' ) );

            return back()->with( 'success', 'Avatar atualizado com sucesso!' );

        } catch ( Exception $e ) {
            return back()->with( 'error', 'Erro ao atualizar avatar: ' . $e->getMessage() );
        }
    }

    /**
     * Remove avatar do usuário
     */
    public function removeAvatar( Request $request ): RedirectResponse
    {
        try {
            $this->settingsService->removeAvatar();

            return back()->with( 'success', 'Avatar removido com sucesso!' );

        } catch ( Exception $e ) {
            return back()->with( 'error', 'Erro ao remover avatar: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza logo da empresa
     */
    public function updateCompanyLogo( Request $request ): RedirectResponse
    {
        try {
            $request->validate( [
                'logo' => 'required|image|mimes:jpeg,png,jpg,gif,webp,svg+xml|max:2048',
            ] );

            $this->settingsService->updateCompanyLogo( $request->file( 'logo' ) );

            return back()->with( 'success', 'Logo da empresa atualizado com sucesso!' );

        } catch ( Exception $e ) {
            return back()->with( 'error', 'Erro ao atualizar logo: ' . $e->getMessage() );
        }
    }

    /**
     * Cria backup das configurações
     */
    public function createBackup( Request $request ): RedirectResponse
    {
        try {
            $type = $request->get( 'type', 'full' );

            if ( $type === 'full' ) {
                $result = $this->backupService->createFullBackup( auth()->user(), 'manual' );
            } elseif ( $type === 'user' ) {
                $result = $this->backupService->createUserSettingsBackup( auth()->user(), 'manual' );
            } else {
                $result = $this->backupService->createSystemSettingsBackup( auth()->user()->tenant_id, 'manual' );
            }

            return back()->with( 'success', 'Backup criado com sucesso!' );

        } catch ( Exception $e ) {
            return back()->with( 'error', 'Erro ao criar backup: ' . $e->getMessage() );
        }
    }

    /**
     * Lista backups disponíveis
     */
    public function listBackups( Request $request ): View
    {
        $tenantId = auth()->user()->tenant_id;
        $backups  = $this->backupService->listBackups( $tenantId );
        $stats    = $this->backupService->getBackupStats( $tenantId );

        return view( 'settings.backups', [
            'backups' => $backups,
            'stats'   => $stats,
        ] );
    }

    /**
     * Restaura backup
     */
    public function restoreBackup( Request $request ): RedirectResponse
    {
        try {
            $request->validate( [
                'filename' => 'required|string',
                'type'     => 'required|in:user,system,full',
            ] );

            $user = auth()->user();

            if ( $request->type === 'full' ) {
                $this->backupService->restoreFullBackup( $user, $request->filename );
            } elseif ( $request->type === 'user' ) {
                $this->backupService->restoreUserSettingsBackup( $user, $request->filename );
            } else {
                $this->backupService->restoreSystemSettingsBackup( $user->tenant_id, $request->filename );
            }

            return back()->with( 'success', 'Backup restaurado com sucesso!' );

        } catch ( Exception $e ) {
            return back()->with( 'error', 'Erro ao restaurar backup: ' . $e->getMessage() );
        }
    }

    /**
     * Remove backup
     */
    public function deleteBackup( Request $request ): RedirectResponse
    {
        try {
            $request->validate( [
                'filename' => 'required|string',
            ] );

            $this->backupService->deleteBackup( auth()->user()->tenant_id, $request->filename );

            return back()->with( 'success', 'Backup removido com sucesso!' );

        } catch ( Exception $e ) {
            return back()->with( 'error', 'Erro ao remover backup: ' . $e->getMessage() );
        }
    }

    /**
     * Restaura configurações padrão
     */
    public function restoreDefaults( Request $request ): RedirectResponse
    {
        try {
            $type = $request->get( 'type', 'user' );

            if ( $type === 'user' ) {
                $this->settingsService->restoreUserDefaultSettings();
                $message = 'Configurações padrão do usuário restauradas com sucesso!';
            } else {
                $this->settingsService->restoreSystemDefaultSettings();
                $message = 'Configurações padrão do sistema restauradas com sucesso!';
            }

            return back()->with( 'success', $message );

        } catch ( Exception $e ) {
            return back()->with( 'error', 'Erro ao restaurar configurações padrão: ' . $e->getMessage() );
        }
    }

    /**
     * Exibe logs de auditoria
     */
    public function audit( Request $request ): View
    {
        $filters = $request->only( [
            'action', 'user_id', 'severity', 'category', 'model_type',
            'start_date', 'end_date', 'security_only', 'data_modifications_only',
            'sort_by', 'sort_direction'
        ] );

        $perPage = $request->get( 'per_page', 50 );
        $logs    = app( \App\Services\AuditService::class)->getAuditLogs( $filters, $perPage );

        return view( 'settings.audit', [
            'logs'    => $logs,
            'filters' => $filters,
        ] );
    }

    /**
     * Obtém sessões ativas do usuário
     */
    private function getActiveSessions(): array
    {
        // Implementação básica - em produção, usar sessão de banco ou Redis
        return [
            [
                'id'            => session()->getId(),
                'ip_address'    => request()->ip(),
                'user_agent'    => request()->userAgent(),
                'last_activity' => now(),
                'is_current'    => true,
            ],
        ];
    }

    /**
     * Obtém integrações disponíveis
     */
    private function getIntegrations(): array
    {
        // Dados mockados - implementar conforme necessidade
        return [
            'mercadopago'      => [
                'name'      => 'Mercado Pago',
                'status'    => 'connected',
                'last_sync' => now()->subHours( 2 ),
            ],
            'google_analytics' => [
                'name'      => 'Google Analytics',
                'status'    => 'disconnected',
                'last_sync' => null,
            ],
        ];
    }

}
