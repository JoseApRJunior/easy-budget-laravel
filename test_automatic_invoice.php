<?php

require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Service;
use App\Enums\ServiceStatus;
use Illuminate\Support\Facades\Log;

Log::info('Testing automatic invoice generation');

$service = Service::where('code', 'ORC-20251112-0003-S003')->first();

if ($service) {
    echo "Testing with service: " . $service->code . "\n";
    echo "Current status: " . $service->status->value . "\n";
    
    // Change status to something else first
    $service->status = ServiceStatus::IN_PROGRESS;
    $service->save();
    echo "Changed to IN_PROGRESS\n";
    
    // Now change back to COMPLETED to trigger observer
    echo "Changing to COMPLETED...\n";
    $service->status = ServiceStatus::COMPLETED;
    $service->save();
    echo "Changed to COMPLETED - observer should have triggered\n";
    
    // Check invoices again
    $invoiceCount = $service->invoices()->count();
    echo "Number of invoices after trigger: " . $invoiceCount . "\n";
    
} else {
    echo "Service not found!\n";
}