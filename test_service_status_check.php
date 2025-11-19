<?php

require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Service;

// Test the service show page directly
$service = Service::where('code', 'ORC-20251112-0003-S003')->first();

if ($service) {
    echo "Service found: " . $service->code . "\n";
    echo "Status: " . $service->status->value . "\n";
    echo "Total: " . $service->total . "\n";
    echo "Items count: " . $service->serviceItems()->count() . "\n";
    
    // Check if there are existing invoices
    $existingInvoices = $service->invoices()->count();
    echo "Existing invoices: " . $existingInvoices . "\n";
    
    // Check if manual invoice creation should be allowed
    $canCreateManual = $service->status->value === 'COMPLETED' || ($service->status->value === 'IN_PROGRESS' && $service->serviceItems()->count() > 0);
    echo "Can create manual invoice: " . ($canCreateManual ? 'Yes' : 'No') . "\n";
    
} else {
    echo "Service not found!\n";
}