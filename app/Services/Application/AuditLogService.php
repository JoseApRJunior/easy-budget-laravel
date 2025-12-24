<?php

declare(strict_types=1);

namespace App\Services\Application;

use App\DTOs\AuditLog\AuditLogDTO;
use App\Models\AuditLog;
use App\Models\User;
use App\Repositories\AuditLogRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Services\Core\Responses\ServiceResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Serviço para gerenciamento de logs de auditoria.
 *
 * Esta classe fornece métodos para registrar e recuperar logs de auditoria,
 * seguindo os padrões do sistema (DTOs, Repositórios, safeExecute).
 */
class AuditLogService extends AbstractBaseService
{
    /**
     * @var AuditLogRepository
     */
    protected $repository;

    /**
     * Construtor do serviço.
     */
    public function __construct(AuditLogRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Registra uma ação genérica.
     */
    public function log(string $action, ?Model $model = null, ?array $oldValues = null, ?array $newValues = null, ?array $metadata = []): ServiceResult
    {
        return $this->safeExecute(function () use ($action, $model, $oldValues, $newValues, $metadata) {
            $user = Auth::user();

            if (!$user) {
                return $this->error('Usuário não autenticado para registro de log.');
            }

            $dto = new AuditLogDTO(
                action: $action,
                model_type: $model ? get_class($model) : 'system',
                model_id: $model ? (int) $model->getKey() : null,
                old_values: $oldValues,
                new_values: $newValues,
                ip_address: Request::ip(),
                user_agent: Request::userAgent(),
                metadata: $metadata,
                severity: $this->determineSeverity($action),
                category: $this->determineCategory($action),
                user_id: $user->id,
                tenant_id: $user->tenant_id
            );

            $log = $this->repository->createFromDTO($dto);
            return $this->success($log, 'Log registrado com sucesso.');
        }, 'Erro ao registrar log de auditoria.');
    }

    /**
     * Registra ação de criação.
     */
    public function logCreated(Model $model, array $metadata = []): ServiceResult
    {
        return $this->log('created', $model, null, $model->toArray(), $metadata);
    }

    /**
     * Registra ação de atualização.
     */
    public function logUpdated(Model $model, array $oldValues, array $newValues, array $metadata = []): ServiceResult
    {
        return $this->log('updated', $model, $oldValues, $newValues, $metadata);
    }

    /**
     * Registra ação de exclusão.
     */
    public function logDeleted(Model $model, array $metadata = []): ServiceResult
    {
        return $this->log('deleted', $model, $model->toArray(), null, $metadata);
    }

    /**
     * Registra ação de restauração.
     */
    public function logRestored(Model $model, array $metadata = []): ServiceResult
    {
        return $this->log('restored', $model, null, $model->toArray(), $metadata);
    }

    /**
     * Registra ação de login.
     */
    public function logLogin(User $user, array $metadata = []): ServiceResult
    {
        $metadata = array_merge($metadata, [
            'login_method' => 'web',
        ]);

        return $this->log('login', $user, null, null, $metadata);
    }

    /**
     * Registra ação de logout.
     */
    public function logLogout(User $user, array $metadata = []): ServiceResult
    {
        return $this->log('logout', $user, null, null, $metadata);
    }

    /**
     * Registra tentativa de login falhada.
     */
    public function logLoginFailed(string $email, array $metadata = []): ServiceResult
    {
        $metadata = array_merge($metadata, [
            'attempted_email' => $email,
        ]);

        return $this->log('login_failed', null, null, null, $metadata);
    }

    /**
     * Registra alteração de senha.
     */
    public function logPasswordChanged(User $user, array $metadata = []): ServiceResult
    {
        return $this->log('password_changed', $user, null, null, $metadata);
    }

    /**
     * Registra ativação de 2FA.
     */
    public function logTwoFactorEnabled(User $user, array $metadata = []): ServiceResult
    {
        return $this->log('two_factor_enabled', $user, null, null, $metadata);
    }

    /**
     * Registra desativação de 2FA.
     */
    public function logTwoFactorDisabled(User $user, array $metadata = []): ServiceResult
    {
        return $this->log('two_factor_disabled', $user, null, null, $metadata);
    }

    /**
     * Registra concessão de permissão.
     */
    public function logPermissionGranted(User $user, string $permission, array $metadata = []): ServiceResult
    {
        $metadata = array_merge($metadata, [
            'granted_permission' => $permission,
        ]);

        return $this->log('permission_granted', $user, null, null, $metadata);
    }

    /**
     * Registra revogação de permissão.
     */
    public function logPermissionRevoked(User $user, string $permission, array $metadata = []): ServiceResult
    {
        $metadata = array_merge($metadata, [
            'revoked_permission' => $permission,
        ]);

        return $this->log('permission_revoked', $user, null, null, $metadata);
    }

    /**
     * Registra atualização de configurações.
     */
    public function logSettingsUpdated(Model $settings, array $oldValues, array $newValues, array $metadata = []): ServiceResult
    {
        return $this->log('settings_updated', $settings, $oldValues, $newValues, $metadata);
    }

    /**
     * Registra atualização de perfil.
     */
    public function logProfileUpdated(User $user, array $oldValues, array $newValues, array $metadata = []): ServiceResult
    {
        return $this->log('profile_updated', $user, $oldValues, $newValues, $metadata);
    }

    /**
     * Registra criação de backup.
     */
    public function logBackupCreated(string $backupName, int $backupSize, array $metadata = []): ServiceResult
    {
        $metadata = array_merge($metadata, [
            'backup_name' => $backupName,
            'backup_size' => $backupSize,
        ]);

        return $this->log('backup_created', null, null, null, $metadata);
    }

    /**
     * Registra restauração de backup.
     */
    public function logBackupRestored(string $backupName, array $metadata = []): ServiceResult
    {
        $metadata = array_merge($metadata, [
            'restored_backup_name' => $backupName,
        ]);

        return $this->log('backup_restored', null, null, null, $metadata);
    }

    /**
     * Registra exclusão de backup.
     */
    public function logBackupDeleted(string $backupName, array $metadata = []): ServiceResult
    {
        $metadata = array_merge($metadata, [
            'deleted_backup_name' => $backupName,
        ]);

        return $this->log('backup_deleted', null, null, null, $metadata);
    }

    /**
     * Busca logs recentes para o tenant atual.
     */
    public function getRecentLogs(int $limit = 10): ServiceResult
    {
        return $this->safeExecute(function () use ($limit) {
            $user = Auth::user();
            if (!$user) {
                return $this->error('Usuário não autenticado.');
            }

            // Usando getFiltered sem filtros para pegar os mais recentes
            $logs = $this->repository->getFiltered([], $limit);
            return $this->success($logs);
        }, 'Erro ao recuperar logs recentes.');
    }

    /**
     * Busca logs filtrados.
     */
    public function getFilteredLogs(array $filters = [], int $perPage = 50): ServiceResult
    {
        return $this->safeExecute(function () use ($filters, $perPage) {
            $logs = $this->repository->getFiltered($filters, $perPage);
            return $this->success($logs);
        }, 'Erro ao recuperar logs filtrados.');
    }

    /**
     * Obtém estatísticas de auditoria.
     */
    public function getAuditStats(int $days = 30): ServiceResult
    {
        return $this->safeExecute(function () use ($days) {
            $user = Auth::user();
            if (!$user) {
                return $this->error('Usuário não autenticado.');
            }

            $stats = $this->repository->getStats($user->tenant_id, $days);
            return $this->success($stats);
        }, 'Erro ao recuperar estatísticas de auditoria.');
    }

    /**
     * Prepara dados para exportação.
     */
    public function prepareExportData(array $filters = []): ServiceResult
    {
        return $this->safeExecute(function () use ($filters) {
            $logs = $this->repository->getFiltered($filters, 1000);
            return $this->success($logs->items());
        }, 'Erro ao preparar dados para exportação.');
    }

    /**
     * Determina a severidade da ação.
     */
    protected function determineSeverity(string $action): string
    {
        $criticalActions = ['login_failed', 'password_changed', 'two_factor_disabled', 'permission_granted', 'permission_revoked'];
        $highActions = ['deleted', 'session_terminated', 'backup_restored'];
        $warningActions = ['created', 'updated', 'two_factor_enabled'];

        if (in_array($action, $criticalActions)) return 'critical';
        if (in_array($action, $highActions)) return 'high';
        if (in_array($action, $warningActions)) return 'warning';

        return 'info';
    }

    /**
     * Determina a categoria da ação.
     */
    protected function determineCategory(string $action): string
    {
        // Poderia vir do modelo AuditLog::COMMON_ACTIONS se disponível publicamente
        // Para simplificar agora, usamos um mapeamento básico ou 'system'
        return 'system';
    }
}
