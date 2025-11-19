<?php

require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Service;
use App\Services\Domain\InvoiceService;

$service = Service::where('code', 'ORC-20251112-0003-S003')->first();

if ($service) {
    echo "Testing generateInvoiceDataFromService method...\n";
    echo "Service: " . $service->code . "\n";
    
    $invoiceService = app(InvoiceService::class);
    
    try {
        $result = $invoiceService->generateInvoiceDataFromService($service->code);
        
        if ($result->isSuccess()) {
            echo "SUCCESS: Invoice data generated!\n";
            $data = $result->getData();
            echo "Customer name: " . $data['customer_name'] . "\n";
            echo "Total: " . $data['total'] . "\n";
            echo "Items count: " . count($data['items']) . "\n";
        } else {
            echo "ERROR: " . $result->getMessage() . "\n";
        }
        
    } catch (\Exception $e) {
        echo "EXCEPTION in generateInvoiceDataFromService:\n";
        echo "Message: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        echo "Trace:\n" . $e->getTraceAsString() . "\n";
    }
    
} else {
    echo "Service not found!\n";
}