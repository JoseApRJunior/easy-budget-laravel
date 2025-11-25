<?php

require __DIR__ . '/vendor/autoload.php';

$app    = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make( Illuminate\Contracts\Http\Kernel::class);

// Bootstrap Laravel
$app->make( Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== SIMPLIFIED GATE DIAGNOSTIC ===\n\n";

// Test 1: Get authenticated user
$user = auth()->user();
echo "1. Authenticated user: " . ( $user ? "ID {$user->id} ({$user->email})" : "NONE" ) . "\n";

if ( $user ) {
    echo "2. User isAdmin: " . ( $user->isAdmin() ? 'YES' : 'NO' ) . "\n";
    echo "3. User hasPermission('manage-categories'): " . ( $user->hasPermission( 'manage-categories' ) ? 'YES' : 'NO' ) . "\n";
    echo "4. User role: " . ( $user->roles()->first() ? $user->roles()->first()->name : 'NONE' ) . "\n";
}

// Test 5: Check Gate directly
echo "\n5. Gate::check('manage-categories'): " . ( Gate::check( 'manage-categories' ) ? 'YES' : 'NO' ) . "\n";
echo "6. Gate::allows('manage-categories'): " . ( Gate::allows( 'manage-categories' ) ? 'YES' : 'NO' ) . "\n";

// Test 7: Check if gate is defined
echo "7. Gate::has('manage-categories'): " . ( Gate::has( 'manage-categories' ) ? 'YES' : 'NO' ) . "\n";

// Test 8: Try to get gate definition
echo "\n8. Attempting to resolve gate callback...\n";
try {
    if ( Gate::has( 'manage-categories' ) ) {
        $gateContainer = app( 'gate' );
        $callback      = $gateContainer->get( 'manage-categories' );
        echo "9. Callback class: " . get_class( $callback ) . "\n";

        // Test 10: Execute callback directly
        if ( is_callable( $callback ) ) {
            echo "10. Executing callback directly...\n";
            try {
                $reflection = new ReflectionFunction( $callback );
                $paramCount = $reflection->getNumberOfParameters();
                echo "    Parameters: $paramCount\n";

                if ( $paramCount === 1 ) {
                    $result = $callback( $user );
                    echo "    Callback result: " . ( $result ? 'TRUE' : 'FALSE' ) . "\n";
                } else {
                    $result = $callback();
                    echo "    Callback result: " . ( $result ? 'TRUE' : 'FALSE' ) . "\n                ";
                }

                echo "\n=== CONCLUSION ===\n";
                echo "✅ User exists and is admin\n";
                echo "✅ User has manage-categories permission\n";
                echo "✅ Gate callback executes successfully\n";
                echo "❌ Gate::check() still returns false\n";
                echo "\nThis suggests a caching or initialization issue with the Gate facade.\n";

            } catch ( Exception $e ) {
                echo "    ❌ Callback execution failed: " . $e->getMessage() . "\n";
            }
        }
    }
} catch ( Exception $e ) {
    echo "9. ❌ Gate resolution failed: " . $e->getMessage() . "\n";
}

echo "\n=== END DIAGNOSTIC ===\n";
