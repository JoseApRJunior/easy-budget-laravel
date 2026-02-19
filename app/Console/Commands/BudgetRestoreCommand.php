<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Budget;
use Illuminate\Console\Command;

class BudgetRestoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'budget:restore {code : O código do orçamento ou ID a ser restaurado}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restaura um orçamento deletado (soft delete)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $code = $this->argument('code');

        $this->info("Buscando orçamento com código/ID: {$code}...");

        // Tenta buscar por ID se for numérico
        $budget = null;
        if (is_numeric($code)) {
            $budget = Budget::withTrashed()->find($code);
        }

        // Se não achou por ID, tenta por código
        if (! $budget) {
            $budget = Budget::withTrashed()->where('code', $code)->first();
        }

        if (! $budget) {
            $this->error("Orçamento não encontrado (nem ativo, nem na lixeira).");
            return 1;
        }

        if (! $budget->trashed()) {
            $this->warn("O orçamento '{$budget->code}' (ID: {$budget->id}) do Tenant #{$budget->tenant_id} já está ativo.");
            return 0;
        }

        if ($this->confirm("Deseja restaurar o orçamento '{$budget->code}' (ID: {$budget->id}) do Tenant #{$budget->tenant_id}?")) {
            $budget->restore();
            $this->info("Orçamento restaurado com sucesso!");
        }

        return 0;
    }
}
