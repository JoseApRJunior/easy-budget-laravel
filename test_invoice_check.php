<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Service;

$service = Service::where('code', 'ORC-20251112-0003-S003')->first();

if ($service) {
    echo "Serviço encontrado:\n";
    echo "ID: " . $service->id . "\n";
    echo "Código: " . $service->code . "\n";
    echo "Status: " . $service->status->value . "\n";
    echo "Cliente ID: " . $service->customer_id . "\n";
    echo "Total de faturas: " . $service->invoices()->count() . "\n";

    if ($service->invoices()->count() > 0) {
        echo "\nFaturas existentes:\n";
        foreach ($service->invoices as $invoice) {
            echo "- Código: " . $invoice->code . " | Status: " . $invoice->status . " | Total: R$ " . number_format($invoice->total_amount, 2, ',', '.') . "\n";
        }
    }
} else {
    echo "Serviço não encontrado\n";
}