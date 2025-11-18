<?php

require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Service;
use App\Enums\ServiceStatus;

// Force log to be written
\Log::info('=== TESTING AUTOMATIC INVOICE GENERATION ===');
\Log::info('Starting test at: ' . now()->toDateTimeString());

$service = Service::where('code', 'ORC-20251112-0003-S003')->first();

if ($service) {
    \Log::info('Service found', [
        'service_id' => $service->id,
        'service_code' => $service->code,
        'current_status' => $service->status->value,
        'tenant_id' => $service->tenant_id
    ]);
    
    // Change status to something else first
    $service->status = ServiceStatus::IN_PROGRESS;
    $service->save();
    \Log::info('Changed to IN_PROGRESS');
    
    // Now change back to COMPLETED to trigger observer
    \Log::info('Changing to COMPLETED...');
    $service->status = ServiceStatus::COMPLETED;
    $service->save();
    \Log::info('Changed to COMPLETED - observer should have triggered');
    
    // Check invoices again
    $invoiceCount = $service->invoices()->count();
    \Log::info('Final invoice count: ' . $invoiceCount);
    
} else {
    \Log::error('Service not found!');
}

\Log::info('=== END TEST ===');

echo "Test completed. Check logs for details.\n";