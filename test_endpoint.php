<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;

// Simulate the QR code generation request
$request = Request::create('/provider/qrcode/generate', 'POST', [
    'text' => 'http://localhost:8000/provider/invoices/create',
    'size' => 180,
    '_token' => csrf_token()
]);

echo "Testing QR Code Generation Endpoint...\n";
echo "CSRF Token: " . csrf_token() . "\n";

try {
    $response = app()->handle($request);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Response Content: " . $response->getContent() . "\n";
    
    if ($response->getStatusCode() === 200) {
        $data = json_decode($response->getContent(), true);
        if (isset($data['success']) && $data['success']) {
            echo "SUCCESS: QR Code generated successfully!\n";
            echo "QR Code length: " . strlen($data['data']['qr_code']) . " characters\n";
        } else {
            echo "ERROR: " . ($data['message'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "ERROR: HTTP " . $response->getStatusCode() . "\n";
    }
    
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}