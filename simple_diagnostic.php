<?php

require_once 'vendor/autoload.php';

$app    = require_once 'bootstrap/app.php';
$kernel = $app->make( Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "=== SIMPLIFIED GATE DIAGNOSTIC ===\n";

    $admin = App\Models\User::where( 'email', 'admin@easybudget.net.br' )->first();

    echo "1. Gate::check() test...\n";
    $result1 = Illuminate\Support\Facades\Gate::check( 'manage-categories', $admin );
    echo "   Result: " . ( $result1 ? 'YES' : 'NO' ) . "\n";

    echo "2. Gate::allows() test...\n";
    $result2 = Illuminate\Support\Facades\Gate::allows( 'manage-categories', $admin );
    echo "   Result: " . ( $result2 ? 'YES' : 'NO' ) . "\n";

    echo "3. Testing Gate::authorize() catch...\n";
    try {
        Illuminate\Support\Facades\Gate::authorize( 'manage-categories', $admin );
        echo "   ✅ Authorization PASSED\n";
    } catch ( Exception $e ) {
        echo "   ❌ Authorization FAILED: " . $e->getMessage() . "\n";

        // Get the authorization response
        $response = $e->getResponse();
        if ( $response ) {
            echo "   Response class: " . get_class( $response ) . "\n";
            echo "   Response message: " . $response->message() . "\n";
        }
    }

    echo "4. Check AuthServiceProvider loading...\n";

    // Test if AuthServiceProvider is loaded
    try {
        $authProvider = app( App\Providers\AuthServiceProvider::class);
        echo "   ✅ AuthServiceProvider loaded successfully\n";

        // Check if boot method was called
        echo "   Testing if gates are registered...\n";

    } catch ( Exception $e ) {
        echo "   ❌ AuthServiceProvider failed: " . $e->getMessage() . "\n";
    }

    echo "5. Alternative test - directly call user methods...\n";

    // Since we know hasPermission works, let's test the gate callback directly
    echo "   User isAdmin(): " . ( $admin->isAdmin() ? 'YES' : 'NO' ) . "\n";
    echo "   User hasRole('admin'): " . ( $admin->hasRole( 'admin' ) ? 'YES' : 'NO' ) . "\n";

    // Test the actual callback from AuthServiceProvider
    echo "   Manual Gate callback: ";
    $callbackResult = ( function ( User $user ) {
        return $user->isAdmin();
    } )( $admin );
    echo ( $callbackResult ? 'YES' : 'NO' ) . "\n";

    echo "\n=== CONCLUSION ===\n";
    echo "If Gate::check() returns NO but manual callback returns YES,\n";
    echo "then there's likely a caching issue or Gate registration problem.\n";

} catch ( Exception $e ) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
