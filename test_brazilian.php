<?php

// Test Brazilian domain for Mercado Pago
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== BRAZILIAN DOMAIN TEST ===\n\n";

$service = new \App\Services\Infrastructure\MercadoPagoOAuthService();
$state = '3:' . time();
$authUrl = $service->getAuthorizationUrl($state);

echo "Generated Brazilian URL:\n";
echo $authUrl . "\n\n";

// Check if it matches the expected pattern
if (strpos($authUrl, 'auth.mercadopago.com.br') !== false) {
    echo "✅ URL is using Brazilian domain (.com.br)\n";
} else {
    echo "❌ URL is not using Brazilian domain\n";
}

echo "\nExpected format:\n";
echo "https://auth.mercadopago.com.br/authorization?client_id=8033813323717594&response_type=code&platform_id=mp&redirect_uri=https%3A%2F%2Fdev.easybudget.net.br%2Fintegrations%2Fmercadopago%2Fcallback&state=3:TIMESTAMP\n";