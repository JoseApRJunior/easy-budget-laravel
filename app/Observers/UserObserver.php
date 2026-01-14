<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\User;

/**
 * Observer for User model to automatically log all CRUD operations.
 */
class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $this->log($user, 'user_created', 'Usuário criado no sistema');
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        $changes = $user->getChanges();

        // IGNORAR atualizações relacionadas a autenticação/login
        // Não registrar atividade para dados do Google OAuth ou timestamps
        $authRelatedFields = ['google_id', 'google_data', 'avatar', 'updated_at', 'last_login_at'];
        $hasOnlyAuthChanges = ! array_diff(array_keys($changes), $authRelatedFields);

        if ($hasOnlyAuthChanges) {
            // Não registrar atividade para mudanças apenas de autenticação
            return;
        }

        // Detectar tipo específico de atualização
        $action = 'user_updated';
        $description = 'Dados do usuário atualizados';

        if (isset($changes['password'])) {
            $action = $user->wasChanged('password') && $user->getOriginal('password') === null
                ? 'password_set'
                : 'password_changed';
            $description = $action === 'password_set'
                ? 'Primeira senha definida'
                : 'Senha alterada';
        } elseif (isset($changes['email'])) {
            $action = 'email_updated';
            $description = 'E-mail atualizado';
        } elseif (isset($changes['logo'])) {
            $action = 'logo_updated';
            $description = 'Logo atualizado';
        }

        $this->log($user, $action, $description, [
            'old_values' => $user->getOriginal(),
            'new_values' => $changes,
        ]);
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        $this->log($user, 'user_deleted', 'Usuário excluído');
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        $this->log($user, 'user_restored', 'Usuário restaurado');
    }

    /**
     * Log activity to audit log.
     */
    private function log(User $user, string $action, string $description, array $extra = []): void
    {
        try {
            AuditLog::withoutTenant()->create([
                'tenant_id' => $user->tenant_id,
                'user_id' => auth()->id() ?? $user->id,
                'action' => $action,
                'model_type' => User::class,
                'model_id' => $user->id,
                'description' => $description,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => array_merge([
                    'email' => $user->email,
                ], $extra),
            ]);
        } catch (\Exception $e) {
            // Silently fail to prevent breaking user operations
            \Log::error('Failed to create audit log', [
                'action' => $action,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
