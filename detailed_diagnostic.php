<?php

require_once 'vendor/autoload.php';

$app    = require_once 'bootstrap/app.php';
$kernel = $app->make( Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "=== DIAGNÓSTICO COMPLETO ===\n";

    $admin = App\Models\User::where( 'email', 'admin@easybudget.net.br' )->first();

    echo "1. Getting Gate instance...\n";
    $gate = app( Illuminate\Auth\Access\Gate::class);
    echo "   ✅ Gate instance created\n";

    echo "2. Testing Gate::before() callbacks...\n";
    $beforeCallbacks = $gate->beforeCallbacks();
    echo "   Found " . count( $beforeCallbacks ) . " before callbacks\n";

    foreach ( $beforeCallbacks as $i => $callback ) {
        echo "   Testing before callback #{$i}...\n";
        $result = $callback( $admin, 'manage-categories' );
        echo "   Result: " . ( $result === true ? 'ALLOW' : ( $result === false ? 'DENY' : 'CONTINUE' ) ) . "\n";
        if ( $result === true ) {
            echo "   ✅ BEFORE CALLBACK ALLOWED - This should make Gate::check() pass!\n";
            break;
        }
    }

    echo "3. Testing Gate::after() callbacks...\n";
    $afterCallbacks = $gate->afterCallbacks();
    echo "   Found " . count( $afterCallbacks ) . " after callbacks\n";

    echo "4. Testing policy resolution...\n";
    $policies = $gate->policies();
    echo "   Policies registered: " . count( $policies ) . "\n";
    if ( isset( $policies[ App\Models\Category::class] ) ) {
        echo "   Category policy: " . $policies[ App\Models\Category::class] . "\n";
    }

    echo "5. Getting gate definitions...\n";
    $abilities = $gate->abilities();
    echo "   Abilities registered: " . count( $abilities ) . "\n";
    if ( isset( $abilities[ 'manage-categories' ] ) ) {
        echo "   ✅ manage-categories gate found\n";
        $callback = $abilities[ 'manage-categories' ]->callback;
        if ( is_callable( $callback ) ) {
            echo "   Testing direct callback...\n";
            $directResult = $callback( $admin );
            echo "   Direct callback result: " . ( $directResult ? 'ALLOW' : 'DENY' ) . "\n";
        }
    } else {
        echo "   ❌ manage-categories gate NOT found!\n";
    }

    echo "6. Final Gate::check test...\n";
    $finalCheck = $gate->check( 'manage-categories', $admin );
    echo "   Final Gate::check result: " . ( $finalCheck ? 'ALLOW' : 'DENY' ) . "\n";

    echo "\n=== TESTING WITH CONTROLLER AUTHORIZATION ===\n";

    // Simulate what CategoryManagementController does
    echo "Simulating \$this->authorize('manage-categories')...\n";
    try {
        $gate->authorize( 'manage-categories', $admin );
        echo "✅ Authorization successful!\n";
    } catch ( Exception $e ) {
        echo "❌ Authorization failed: " . $e->getMessage() . "\n";
    }

} catch ( Exception $e ) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
