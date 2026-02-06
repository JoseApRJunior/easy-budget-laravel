<?php
declare(strict_types=1);

/**
 * Script de Verificação de Fila (Queue) - Easy Budget
 * Este script verifica se há jobs pendentes ou falhos no banco de dados.
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "--- DIAGNÓSTICO DE FILA (QUEUE) ---\n";
echo "Data: " . date('Y-m-d H:i:s') . "\n";
echo "Ambiente: " . config('app.env') . "\n";
echo "Driver de Fila: " . config('queue.default') . "\n\n";

try {
    if (Schema::hasTable('jobs')) {
        $pendingJobs = DB::table('jobs')->count();
        echo "Jobs Pendentes: " . $pendingJobs . "\n";
        
        if ($pendingJobs > 0) {
            $oldestJob = DB::table('jobs')->orderBy('created_at', 'asc')->first();
            echo "Job mais antigo criado em: " . date('Y-m-d H:i:s', $oldestJob->created_at) . "\n";
        }
    } else {
        echo "AVISO: Tabela 'jobs' não encontrada. Verifique se as migrações foram rodadas.\n";
    }

    if (Schema::hasTable('failed_jobs')) {
        $failedJobs = DB::table('failed_jobs')->count();
        echo "Jobs Falhos: " . $failedJobs . "\n";
        
        if ($failedJobs > 0) {
            $lastFailed = DB::table('failed_jobs')->orderBy('failed_at', 'desc')->first();
            echo "Última falha em: " . $lastFailed->failed_at . "\n";
            echo "Erro: " . substr($lastFailed->exception, 0, 200) . "...\n";
        }
    } else {
        echo "AVISO: Tabela 'failed_jobs' não encontrada.\n";
    }

    echo "\n--- RECOMENDAÇÃO ---\n";
    if (config('queue.default') === 'database' || config('queue.default') === 'redis') {
        echo "Para processar a fila agora, execute via SSH:\n";
        echo "php artisan queue:work --once\n";
    } else if (config('queue.default') === 'sync') {
        echo "A fila está configurada como 'sync'. Os e-mails deveriam ser enviados instantaneamente.\n";
    }

} catch (\Exception $e) {
    echo "ERRO NO DIAGNÓSTICO: " . $e->getMessage() . "\n";
}

echo "\n--- FIM DO DIAGNÓSTICO ---\n";
