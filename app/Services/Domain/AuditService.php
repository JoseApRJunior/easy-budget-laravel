<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Serviço para gerenciamento de auditoria do sistema
 */
class AuditService
{
    /**
     * Registra ação de criação
     */
    public function logCreated( Model $model, array $metadata = [] ): AuditLog
    {
        return AuditLog::log( 'created', $model, null, $model->toArray(), $metadata );
    }

    /**
     * Registra ação de atualização
     */
    public function logUpdated( Model $model, array $oldValues, array $newValues, array $metadata = [] ): AuditLog
    {
        return AuditLog::log( 'updated', $model, $oldValues, $newValues, $metadata );
    }

    /**
     * Registra ação de exclusão
     */
    public function logDeleted( Model $model, array $metadata = [] ): AuditLog
    {
        return AuditLog::log( 'deleted', $model, $model->toArray(), null, $metadata );
    }

    /**
     * Registra ação de restauração
     */
    public function logRestored( Model $model, array $metadata = [] ): AuditLog
    {
        return AuditLog::log( 'restored', $model, null, $model->toArray(), $metadata );
    }

    /**
     * Registra ação de arquivamento
     */
    public function logArchived( Model $model, array $metadata = [] ): AuditLog
    {
        return AuditLog::log( 'archived', $model, $model->toArray(), null, $metadata );
    }

    /**
     * Registra login de usuário
     */
    public function logLogin( User $user, array $metadata = [] ): AuditLog
    {
        $metadata = array_merge( $metadata, [
            'ip_address'   => Request::ip(),
            'user_agent'   => Request::userAgent(),
            'login_method' => 'web',
        ] );

        return AuditLog::log( 'login', null, null, null, $metadata )->fill( [
            'model_type' => User::class,
            'model_id'   => $user->id,
        ] )->save() && AuditLog::log( 'login', null, null, null, $metadata );
    }

    /**
     * Registra logout de usuário
     */
    public function logLogout( User $user, array $metadata = [] ): AuditLog
    {
        $metadata = array_merge( $metadata, [
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ] );

        return AuditLog::log( 'logout', null, null, null, $metadata )->fill( [
            'model_type' => User::class,
            'model_id'   => $user->id,
        ] )->save() && AuditLog::log( 'logout', null, null, null, $metadata );
    }

    /**
     * Registra tentativa de login falhada
     */
    public function logLoginFailed( string $email, array $metadata = [] ): AuditLog
    {
        $metadata = array_merge( $metadata, [
            'ip_address'      => Request::ip(),
            'user_agent'      => Request::userAgent(),
            'attempted_email' => $email,
        ] );

        return AuditLog::log( 'login_failed', null, null, null, $metadata );
    }

    /**
     * Registra mudança de senha
     */
    public function logPasswordChanged( User $user, array $metadata = [] ): AuditLog
    {
        return AuditLog::log( 'password_changed', $user, null, null, $metadata );
    }

    /**
     * Registra reset de senha
     */
    public function logPasswordReset( User $user, array $metadata = [] ): AuditLog
    {
        return AuditLog::log( 'password_reset', $user, null, null, $metadata );
    }

    /**
     * Registra ativação de 2FA
     */
    public function logTwoFactorEnabled( User $user, array $metadata = [] ): AuditLog
    {
        return AuditLog::log( 'two_factor_enabled', $user, null, null, $metadata );
    }

    /**
     * Registra desativação de 2FA
     */
    public function logTwoFactorDisabled( User $user, array $metadata = [] ): AuditLog
    {
        return AuditLog::log( 'two_factor_disabled', $user, null, null, $metadata );
    }

    /**
     * Registra término de sessão
     */
    public function logSessionTerminated( User $user, string $sessionId, array $metadata = [] ): AuditLog
    {
        $metadata = array_merge( $metadata, [
            'terminated_session_id' => $sessionId,
        ] );

        return AuditLog::log( 'session_terminated', $user, null, null, $metadata );
    }

    /**
     * Registra concessão de permissão
     */
    public function logPermissionGranted( User $user, string $permission, array $metadata = [] ): AuditLog
    {
        $metadata = array_merge( $metadata, [
            'granted_permission' => $permission,
        ] );

        return AuditLog::log( 'permission_granted', $user, null, null, $metadata );
    }

    /**
     * Registra revogação de permissão
     */
    public function logPermissionRevoked( User $user, string $permission, array $metadata = [] ): AuditLog
    {
        $metadata = array_merge( $metadata, [
            'revoked_permission' => $permission,
        ] );

        return AuditLog::log( 'permission_revoked', $user, null, null, $metadata );
    }

    /**
     * Registra upload de arquivo
     */
    public function logFileUploaded( string $filename, string $mimeType, int $fileSize, array $metadata = [] ): AuditLog
    {
        $metadata = array_merge( $metadata, [
            'uploaded_filename' => $filename,
            'mime_type'         => $mimeType,
            'file_size'         => $fileSize,
        ] );

        return AuditLog::log( 'file_uploaded', null, null, null, $metadata );
    }

    /**
     * Registra exclusão de arquivo
     */
    public function logFileDeleted( string $filename, array $metadata = [] ): AuditLog
    {
        $metadata = array_merge( $metadata, [
            'deleted_filename' => $filename,
        ] );

        return AuditLog::log( 'file_deleted', null, null, null, $metadata );
    }

    /**
     * Registra atualização de avatar
     */
    public function logAvatarUpdated( User $user, string $oldAvatar, string $newAvatar, array $metadata = [] ): AuditLog
    {
        $metadata = array_merge( $metadata, [
            'old_avatar' => $oldAvatar,
            'new_avatar' => $newAvatar,
        ] );

        return AuditLog::log( 'avatar_updated', $user, [ 'avatar' => $oldAvatar ], [ 'avatar' => $newAvatar ], $metadata );
    }

    /**
     * Registra criação de backup
     */
    public function logBackupCreated( string $backupName, int $backupSize, array $metadata = [] ): AuditLog
    {
        $metadata = array_merge( $metadata, [
            'backup_name' => $backupName,
            'backup_size' => $backupSize,
        ] );

        return AuditLog::log( 'backup_created', null, null, null, $metadata );
    }

    /**
     * Registra restauração de backup
     */
    public function logBackupRestored( string $backupName, array $metadata = [] ): AuditLog
    {
        $metadata = array_merge( $metadata, [
            'restored_backup_name' => $backupName,
        ] );

        return AuditLog::log( 'backup_restored', null, null, null, $metadata );
    }

    /**
     * Registra exclusão de backup
     */
    public function logBackupDeleted( string $backupName, array $metadata = [] ): AuditLog
    {
        $metadata = array_merge( $metadata, [
            'deleted_backup_name' => $backupName,
        ] );

        return AuditLog::log( 'backup_deleted', null, null, null, $metadata );
    }

    /**
     * Registra atualização de configurações
     */
    public function logSettingsUpdated( Model $settings, array $oldValues, array $newValues, array $metadata = [] ): AuditLog
    {
        return AuditLog::log( 'settings_updated', $settings, $oldValues, $newValues, $metadata );
    }

    /**
     * Registra atualização de perfil
     */
    public function logProfileUpdated( User $user, array $oldValues, array $newValues, array $metadata = [] ): AuditLog
    {
        return AuditLog::log( 'profile_updated', $user, $oldValues, $newValues, $metadata );
    }

    /**
     * Registra ação de manutenção do sistema
     */
    public function logSystemMaintenance( string $action, array $metadata = [] ): AuditLog
    {
        $metadata = array_merge( $metadata, [
            'maintenance_action' => $action,
        ] );

        return AuditLog::log( 'system_maintenance', null, null, null, $metadata );
    }

    /**
     * Registra limpeza de cache
     */
    public function logCacheCleared( array $metadata = [] ): AuditLog
    {
        return AuditLog::log( 'cache_cleared', null, null, null, $metadata );
    }

    /**
     * Registra execução de migração
     */
    public function logMigrationRan( string $migration, array $metadata = [] ): AuditLog
    {
        $metadata = array_merge( $metadata, [
            'migration_name' => $migration,
        ] );

        return AuditLog::log( 'migration_ran', null, null, null, $metadata );
    }

    /**
     * Obtém logs de auditoria com filtros
     */
    public function getAuditLogs( array $filters = [], int $perPage = 50 ): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = AuditLog::with( [ 'user', 'tenant' ] )
            ->where( 'tenant_id', Auth::user()->tenant_id );

        // Filtros por ação
        if ( isset( $filters[ 'action' ] ) && $filters[ 'action' ] ) {
            $query->where( 'action', $filters[ 'action' ] );
        }

        // Filtros por usuário
        if ( isset( $filters[ 'user_id' ] ) && $filters[ 'user_id' ] ) {
            $query->where( 'user_id', $filters[ 'user_id' ] );
        }

        // Filtros por severidade
        if ( isset( $filters[ 'severity' ] ) && $filters[ 'severity' ] ) {
            $query->where( 'severity', $filters[ 'severity' ] );
        }

        // Filtros por categoria
        if ( isset( $filters[ 'category' ] ) && $filters[ 'category' ] ) {
            $query->where( 'category', $filters[ 'category' ] );
        }

        // Filtros por modelo
        if ( isset( $filters[ 'model_type' ] ) && $filters[ 'model_type' ] ) {
            $query->where( 'model_type', $filters[ 'model_type' ] );
        }

        // Filtros por período
        if ( isset( $filters[ 'start_date' ] ) && $filters[ 'start_date' ] ) {
            $query->where( 'created_at', '>=', $filters[ 'start_date' ] );
        }

        if ( isset( $filters[ 'end_date' ] ) && $filters[ 'end_date' ] ) {
            $query->where( 'created_at', '<=', $filters[ 'end_date' ] );
        }

        // Filtros de segurança
        if ( isset( $filters[ 'security_only' ] ) && $filters[ 'security_only' ] ) {
            $query->security();
        }

        // Filtros de modificações de dados
        if ( isset( $filters[ 'data_modifications_only' ] ) && $filters[ 'data_modifications_only' ] ) {
            $query->dataModifications();
        }

        // Ordenação
        $query->orderBy( $filters[ 'sort_by' ] ?? 'created_at', $filters[ 'sort_direction' ] ?? 'desc' );

        return $query->paginate( $perPage );
    }

    /**
     * Obtém estatísticas de auditoria
     */
    public function getAuditStats( int $days = 30 ): array
    {
        $tenantId  = Auth::user()->tenant_id;
        $startDate = now()->subDays( $days );

        $baseQuery = AuditLog::where( 'tenant_id', $tenantId )
            ->where( 'created_at', '>=', $startDate );

        return [
            'total_logs'         => ( clone $baseQuery )->count(),
            'logs_by_severity'   => ( clone $baseQuery )->selectRaw( 'severity, COUNT(*) as count' )
                ->groupBy( 'severity' )
                ->pluck( 'count', 'severity' )
                ->toArray(),
            'logs_by_category'   => ( clone $baseQuery )->selectRaw( 'category, COUNT(*) as count' )
                ->whereNotNull( 'category' )
                ->groupBy( 'category' )
                ->pluck( 'count', 'category' )
                ->toArray(),
            'logs_by_action'     => ( clone $baseQuery )->selectRaw( 'action, COUNT(*) as count' )
                ->groupBy( 'action' )
                ->orderByDesc( 'count' )
                ->limit( 10 )
                ->pluck( 'count', 'action' )
                ->toArray(),
            'security_incidents' => ( clone $baseQuery )->security()->count(),
            'data_modifications' => ( clone $baseQuery )->dataModifications()->count(),
            'unique_users'       => ( clone $baseQuery )->distinct( 'user_id' )->count( 'user_id' ),
            'logs_per_day'       => ( clone $baseQuery )->selectRaw( 'DATE(created_at) as date, COUNT(*) as count' )
                ->groupBy( 'date' )
                ->orderBy( 'date' )
                ->pluck( 'count', 'date' )
                ->toArray(),
        ];
    }

    /**
     * Obtém logs de segurança recentes
     */
    public function getRecentSecurityLogs( int $limit = 20 ): \Illuminate\Database\Eloquent\Collection
    {
        return AuditLog::with( [ 'user' ] )
            ->where( 'tenant_id', Auth::user()->tenant_id )
            ->security()
            ->recent( 7 )
            ->orderBy( 'created_at', 'desc' )
            ->limit( $limit )
            ->get();
    }

    /**
     * Obtém logs de atividades do usuário atual
     */
    public function getUserActivityLogs( int $userId, int $limit = 50 ): \Illuminate\Database\Eloquent\Collection
    {
        return AuditLog::with( [ 'auditable' ] )
            ->where( 'tenant_id', Auth::user()->tenant_id )
            ->where( 'user_id', $userId )
            ->orderBy( 'created_at', 'desc' )
            ->limit( $limit )
            ->get();
    }

    /**
     * Limpa logs antigos
     */
    public function cleanupOldLogs( int $daysToKeep = 90 ): int
    {
        $tenantId   = Auth::user()->tenant_id;
        $cutoffDate = now()->subDays( $daysToKeep );

        // Mantém logs críticos por mais tempo
        $criticalLogsDeleted = AuditLog::where( 'tenant_id', $tenantId )
            ->where( 'severity', 'critical' )
            ->where( 'created_at', '<', $cutoffDate->subDays( 90 ) ) // 180 dias para críticos
            ->delete();

        // Remove logs antigos (exceto críticos)
        $oldLogsDeleted = AuditLog::where( 'tenant_id', $tenantId )
            ->where( 'severity', '!=', 'critical' )
            ->where( 'created_at', '<', $cutoffDate )
            ->delete();

        return $criticalLogsDeleted + $oldLogsDeleted;
    }

    /**
     * Exporta logs de auditoria
     */
    public function exportAuditLogs( array $filters = [], string $format = 'json' ): string
    {
        $logs = $this->getAuditLogs( $filters, 1000 )->items();

        return match ( $format ) {
            'json'  => json_encode( $logs, JSON_PRETTY_PRINT ),
            'csv'   => $this->convertLogsToCsv( $logs ),
            default => throw new \InvalidArgumentException( 'Formato não suportado: ' . $format ),
        };
    }

    /**
     * Converte logs para CSV
     */
    private function convertLogsToCsv( array $logs ): string
    {
        if ( empty( $logs ) ) {
            return '';
        }

        $headers = [
            'ID',
            'Data/Hora',
            'Usuário',
            'Ação',
            'Modelo',
            'Severidade',
            'Categoria',
            'IP',
            'Descrição',
        ];

        $output = implode( ',', $headers ) . "\n";

        foreach ( $logs as $log ) {
            $row = [
                $log->id,
                $log->created_at->format( 'Y-m-d H:i:s' ),
                $log->user?->email ?? 'Sistema',
                $log->action,
                $log->model_type ?? '',
                $log->severity,
                $log->category ?? '',
                $log->ip_address ?? '',
                $log->description ?? '',
            ];

            // Escapa valores que podem conter vírgulas
            $escapedRow = array_map( function ( $value ) {
                return '"' . str_replace( '"', '""', $value ) . '"';
            }, $row );

            $output .= implode( ',', $escapedRow ) . "\n";
        }

        return $output;
    }

    /**
     * Verifica se uma ação requer auditoria
     */
    public function shouldAudit( string $action, ?Model $model = null ): bool
    {
        // Sempre audita ações críticas
        $criticalActions = [
            'login_failed',
            'password_changed',
            'two_factor_disabled',
            'permission_granted',
            'permission_revoked',
            'deleted',
        ];

        if ( in_array( $action, $criticalActions ) ) {
            return true;
        }

        // Audita ações importantes
        $importantActions = [
            'created',
            'updated',
            'restored',
            'settings_updated',
            'profile_updated',
        ];

        if ( in_array( $action, $importantActions ) ) {
            return true;
        }

        // Não audita ações triviais por padrão
        $trivialActions = [
            'login',
            'logout',
            'file_uploaded',
        ];

        return !in_array( $action, $trivialActions );
    }

    /**
     * Registra ação personalizada
     */
    public function logCustomAction(
        string $action,
        string $description,
        array $metadata = [],
        string $severity = 'info',
        ?Model $model = null,
    ): AuditLog {
        $user = Auth::user();

        return AuditLog::create( [
            'tenant_id'   => $user->tenant_id,
            'user_id'     => $user->id,
            'action'      => $action,
            'model_type'  => $model ? get_class( $model ) : null,
            'model_id'    => $model ? $model->getKey() : null,
            'metadata'    => $metadata,
            'description' => $description,
            'severity'    => $severity,
            'category'    => $metadata[ 'category' ] ?? 'system',
            'ip_address'  => Request::ip(),
            'user_agent'  => Request::userAgent(),
        ] );
    }

}
