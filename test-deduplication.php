<?php

require __DIR__.'/vendor/autoload.php';

// Inicializar Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Testar Carbon
use Illuminate\Support\Carbon;

echo "=== Testando Carbon ===\n";
$now = Carbon::now();
echo 'Carbon instance: '.get_class($now)."\n";
echo 'addMinutes exists: '.method_exists($now, 'addMinutes')."\n";
echo 'toISOString exists: '.method_exists($now, 'toISOString')."\n";
echo 'format exists: '.method_exists($now, 'format')."\n";
echo 'now()->addMinutes(30): '.$now->addMinutes(30)."\n";
echo "now()->format('Y-m-d H:i:s'): ".$now->format('Y-m-d H:i:s')."\n\n";

// Testar cache
use Illuminate\Support\Facades\Cache;

echo "=== Testando Cache ===\n";

// Limpar cache se existir
Cache::forget('email:test:deduplication');

// Testar Cache::add
$added = Cache::add('email:test:deduplication', true, Carbon::now()->addMinutes(1));
echo 'Cache::add() - Primeira tentativa: '.($added ? 'Sucesso' : 'Falha')."\n";

// Tentar adicionar novamente
$added2 = Cache::add('email:test:deduplication', true, Carbon::now()->addMinutes(1));
echo 'Cache::add() - Segunda tentativa: '.($added2 ? 'Sucesso' : 'Falha')."\n";

// Verificar se valor existe
echo 'Cache::has(): '.(Cache::has('email:test:deduplication') ? 'Sim' : 'Não')."\n";

// Esperar 2 segundos e verificar novamente
echo "\n=== Esperando 2 segundos para testar expiração ===\n";
sleep(2);
echo 'Cache::has() após 2 segundos: '.(Cache::has('email:test:deduplication') ? 'Sim' : 'Não')."\n";

// Limpar cache
Cache::forget('email:test:deduplication');
echo "\n=== Cache limpo ===\n";
