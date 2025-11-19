<?php

require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Service;
use App\Services\Domain\InvoiceService;

$service = Service::where('code', 'ORC-20251112-0003-S003')->first();

if ($service) {
    echo "Testing InvoiceService step by step...\n";
    echo "Service: " . $service->code . "\n";
    
    // Load relationships
    $service->load(['budget.customer', 'serviceItems.product']);
    
    echo "Budget loaded: " . ($service->budget ? 'Yes' : 'No') . "\n";
    echo "Customer loaded: " . ($service->budget->customer ? 'Yes' : 'No') . "\n";
    echo "Service items count: " . $service->serviceItems->count() . "\n";
    
    if ($service->budget && $service->budget->customer) {
        echo "Customer ID: " . $service->budget->customer->id . "\n";
        echo "Customer Name: " . $service->budget->customer->name . "\n";
    }
    
    // Test the specific method that's failing
    $invoiceService = app(InvoiceService::class);
    
    try {
        echo "\nTesting generateInvoiceDataFromService...\n";
        $result = $invoiceService->generateInvoiceDataFromService($service->code);
        
        if ($result->isSuccess()) {
            echo "SUCCESS: Data generated\n";
            $data = $result->getData();
            echo "Total: " . $data['total'] . "\n";
        } else {
            echo "ERROR: " . $result->getMessage() . "\n";
        }
        
    } catch (\Exception $e) {
        echo "EXCEPTION: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        
        // Get the full trace to find the exact location
        $trace = $e->getTrace();
        foreach ($trace as $i => $frame) {
            if (isset($frame['file']) && strpos($frame['file'], 'InvoiceService.php') !== false) {
                echo "InvoiceService trace at index $i:\n";
                echo "  File: " . $frame['file'] . ":" . $frame['line'] . "\n";
                echo "  Function: " . $frame['function'] . "\n";
                if (isset($frame['args'])) {
                    echo "  Args: " . json_encode(array_map(function($arg) {
                        return is_object($arg) ? get_class($arg) : (is_array($arg) ? 'array' : $arg);
                    }, $frame['args'])) . "\n";
                }
                break;
            }
        }
    }
    
} else {
    echo "Service not found!\n";
}