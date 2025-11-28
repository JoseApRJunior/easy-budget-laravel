<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CategoryPivotSanitizeCommand extends Command
{
    protected $signature = 'categories:pivot:sanitize {--tenant= : ID do tenant para corrigir apenas} {--dry-run : Apenas mostra o que será removido} {--force : Executa sem confirmação}';

    protected $description = 'Remove anexos inconsistentes em category_tenant: categorias privadas de outro tenant vinculadas ao tenant atual.';

    public function handle(): int
    {
        $tenantOption = $this->option('tenant');
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $tenantIds = [];
        if ($tenantOption !== null && $tenantOption !== '') {
            $tenantId = (int) $tenantOption;
            $exists = DB::table('tenants')->where('id', $tenantId)->exists();
            if (! $exists) {
                $this->error("Tenant {$tenantId} não existe.");

                return self::FAILURE;
            }
            $tenantIds = [$tenantId];
        } else {
            $tenantIds = DB::table('tenants')->pluck('id')->all();
        }

        $totalInconsistent = 0;
        $report = [];

        foreach ($tenantIds as $tid) {
            $rows = DB::table('category_tenant')
                ->join('categories', 'categories.id', '=', 'category_tenant.category_id')
                ->where('category_tenant.tenant_id', $tid)
                ->whereNotNull('categories.tenant_id')
                ->whereColumn('categories.tenant_id', '!=', 'category_tenant.tenant_id')
                ->select(['category_tenant.category_id', 'category_tenant.tenant_id'])
                ->get();

            $count = $rows->count();
            $totalInconsistent += $count;
            $report[] = [
                'tenant_id' => $tid,
                'count' => $count,
            ];
        }

        foreach ($report as $r) {
            $this->line("Tenant {$r['tenant_id']}: {$r['count']} anexos inconsistentes");
        }

        if ($dryRun) {
            $this->info("Dry-run: nenhum anexo foi removido. Total inconsistentes: {$totalInconsistent}");

            return self::SUCCESS;
        }

        if (! $force) {
            if (! $this->confirm('Deseja realmente remover todos os anexos inconsistentes listados?', false)) {
                $this->warn('Operação cancelada.');

                return self::SUCCESS;
            }
        }

        DB::beginTransaction();
        try {
            foreach ($tenantIds as $tid) {
                $query = DB::table('category_tenant')
                    ->where('tenant_id', $tid)
                    ->select(['category_id', 'tenant_id']);

                $query->chunkById(500, function ($chunk) {
                    foreach ($chunk as $row) {

                        DB::table('category_tenant')
                            ->where('tenant_id', $row->tenant_id)
                            ->where('category_id', $row->category_id)
                            ->limit(1)
                            ->delete();
                    }
                }, 'category_id');
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Falha ao remover anexos inconsistentes: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info('Anexos inconsistentes removidos com sucesso.');

        return self::SUCCESS;
    }
}
