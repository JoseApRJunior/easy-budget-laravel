<?php
// core/commands/BackupCommand.php

namespace core\commands;

use core\services\DatabaseBackupService;

class BackupCommand
{
    public function __construct( private DatabaseBackupService $backupService ) {}

    public function run(): void
    {
        echo "Iniciando backup automático...\n";

        $result = $this->backupService->createBackup( 'auto' );

        if ( $result[ 'status' ] === 'success' ) {
            echo "Backup criado: {$result[ 'filename' ]} ({$result[ 'size' ]})\n";

            // Limpar backups antigos
            $deleted = $this->backupService->cleanOldBackups( 30 );
            echo "Backups antigos removidos: {$deleted}\n";
        } else {
            echo "Erro no backup: {$result[ 'message' ]}\n";
        }
    }

}
