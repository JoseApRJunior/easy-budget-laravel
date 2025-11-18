<?php

require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Service;
use App\Enums\ServiceStatus;

$service = Service::where('code', 'ORC-20251112-0003-S003')->first();

if ($service) {
    echo "Service found!\n";
    echo "Service ID: " . $service->id . "\n";
    echo "Service Code: " . $service->code . "\n";
    echo "Current Status: " . $service->status->value . "\n";
    echo "Is Completed: " . ($service->status->value === ServiceStatus::COMPLETED->value ? 'Yes' : 'No') . "\n";
    echo "Tenant ID: " . $service->tenant_id . "\n";

    // Check if there are any invoices
    $invoiceCount = $service->invoices()->count();
    echo "Number of invoices: " . $invoiceCount . "\n";

    // Check if status was changed recently
    if ($service->isDirty('status')) {
        echo "Status was changed recently\n";
    } else {
        echo "Status was not changed recently\n";
    }

} else {
    echo "Service not found!\n";
}
