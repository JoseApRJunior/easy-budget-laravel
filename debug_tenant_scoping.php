<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Service;
use App\Models\User;
use App\Models\Tenant;

// Configurar a conexão com o banco de dados
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DETAILED TENANT SCOPING DEBUG ===" . PHP_EOL . PHP_EOL;

// Test 1: Verificar se o serviço existe sem nenhum scoping
echo "Test 1: Service without any scoping" . PHP_EOL;
$serviceNoScope = Service::withoutGlobalScopes()->where('code', 'ORC-20251112-0003-S003')->first();
if ($serviceNoScope) {
    echo "✓ Found: ID=" . $serviceNoScope->id . ", Tenant=" . $serviceNoScope->tenant_id . PHP_EOL;
} else {
    echo "✗ Not found" . PHP_EOL;
}

// Test 2: Verificar usuário atual (simulando autenticação)
echo PHP_EOL . "Test 2: Current user simulation" . PHP_EOL;
$user3 = User::find(3);
if ($user3) {
    echo "User 3: ID=" . $user3->id . ", Name=" . $user3->name . ", Tenant=" . ($user3->tenant_id ?? 'null') . PHP_EOL;
    
    // Simular autenticação como User 3
    auth()->login($user3);
    echo "✓ Logged in as User 3" . PHP_EOL;
    
    // Test 3: Verificar scoping com usuário autenticado
    echo PHP_EOL . "Test 3: Service query with authenticated user" . PHP_EOL;
    
    // Limpar cache de queries
    \Illuminate\Support\Facades\DB::flushQueryLog();
    \Illuminate\Support\Facades\DB::enableQueryLog();
    
    $serviceWithAuth = Service::where('code', 'ORC-20251112-0003-S003')->first();
    $queries = \Illuminate\Support\Facades\DB::getQueryLog();
    
    echo "Query executed: " . $queries[0]['query'] . PHP_EOL;
    echo "Bindings: " . json_encode($queries[0]['bindings']) . PHP_EOL;
    
    if ($serviceWithAuth) {
        echo "✓ Found: ID=" . $serviceWithAuth->id . ", Tenant=" . $serviceWithAuth->tenant_id . PHP_EOL;
    } else {
        echo "✗ Not found (this indicates tenant scoping is working)" . PHP_EOL;
    }
    
    // Test 4: Verificar com withoutGlobalScopes
    echo PHP_EOL . "Test 4: Service query with withoutGlobalScopes" . PHP_EOL;
    \Illuminate\Support\Facades\DB::flushQueryLog();
    $serviceWithoutScope = Service::withoutGlobalScopes()->where('code', 'ORC-20251112-0003-S003')->first();
    $queries2 = \Illuminate\Support\Facades\DB::getQueryLog();
    
    echo "Query executed: " . $queries2[0]['query'] . PHP_EOL;
    echo "Bindings: " . json_encode($queries2[0]['bindings']) . PHP_EOL;
    
    if ($serviceWithoutScope) {
        echo "✓ Found: ID=" . $serviceWithoutScope->id . ", Tenant=" . $serviceWithoutScope->tenant_id . PHP_EOL;
    } else {
        echo "✗ Not found" . PHP_EOL;
    }
    
    // Test 5: Verificar ServiceService
    echo PHP_EOL . "Test 5: ServiceService test" . PHP_EOL;
    $serviceService = app(\App\Services\Domain\ServiceService::class);
    $result = $serviceService->findByCode('ORC-20251112-0003-S003');
    
    echo "ServiceService result:" . PHP_EOL;
    echo "  Success: " . ($result->isSuccess() ? 'true' : 'false') . PHP_EOL;
    echo "  Message: " . $result->getMessage() . PHP_EOL;
    echo "  Has data: " . ($result->hasData() ? 'true' : 'false') . PHP_EOL;
    
    if ($result->isSuccess() && $result->hasData()) {
        $service = $result->getData();
        echo "  Service ID: " . $service->id . PHP_EOL;
        echo "  Service Tenant: " . $service->tenant_id . PHP_EOL;
    }
    
} else {
    echo "✗ User 3 not found" . PHP_EOL;
}

echo PHP_EOL . "=== END DEBUG ===" . PHP_EOL;