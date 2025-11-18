<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Foundation\Application;
use App\Models\Service;
use App\Models\User;

// Inicializar a aplicação Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DEBUG TENANT ACCESS ===" . PHP_EOL . PHP_EOL;

// Verificar usuário autenticado
if (auth()->check()) {
    $user = auth()->user();
    echo "✓ User is authenticated" . PHP_EOL;
    echo "User ID: " . $user->id . PHP_EOL;
    echo "User Tenant ID: " . ($user->tenant_id ?? 'null') . PHP_EOL;
    echo "User Name: " . $user->name . PHP_EOL;
    echo "User Company: " . ($user->company_name ?? 'null') . PHP_EOL;
    echo "User Email: " . $user->email . PHP_EOL;
} else {
    echo "✗ User is NOT authenticated" . PHP_EOL;
}

echo PHP_EOL . "=== SERVICE CHECK ===" . PHP_EOL . PHP_EOL;

// Buscar o serviço sem restrições de tenant
$service = Service::withoutGlobalScopes()->where('code', 'ORC-20251112-0003-S003')->first();

if ($service) {
    echo "✓ Service found" . PHP_EOL;
    echo "Service ID: " . $service->id . PHP_EOL;
    echo "Service Tenant ID: " . $service->tenant_id . PHP_EOL;
    echo "Service Code: " . $service->code . PHP_EOL;
    echo "Service Status: " . ($service->status->value ?? 'null') . PHP_EOL;
    echo "Service Description: " . ($service->description ?? 'null') . PHP_EOL;

    // Verificar tenant do serviço
    if ($service->tenant) {
        echo "Service Tenant Name: " . $service->tenant->name . PHP_EOL;
        echo "Service Tenant CNPJ: " . $service->tenant->cnpj . PHP_EOL;
    }

} else {
    echo "✗ Service NOT found" . PHP_EOL;
}

echo PHP_EOL . "=== TENANT RELATIONSHIPS ===" . PHP_EOL . PHP_EOL;

if (auth()->check() && $service) {
    $user = auth()->user();

    echo "User Tenant ID: " . ($user->tenant_id ?? 'null') . PHP_EOL;
    echo "Service Tenant ID: " . $service->tenant_id . PHP_EOL;

    if ($user->tenant_id === $service->tenant_id) {
        echo "✓ User and Service are in the SAME tenant" . PHP_EOL;
    } else {
        echo "✗ User and Service are in DIFFERENT tenants" . PHP_EOL;
        echo "This explains the 404 error!" . PHP_EOL;
    }

    // Verificar se o usuário tem acesso ao tenant do serviço
    if ($user->tenant) {
        echo PHP_EOL . "User Tenant Details:" . PHP_EOL;
        echo "User Tenant Name: " . $user->tenant->name . PHP_EOL;
        echo "User Tenant CNPJ: " . $user->tenant->cnpj . PHP_EOL;
    }
}

echo PHP_EOL . "=== SOLUTION OPTIONS ===" . PHP_EOL . PHP_EOL;

if (auth()->check() && $service) {
    $user = auth()->user();

    if ($user->tenant_id !== $service->tenant_id) {
        echo "To fix this issue, you have several options:" . PHP_EOL;
        echo "1. Change the service's tenant_id to match the user's tenant" . PHP_EOL;
        echo "2. Change the user's tenant_id to match the service's tenant" . PHP_EOL;
        echo "3. Create a new service in the user's tenant with the same code" . PHP_EOL;
        echo "4. Implement cross-tenant access if business rules allow it" . PHP_EOL;
    }
}

echo PHP_EOL . "=== END DEBUG ===" . PHP_EOL;
