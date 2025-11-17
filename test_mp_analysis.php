<?php

// Test script to analyze Mercado Pago OAuth flow
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== MERCADO PAGO INTEGRATION ANALYSIS ===\n\n";

// 1. Check configuration
echo "1. CONFIGURATION CHECK:\n";
echo "   Client ID: " . config('services.mercadopago.client_id') . "\n";
echo "   Redirect URI: " . config('services.mercadopago.redirect_uri') . "\n";
echo "   Access Token: " . substr(config('services.mercadopago.access_token'), 0, 20) . "...\n";
echo "   Webhook Secret: " . substr(config('services.mercadopago.webhook_secret'), 0, 10) . "...\n\n";

// 2. Test OAuth URL generation
$service = new \App\Services\Infrastructure\MercadoPagoOAuthService();
$state = '1:' . time();
$authUrl = $service->getAuthorizationUrl($state);

echo "2. OAUTH URL GENERATION:\n";
echo "   Generated URL: " . $authUrl . "\n";
echo "   State: " . $state . "\n\n";

// 3. Parse URL components
$parsedUrl = parse_url($authUrl);
echo "3. URL COMPONENTS:\n";
echo "   Scheme: " . $parsedUrl['scheme'] . "\n";
echo "   Host: " . $parsedUrl['host'] . "\n";
echo "   Path: " . $parsedUrl['path'] . "\n";
parse_str($parsedUrl['query'], $queryParams);
echo "   Query Parameters:\n";
foreach ($queryParams as $key => $value) {
    echo "     - $key: $value\n";
}
echo "\n";

// 4. Check routes
echo "4. ROUTES CHECK:\n";
$routes = [
    'integrations.mercadopago.index' => '/integrations/mercadopago',
    'integrations.mercadopago.callback' => '/integrations/mercadopago/callback',
    'webhooks.mercadopago.invoices' => '/webhooks/mercadopago/invoices',
    'webhooks.mercadopago.plans' => '/webhooks/mercadopago/plans',
];

foreach ($routes as $name => $path) {
    try {
        $url = route($name);
        echo "   $name: $url ✓\n";
    } catch (\Exception $e) {
        echo "   $name: ERROR - " . $e->getMessage() . "\n";
    }
}
echo "\n";

// 5. Test database connection
echo "5. DATABASE CHECK:\n";
try {
    $credentials = \App\Models\ProviderCredential::where('payment_gateway', 'mercadopago')->first();
    if ($credentials) {
        echo "   Found credentials in database ✓\n";
        echo "   Tenant ID: " . $credentials->tenant_id . "\n";
        echo "   User ID Gateway: " . $credentials->user_id_gateway . "\n";
        echo "   Created: " . $credentials->created_at . "\n";
    } else {
        echo "   No credentials found in database\n";
    }
} catch (\Exception $e) {
    echo "   Database error: " . $e->getMessage() . "\n";
}
echo "\n";

echo "=== ANALYSIS COMPLETE ===\n";