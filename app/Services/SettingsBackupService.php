<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\SystemSettings;
use App\Models\User;
use App\Models\UserSettings;
use Exception;
use Illuminate\Support\Facades\Storage;

/**
 * Serviço para backup e restauração de configurações
 */
class SettingsBackupService
{
    /**
     * Cria backup das configurações do usuário
     */
    public function createUserSettingsBackup( User $user, string $reason = 'manual' ): array
    {
        try {
            // Obtém configurações atuais do usuário
            $userSettings = $user->settings ?? UserSettings::create( [
                'user_id'   => $user->id,
                'tenant_id' => $user->tenant_id,
            ] );

            $backupData = [
                'user_id'     => $user->id,
                'tenant_id'   => $user->tenant_id,
                'settings'    => $userSettings->toArray(),
                'created_at'  => now(),
                'version'     => '2.0',
                'backup_type' => 'user_settings',
                'metadata'    => [
                    'laravel_version' => app()->version(),
                    'php_version'     => PHP_VERSION,
                    'backup_reason'   => $reason,
                    'backup_by'       => auth()->id(),
                ]
            ];

            $filename = $this->generateBackupFilename( $user, 'user_settings' );
            $path     = "backups/settings/{$user->tenant_id}/{$filename}";

            // Salva backup no storage
            Storage::put( $path, json_encode( $backupData, JSON_PRETTY_PRINT ) );

            // Registra auditoria
            app( AuditService::class)->logBackupCreated( $filename, Storage::size( $path ), [
                'backup_type' => 'user_settings',
                'reason'      => $reason,
            ] );

            return [
                'success'    => true,
                'filename'   => $filename,
                'path'       => $path,
                'size'       => Storage::size( $path ),
                'created_at' => now(),
            ];

        } catch ( Exception $e ) {
            throw new Exception( 'Erro ao criar backup das configurações: ' . $e->getMessage() );
        }
    }

    /**
     * Cria backup das configurações do sistema
     */
    public function createSystemSettingsBackup( int $tenantId, string $reason = 'manual' ): array
    {
        try {
            // Obtém configurações atuais do sistema
            $systemSettings = SystemSettings::where( 'tenant_id', $tenantId )->first();

            if ( !$systemSettings ) {
                throw new Exception( 'Configurações do sistema não encontradas' );
            }

            $backupData = [
                'tenant_id'   => $tenantId,
                'settings'    => $systemSettings->toArray(),
                'created_at'  => now(),
                'version'     => '2.0',
                'backup_type' => 'system_settings',
                'metadata'    => [
                    'laravel_version' => app()->version(),
                    'php_version'     => PHP_VERSION,
                    'backup_reason'   => $reason,
                    'backup_by'       => auth()->id(),
                ]
            ];

            $filename = $this->generateBackupFilename( null, 'system_settings', $tenantId );
            $path     = "backups/settings/{$tenantId}/{$filename}";

            // Salva backup no storage
            Storage::put( $path, json_encode( $backupData, JSON_PRETTY_PRINT ) );

            // Registra auditoria
            app( AuditService::class)->logBackupCreated( $filename, Storage::size( $path ), [
                'backup_type' => 'system_settings',
                'reason'      => $reason,
            ] );

            return [
                'success'    => true,
                'filename'   => $filename,
                'path'       => $path,
                'size'       => Storage::size( $path ),
                'created_at' => now(),
            ];

        } catch ( Exception $e ) {
            throw new Exception( 'Erro ao criar backup das configurações do sistema: ' . $e->getMessage() );
        }
    }

    /**
     * Restaura configurações do usuário a partir de backup
     */
    public function restoreUserSettingsBackup( User $user, string $filename ): array
    {
        try {
            $path = "backups/settings/{$user->tenant_id}/{$filename}";

            if ( !Storage::exists( $path ) ) {
                throw new Exception( 'Arquivo de backup não encontrado' );
            }

            $backupContent = Storage::get( $path );
            $backupData    = json_decode( $backupContent, true );

            // Valida backup
            if ( !$backupData || $backupData[ 'user_id' ] !== $user->id ) {
                throw new Exception( 'Backup inválido ou não pertence ao usuário' );
            }

            if ( $backupData[ 'backup_type' ] !== 'user_settings' ) {
                throw new Exception( 'Tipo de backup inválido' );
            }

            // Cria backup atual antes de restaurar
            $this->createUserSettingsBackup( $user, 'pre_restore' );

            // Restaura configurações
            $userSettings = $user->settings ?? UserSettings::create( [
                'user_id'   => $user->id,
                'tenant_id' => $user->tenant_id,
            ] );

            $oldValues = $userSettings->toArray();

            $userSettings->update( $backupData[ 'settings' ] );

            // Registra auditoria
            app( AuditService::class)->logBackupRestored( $filename, [
                'backup_type' => 'user_settings',
                'restored_by' => auth()->id(),
            ] );

            return [
                'success'        => true,
                'message'        => 'Configurações restauradas com sucesso',
                'backup_version' => $backupData[ 'version' ],
                'backup_date'    => $backupData[ 'created_at' ],
            ];

        } catch ( Exception $e ) {
            throw new Exception( 'Erro ao restaurar backup: ' . $e->getMessage() );
        }
    }

    /**
     * Restaura configurações do sistema a partir de backup
     */
    public function restoreSystemSettingsBackup( int $tenantId, string $filename ): array
    {
        try {
            $path = "backups/settings/{$tenantId}/{$filename}";

            if ( !Storage::exists( $path ) ) {
                throw new Exception( 'Arquivo de backup não encontrado' );
            }

            $backupContent = Storage::get( $path );
            $backupData    = json_decode( $backupContent, true );

            // Valida backup
            if ( !$backupData || $backupData[ 'tenant_id' ] !== $tenantId ) {
                throw new Exception( 'Backup inválido ou não pertence ao tenant' );
            }

            if ( $backupData[ 'backup_type' ] !== 'system_settings' ) {
                throw new Exception( 'Tipo de backup inválido' );
            }

            // Cria backup atual antes de restaurar
            $this->createSystemSettingsBackup( $tenantId, 'pre_restore' );

            // Restaura configurações
            $systemSettings = SystemSettings::where( 'tenant_id', $tenantId )->first();

            if ( !$systemSettings ) {
                throw new Exception( 'Configurações do sistema não encontradas' );
            }

            $oldValues = $systemSettings->toArray();

            $systemSettings->update( $backupData[ 'settings' ] );

            // Registra auditoria
            app( AuditService::class)->logBackupRestored( $filename, [
                'backup_type' => 'system_settings',
                'restored_by' => auth()->id(),
            ] );

            return [
                'success'        => true,
                'message'        => 'Configurações do sistema restauradas com sucesso',
                'backup_version' => $backupData[ 'version' ],
                'backup_date'    => $backupData[ 'created_at' ],
            ];

        } catch ( Exception $e ) {
            throw new Exception( 'Erro ao restaurar backup do sistema: ' . $e->getMessage() );
        }
    }

    /**
     * Lista backups disponíveis
     */
    public function listBackups( int $tenantId, ?string $type = null ): array
    {
        try {
            $directory = "backups/settings/{$tenantId}";

            if ( !Storage::exists( $directory ) ) {
                return [];
            }

            $files   = Storage::files( $directory );
            $backups = [];

            foreach ( $files as $file ) {
                $filename = basename( $file );

                // Filtra por tipo se especificado
                if ( $type ) {
                    $backupType = $this->extractBackupType( $filename );
                    if ( $backupType !== $type ) {
                        continue;
                    }
                }

                $content = Storage::get( $file );
                $data    = json_decode( $content, true );

                if ( !$data ) {
                    continue;
                }

                $backups[] = [
                    'filename'    => $filename,
                    'path'        => $file,
                    'size'        => Storage::size( $file ),
                    'created_at'  => $data[ 'created_at' ] ?? Storage::lastModified( $file ),
                    'version'     => $data[ 'version' ] ?? '1.0',
                    'backup_type' => $data[ 'backup_type' ] ?? 'unknown',
                    'metadata'    => $data[ 'metadata' ] ?? [],
                ];
            }

            // Ordena por data de criação (mais recente primeiro)
            usort( $backups, function ( $a, $b ) {
                return strtotime( $b[ 'created_at' ] ) <=> strtotime( $a[ 'created_at' ] );
            } );

            return $backups;

        } catch ( Exception $e ) {
            throw new Exception( 'Erro ao listar backups: ' . $e->getMessage() );
        }
    }

    /**
     * Remove backup
     */
    public function deleteBackup( int $tenantId, string $filename ): bool
    {
        try {
            $path = "backups/settings/{$tenantId}/{$filename}";

            if ( !Storage::exists( $path ) ) {
                throw new Exception( 'Arquivo de backup não encontrado' );
            }

            $deleted = Storage::delete( $path );

            if ( $deleted ) {
                // Registra auditoria
                app( AuditService::class)->logBackupDeleted( $filename, [
                    'deleted_by' => auth()->id(),
                ] );
            }

            return $deleted;

        } catch ( Exception $e ) {
            throw new Exception( 'Erro ao remover backup: ' . $e->getMessage() );
        }
    }

    /**
     * Limpa backups antigos
     */
    public function cleanupOldBackups( int $tenantId, int $daysToKeep = 30 ): int
    {
        try {
            $backups      = $this->listBackups( $tenantId );
            $cutoffDate   = now()->subDays( $daysToKeep );
            $deletedCount = 0;

            foreach ( $backups as $backup ) {
                $backupDate = strtotime( $backup[ 'created_at' ] );

                if ( $backupDate < $cutoffDate->timestamp ) {
                    $this->deleteBackup( $tenantId, $backup[ 'filename' ] );
                    $deletedCount++;
                }
            }

            return $deletedCount;

        } catch ( Exception $e ) {
            throw new Exception( 'Erro ao limpar backups antigos: ' . $e->getMessage() );
        }
    }

    /**
     * Obtém informações de um backup específico
     */
    public function getBackupInfo( int $tenantId, string $filename ): ?array
    {
        try {
            $path = "backups/settings/{$tenantId}/{$filename}";

            if ( !Storage::exists( $path ) ) {
                return null;
            }

            $content = Storage::get( $path );
            $data    = json_decode( $content, true );

            if ( !$data ) {
                return null;
            }

            return [
                'filename'       => $filename,
                'path'           => $path,
                'size'           => Storage::size( $path ),
                'created_at'     => $data[ 'created_at' ],
                'version'        => $data[ 'version' ],
                'backup_type'    => $data[ 'backup_type' ],
                'metadata'       => $data[ 'metadata' ],
                'settings_count' => count( $data[ 'settings' ] ?? [] ),
            ];

        } catch ( Exception $e ) {
            return null;
        }
    }

    /**
     * Valida integridade de um backup
     */
    public function validateBackup( int $tenantId, string $filename ): array
    {
        try {
            $path = "backups/settings/{$tenantId}/{$filename}";

            if ( !Storage::exists( $path ) ) {
                return [
                    'valid' => false,
                    'error' => 'Arquivo não encontrado',
                ];
            }

            $content = Storage::get( $path );
            $data    = json_decode( $content, true );

            if ( !$data ) {
                return [
                    'valid' => false,
                    'error' => 'Arquivo JSON inválido',
                ];
            }

            // Verifica estrutura obrigatória
            $requiredFields = [ 'created_at', 'version', 'backup_type', 'settings' ];

            foreach ( $requiredFields as $field ) {
                if ( !isset( $data[ $field ] ) ) {
                    return [
                        'valid' => false,
                        'error' => "Campo obrigatório ausente: {$field}",
                    ];
                }
            }

            // Verifica versão compatível
            if ( version_compare( $data[ 'version' ], '2.0', '<' ) ) {
                return [
                    'valid' => false,
                    'error' => 'Versão do backup muito antiga',
                ];
            }

            return [
                'valid'          => true,
                'version'        => $data[ 'version' ],
                'backup_type'    => $data[ 'backup_type' ],
                'created_at'     => $data[ 'created_at' ],
                'settings_count' => count( $data[ 'settings' ] ),
            ];

        } catch ( Exception $e ) {
            return [
                'valid' => false,
                'error' => 'Erro ao validar backup: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Cria backup completo (usuário + sistema)
     */
    public function createFullBackup( User $user, string $reason = 'manual' ): array
    {
        try {
            $tenantId = $user->tenant_id;

            // Cria backups individuais
            $userBackup   = $this->createUserSettingsBackup( $user, $reason );
            $systemBackup = $this->createSystemSettingsBackup( $tenantId, $reason );

            // Cria backup consolidado
            $fullBackupData = [
                'tenant_id'   => $tenantId,
                'user_id'     => $user->id,
                'created_at'  => now(),
                'version'     => '2.0',
                'backup_type' => 'full_backup',
                'backups'     => [
                    'user_settings'   => $userBackup[ 'filename' ],
                    'system_settings' => $systemBackup[ 'filename' ],
                ],
                'metadata'    => [
                    'laravel_version' => app()->version(),
                    'php_version'     => PHP_VERSION,
                    'backup_reason'   => $reason,
                    'backup_by'       => auth()->id(),
                ]
            ];

            $filename = $this->generateBackupFilename( $user, 'full_backup' );
            $path     = "backups/settings/{$tenantId}/{$filename}";

            Storage::put( $path, json_encode( $fullBackupData, JSON_PRETTY_PRINT ) );

            return [
                'success'    => true,
                'filename'   => $filename,
                'path'       => $path,
                'size'       => Storage::size( $path ),
                'created_at' => now(),
                'backups'    => [
                    'user_settings'   => $userBackup,
                    'system_settings' => $systemBackup,
                ],
            ];

        } catch ( Exception $e ) {
            throw new Exception( 'Erro ao criar backup completo: ' . $e->getMessage() );
        }
    }

    /**
     * Restaura backup completo
     */
    public function restoreFullBackup( User $user, string $filename ): array
    {
        try {
            $path = "backups/settings/{$user->tenant_id}/{$filename}";

            if ( !Storage::exists( $path ) ) {
                throw new Exception( 'Arquivo de backup completo não encontrado' );
            }

            $backupContent = Storage::get( $path );
            $backupData    = json_decode( $backupContent, true );

            if ( $backupData[ 'backup_type' ] !== 'full_backup' ) {
                throw new Exception( 'Tipo de backup inválido' );
            }

            // Restaura backups individuais
            $results = [];

            if ( isset( $backupData[ 'backups' ][ 'user_settings' ] ) ) {
                $results[ 'user_settings' ] = $this->restoreUserSettingsBackup(
                    $user,
                    $backupData[ 'backups' ][ 'user_settings' ],
                );
            }

            if ( isset( $backupData[ 'backups' ][ 'system_settings' ] ) ) {
                $results[ 'system_settings' ] = $this->restoreSystemSettingsBackup(
                    $user->tenant_id,
                    $backupData[ 'backups' ][ 'system_settings' ],
                );
            }

            return [
                'success' => true,
                'message' => 'Backup completo restaurado com sucesso',
                'results' => $results,
            ];

        } catch ( Exception $e ) {
            throw new Exception( 'Erro ao restaurar backup completo: ' . $e->getMessage() );
        }
    }

    /**
     * Gera nome único para arquivo de backup
     */
    private function generateBackupFilename( ?User $user, string $type, ?int $tenantId = null ): string
    {
        $timestamp = now()->format( 'Y_m_d_H_i_s' );
        $random    = substr( md5( uniqid() ), 0, 8 );

        if ( $user ) {
            return "backup_{$type}_{$user->id}_{$timestamp}_{$random}.json";
        }

        return "backup_{$type}_{$tenantId}_{$timestamp}_{$random}.json";
    }

    /**
     * Extrai tipo de backup do nome do arquivo
     */
    private function extractBackupType( string $filename ): string
    {
        if ( str_contains( $filename, 'user_settings' ) ) {
            return 'user_settings';
        }

        if ( str_contains( $filename, 'system_settings' ) ) {
            return 'system_settings';
        }

        if ( str_contains( $filename, 'full_backup' ) ) {
            return 'full_backup';
        }

        return 'unknown';
    }

    /**
     * Obtém estatísticas de backups
     */
    public function getBackupStats( int $tenantId ): array
    {
        try {
            $backups = $this->listBackups( $tenantId );

            $stats = [
                'total_backups' => count( $backups ),
                'total_size'    => 0,
                'by_type'       => [],
                'oldest_backup' => null,
                'newest_backup' => null,
            ];

            foreach ( $backups as $backup ) {
                $stats[ 'total_size' ] += $backup[ 'size' ];

                $type = $backup[ 'backup_type' ];
                if ( !isset( $stats[ 'by_type' ][ $type ] ) ) {
                    $stats[ 'by_type' ][ $type ] = 0;
                }
                $stats[ 'by_type' ][ $type ]++;

                // Verifica data mais antiga e mais recente
                $backupDate = strtotime( $backup[ 'created_at' ] );

                if ( !$stats[ 'oldest_backup' ] || $backupDate < strtotime( $stats[ 'oldest_backup' ] ) ) {
                    $stats[ 'oldest_backup' ] = $backup[ 'created_at' ];
                }

                if ( !$stats[ 'newest_backup' ] || $backupDate > strtotime( $stats[ 'newest_backup' ] ) ) {
                    $stats[ 'newest_backup' ] = $backup[ 'created_at' ];
                }
            }

            return $stats;

        } catch ( Exception $e ) {
            return [
                'total_backups' => 0,
                'total_size'    => 0,
                'by_type'       => [],
                'oldest_backup' => null,
                'newest_backup' => null,
            ];
        }
    }

}
