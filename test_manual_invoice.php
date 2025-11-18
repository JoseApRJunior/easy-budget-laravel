<?php

require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Service;
use App\Services\Domain\InvoiceService;
use Illuminate\Support\Facades\Log;

// Clear log file
file_put_contents(storage_path('logs/laravel.log'), '');

Log::info('=== STARTING MANUAL INVOICE TEST ===');

$service = Service::where('code', 'ORC-20251112-0003-S003')->first();

if ($service) {
    echo "Testing manual invoice creation for service: " . $service->code . "\n";
    echo "Current status: " . $service->status->value . "\n";
    echo "Tenant ID: " . $service->tenant_id . "\n";
    
    // Load budget and customer
    $service->load(['budget.customer']);
    echo "Budget loaded: " . ($service->budget ? 'Yes' : 'No') . "\n";
    echo "Customer loaded: " . ($service->budget->customer ? 'Yes' : 'No') . "\n";
    
    if ($service->budget && $service->budget->customer) {
        echo "Customer ID: " . $service->budget->customer->id . "\n";
        echo "Customer Name: " . $service->budget->customer->name . "\n";
    }
    
    // Simulate manual invoice creation
    $invoiceData = [
        'issue_date' => now()->format('Y-m-d'),
        'due_date' => now()->addDays(30)->format('Y-m-d'),
        'notes' => 'Fatura criada manualmente para teste',
        'is_automatic' => false,
    ];
    
    echo "\nCreating manual invoice...\n";
    Log::info('Creating manual invoice', ['service_code' => $service->code]);
    
    try {
        $invoiceService = app(InvoiceService::class);
        $result = $invoiceService->createInvoiceFromService($service->code, $invoiceData);
        
        if ($result->isSuccess()) {
            $invoice = $result->getData();
            echo "SUCCESS: Manual invoice created!\n";
            echo "Invoice ID: " . $invoice->id . "\n";
            echo "Invoice Code: " . $invoice->code . "\n";
            echo "Total Value: " . $invoice->total_value . "\n";
        } else {
            echo "ERROR: Failed to create manual invoice\n";
            echo "Error message: " . $result->getMessage() . "\n";
            Log::error('Failed to create manual invoice', [
                'error' => $result->getMessage()
            ]);
        }
    } catch (\Exception $e) {
        echo "EXCEPTION: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        Log::error('Exception creating manual invoice', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
    }
    
    // Check invoices again
    $invoiceCount = $service->invoices()->count();
    echo "\nTotal invoices for service: " . $invoiceCount . "\n";
    
} else {
    echo "Service not found!\n";
}

Log::info('=== END MANUAL INVOICE TEST ===');