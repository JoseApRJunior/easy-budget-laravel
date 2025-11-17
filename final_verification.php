<?php

// Final verification after MCP activation
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== POST-MCP ACTIVATION VERIFICATION ===\n\n";

// 1. Verify current configuration
echo "1. CURRENT CONFIGURATION:\n";
echo "   Client ID: " . config('services.mercadopago.client_id') . "\n";
echo "   Redirect URI: " . config('services.mercadopago.redirect_uri') . "\n";
echo "   Access Token: " . substr(config('services.mercadopago.access_token'), 0, 20) . "...\n";
echo "   Webhook Secret: " . substr(config('services.mercadopago.webhook_secret'), 0, 10) . "...\n";
echo "   App URL: " . config('app.url') . "\n\n";

// 2. Test Brazilian OAuth URL
echo "2. BRAZILIAN OAUTH URL:\n";
$service = new \App\Services\Infrastructure\MercadoPagoOAuthService();
$state = '3:' . time();
$authUrl = $service->getAuthorizationUrl($state);
echo "   Generated URL: " . $authUrl . "\n";
echo "   Domain: " . (strpos($authUrl, '.com.br') !== false ? '✅ Brazilian (.com.br)' : '❌ Not Brazilian') . "\n";
echo "   Client ID: " . (strpos($authUrl, '8033813323717594') !== false ? '✅ Correct' : '❌ Wrong') . "\n";
echo "   Redirect URI: " . (strpos($authUrl, 'dev.easybudget.net.br') !== false ? '✅ Correct' : '❌ Wrong') . "\n\n";

// 3. Check system status
echo "3. SYSTEM STATUS:\n";
echo "   Environment: " . config('app.env') . "\n";
echo "   Debug Mode: " . (config('app.debug') ? '✅ Enabled' : '❌ Disabled') . "\n";
echo "   Timezone: " . config('app.timezone') . "\n\n";

// 4. Test database connection
echo "4. DATABASE STATUS:\n";
try {
    $user = \App\Models\User::first();
    if ($user) {
        echo "   ✅ Database connection working\n";
        echo "   ✅ Users table accessible\n";
    } else {
        echo "   ⚠️  No users found in database\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n=== READY FOR FINAL TEST ===\n";
echo "🎯 Next steps:\n";
echo "   1. Access: https://dev.easybudget.net.br/settings\n";
echo "   2. Click: Integrações tab\n";
echo "   3. Click: Conectar Mercado Pago\n";
echo "   4. Login with: TESTUSER838691964 / XCqjL4QWaO\n";
echo "   5. Authorize the application\n\n";
echo "💡 If it still fails, check:\n";
echo "   - App is approved in Mercado Pago panel\n";
echo "   - Redirect URI is configured correctly\n";
echo "   - User TESTUSER838691964 exists and is active\n\n";

echo "=== VERIFICATION COMPLETE ===\n";