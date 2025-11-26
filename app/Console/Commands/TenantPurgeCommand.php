<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TenantPurgeCommand extends Command
{
    protected $signature = 'tenant:purge {tenant_id : ID do tenant} {--dry-run : Apenas exibe contagens} {--force : Executa sem confirmação}';

    protected $description = 'Remove todos os registros relacionados ao tenant informado (por coluna tenant_id).';

    public function handle(): int
    {
        $tenantId = (int) $this->argument('tenant_id');
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        if (!$dryRun && !$force) {
            if (!$this->confirm("Tem certeza que deseja remover todos os dados do tenant {$tenantId}?", false)) {
                $this->warn('Operação cancelada.');
                return self::SUCCESS;
            }
        }

        $database = DB::getDatabaseName();
        $tables = DB::table('information_schema.columns')
            ->select('TABLE_NAME')
            ->where('TABLE_SCHEMA', $database)
            ->where('COLUMN_NAME', 'tenant_id')
            ->distinct()
            ->pluck('TABLE_NAME')
            ->values()
            ->all();

        if (empty($tables)) {
            $this->info('Nenhuma tabela com coluna tenant_id encontrada.');
            return self::SUCCESS;
        }

        $summary = [];

        if ($dryRun) {
            foreach ($tables as $table) {
                $count = DB::table($table)->where('tenant_id', $tenantId)->count();
                $summary[$table] = $count;
            }
            $this->table(['Tabela', 'Registros a excluir'], array_map(function ($table) use ($summary) {
                return [$table, $summary[$table] ?? 0];
            }, $tables));
            $total = array_sum($summary);
            $this->info("Total a excluir: {$total}");
            return self::SUCCESS;
        }

        DB::beginTransaction();
        try {
            // Desabilita FKs para evitar bloqueios durante exclusões em cascata manual
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            foreach ($tables as $table) {
                $deleted = DB::table($table)->where('tenant_id', $tenantId)->delete();
                $summary[$table] = $deleted;
                $this->line(sprintf('Tabela %s: %d registros excluídos', $table, $deleted));
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Falha ao excluir registros: ' . $e->getMessage());
            return self::FAILURE;
        }

        $total = array_sum($summary);
        $this->info("Exclusão concluída. Total removido: {$total}");
        return self::SUCCESS;
    }
}

