<?php
// Inclua o arquivo de constantes
require __DIR__ . '/app/helpers/constantes.php';
// backup_cron.php
require_once __DIR__ . '/public_html/bootstrap.php';

$backupService = new \core\services\DatabaseBackupService();
$command       = new \core\commands\BackupCommand( $backupService );
$command->run();

// # Adicionar ao crontab (Linux) ou Task Scheduler (Windows)
// # Backup diário às 2:00 AM
// 0 2 * * * cd /path/to/project && php -f backup_cron.php
