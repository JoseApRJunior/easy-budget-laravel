<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Models\SystemSettings;
use App\Models\User;
use App\Models\UserSettings;
use App\Services\Application\AuditLogService;
use App\Services\Application\FileUploadService;
use App\Services\Core\Abstracts\AbstractBaseService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Serviço principal para gerenciamento de configurações
 */
class SettingsService extends AbstractBaseService
{
    /**
     * Obtém configurações do usuário atual
     */
    public function getUserSettings(?User $user = null): UserSettings
    {
        $user = $user ?? Auth::user();

        if (! $user) {
            throw new Exception('Usuário não autenticado');
        }

        return UserSettings::firstOrCreate(
            [
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
            ],
            [
                'primary_color' => '#3B82F6',
                'layout_density' => 'normal',
                'sidebar_position' => 'left',
                'animations_enabled' => true,
                'sound_enabled' => true,
                'email_notifications' => true,
                'transaction_notifications' => true,
                'weekly_reports' => false,
                'security_alerts' => true,
                'newsletter_subscription' => false,
                'push_notifications' => false,
            ],
        );
    }

    /**
     * Obtém configurações do sistema
     */
    public function getSystemSettings(?int $tenantId = null): SystemSettings
    {
        $tenantId = $tenantId ?? Auth::user()->tenant_id;

        return SystemSettings::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'company_name' => 'Empresa',
                'contact_email' => 'contato@empresa.com',
                'currency' => 'BRL',
                'timezone' => 'America/Sao_Paulo',
                'language' => 'pt-BR',
                'maintenance_mode' => false,
                'registration_enabled' => true,
                'email_verification_required' => true,
                'session_lifetime' => 120,
                'max_login_attempts' => 5,
                'lockout_duration' => 15,
                'max_file_size' => 2048,
            ],
        );
    }

    /**
     * Atualiza configurações do usuário
     */
    public function updateUserSettings(array $data, ?User $user = null): UserSettings
    {
        $user = $user ?? Auth::user();
        $userSettings = $this->getUserSettings($user);

        // Valores antigos para auditoria
        $oldValues = $userSettings->toArray();

        // Remove campos que não devem ser atualizados diretamente
        $protectedFields = ['id', 'user_id', 'tenant_id', 'created_at', 'updated_at'];
        foreach ($protectedFields as $field) {
            unset($data[$field]);
        }

        // Atualiza configurações
        $userSettings->update($data);

        // Registra auditoria
        app(AuditLogService::class)->logSettingsUpdated($userSettings, $oldValues, $data, [
            'settings_type' => 'user_settings',
        ]);

        return $userSettings->fresh();
    }

    /**
     * Atualiza configurações do sistema
     */
    public function updateSystemSettings(array $data, ?int $tenantId = null): SystemSettings
    {
        $tenantId = $tenantId ?? Auth::user()->tenant_id;
        $systemSettings = $this->getSystemSettings($tenantId);

        // Valores antigos para auditoria
        $oldValues = $systemSettings->toArray();

        // Remove campos que não devem ser atualizados diretamente
        $protectedFields = ['id', 'tenant_id', 'created_at', 'updated_at'];
        foreach ($protectedFields as $field) {
            unset($data[$field]);
        }

        // Atualiza configurações
        $systemSettings->update($data);

        // Registra auditoria
        app(AuditLogService::class)->logSettingsUpdated($systemSettings, $oldValues, $data, [
            'settings_type' => 'system_settings',
        ]);

        return $systemSettings->fresh();
    }

    /**
     * Atualiza configurações gerais (empresa e contato)
     */
    public function updateGeneralSettings(array $data): array
    {
        $systemSettings = $this->updateSystemSettings($data);

        return [
            'success' => true,
            'message' => 'Configurações gerais atualizadas com sucesso',
            'settings' => $systemSettings,
        ];
    }

    /**
     * Atualiza configurações de perfil do usuário
     */
    public function updateProfileSettings(array $data): array
    {
        $user = $this->authUser();

        if (! $user) {
            throw new Exception('Usuário não autenticado ou inválido');
        }

        // Busca o usuário do banco para garantir que é um modelo Eloquent
        $user = User::find($user->id);

        // Atualiza dados básicos do usuário se fornecidos
        if (isset($data['full_name']) || isset($data['email']) || isset($data['phone']) || isset($data['birth_date']) || isset($data['extra_links'])) {
            $user->update([
                'name' => $data['full_name'] ?? $user->name,
                'email' => $data['email'] ?? $user->email,
                'phone' => $data['phone'] ?? $user->phone,
                'birth_date' => $data['birth_date'] ?? $user->birth_date,
                'extra_links' => $data['extra_links'] ?? $user->extra_links,
            ]);
        }

        // Atualiza configurações específicas
        $userSettings = $this->updateUserSettings($data);

        return [
            'success' => true,
            'message' => 'Configurações de perfil atualizadas com sucesso',
            'user' => $user->fresh(),
            'settings' => $userSettings,
        ];
    }

    /**
     * Atualiza configurações de segurança
     */
    public function updateSecuritySettings(array $data): array
    {
        $user = $this->authUser();

        if (! $user) {
            throw new Exception('Usuário não autenticado ou inválido');
        }

        // Busca o usuário do banco para garantir que é um modelo Eloquent
        $user = User::find($user->id);

        // Verifica senha atual se fornecida nova senha
        if (isset($data['new_password'])) {
            if (! isset($data['current_password'])) {
                throw new Exception('Senha atual é obrigatória para alterar a senha');
            }

            if (! password_verify($data['current_password'], $user->password)) {
                throw new Exception('Senha atual incorreta');
            }

            // Atualiza senha
            $user->update([
                'password' => bcrypt($data['new_password']),
            ]);

            // Registra auditoria
            app(AuditLogService::class)->logPasswordChanged($user);
        }

        // Atualiza configurações de segurança
        $userSettings = $this->updateUserSettings($data);

        return [
            'success' => true,
            'message' => 'Configurações de segurança atualizadas com sucesso',
            'user' => $user->fresh(),
            'settings' => $userSettings,
        ];
    }

    /**
     * Atualiza configurações de notificação
     */
    public function updateNotificationSettings(array $data): array
    {
        $userSettings = $this->updateUserSettings($data);

        return [
            'success' => true,
            'message' => 'Configurações de notificação atualizadas com sucesso',
            'settings' => $userSettings,
        ];
    }

    /**
     * Atualiza configurações de integração
     */
    public function updateIntegrationSettings(array $data): array
    {
        $user = $this->authUser();

        if (! $user) {
            throw new Exception('Usuário não autenticado ou inválido');
        }

        // Busca o usuário do banco para garantir que é um modelo Eloquent
        $user = User::find($user->id);

        // Implementação específica para integrações
        // Por enquanto, armazena nas preferências customizadas
        $customPreferences = $user->settings->custom_preferences ?? [];
        $customPreferences['integrations'] = $data;

        $userSettings = $this->updateUserSettings([
            'custom_preferences' => $customPreferences,
        ]);

        return [
            'success' => true,
            'message' => 'Configurações de integração atualizadas com sucesso',
            'settings' => $userSettings,
        ];
    }

    /**
     * Atualiza configurações de personalização
     */
    public function updateCustomizationSettings(array $data): array
    {
        $userSettings = $this->updateUserSettings($data);

        return [
            'success' => true,
            'message' => 'Configurações de personalização atualizadas com sucesso',
            'settings' => $userSettings,
        ];
    }

    /**
     * Atualiza avatar do usuário
     */
    public function updateAvatar($avatarFile): array
    {
        $user = Auth::user();
        $userSettings = $this->getUserSettings($user);

        // Faz upload do novo avatar
        $uploadService = app(FileUploadService::class);
        $uploadResult = $uploadService->uploadAvatar($avatarFile, $user->id, $user->tenant_id);

        // Remove avatar antigo se existir
        if ($userSettings->avatar) {
            $oldPath = str_replace('/storage/', '', parse_url($userSettings->avatar, PHP_URL_PATH));
            if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        // Atualiza caminho do avatar
        $userSettings->update([
            'avatar' => $uploadResult['paths']['original'],
        ]);

        // Registra auditoria
        app(AuditLogService::class)->logAvatarUpdated(
            $user,
            $userSettings->getOriginal('avatar'),
            $uploadResult['paths']['original'],
        );

        return [
            'success' => true,
            'message' => 'Avatar atualizado com sucesso',
            'avatar_url' => $uploadResult['url'],
            'settings' => $userSettings->fresh(),
        ];
    }

    /**
     * Remove avatar do usuário
     */
    public function removeAvatar(): array
    {
        $user = Auth::user();
        $userSettings = $this->getUserSettings($user);

        if (! $userSettings->avatar) {
            throw new Exception('Usuário não possui avatar');
        }

        // Remove arquivos do avatar
        $uploadService = app(FileUploadService::class);
        $oldPath = str_replace('/storage/', '', parse_url($userSettings->avatar, PHP_URL_PATH));

        if ($oldPath) {
            // Remove diferentes tamanhos do avatar
            $pathsToRemove = [
                $oldPath,
                str_replace('avatars/', 'avatars/thumb_150_', $oldPath),
                str_replace('avatars/', 'avatars/thumb_300_', $oldPath),
            ];

            foreach ($pathsToRemove as $path) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
        }

        // Remove referência do banco
        $oldAvatar = $userSettings->avatar;
        $userSettings->update(['avatar' => null]);

        // Registra auditoria
        app(AuditLogService::class)->logAvatarUpdated($user, $oldAvatar, '');

        return [
            'success' => true,
            'message' => 'Avatar removido com sucesso',
            'settings' => $userSettings->fresh(),
        ];
    }

    /**
     * Atualiza logo da empresa
     */
    public function updateCompanyLogo($logoFile): array
    {
        $tenantId = Auth::user()->tenant_id;
        $systemSettings = $this->getSystemSettings($tenantId);

        // Faz upload do novo logo
        $uploadService = app(FileUploadService::class);
        $uploadResult = $uploadService->uploadCompanyLogo($logoFile, $tenantId);

        // Remove logo antigo se existir
        if ($systemSettings->logo) {
            $oldPath = str_replace('/storage/', '', parse_url($systemSettings->logo, PHP_URL_PATH));
            if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        // Atualiza caminho do logo
        $systemSettings->update([
            'logo' => $uploadResult['paths']['original'],
        ]);

        return [
            'success' => true,
            'message' => 'Logo da empresa atualizado com sucesso',
            'logo_url' => $uploadResult['url'],
            'settings' => $systemSettings->fresh(),
        ];
    }

    /**
     * Obtém configurações completas do usuário
     */
    public function getCompleteUserSettings(?User $user = null): array
    {
        $user = $user ?? $this->authUser();

        if (! $user) {
            throw new Exception('Usuário não autenticado ou inválido');
        }

        // Busca o usuário do banco para garantir que é um modelo Eloquent
        $user = User::find($user->id);

        // Carrega relacionamentos necessários para o perfil
        $user->load([
            'provider.commonData',
            'provider.contact',
        ]);

        $userSettings = $this->getUserSettings($user);

        return [
            'user' => $user,
            'settings' => $userSettings,
            'preferences' => [
                'theme' => $userSettings->theme,
                'primary_color' => $userSettings->primary_color,
                'layout_density' => $userSettings->layout_density,
                'sidebar_position' => $userSettings->sidebar_position,
                'animations_enabled' => $userSettings->animations_enabled,
                'sound_enabled' => $userSettings->sound_enabled,
            ],
            'notifications' => [
                'email_notifications' => $userSettings->email_notifications,
                'transaction_notifications' => $userSettings->transaction_notifications,
                'weekly_reports' => $userSettings->weekly_reports,
                'security_alerts' => $userSettings->security_alerts,
                'newsletter_subscription' => $userSettings->newsletter_subscription,
                'push_notifications' => $userSettings->push_notifications,
            ],
            'social_links' => [
                'facebook' => $userSettings->social_facebook,
                'twitter' => $userSettings->social_twitter,
                'linkedin' => $userSettings->social_linkedin,
                'instagram' => $userSettings->social_instagram,
            ],
        ];
    }

    /**
     * Obtém configurações completas do sistema
     */
    public function getCompleteSystemSettings(?int $tenantId = null): array
    {
        $tenantId = $tenantId ?? Auth::user()->tenant_id;
        $systemSettings = $this->getSystemSettings($tenantId);

        return [
            'settings' => $systemSettings,
            'company' => [
                'name' => $systemSettings->company_name,
                'email' => $systemSettings->contact_email,
                'phone' => $systemSettings->phone,
                'website' => $systemSettings->website,
                'logo' => $systemSettings->logo_url,
            ],
            'regional' => [
                'currency' => $systemSettings->currency,
                'currency_symbol' => $systemSettings->currency_symbol,
                'timezone' => $systemSettings->timezone,
                'timezone_name' => $systemSettings->timezone_name,
                'language' => $systemSettings->language,
                'language_name' => $systemSettings->language_name,
            ],
            'address' => [
                'street' => $systemSettings->address_street,
                'number' => $systemSettings->address_number,
                'complement' => $systemSettings->address_complement,
                'neighborhood' => $systemSettings->address_neighborhood,
                'city' => $systemSettings->address_city,
                'state' => $systemSettings->address_state,
                'zip_code' => $systemSettings->address_zip_code,
                'country' => $systemSettings->address_country,
                'full_address' => $systemSettings->full_address,
            ],
            'system' => [
                'maintenance_mode' => $systemSettings->maintenance_mode,
                'maintenance_message' => $systemSettings->maintenance_message,
                'registration_enabled' => $systemSettings->registration_enabled,
                'email_verification_required' => $systemSettings->email_verification_required,
            ],
            'security' => [
                'session_lifetime' => $systemSettings->session_lifetime,
                'formatted_session_lifetime' => $systemSettings->formatted_session_lifetime,
                'max_login_attempts' => $systemSettings->max_login_attempts,
                'lockout_duration' => $systemSettings->lockout_duration,
            ],
            'files' => [
                'allowed_file_types' => $systemSettings->allowed_file_types,
                'max_file_size' => $systemSettings->max_file_size,
            ],
        ];
    }

    /**
     * Restaura configurações padrão do usuário
     */
    public function restoreUserDefaultSettings(?User $user = null): array
    {
        $user = $user ?? Auth::user();
        $userSettings = $this->getUserSettings($user);

        $oldValues = $userSettings->toArray();

        // Configurações padrão
        $defaultSettings = [
            'theme' => 'auto',
            'primary_color' => '#3B82F6',
            'layout_density' => 'normal',
            'sidebar_position' => 'left',
            'animations_enabled' => true,
            'sound_enabled' => true,
            'email_notifications' => true,
            'transaction_notifications' => true,
            'weekly_reports' => false,
            'security_alerts' => true,
            'newsletter_subscription' => false,
            'push_notifications' => false,
            'full_name' => null,
            'bio' => null,
            'phone' => null,
            'birth_date' => null,
            'social_facebook' => null,
            'social_twitter' => null,
            'social_linkedin' => null,
            'social_instagram' => null,
            'custom_preferences' => null,
        ];

        $userSettings->update($defaultSettings);

        // Registra auditoria
        app(AuditLogService::class)->logSettingsUpdated($userSettings, $oldValues, $defaultSettings, [
            'type' => 'reset',
            'scope' => 'user',
        ]);

        return [
            'success' => true,
            'message' => 'Configurações padrão restauradas com sucesso',
            'settings' => $userSettings->fresh(),
        ];
    }

    /**
     * Restaura configurações padrão do sistema
     */
    public function restoreSystemDefaultSettings(?int $tenantId = null): array
    {
        $tenantId = $tenantId ?? Auth::user()->tenant_id;
        $systemSettings = $this->getSystemSettings($tenantId);

        $oldValues = $systemSettings->toArray();

        // Configurações padrão
        $defaultSettings = [
            'company_name' => 'Empresa',
            'contact_email' => 'contato@empresa.com',
            'phone' => null,
            'website' => null,
            'logo' => null,
            'currency' => 'BRL',
            'timezone' => 'America/Sao_Paulo',
            'language' => 'pt-BR',
            'address_street' => null,
            'address_number' => null,
            'address_complement' => null,
            'address_neighborhood' => null,
            'address_city' => null,
            'address_state' => null,
            'address_zip_code' => null,
            'address_country' => null,
            'maintenance_mode' => false,
            'maintenance_message' => null,
            'registration_enabled' => true,
            'email_verification_required' => true,
            'session_lifetime' => 120,
            'max_login_attempts' => 5,
            'lockout_duration' => 15,
            'allowed_file_types' => null,
            'max_file_size' => 2048,
            'system_preferences' => null,
        ];

        $systemSettings->update($defaultSettings);

        // Registra auditoria
        app(AuditLogService::class)->logSettingsUpdated($systemSettings, $oldValues, $defaultSettings, [
            'type' => 'reset',
            'scope' => 'system',
        ]);

        return [
            'success' => true,
            'message' => 'Configurações padrão do sistema restauradas com sucesso',
            'settings' => $systemSettings->fresh(),
        ];
    }

    /**
     * Valida configurações antes de salvar
     */
    public function validateSettings(array $data, string $type = 'user'): array
    {
        $errors = [];

        if ($type === 'user') {
            $rules = UserSettings::businessRules();
        } else {
            $rules = SystemSettings::businessRules();
        }

        foreach ($rules as $field => $rule) {
            if (isset($data[$field])) {
                // Implementação básica de validação
                // Em produção, usar Laravel Validator
                $value = $data[$field];

                // Validação obrigatória
                if (str_contains($rule, 'required') && empty($value)) {
                    $errors[$field] = "O campo {$field} é obrigatório";
                }

                // Validação de email
                if (str_contains($rule, 'email') && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = "O campo {$field} deve ser um email válido";
                }

                // Validação de URL
                if (str_contains($rule, 'url') && ! filter_var($value, FILTER_VALIDATE_URL)) {
                    $errors[$field] = "O campo {$field} deve ser uma URL válida";
                }
            }
        }

        return $errors;
    }

    /**
     * Obtém estatísticas das configurações
     */
    public function getSettingsStats(?int $tenantId = null): array
    {
        $tenantId = $tenantId ?? Auth::user()->tenant_id;

        return [
            'user_settings' => [
                'total_users_with_settings' => UserSettings::where('tenant_id', $tenantId)->count(),
                'themes_distribution' => UserSettings::where('tenant_id', $tenantId)
                    ->selectRaw('theme, COUNT(*) as count')
                    ->groupBy('theme')
                    ->pluck('count', 'theme')
                    ->toArray(),
                'notifications_enabled' => UserSettings::where('tenant_id', $tenantId)
                    ->where('email_notifications', true)
                    ->count(),
            ],
            'system_settings' => [
                'maintenance_mode' => SystemSettings::where('tenant_id', $tenantId)->value('maintenance_mode'),
                'registration_enabled' => SystemSettings::where('tenant_id', $tenantId)->value('registration_enabled'),
                'email_verification_required' => SystemSettings::where('tenant_id', $tenantId)->value('email_verification_required'),
            ],
        ];
    }
}
