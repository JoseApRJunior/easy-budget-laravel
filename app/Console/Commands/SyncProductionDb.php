<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncProductionDb extends Command
{
    // O comando que você digitará no terminal
    protected $signature = 'db:sync-prod';
    protected $description = 'Clona o banco de produção para o ambiente de staging';

    public function handle()
    {
        if (config('app.env') === 'production') {
            $this->error('ERRO: Você não pode rodar este comando na Produção!');
            return;
        }

        $this->info('Iniciando sincronização...');

        // 1. Configurações (Ajuste com os nomes dos seus bancos)
        $prodDb = 'easybudget';
        $devDb  = 'easybudget-de';
        $user   = config('database.connections.mysql.username');
        $pass   = config('database.connections.mysql.password');

        // 2. Dump e Restore (Executa comandos do sistema)
        $command = "mysqldump -u {$user} -p'{$pass}' {$prodDb} | mysql -u {$user} -p'{$pass}' {$devDb}";

        system($command);

        $this->info('Banco de dados sincronizado com sucesso!');

        // 3. Opcional: Sanitização de dados
        // DB::table('users')->update(['email' => DB::raw("CONCAT(id, '@easybudget.test')")]);
        // $this->info('Dados sensíveis ocultados.');
    }
}
