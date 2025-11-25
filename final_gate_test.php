<?php

require_once 'vendor/autoload.php';

$app    = require_once 'bootstrap/app.php';
$kernel = $app->make( Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "=== FINAL GATE TEST ===\n";

    $admin = App\Models\User::where( 'email', 'admin@easybudget.net.br' )->first();

    echo "1. User exists: " . ( $admin ? 'YES' : 'NO' ) . "\n";
    echo "2. User isAdmin(): " . ( $admin && $admin->isAdmin() ? 'YES' : 'NO' ) . "\n";
    echo "3. User hasPermission('manage-categories'): " . ( $admin && $admin->hasPermission( 'manage-categories' ) ? 'YES' : 'NO' ) . "\n";
    echo "4. Gate::check('manage-categories'): " . ( Illuminate\Support\Facades\Gate::check( 'manage-categories', $admin ) ? 'YES' : 'NO' ) . "\n";

    // Manual test of the gate logic
    if ( $admin ) {
        echo "5. Manual isAdmin() check: " . ( $admin->hasRole( 'admin' ) ? 'YES' : 'NO' ) . "\n";
        echo "6. Manual gate logic: " . ( $admin->isAdmin() ? 'PASS' : 'FAIL' ) . "\n";
    }

    echo "\n=== URL CHECK ===\n";
    echo "Local dev URL: http://localhost:8000/admin/categories\n";
    echo "Production URL: https://dev.easybudget.net.br/admin/categories\n";
    echo "Make sure you're testing on the right environment!\n";

} catch ( Exception $e ) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
