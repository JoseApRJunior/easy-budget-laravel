<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Models\ProviderCredential;
use App\Services\Application\AuditLogService;
use App\Services\Application\FileUploadService;
use App\Services\Application\SettingsBackupService;
use App\Services\Domain\SettingsService;
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
        private AuditLogService $auditLogService,
    ) {}

    /**
     * Exibe página principal de configurações
     */
    public function index(Request $request): View
    {
        $activeTab = $request->get('tab', 'profile');

        // Obtém configurações completas
        $userSettings = $this->settingsService->getCompleteUserSettings();
        $systemSettings = $this->settingsService->getCompleteSystemSettings();

        // Dados para as abas
        $tabs = [
            'general' => [
                'label' => 'Geral',
                'icon' => 'building',
                'data' => [
                    'system_settings' => $systemSettings,
                ],
            ],
            'profile' => [
                'label' => 'Perfil',
                'icon' => 'person',
                'data' => [
                    'user_settings' => $userSettings,
                ],
            ],
            'security' => [
                'label' => 'Segurança',
                'icon' => 'shield-check',
                'data' => [
                    'user_settings' => $userSettings,
                    'sessions' => $this->getActiveSessions(),
                ],
            ],
            'notifications' => [
                'label' => 'Notificações',
                'icon' => 'bell',
                'data' => [
                    'user_settings' => $userSettings,
                ],
            ],
            'integrations' => [
                'label' => 'Integrações',
                'icon' => 'link',
                'data' => [
                    'user_settings' => $userSettings,
                    'integrations' => $this->getIntegrations(),
                ],
            ],
            'customization' => [
                'label' => 'Personalização',
                'icon' => 'palette',
                'data' => [
                    'user_settings' => $userSettings,
                ],
            ],
            'provider' => [
                'label' => 'Provider',
                'icon' => 'building',
                'data' => [
                    'provider' => auth()->user()->provider,
                ],
            ],
        ];

        return view('settings.index', [
            'activeTab' => $activeTab,
            'tabs' => $tabs,
            'userSettings' => $userSettings,
            'systemSettings' => $systemSettings,
        ]);
    }

    /**
     * Atualiza configurações gerais
     */
    public function updateGeneral(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_email' => 'required|email:rfc,dns|max:255',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'currency' => 'required|in:BRL,USD,EUR',
            'timezone' => 'required|timezone',
            'language' => 'required|in:pt-BR,en-US,es-ES',
            'address_street' => 'nullable|string|max:255',
            'address_number' => 'nullable|string|max:20',
            'address_complement' => 'nullable|string|max:100',
            'address_neighborhood' => 'nullable|string|max:100',
            'address_city' => 'nullable|string|max:100',
            'address_state' => 'nullable|string|max:50',
            'address_zip_code' => 'nullable|string|max:10',
            'address_country' => 'nullable|string|max:50',
        ]);

        $this->settingsService->updateGeneralSettings($validated);

        return back()->with('success', 'Configurações gerais atualizadas com sucesso!');
    }

    /**
     * Atualiza configurações de perfil
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date|before:today',
            'social_facebook' => 'nullable|url|max:255',
            'social_twitter' => 'nullable|url|max:255',
            'social_linkedin' => 'nullable|url|max:255',
            'social_instagram' => 'nullable|url|max:255',
            'extra_links' => 'nullable|string|max:1000',
        ]);

        $this->settingsService->updateProfileSettings($validated);

        return back()->with('success', 'Perfil atualizado com sucesso!');
    }

    /**
     * Atualiza configurações de segurança
     */
    public function updateSecurity(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => 'required_with:new_password|string',
            'new_password' => 'nullable|string|min:8|max:255|confirmed',
            'email_notifications' => 'boolean',
            'transaction_notifications' => 'boolean',
            'weekly_reports' => 'boolean',
            'security_alerts' => 'boolean',
            'newsletter_subscription' => 'boolean',
            'push_notifications' => 'boolean',
        ]);

        $this->settingsService->updateSecuritySettings($validated);

        return back()->with('success', 'Configurações de segurança atualizadas com sucesso!');
    }

    /**
     * Atualiza configurações de notificação
     */
    public function updateNotifications(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email_notifications' => 'boolean',
            'transaction_notifications' => 'boolean',
            'weekly_reports' => 'boolean',
            'security_alerts' => 'boolean',
            'newsletter_subscription' => 'boolean',
            'push_notifications' => 'boolean',
        ]);

        $this->settingsService->updateNotificationSettings($validated);

        return back()->with('success', 'Configurações de notificação atualizadas com sucesso!');
    }

    /**
     * Atualiza configurações de integração
     */
    public function updateIntegrations(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'api_keys' => 'nullable|array',
            'webhook_urls' => 'nullable|array',
            'integration_settings' => 'nullable|array',
        ]);

        $this->settingsService->updateIntegrationSettings($validated);

        return back()->with('success', 'Configurações de integração atualizadas com sucesso!');
    }

    /**
     * Atualiza configurações de personalização
     */
    public function updateCustomization(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'primary_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'layout_density' => 'required|in:compact,normal,spacious',
            'sidebar_position' => 'required|in:left,right',
            'animations_enabled' => 'boolean',
            'sound_enabled' => 'boolean',
        ]);

        $this->settingsService->updateCustomizationSettings($validated);

        return back()->with('success', 'Configurações de personalização atualizadas com sucesso!');
    }

    /**
     * Atualiza avatar do usuário
     */
    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $this->settingsService->updateAvatar($request->file('avatar'));

        return back()->with('success', 'Avatar atualizado com sucesso!');
    }

    /**
     * Remove avatar do usuário
     */
    public function removeAvatar(Request $request): RedirectResponse
    {
        $this->settingsService->removeAvatar();

        return back()->with('success', 'Avatar removido com sucesso!');
    }

    /**
     * Atualiza logo da empresa
     */
    public function updateCompanyLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,webp,svg+xml|max:2048',
        ]);

        $this->settingsService->updateCompanyLogo($request->file('logo'));

        return back()->with('success', 'Logo da empresa atualizado com sucesso!');
    }

    /**
     * Cria backup das configurações
     */
    public function createBackup(Request $request): RedirectResponse
    {
        $type = $request->get('type', 'full');

        if ($type === 'full') {
            $result = $this->backupService->createFullBackup(auth()->user(), 'manual');
        } elseif ($type === 'user') {
            $result = $this->backupService->createUserSettingsBackup(auth()->user(), 'manual');
        } else {
            $result = $this->backupService->createSystemSettingsBackup(auth()->user()->tenant_id, 'manual');
        }

        return back()->with('success', 'Backup criado com sucesso!');
    }

    /**
     * Lista backups disponíveis
     */
    public function listBackups(Request $request): View
    {
        $tenantId = auth()->user()->tenant_id;
        $backups = $this->backupService->listBackups($tenantId);
        $stats = $this->backupService->getBackupStats($tenantId);

        return view('settings.backups', [
            'backups' => $backups,
            'stats' => $stats,
        ]);
    }

    /**
     * Restaura backup
     */
    public function restoreBackup(Request $request): RedirectResponse
    {
        $request->validate([
            'filename' => 'required|string',
            'type' => 'required|in:user,system,full',
        ]);

        $user = auth()->user();

        if ($request->type === 'full') {
            $this->backupService->restoreFullBackup($user, $request->filename);
        } elseif ($request->type === 'user') {
            $this->backupService->restoreUserSettingsBackup($user, $request->filename);
        } else {
            $this->backupService->restoreSystemSettingsBackup($user->tenant_id, $request->filename);
        }

        return back()->with('success', 'Backup restaurado com sucesso!');
    }

    /**
     * Remove backup
     */
    public function deleteBackup(Request $request): RedirectResponse
    {
        $request->validate([
            'filename' => 'required|string',
        ]);

        $this->backupService->deleteBackup(auth()->user()->tenant_id, $request->filename);

        return back()->with('success', 'Backup removido com sucesso!');
    }

    /**
     * Restaura configurações padrão
     */
    public function restoreDefaults(Request $request): RedirectResponse
    {
        $type = $request->get('type', 'user');

        if ($type === 'user') {
            $this->settingsService->restoreUserDefaultSettings();
            $message = 'Configurações padrão do usuário restauradas com sucesso!';
        } else {
            $this->settingsService->restoreSystemDefaultSettings();
            $message = 'Configurações padrão do sistema restauradas com sucesso!';
        }

        return back()->with('success', $message);
    }

    /**
     * Obtém sessões ativas (mock para demonstração)
     */
    private function getActiveSessions(): array
    {
        return [
            [
                'id' => 1,
                'device' => 'Windows PC - Chrome',
                'ip' => '192.168.1.1',
                'is_current' => true,
                'last_activity' => now()->toDateTimeString(),
            ],
            [
                'id' => 2,
                'device' => 'iPhone 13 - Safari',
                'ip' => '177.45.12.3',
                'is_current' => false,
                'last_activity' => now()->subHours(2)->toDateTimeString(),
            ],
        ];
    }

    /**
     * Obtém integrações ativas (mock para demonstração)
     */
    private function getIntegrations(): array
    {
        return [
            [
                'id' => 'google',
                'name' => 'Google Calendar',
                'status' => 'connected',
                'icon' => 'google',
                'last_sync' => now()->subDays(1)->toDateTimeString(),
            ],
            [
                'id' => 'whatsapp',
                'name' => 'WhatsApp Business',
                'status' => 'disconnected',
                'icon' => 'whatsapp',
                'last_sync' => null,
            ],
            [
                'id' => 'stripe',
                'name' => 'Stripe Payments',
                'status' => 'connected',
                'icon' => 'credit-card',
                'last_sync' => now()->subHours(5)->toDateTimeString(),
            ],
        ];
    }
}
