<?php

namespace App\Console\Commands;

use App\Enums\BudgetShareStatus;
use App\Models\BudgetShare;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupDuplicateBudgetShares extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'budget:cleanup-shares';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpa compartilhamentos duplicados (mesmo orçamento e e-mail), mantendo apenas o mais recente ativo.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando limpeza de compartilhamentos duplicados...');

        // Busca grupos de compartilhamentos duplicados ativos (sem escopo global de tenant)
        $duplicates = BudgetShare::withoutGlobalScopes()
            ->where('is_active', true)
            ->select('budget_id', 'recipient_email', DB::raw('count(*) as total'))
            ->groupBy('budget_id', 'recipient_email')
            ->having('total', '>', 1)
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info('Nenhum compartilhamento duplicado encontrado.');

            return 0;
        }

        $totalCleaned = 0;

        foreach ($duplicates as $duplicate) {
            // Busca todos os compartilhamentos ativos deste grupo, ordenados do mais novo para o mais antigo
            $shares = BudgetShare::withoutGlobalScopes()
                ->where('budget_id', $duplicate->budget_id)
                ->where('recipient_email', $duplicate->recipient_email)
                ->where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->get();

            // O primeiro (index 0) é o mais recente, mantemos ele.
            // Os outros (index 1 em diante) serão desativados.
            $toDeactivate = $shares->slice(1);

            foreach ($toDeactivate as $share) {
                $share->update([
                    'is_active' => false,
                    'status' => BudgetShareStatus::EXPIRED->value,
                ]);
                $totalCleaned++;
            }
        }

        $this->info("Limpeza concluída! {$totalCleaned} compartilhamentos antigos foram desativados.");

        return 0;
    }
}
