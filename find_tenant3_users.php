<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

try {
    echo "Buscando usuários do Tenant ID 3:\n";

    $users = User::where('tenant_id', 3)->get();

    if ($users->isEmpty()) {
        echo "Nenhum usuário encontrado no Tenant ID 3\n";
    } else {
        foreach ($users as $user) {
            echo "- ID: {$user->id}, Nome: {$user->name}, Email: {$user->email}, Tenant: {$user->tenant_id}\n";
        }
    }

    echo "\nBuscando todos os tenants:\n";
    $tenants = \App\Models\Tenant::all();
    foreach ($tenants as $tenant) {
        echo "- ID: {$tenant->id}, Nome: {$tenant->name}\n";
    }

} catch (\Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
