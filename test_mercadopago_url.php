<?php

// Test script to check Mercado Pago OAuth URL generation
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = new \App\Services\Infrastructure\MercadoPagoOAuthService();
$state = '1:' . time();
$authUrl = $service->getAuthorizationUrl($state);

echo "Generated Authorization URL:\n";
echo $authUrl . "\n\n";

echo "Configuration Check:\n";
echo "Client ID: " . config('services.mercadopago.client_id') . "\n";
echo "Redirect URI: " . config('services.mercadopago.redirect_uri') . "\n";
echo "State: " . $state . "\n";