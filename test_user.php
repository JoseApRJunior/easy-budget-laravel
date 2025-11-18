<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Get current user (assuming we're testing with user ID 1)
try {
    $user = App\Models\User::find(1);
    if ($user) {
        echo "User ID: " . $user->id . PHP_EOL;
        echo "User name: " . $user->name . PHP_EOL;
        echo "User tenant ID: " . $user->tenant_id . PHP_EOL;
        
        // Check budgets for this tenant
        $budgets = App\Models\Budget::where('tenant_id', $user->tenant_id)->get();
        echo "Budgets for tenant " . $user->tenant_id . ": " . $budgets->count() . PHP_EOL;
        
        foreach ($budgets as $budget) {
            echo "  - Budget ID: " . $budget->id . ", Code: " . $budget->code . ", Status: " . $budget->status . PHP_EOL;
        }
    } else {
        echo "User not found." . PHP_EOL;
    }
} catch (Exception $e) {
    echo "Error accessing user: " . $e->getMessage() . PHP_EOL;
}