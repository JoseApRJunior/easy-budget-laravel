<?php

require_once 'vendor/autoload.php';

$app    = require_once 'bootstrap/app.php';
$kernel = $app->make( Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "=== GATE DEBUGGING ===\n";

    $admin = App\Models\User::where( 'email', 'admin@easybudget.net.br' )->first();

    // Test individual gate definitions
    echo "Testing Gate::check('manage-categories', \$admin)...\n";
    $checkResult = Illuminate\Support\Facades\Gate::check( 'manage-categories', $admin );
    echo "Gate::check result: " . ( $checkResult ? 'YES' : 'NO' ) . "\n";

    echo "\nTesting Gate::define('manage-categories') callback...\n";
    $gateDefinition = Illuminate\Support\Facades\Gate::getDefinitionFor( 'manage-categories' );
    if ( $gateDefinition ) {
        echo "Gate definition found: " . get_class( $gateDefinition ) . "\n";

        // Test the callback directly
        $callback = $gateDefinition->callback;
        if ( is_callable( $callback ) ) {
            echo "Testing callback directly...\n";
            $callbackResult = $callback( $admin );
            echo "Callback result: " . ( $callbackResult ? 'YES' : 'NO' ) . "\n";
        }
    }

    echo "\nTesting Gate::before callback...\n";
    $beforeCallbacks = Illuminate\Support\Facades\Gate::beforeCallbacks();
    echo "Before callbacks count: " . count( $beforeCallbacks ) . "\n";

    foreach ( $beforeCallbacks as $index => $callback ) {
        echo "Testing before callback #{$index}...\n";
        try {
            $result = $callback( $admin, 'manage-categories' );
            echo "Before callback result: " . ( $result === null ? 'null (continue)' : ( $result ? 'true (allow)' : 'false (deny)' ) ) . "\n";
        } catch ( Exception $e ) {
            echo "Before callback error: " . $e->getMessage() . "\n";
        }
    }

} catch ( Exception $e ) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
