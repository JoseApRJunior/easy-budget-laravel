<?php

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Service;

echo "=== Verificando Acesso do Usuário ===\n";

// Verificar usuário logado (ID 2 baseado nos logs)
$user = User::find(2);
if ($user) {
    echo "✅ Usuário encontrado:\n";
    echo "   ID: {$user->id}\n";
    echo "   Nome: {$user->name}\n";
    echo "   Email: {$user->email}\n";
    echo "   Tenant ID: {$user->tenant_id}\n";
    echo "   Role: " . ($user->role ?? 'N/A') . "\n";
} else {
    echo "❌ Usuário ID 2 não encontrado\n";
    exit;
}

echo "\n=== Verificando Serviço ORC-20251112-0003-S003 ===\n";

// Verificar o serviço sem tenant scoping
$service = Service::withoutGlobalScopes()->where('code', 'ORC-20251112-0003-S003')->first();

if ($service) {
    echo "✅ Serviço encontrado:\n";
    echo "   ID: {$service->id}\n";
    echo "   Código: {$service->code}\n";
    echo "   Tenant ID: {$service->tenant_id}\n";
    echo "   Descrição: {$service->description}\n";
    echo "   Status: {$service->status->value}\n";
    
    echo "\n=== Verificando Acesso Cruzado ===\n";
    echo "User Tenant: {$user->tenant_id}\n";
    echo "Service Tenant: {$service->tenant_id}\n";
    echo "Acesso permitido: " . ($user->tenant_id === $service->tenant_id ? '✅ SIM' : '❌ NÃO') . "\n";
    
} else {
    echo "❌ Serviço ORC-20251112-0003-S003 não encontrado\n";
}

echo "\n=== Verificando Todos os Serviços do Tenant do Usuário ===\n";
$services = Service::withoutGlobalScopes()
    ->where('tenant_id', $user->tenant_id)
    ->get();

echo "Total de serviços do tenant {$user->tenant_id}: {$services->count()}\n";
foreach ($services as $s) {
    echo "- {$s->code} (ID: {$s->id}, Status: {$s->status->value})\n";
}