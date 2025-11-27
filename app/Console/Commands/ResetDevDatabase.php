<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ResetDevDatabase extends Command
{
    protected $signature = 'easybudget:reset-dev';

    protected $description = 'Limpa e reseta o banco com dados de desenvolvimento';

    public function handle(): void
    {
        $this->info('🔄 Executando reset completo do banco de dados para desenvolvimento...');
        $this->call('db:seed', ['--class' => 'DatabaseCleanerAndSeeder']);
        $this->info('✅ Banco resetado com sucesso!');
    }
}
