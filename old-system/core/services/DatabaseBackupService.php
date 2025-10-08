<?php
// core/services/DatabaseBackupService.php

namespace core\services;

use Exception;
use RuntimeException;

class DatabaseBackupService
{
    private string $backupPath;
    private string $host;
    private string $database;
    private string $username;
    private string $password;

    public function __construct()
    {
        $this->backupPath = STORAGE_PATH . '/backups/';
        $this->host       = env( 'DB_HOST' );
        $this->database   = env( 'DB_NAME' );
        $this->username   = env( 'DB_USER' );
        $this->password   = env( 'DB_PASSWORD' );

        if ( !is_dir( $this->backupPath ) ) {
            mkdir( $this->backupPath, 0755, true );
        }
    }

    public function createBackup( string $type = 'manual' ): array
    {
        $timestamp = date( 'Y-m-d_H-i-s' );
        $filename  = "{$this->database}_{$type}_{$timestamp}.sql";
        $filepath  = $this->backupPath . $filename;

        try {
            $command    = $this->buildMysqldumpCommand( $filepath );
            $output     = [];
            $returnCode = 0;

            exec( $command, $output, $returnCode );

            if ( $returnCode !== 0 ) {
                throw new RuntimeException( 'Falha ao executar mysqldump: ' . implode( "\n", $output ) );
            }

            if ( !file_exists( $filepath ) || filesize( $filepath ) === 0 ) {
                throw new RuntimeException( 'Arquivo de backup não foi criado ou está vazio' );
            }

            // Comprimir backup
            $this->compressBackup( $filepath );

            return [ 
                'status'   => 'success',
                'filename' => $filename . '.gz',
                'size'     => $this->formatBytes( filesize( $filepath . '.gz' ) ),
                'path'     => $filepath . '.gz'
            ];

        } catch ( Exception $e ) {
            $this->sendFailureNotification( 'create', $e->getMessage() );
            logger()->error( 'Erro no backup: ' . $e->getMessage() );
            return [ 'status' => 'error', 'message' => $e->getMessage() ];
        }
    }

    public function restoreBackup( string $filename ): array
    {
        $filepath = $this->backupPath . $filename;

        if ( !file_exists( $filepath ) ) {
            return [ 'status' => 'error', 'message' => 'Arquivo de backup não encontrado' ];
        }
        $fileSql = '';

        try {
            // Descomprimir se necessário
            if ( str_ends_with( $filename, '.gz' ) ) {
                $this->decompressBackup( $filepath );
                $filepath = str_replace( '.gz', '', $filepath );
                $fileSql  = $filepath;
            }

            $command    = $this->buildMysqlCommand( $filepath );
            $output     = [];
            $returnCode = 0;

            exec( $command, $output, $returnCode );

            if ( $returnCode !== 0 ) {
                throw new RuntimeException( 'Falha ao restaurar backup: ' . implode( "\n", $output ) );
            }

            // Remove o arquivo SQL descomprimido após a restauração
            if ( $fileSql && file_exists( $fileSql ) ) {
                unlink( $fileSql );
            }

            return [ 'status' => 'success', 'message' => 'Backup restaurado com sucesso' ];

        } catch ( Exception $e ) {
            $this->sendFailureNotification( 'restore', $e->getMessage() );
            logger()->error( 'Erro na restauração: ' . $e->getMessage() );
            return [ 'status' => 'error', 'message' => $e->getMessage() ];
        }
    }

    public function listBackups(): array
    {
        $backups = [];
        $files   = glob( $this->backupPath . '*.sql*' );

        foreach ( $files as $file ) {
            $backups[] = [ 
                'filename' => basename( $file ),
                'size'     => $this->formatBytes( filesize( $file ) ),
                'date'     => date( 'd/m/Y H:i:s', filemtime( $file ) ),
                'type'     => $this->getBackupType( basename( $file ) )
            ];
        }

        // Ordenar por data (mais recente primeiro)
        usort( $backups, fn( $a, $b ) => filemtime( $this->backupPath . $b[ 'filename' ] ) - filemtime( $this->backupPath . $a[ 'filename' ] ) );

        return $backups;
    }

    public function deleteBackup( string $filename ): bool
    {
        $filepath = $this->backupPath . $filename;
        return file_exists( $filepath ) && unlink( $filepath );
    }

    public function cleanOldBackups( int $daysToKeep = 30 ): int
    {
        $deleted    = 0;
        $cutoffTime = time() - ( $daysToKeep * 24 * 60 * 60 );
        $files      = glob( $this->backupPath . '*.sql*' );

        foreach ( $files as $file ) {
            if ( filemtime( $file ) < $cutoffTime ) {
                if ( unlink( $file ) ) {
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    private function buildMysqldumpCommand( string $filepath ): string
    {
        $mysqldumpPath = $this->getMysqldumpPath();
        $passwordPart  = $this->password ? "-p{$this->password}" : '';

        return sprintf(
            '"%s" -h%s -u%s %s --routines --triggers --single-transaction %s > "%s" 2>&1',
            $mysqldumpPath,
            $this->host,
            $this->username,
            $passwordPart,
            $this->database,
            $filepath,
        );
    }

    private function buildMysqlCommand( string $filepath ): string
    {
        $mysqlPath    = $this->getMysqlPath();
        $passwordPart = $this->password ? "-p{$this->password}" : '';

        return sprintf(
            '"%s" -h%s -u%s %s %s < "%s" 2>&1',
            $mysqlPath,
            $this->host,
            $this->username,
            $passwordPart,
            $this->database,
            $filepath,
        );
    }

    private function getMysqldumpPath(): string
    {
        // Tentar diferentes localizações
        $paths = [ 
            'C:\xampp\mysql\bin\mysqldump.exe',
            'mysqldump', // PATH do sistema
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump'
        ];

        foreach ( $paths as $path ) {
            if ( is_executable( $path ) || $path === 'mysqldump' ) {
                return $path;
            }
        }

        throw new RuntimeException( 'mysqldump não encontrado' );
    }

    private function getMysqlPath(): string
    {
        $paths = [ 
            'C:\xampp\mysql\bin\mysql.exe',
            'mysql',
            '/usr/bin/mysql',
            '/usr/local/bin/mysql'
        ];

        foreach ( $paths as $path ) {
            if ( is_executable( $path ) || $path === 'mysql' ) {
                return $path;
            }
        }

        throw new RuntimeException( 'mysql não encontrado' );
    }

    private function compressBackup( string $filepath ): void
    {
        if ( function_exists( 'gzencode' ) ) {
            $data = file_get_contents( $filepath );
            file_put_contents( $filepath . '.gz', gzencode( $data, 9 ) );
            unlink( $filepath ); // Remove arquivo original
        }
    }

    private function decompressBackup( string $filepath ): void
    {
        if ( function_exists( 'gzdecode' ) ) {
            $data = gzdecode( file_get_contents( $filepath ) );
            file_put_contents( str_replace( '.gz', '', $filepath ), $data );
        }
    }

    private function formatBytes( int $bytes ): string
    {
        $units = [ 'B', 'KB', 'MB', 'GB' ];
        $bytes = max( $bytes, 0 );
        $pow   = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
        $pow   = min( $pow, count( $units ) - 1 );

        return round( $bytes / ( 1024 ** $pow ), 2 ) . ' ' . $units[ $pow ];
    }

    private function getBackupType( string $filename ): string
    {
        if ( str_contains( $filename, '_auto_' ) ) return 'Automático';
        if ( str_contains( $filename, '_manual_' ) ) return 'Manual';
        return 'Desconhecido';
    }

    // Adicionar ao DatabaseBackupService
    public function getDiskSpace(): array
    {
        $total = disk_total_space( $this->backupPath );
        $free  = disk_free_space( $this->backupPath );
        $used  = $total - $free;

        return [ 
            'total'        => $this->formatBytes( (int) $total ),
            'free'         => $this->formatBytes( (int) $free ),
            'used'         => $this->formatBytes( $used ),
            'percent_used' => round( ( $used / $total ) * 100, 2 )
        ];
    }

    public function validateBackupFile( string $filename ): bool
    {
        return preg_match( '/^[a-zA-Z0-9_\-\.]+\.(sql|gz)$/', $filename ) &&
            !str_contains( $filename, '..' ) &&
            file_exists( $this->backupPath . $filename );
    }

    private function sendFailureNotification( string $operation, string $error ): void
    {
        $adminEmail = env( 'ADMIN_EMAIL' );
        if ( $adminEmail ) {
            $subject = "Falha no Backup - " . ucfirst( $operation );
            $message = "Erro na operação de backup: {$operation}\n\nDetalhes: {$error}\n\nData: " . date( 'd/m/Y H:i:s' );

            mail( $adminEmail, $subject, $message );
        }
    }

}
