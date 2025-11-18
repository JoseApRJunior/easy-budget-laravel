<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Service;
use App\Models\User;
use App\Services\Domain\ServiceService;

// Configurar a conexão com o banco de dados
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== RELATIONSHIP LOADING DEBUG ===" . PHP_EOL . PHP_EOL;

// Simular o usuário autenticado (User ID 3)
$user3 = User::find(3);
auth()->login($user3);
echo "✓ Logged in as User 3" . PHP_EOL . PHP_EOL;

// Testar carregamento de relacionamentos passo a passo
$service = Service::withoutGlobalScopes()->where('code', 'ORC-20251112-0003-S003')->first();

if (!$service) {
    echo "✗ Service not found" . PHP_EOL;
    exit;
}

echo "Service found: ID=" . $service->id . ", Tenant=" . $service->tenant_id . PHP_EOL . PHP_EOL;

// Testar cada relacionamento individualmente
$relationships = [
    'budget',
    'budget.customer',
    'budget.customer.commonData',
    'category',
    'serviceItems',
    'serviceItems.product',
    'serviceStatus',
    'schedules'
];

foreach ($relationships as $relation) {
    echo "Testing relationship: $relation" . PHP_EOL;
    try {
        $testService = Service::withoutGlobalScopes()->where('code', 'ORC-20251112-0003-S003')->first();
        $testService->load($relation);
        echo "  ✓ Success - Data loaded" . PHP_EOL;
        
        // Mostrar alguns dados para debug
        if ($relation === 'budget' && $testService->budget) {
            echo "    Budget ID: " . $testService->budget->id . PHP_EOL;
        }
        if ($relation === 'category' && $testService->category) {
            echo "    Category ID: " . $testService->category->id . PHP_EOL;
        }
        
    } catch (\Exception $e) {
        echo "  ✗ Error: " . $e->getMessage() . PHP_EOL;
        echo "    File: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
    }
    echo PHP_EOL;
}

// Testar ServiceService com relacionamentos mínimos
echo "Testing ServiceService with minimal relationships..." . PHP_EOL;
try {
    $serviceService = app(ServiceService::class);
    $result = $serviceService->findByCode('ORC-20251112-0003-S003', ['category']);
    
    echo "  Success: " . ($result->isSuccess() ? 'true' : 'false') . PHP_EOL;
    echo "  Message: " . $result->getMessage() . PHP_EOL;
    
    if ($result->isSuccess()) {
        $service = $result->getData();
        echo "  Service ID: " . $service->id . PHP_EOL;
        echo "  Service loaded with category: " . ($service->category ? 'yes' : 'no') . PHP_EOL;
    }
} catch (\Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . PHP_EOL;
    echo "    File: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
}

echo PHP_EOL . "=== END DEBUG ===" . PHP_EOL;