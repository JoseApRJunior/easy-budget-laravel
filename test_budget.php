<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $budget = App\Models\Budget::first();
    if ($budget) {
        echo "First budget ID: " . $budget->id . PHP_EOL;
        echo "Budget code: " . $budget->code . PHP_EOL;
        echo "Tenant ID: " . $budget->tenant_id . PHP_EOL;
    } else {
        echo "No budgets found in database." . PHP_EOL;
    }
} catch (Exception $e) {
    echo "Error accessing Budget model: " . $e->getMessage() . PHP_EOL;
}

try {
    $budgetCount = App\Models\Budget::count();
    echo "Total budgets: " . $budgetCount . PHP_EOL;
} catch (Exception $e) {
    echo "Error counting budgets: " . $e->getMessage() . PHP_EOL;
}