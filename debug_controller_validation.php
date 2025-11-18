<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Service;
use App\Models\User;
use App\Models\Tenant;
use App\Services\Domain\ServiceService;

// Configurar a conexão com o banco de dados
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== SERVICE CONTROLLER VALIDATION DEBUG ===" . PHP_EOL . PHP_EOL;

// Simular o usuário autenticado (User ID 3)
$user3 = User::find(3);
if (!$user3) {
    echo "✗ User 3 not found" . PHP_EOL;
    exit;
}

echo "User 3 details:" . PHP_EOL;
echo "  ID: " . $user3->id . PHP_EOL;
echo "  Name: " . $user3->name . PHP_EOL;
echo "  Email: " . $user3->email . PHP_EOL;
echo "  Tenant ID: " . ($user3->tenant_id ?? 'null') . PHP_EOL;

// Simular autenticação
auth()->login($user3);
echo "✓ Logged in as User 3" . PHP_EOL . PHP_EOL;

// Testar o ServiceService (igual ao controller)
echo "Testing ServiceService..." . PHP_EOL;
$serviceService = app(ServiceService::class);
$result = $serviceService->findByCode('ORC-20251112-0003-S003', [
    'budget.customer.commonData',
    'budget.customer.contacts',
    'category',
    'serviceItems.product',
    'serviceStatus',
    'schedules' => function ($q) {
        $q->latest()->limit(1);
    }
]);

echo "ServiceService result:" . PHP_EOL;
echo "  Success: " . ($result->isSuccess() ? 'true' : 'false') . PHP_EOL;
echo "  Message: " . $result->getMessage() . PHP_EOL;

if ($result->isSuccess()) {
    $service = $result->getData();
    echo "  Service ID: " . $service->id . PHP_EOL;
    echo "  Service Code: " . $service->code . PHP_EOL;
    echo "  Service Tenant ID: " . $service->tenant_id . PHP_EOL;
    echo "  Service Status: " . ($service->status->value ?? 'null') . PHP_EOL;
    
    // Simular a validação do controller
    echo PHP_EOL . "Controller validation test:" . PHP_EOL;
    $userTenantId = auth()->user()->tenant_id ?? null;
    echo "  User Tenant ID: " . ($userTenantId ?? 'null') . PHP_EOL;
    echo "  Service Tenant ID: " . $service->tenant_id . PHP_EOL;
    echo "  Types - User: " . gettype($userTenantId) . ", Service: " . gettype($service->tenant_id) . PHP_EOL;
    echo "  Values - User: " . var_export($userTenantId, true) . ", Service: " . var_export($service->tenant_id, true) . PHP_EOL;
    
    if ($service->tenant_id !== $userTenantId) {
        echo "  ✗ VALIDATION FAILED: Service tenant does not match user tenant" . PHP_EOL;
        echo "  This would cause a 404 error!" . PHP_EOL;
    } else {
        echo "  ✓ VALIDATION PASSED: Service tenant matches user tenant" . PHP_EOL;
        echo "  This should allow access to the service!" . PHP_EOL;
    }
} else {
    echo "  ✗ ServiceService failed to find the service" . PHP_EOL;
}

echo PHP_EOL . "=== END DEBUG ===" . PHP_EOL;