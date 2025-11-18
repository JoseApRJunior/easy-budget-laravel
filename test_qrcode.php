<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Infrastructure\QrCodeService;

$qrCodeService = app(QrCodeService::class);

echo "Testing QR Code Service...\n";

try {
    $testText = "http://localhost:8000/provider/invoices/create";
    $testSize = 180;

    echo "Generating QR code for: $testText\n";
    echo "Size: $testSize\n";

    $result = $qrCodeService->generateDataUri($testText, $testSize);

    if (empty($result)) {
        echo "ERROR: QR Code generation returned empty result\n";
    } else {
        echo "SUCCESS: QR Code generated successfully\n";
        echo "Result length: " . strlen($result) . " characters\n";
        echo "First 100 characters: " . substr($result, 0, 100) . "...\n";
    }

} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "Test completed.\n";
