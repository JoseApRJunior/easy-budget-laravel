<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Service;
use App\Models\User;
use App\Models\Tenant;

// Configurar a conexão com o banco de dados
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DEBUG SERVICE ACCESS ISSUE ===" . PHP_EOL . PHP_EOL;

// Buscar o serviço específico
echo "Searching for service ORC-20251112-0003-S003..." . PHP_EOL;
$service = Service::withoutGlobalScopes()->where('code', 'ORC-20251112-0003-S003')->first();

if ($service) {
    echo "✓ Service found:" . PHP_EOL;
    echo "  ID: " . $service->id . PHP_EOL;
    echo "  Code: " . $service->code . PHP_EOL;
    echo "  Tenant ID: " . $service->tenant_id . PHP_EOL;
    echo "  Status: " . ($service->status->value ?? 'null') . PHP_EOL;
    echo "  Description: " . ($service->description ?? 'null') . PHP_EOL;
    
    // Buscar informações do tenant do serviço
    $serviceTenant = Tenant::find($service->tenant_id);
    if ($serviceTenant) {
        echo PHP_EOL . "Service Tenant:" . PHP_EOL;
        echo "  ID: " . $serviceTenant->id . PHP_EOL;
        echo "  Name: " . $serviceTenant->name . PHP_EOL;
        echo "  CNPJ: " . $serviceTenant->cnpj . PHP_EOL;
    }
    
} else {
    echo "✗ Service NOT found!" . PHP_EOL;
}

// Buscar todos os tenants
echo PHP_EOL . "=== ALL TENANTS ===" . PHP_EOL;
$tenants = Tenant::all();
foreach ($tenants as $tenant) {
    echo "ID: " . $tenant->id . ", Name: " . $tenant->name . ", CNPJ: " . $tenant->cnpj . PHP_EOL;
}

// Buscar todos os usuários e seus tenants
echo PHP_EOL . "=== ALL USERS ===" . PHP_EOL;
$users = User::all();
foreach ($users as $user) {
    echo "ID: " . $user->id . ", Name: " . $user->name . ", Email: " . $user->email . ", Tenant ID: " . ($user->tenant_id ?? 'null') . PHP_EOL;
}

echo PHP_EOL . "=== ANALYSIS ===" . PHP_EOL;

if ($service) {
    echo "The service ORC-20251112-0003-S003 exists and belongs to Tenant ID: " . $service->tenant_id . PHP_EOL;
    
    // Verificar se existe algum usuário com acesso a este tenant
    $usersWithAccess = User::where('tenant_id', $service->tenant_id)->get();
    
    if ($usersWithAccess->count() > 0) {
        echo "Users with access to this tenant:" . PHP_EOL;
        foreach ($usersWithAccess as $user) {
            echo "  - ID: " . $user->id . ", Name: " . $user->name . ", Email: " . $user->email . PHP_EOL;
        }
    } else {
        echo "✗ No users found with access to Tenant ID " . $service->tenant_id . PHP_EOL;
    }
    
    echo PHP_EOL . "=== SOLUTION ===" . PHP_EOL;
    echo "To access this service, you need to:" . PHP_EOL;
    echo "1. Log in as one of the users listed above, OR" . PHP_EOL;
    echo "2. Change the service's tenant_id to match your current user's tenant" . PHP_EOL;
}

echo PHP_EOL . "=== END DEBUG ===" . PHP_EOL;