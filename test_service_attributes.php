<?php

require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Service;

$service = Service::where('code', 'ORC-20251112-0003-S003')->first();

if ($service) {
    echo "Service attributes:\n";
    foreach ($service->getAttributes() as $key => $value) {
        echo "  $key: $value\n";
    }
    
    echo "\nService relationships:\n";
    $service->load(['budget.customer']);
    
    echo "Budget: " . ($service->budget ? 'Yes' : 'No') . "\n";
    if ($service->budget) {
        echo "Budget attributes:\n";
        foreach ($service->budget->getAttributes() as $key => $value) {
            echo "  $key: $value\n";
        }
    }
    
    echo "\nCustomer: " . ($service->budget->customer ? 'Yes' : 'No') . "\n";
    if ($service->budget->customer) {
        echo "Customer attributes:\n";
        foreach ($service->budget->customer->getAttributes() as $key => $value) {
            echo "  $key: $value\n";
        }
    }
}