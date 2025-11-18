<?php

require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Service;
use App\Enums\ServiceStatus;
use Illuminate\Support\Facades\Log;

// Clear logs to see only our test
file_put_contents(storage_path('logs/laravel.log'), '');

echo "=== Testing Observer Registration ===\n";

// Check if observer is registered
$observers = app('events')->getListeners('eloquent.updated: ' . Service::class);
echo "Number of observers for Service updated event: " . count($observers) . "\n";

// Test actual save to trigger observer
echo "\n=== Testing Actual Save Trigger ===\n";
$service = Service::where('code', 'ORC-20251112-0003-S003')->first();

if ($service) {
    echo "Service found: " . $service->code . "\n";
    echo "Current status: " . $service->status->value . "\n";
    
    // Change status to trigger observer
    echo "Changing status to trigger observer...\n";
    $service->status = ServiceStatus::IN_PROGRESS;
    $service->save();
    echo "Status changed to IN_PROGRESS\n";
    
    // Now change to COMPLETED
    echo "Changing to COMPLETED...\n";
    $service->status = ServiceStatus::COMPLETED;
    $service->save();
    echo "Status changed to COMPLETED\n";
    
} else {
    echo "Service not found!\n";
}

echo "\nTest completed. Check logs for observer activity.\n";