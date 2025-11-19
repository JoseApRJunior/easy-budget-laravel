<?php

require_once __DIR__.'/vendor/autoload.php';

use App\Models\Service;
use App\Services\Domain\InvoiceService;

$service = Service::where('code', 'ORC-20251112-0003-S003')->first();

if ($service) {
    echo "Service found: " . $service->code . "\n";
    echo "Status: " . $service->status->value . "\n";
    echo "Budget exists: " . ($service->budget ? 'Yes' : 'No') . "\n";
    
    if ($service->budget) {
        echo "Customer exists: " . ($service->budget->customer ? 'Yes' : 'No') . "\n";
        if ($service->budget->customer) {
            echo "Customer ID: " . $service->budget->customer->id . "\n";
            echo "Customer name: " . $service->budget->customer->name . "\n";
        }
    }
    
    // Test invoice creation
    echo "\nTesting invoice creation...\n";
    $invoiceService = app(InvoiceService::class);
    $result = $invoiceService->createInvoiceFromService('ORC-20251112-0003-S003', ['is_automatic' => false]);
    
    if ($result->isSuccess()) {
        echo "Invoice created successfully!\n";
        echo "Invoice code: " . $result->getData()->code . "\n";
    } else {
        echo "Failed to create invoice: " . $result->getMessage() . "\n";
    }
} else {
    echo "Service not found\n";
}