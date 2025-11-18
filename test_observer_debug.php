<?php

require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Service;
use App\Observers\ServiceObserver;
use Illuminate\Support\Facades\Log;

echo "=== Testing Observer Registration Debug ===\n";

// Get all observers for Service model
$observers = Service::getModelEvents();
echo "Model events: " . json_encode($observers) . "\n";

// Check if ServiceObserver is registered
$dispatcher = Service::getEventDispatcher();
$listeners = $dispatcher->getListeners('eloquent.updated: ' . Service::class);
echo "Number of listeners: " . count($listeners) . "\n";

foreach ($listeners as $listener) {
    echo "Listener type: " . gettype($listener) . "\n";
    if (is_array($listener)) {
        echo "Listener class: " . get_class($listener[0]) . "\n";
        echo "Listener method: " . $listener[1] . "\n";
    } elseif (is_object($listener)) {
        echo "Listener class: " . get_class($listener) . "\n";
    }
}

echo "\n=== Testing Service Update ===\n";
$service = Service::where('code', 'ORC-20251112-0003-S003')->first();

if ($service) {
    echo "Service found: " . $service->code . "\n";
    echo "Current status: " . $service->status->value . "\n";
    
    // Change status to IN_PROGRESS first
    echo "Changing to IN_PROGRESS...\n";
    $service->status = \App\Enums\ServiceStatus::IN_PROGRESS;
    $service->save();
    echo "Status changed to IN_PROGRESS\n";
    
    // Now change to COMPLETED to trigger observer
    echo "Changing to COMPLETED...\n";
    $service->status = \App\Enums\ServiceStatus::COMPLETED;
    $service->save();
    echo "Status changed to COMPLETED\n";
    
} else {
    echo "Service not found!\n";
}