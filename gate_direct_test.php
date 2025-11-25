<?php

require __DIR__ . '/vendor/autoload.php';

$app    = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make( Illuminate\Contracts\Http\Kernel::class);

// Bootstrap Laravel
$app->make( Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== DIRECT GATE SYSTEM DIAGNOSTIC ===\n\n";

// Test 1: Get App instance
$appInstance = app();
echo "1. Application instance: " . get_class( $appInstance ) . "\n";

// Test 2: Get Gate facade
$gate = app( Gate::class);
echo "2. Gate class: " . get_class( $gate ) . "\n";

// Test 3: List all defined abilities
$abilities = $gate->abilities();
echo "3. Defined abilities:\n";
foreach ( $abilities as $ability => $callback ) {
    echo "   - '$ability': " . ( is_callable( $callback ) ? 'Closure' : get_class( $callback ) ) . "\n";
}
echo "\n";

// Test 4: Check if 'manage-categories' is defined
$hasManageCategories = $gate->has( 'manage-categories' );
echo "4. Gate 'manage-categories' defined: " . ( $hasManageCategories ? 'YES' : 'NO' ) . "\n";

if ( $hasManageCategories ) {
    // Test 5: Get the actual callback for 'manage-categories'
    $callback = $gate->get( 'manage-categories' );
    echo "5. Callback type: " . get_class( $callback ) . "\n";

    // Test 6: Get authenticated user
    $user = auth()->user();
    echo "6. Authenticated user: " . ( $user ? "ID {$user->id} ({$user->email})" : "NONE" ) . "\n";

    if ( $user ) {
        echo "7. User isAdmin: " . ( $user->isAdmin() ? 'YES' : 'NO' ) . "\n";
        echo "8. User hasPermission('manage-categories'): " . ( $user->hasPermission( 'manage-categories' ) ? 'YES' : 'NO' ) . "\n";
        echo "9. User role: " . ( $user->roles()->first() ? $user->roles()->first()->name : 'NONE' ) . "\n";

        // Test 10: Try to resolve the callback with dependency injection
        try {
            $reflection = new ReflectionFunction( $callback );
            $params     = $reflection->getParameters();

            echo "10. Gate callback parameters:\n";
            foreach ( $params as $param ) {
                echo "    - {$param->name}: " . ( $param->getType() ? $param->getType()->getName() : 'mixed' ) . "\n";

                if ( $param->getType() && $param->getType()->getName() === 'App\Models\User' ) {
                    echo "      ✅ Resolving as App\Models\User\n";
                    try {
                        $resolvedUser = app( App\Models\User::class);
                        echo "      Resolved user ID: " . ( $resolvedUser ? $resolvedUser->id : 'NULL' ) . "\n";
                        echo "      Resolved user isAdmin: " . ( $resolvedUser ? ( $resolvedUser->isAdmin() ? 'YES' : 'NO' ) : 'NULL' ) . "\n";
                    } catch ( Exception $e ) {
                        echo "      ❌ Failed to resolve: " . $e->getMessage() . "\n";
                    }
                }
            }
        } catch ( Exception $e ) {
            echo "10. ❌ Failed to analyze callback: " . $e->getMessage() . "\n";
        }
    }
}

// Test 11: Try direct callback execution
echo "\n11. Direct callback execution test:\n";
if ( isset( $callback ) && isset( $user ) ) {
    try {
        if ( $reflection->getNumberOfParameters() === 1 ) {
            // Gate expects User parameter
            $result = $callback( $user );
            echo "    Direct execution result: " . ( $result ? 'TRUE' : 'FALSE' ) . "\n";
        } else {
            echo "    Callback expects no parameters\n";
            $result = $callback();
            echo "    Direct execution result: " . ( $result ? 'TRUE' : 'FALSE' ) . "\n";
        }
    } catch ( Exception $e ) {
        echo "    ❌ Direct execution failed: " . $e->getMessage() . "\n";
    }
}

// Test 12: Check Gate::before() hooks
$beforeCallbacks = $gate->beforeCallbacks;
echo "\n12. Gate::before() callbacks: " . count( $beforeCallbacks ) . "\n";
foreach ( $beforeCallbacks as $i => $beforeCallback ) {
    echo "    $i: " . get_class( $beforeCallback ) . "\n";
}

echo "\n=== END DIAGNOSTIC ===\n";
