<?php

require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Service;

$service = Service::where('code', 'ORC-20251112-0003-S003')->first();

if ($service) {
    echo "Service found: " . $service->code . "\n";
    echo "Service ID: " . $service->id . "\n";
    echo "Budget ID: " . $service->budget_id . "\n";
    
    // Load budget relationship
    $service->load('budget');
    echo "Budget loaded: " . ($service->budget ? 'Yes' : 'No') . "\n";
    
    if ($service->budget) {
        echo "Budget ID: " . $service->budget->id . "\n";
        
        // Load customer relationship
        $service->budget->load('customer');
        echo "Customer loaded: " . ($service->budget->customer ? 'Yes' : 'No') . "\n";
        
        if ($service->budget->customer) {
            echo "Customer ID: " . $service->budget->customer->id . "\n";
            echo "Customer Name: " . $service->budget->customer->name . "\n";
        }
    }
    
    // Check if service has direct customer_id
    echo "Direct customer_id: " . ($service->customer_id ?? 'null') . "\n";
    
} else {
    echo "Service not found!\n";
}