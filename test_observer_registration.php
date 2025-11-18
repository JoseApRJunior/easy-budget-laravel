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

// Test manual trigger
echo "\n=== Testing Manual Event Trigger ===\n";
$service = Service::where('code', 'ORC-20251112-0003-S003')->first();

if ($service) {
    echo "Service found: " . $service->code . "\n";
    echo "Current status: " . $service->status->value . "\n";
    
    // Manually trigger the updated event
    echo "Manually triggering updated event...\n";
    $service->fireModelEvent('updated', false);
    
    echo "Event triggered. Check logs.\n";
} else {
    echo "Service not found!\n";
}