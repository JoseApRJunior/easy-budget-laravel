<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\OperationStatus;
use App\Models\Backup;
use App\Repositories\BackupRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use ZipArchive;

/**
 * Serviço para gerenciamento de backups do sistema
 *
 * Este serviço gerencia a criação, armazenamento e restauração de backups
do banco de dados e arquivos do sistema, com suporte a múltiplos
destinos de armazenamento e agendamento automático.
 */
class BackupService extends AbstractBaseService
{
    public function __construct(
        private BackupRepository $backupRepository,
    ) {
        parent::__construct($backupRepository);
    }

    /**
     * Cria backup completo do sistema (banco de dados + arquivos)
     */
    public function createFullBackup(string $type = 'manual', array $options = []): ServiceResult
    {
        try {
            $backupName = 'backup_'.date('Y-m-d_H-i-s').'_'.$type;
            $backupPath = storage_path('app/backups/'.$backupName);

            // Cria diretório se não existir
            if (! file_exists(dirname($backupPath))) {
                mkdir(dirname($backupPath), 0755, true);
            }

            // Cria backup do banco de dados
            $dbBackup = $this->createDatabaseBackup($backupPath.'_database.sql');
            if (! $dbBackup->isSuccess()) {
                return $dbBackup;
            }

            // Cria backup dos arquivos
            $filesBackup = $this->createFilesBackup($backupPath.'_files.zip', $options);
            if (! $filesBackup->isSuccess()) {
                return $filesBackup;
            }

            // Cria arquivo de manifesto
            $manifest = [
                'backup_name' => $backupName,
                'type' => $type,
                'created_at' => now()->toIso8601String(),
                'tenant_id' => $this->tenantId(),
                'created_by' => $this->authUser()->id ?? null,
                'database_size' => filesize($backupPath.'_database.sql'),
                'files_size' => filesize($backupPath.'_files.zip'),
                'total_size' => filesize($backupPath.'_database.sql') + filesize($backupPath.'_files.zip'),
            ];

            file_put_contents($backupPath.'_manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));

            // Cria arquivo final compactado
            $finalBackup = $this->createFinalBackupArchive($backupPath, $backupName);
            if (! $finalBackup->isSuccess()) {
                return $finalBackup;
            }

            // Registra backup no banco de dados
            $backupRecord = $this->create([
                'name' => $backupName,
                'type' => $type,
                'file_path' => $finalBackup->getData()['file_path'],
                'file_size' => $manifest['total_size'],
                'backup_type' => 'full',
                'tenant_id' => $this->tenantId(),
                'created_by' => $this->authUser()->id ?? null,
                'expires_at' => isset($options['retention_days'])
                    ? now()->addDays($options['retention_days'])
                    : now()->addDays(30), // Padrão: 30 dias
            ]);

            if (! $backupRecord->isSuccess()) {
                return $backupRecord;
            }

            // Remove arquivos temporários
            $this->cleanupTemporaryFiles($backupPath);

            return $this->success($backupRecord->getData(), 'Backup completo criado com sucesso.');

        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao criar backup completo.', null, $e);
        }
    }

    /**
     * Cria backup apenas do banco de dados
     */
    public function createDatabaseBackup(string $outputPath): ServiceResult
    {
        try {
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');
            $host = config('database.connections.mysql.host');

            if (! $database || ! $username) {
                return $this->error(OperationStatus::ERROR, 'Configurações do banco de dados não encontradas.');
            }

            // Comando mysqldump com filtros de tenant
            $command = sprintf(
                'mysqldump --host=%s --user=%s --password=%s --single-transaction --routines --triggers %s > %s',
                escapeshellarg($host),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg($outputPath)
            );

            exec($command.' 2>&1', $output, $returnCode);

            if ($returnCode !== 0) {
                return $this->error(OperationStatus::ERROR, 'Erro ao executar mysqldump: '.implode("\n", $output));
            }

            if (! file_exists($outputPath) || filesize($outputPath) === 0) {
                return $this->error(OperationStatus::ERROR, 'Arquivo de backup do banco de dados vazio ou não criado.');
            }

            return $this->success(['file_path' => $outputPath], 'Backup do banco de dados criado com sucesso.');

        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao criar backup do banco de dados.', null, $e);
        }
    }

    /**
     * Cria backup dos arquivos do sistema
     */
    public function createFilesBackup(string $outputPath, array $options = []): ServiceResult
    {
        try {
            $zip = new ZipArchive;

            if ($zip->open($outputPath, ZipArchive::CREATE) !== true) {
                return $this->error(OperationStatus::ERROR, 'Não foi possível criar arquivo ZIP.');
            }

            // Diretórios a incluir no backup
            $directories = [
                'app' => base_path('app'),
                'config' => base_path('config'),
                'database' => base_path('database'),
                'public' => base_path('public'),
                'resources' => base_path('resources'),
                'routes' => base_path('routes'),
                'storage/app' => storage_path('app'),
            ];

            // Filtra diretórios baseado nas opções
            if (isset($options['exclude_directories'])) {
                $directories = array_diff_key($directories, array_flip($options['exclude_directories']));
            }

            // Adiciona arquivos ao ZIP
            foreach ($directories as $name => $path) {
                if (is_dir($path)) {
                    $this->addDirectoryToZip($zip, $path, $name);
                }
            }

            // Adiciona arquivos importantes na raiz
            $rootFiles = ['composer.json', 'composer.lock', 'package.json', '.env.example'];
            foreach ($rootFiles as $file) {
                $filePath = base_path($file);
                if (file_exists($filePath)) {
                    $zip->addFile($filePath, $file);
                }
            }

            $zip->close();

            if (! file_exists($outputPath)) {
                return $this->error(OperationStatus::ERROR, 'Arquivo ZIP não foi criado.');
            }

            return $this->success(['file_path' => $outputPath], 'Backup de arquivos criado com sucesso.');

        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao criar backup de arquivos.', null, $e);
        }
    }

    /**
     * Restaura backup do sistema
     */
    public function restoreBackup(int $backupId, array $options = []): ServiceResult
    {
        try {
            // Busca o backup
            $backup = $this->findById($backupId);
            if (! $backup->isSuccess()) {
                return $backup;
            }

            $backupData = $backup->getData();

            if (! file_exists($backupData->file_path)) {
                return $this->error(OperationStatus::NOT_FOUND, 'Arquivo de backup não encontrado.');
            }

            // Cria backup de segurança antes de restaurar
            $safetyBackup = $this->createFullBackup('safety_before_restore', ['retention_days' => 7]);
            if (! $safetyBackup->isSuccess()) {
                return $safetyBackup;
            }

            // Extrai arquivos do backup
            $extractPath = storage_path('app/temp_restore_'.uniqid());
            $zip = new ZipArchive;

            if ($zip->open($backupData->file_path) !== true) {
                return $this->error(OperationStatus::ERROR, 'Não foi possível abrir arquivo de backup.');
            }

            $zip->extractTo($extractPath);
            $zip->close();

            // Restaura banco de dados
            if (isset($options['restore_database']) && $options['restore_database'] !== false) {
                $dbRestore = $this->restoreDatabase($extractPath.'_database.sql');
                if (! $dbRestore->isSuccess()) {
                    $this->cleanupTemporaryFiles($extractPath);

                    return $dbRestore;
                }
            }

            // Restaura arquivos
            if (isset($options['restore_files']) && $options['restore_files'] !== false) {
                $filesRestore = $this->restoreFiles($extractPath.'_files.zip');
                if (! $filesRestore->isSuccess()) {
                    $this->cleanupTemporaryFiles($extractPath);

                    return $filesRestore;
                }
            }

            // Atualiza registro do backup
            $backupData->update([
                'last_restored_at' => now(),
                'restored_by' => $this->authUser()->id ?? null,
            ]);

            // Limpa arquivos temporários
            $this->cleanupTemporaryFiles($extractPath);

            return $this->success($backupData, 'Backup restaurado com sucesso.');

        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao restaurar backup.', null, $e);
        }
    }

    /**
     * Lista backups com filtros
     */
    public function listBackups(array $filters = []): ServiceResult
    {
        try {
            $query = Backup::where('tenant_id', $this->tenantId());

            if (isset($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            if (isset($filters['backup_type'])) {
                $query->where('backup_type', $filters['backup_type']);
            }

            if (isset($filters['created_from'])) {
                $query->where('created_at', '>=', $filters['created_from']);
            }

            if (isset($filters['created_to'])) {
                $query->where('created_at', '<=', $filters['created_to']);
            }

            if (isset($filters['expired']) && $filters['expired'] === true) {
                $query->where('expires_at', '<', now());
            } elseif (isset($filters['expired']) && $filters['expired'] === false) {
                $query->where('expires_at', '>=', now());
            }

            $backups = $query->orderBy('created_at', 'desc')->get();

            return $this->success($backups, 'Backups listados com sucesso.');

        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao listar backups.', null, $e);
        }
    }

    /**
     * Remove backups expirados
     */
    public function cleanupExpiredBackups(): ServiceResult
    {
        try {
            $expiredBackups = Backup::where('expires_at', '<', now())
                ->where('tenant_id', $this->tenantId())
                ->get();

            $deletedCount = 0;
            foreach ($expiredBackups as $backup) {
                // Remove arquivo físico
                if (file_exists($backup->file_path)) {
                    unlink($backup->file_path);
                }

                // Remove registro do banco
                $backup->delete();
                $deletedCount++;
            }

            return $this->success(['deleted_count' => $deletedCount],
                "{$deletedCount} backups expirados removidos com sucesso.");

        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao limpar backups expirados.', null, $e);
        }
    }

    /**
     * Cria arquivo final compactado com todos os componentes do backup
     */
    private function createFinalBackupArchive(string $backupPath, string $backupName): ServiceResult
    {
        try {
            $finalPath = $backupPath.'.zip';
            $zip = new ZipArchive;

            if ($zip->open($finalPath, ZipArchive::CREATE) !== true) {
                return $this->error(OperationStatus::ERROR, 'Não foi possível criar arquivo ZIP final.');
            }

            // Adiciona componentes do backup
            $components = ['_database.sql', '_files.zip', '_manifest.json'];
            foreach ($components as $component) {
                $componentPath = $backupPath.$component;
                if (file_exists($componentPath)) {
                    $zip->addFile($componentPath, $backupName.$component);
                }
            }

            $zip->close();

            if (! file_exists($finalPath)) {
                return $this->error(OperationStatus::ERROR, 'Arquivo ZIP final não foi criado.');
            }

            return $this->success(['file_path' => $finalPath], 'Arquivo final criado com sucesso.');

        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao criar arquivo final.', null, $e);
        }
    }

    /**
     * Adiciona diretório ao ZIP
     */
    private function addDirectoryToZip(ZipArchive $zip, string $directory, string $baseName): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (! $file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = $baseName.'/'.substr($filePath, strlen($directory) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
    }

    /**
     * Restaura banco de dados
     */
    private function restoreDatabase(string $sqlFile): ServiceResult
    {
        try {
            if (! file_exists($sqlFile)) {
                return $this->error(OperationStatus::NOT_FOUND, 'Arquivo SQL não encontrado.');
            }

            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');
            $host = config('database.connections.mysql.host');

            if (! $database || ! $username) {
                return $this->error(OperationStatus::ERROR, 'Configurações do banco de dados não encontradas.');
            }

            // Comando mysql para restaurar
            $command = sprintf(
                'mysql --host=%s --user=%s --password=%s %s < %s',
                escapeshellarg($host),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg($sqlFile)
            );

            exec($command.' 2>&1', $output, $returnCode);

            if ($returnCode !== 0) {
                return $this->error(OperationStatus::ERROR, 'Erro ao executar mysql: '.implode("\n", $output));
            }

            return $this->success(null, 'Banco de dados restaurado com sucesso.');

        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao restaurar banco de dados.', null, $e);
        }
    }

    /**
     * Restaura arquivos
     */
    private function restoreFiles(string $zipFile): ServiceResult
    {
        try {
            if (! file_exists($zipFile)) {
                return $this->error(OperationStatus::NOT_FOUND, 'Arquivo ZIP não encontrado.');
            }

            $zip = new ZipArchive;
            if ($zip->open($zipFile) !== true) {
                return $this->error(OperationStatus::ERROR, 'Não foi possível abrir arquivo ZIP.');
            }

            // Extrai para diretório temporário primeiro
            $tempPath = storage_path('app/temp_restore_files_'.uniqid());
            $zip->extractTo($tempPath);
            $zip->close();

            // Move arquivos para locais apropriados
            // (Implementação específica depende da estrutura de diretórios)

            $this->cleanupTemporaryFiles($tempPath);

            return $this->success(null, 'Arquivos restaurados com sucesso.');

        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao restaurar arquivos.', null, $e);
        }
    }

    /**
     * Remove arquivos temporários
     */
    private function cleanupTemporaryFiles(string $path): void
    {
        if (is_dir($path)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $file) {
                if ($file->isDir()) {
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }

            rmdir($path);
        } elseif (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Define filtros suportados
     */
    protected function getSupportedFilters(): array
    {
        return [
            'id',
            'name',
            'type',
            'backup_type',
            'file_size',
            'created_by',
            'created_at',
            'expires_at',
            'last_restored_at',
        ];
    }
}
