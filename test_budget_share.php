<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Simulate the budget-share index method
try {
    $user = App\Models\User::find(1);
    if (!$user) {
        echo "User not found." . PHP_EOL;
        exit;
    }
    
    echo "Testing budget-share service for user: " . $user->name . " (tenant: " . $user->tenant_id . ")" . PHP_EOL;
    
    // Test the BudgetShareService list method
    $budgetShareService = app(App\Services\Domain\BudgetShareService::class);
    $result = $budgetShareService->list(['tenant_id' => $user->tenant_id]);
    
    echo "Service result success: " . ($result->isSuccess() ? 'Yes' : 'No') . PHP_EOL;
    echo "Service message: " . $result->getMessage() . PHP_EOL;
    
    $data = $result->getData();
    echo "Data type: " . gettype($data) . PHP_EOL;
    if (is_object($data)) {
        echo "Data class: " . get_class($data) . PHP_EOL;
        if (method_exists($data, 'count')) {
            echo "Data count: " . $data->count() . PHP_EOL;
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
    echo "File: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
    echo "Trace: " . $e->getTraceAsString() . PHP_EOL;
}