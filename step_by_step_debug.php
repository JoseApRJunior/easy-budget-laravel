<?php

require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Service;
use App\Services\Domain\InvoiceService;

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$service = Service::where('code', 'ORC-20251112-0003-S003')->first();

if ($service) {
    echo "=== Testing InvoiceService Manually ===\n";
    echo "Service: " . $service->code . "\n";
    
    // Load relationships
    $service->load(['budget.customer', 'serviceItems.product']);
    
    echo "Budget: " . ($service->budget ? 'Yes' : 'No') . "\n";
    echo "Customer: " . ($service->budget->customer ? 'Yes' : 'No') . "\n";
    echo "Items: " . $service->serviceItems->count() . "\n";
    
    // Test each step manually
    try {
        echo "\n--- Step 1: Check Service ---\n";
        if (!$service) {
            throw new Exception("Service not found");
        }
        echo "Service found: " . $service->id . "\n";
        
        echo "\n--- Step 2: Check Budget ---\n";
        if (!$service->budget) {
            throw new Exception("Budget not found");
        }
        echo "Budget found: " . $service->budget->id . "\n";
        
        echo "\n--- Step 3: Check Customer ---\n";
        if (!$service->budget->customer) {
            throw new Exception("Customer not found");
        }
        echo "Customer found: " . $service->budget->customer->id . "\n";
        
        echo "\n--- Step 4: Check Service Items ---\n";
        $serviceItems = $service->serviceItems;
        echo "Service items count: " . $serviceItems->count() . "\n";
        
        echo "\n--- Step 5: Test ServiceItems Loop ---\n";
        foreach ($serviceItems as $item) {
            echo "Item ID: " . $item->id . ", Product ID: " . ($item->product_id ?? 'null') . "\n";
            if ($item->product) {
                echo "  Product name: " . $item->product->name . "\n";
            }
        }
        
        echo "\n--- Step 6: Test InvoiceService Method ---\n";
        $invoiceService = app(InvoiceService::class);
        
        // Test the method directly
        echo "Calling generateInvoiceDataFromService...\n";
        $result = $invoiceService->generateInvoiceDataFromService($service->code);
        
        if ($result->isSuccess()) {
            echo "SUCCESS!\n";
            $data = $result->getData();
            echo "Total: " . $data['total'] . "\n";
        } else {
            echo "ERROR: " . $result->getMessage() . "\n";
        }
        
    } catch (\Exception $e) {
        echo "EXCEPTION CAUGHT:\n";
        echo "Message: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        echo "Trace:\n";
        
        $trace = $e->getTrace();
        foreach ($trace as $i => $frame) {
            if (isset($frame['file'])) {
                echo "  #$i " . $frame['file'] . ":" . ($frame['line'] ?? '?') . " - " . ($frame['function'] ?? '?') . "()\n";
                if (strpos($frame['file'], 'InvoiceService.php') !== false) {
                    echo "  *** InvoiceService error location ***\n";
                }
            }
        }
    }
    
} else {
    echo "Service not found!\n";
}