<?php

require __DIR__ . '/vendor/autoload.php';

$app    = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make( Illuminate\Contracts\Http\Kernel::class);

// Bootstrap Laravel
$app->make( Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== WEB CONTEXT GATE DIAGNOSTIC ===\n\n";

// Simulate web request context with authentication
try {
    // Find an admin user from database
    $adminUser = \App\Models\User::where( 'email', 'provider@easybudget.com' )->first();

    if ( !$adminUser ) {
        echo "❌ Admin user not found. Searching for any admin...\n";
        $adminUser = \App\Models\User::whereHas( 'roles', function ( $query ) {
            $query->where( 'name', 'admin' );
        } )->first();
    }

    if ( !$adminUser ) {
        echo "❌ No admin users found in database\n";
        echo "Available users:\n";
        $users = \App\Models\User::with( 'roles' )->limit( 5 )->get();
        foreach ( $users as $user ) {
            $roleNames = $user->roles->pluck( 'name' )->implode( ', ' ) ?: 'NO ROLES';
            echo "  - {$user->email} (ID: {$user->id}) - Roles: {$roleNames}\n";
        }
        exit( 1 );
    }

    echo "✅ Found admin user: {$adminUser->email} (ID: {$adminUser->id})\n";
    echo "1. User isAdmin: " . ( $adminUser->isAdmin() ? 'YES' : 'NO' ) . "\n";
    echo "2. User hasPermission('manage-categories'): " . ( $adminUser->hasPermission( 'manage-categories' ) ? 'YES' : 'NO' ) . "\n";

    // Authenticate user manually for this context
    auth()->login( $adminUser );

    echo "3. After login - auth()->user(): " . ( auth()->user() ? "ID " . auth()->user()->id : "NULL" ) . "\n";

    // Test Gate now that user is authenticated
    echo "\n4. Gate::check('manage-categories'): " . ( Gate::check( 'manage-categories' ) ? 'YES' : 'NO' ) . "\n";
    echo "5. Gate::allows('manage-categories'): " . ( Gate::allows( 'manage-categories' ) ? 'YES' : 'NO' ) . "\n";
    echo "6. Gate::has('manage-categories'): " . ( Gate::has( 'manage-categories' ) ? 'YES' : 'NO' ) . "\n";

    // Direct authorization test
    echo "\n7. Direct Gate::authorize() test:\n";
    try {
        Gate::authorize( 'manage-categories' );
        echo "   ✅ Authorization SUCCESSFUL\n";
    } catch ( Exception $e ) {
        echo "   ❌ Authorization FAILED: " . $e->getMessage() . "\n";
        echo "   Exception class: " . get_class( $e ) . "\n";
    }

    // Test AuthServiceProvider gate definition
    echo "\n8. Checking AuthServiceProvider definition...\n";
    $authServiceProvider = new \App\Providers\AuthServiceProvider( $app );
    echo "   AuthServiceProvider loaded: ✅\n";

    // Check if gates are defined in boot method
    echo "9. Checking for any gate definitions...\n";
    try {
        $gate = app( 'gate' );
        echo "   Gate container available: ✅\n";
        echo "   Gate class: " . get_class( $gate ) . "\n";

        // Try to get gate abilities via reflection or container
        $abilities = $gate->abilities ?? $gate->getAbilities() ?? null;
        if ( $abilities ) {
            echo "   Abilities found: " . count( $abilities ) . "\n";
            foreach ( $abilities as $name => $callback ) {
                echo "   - '$name': " . ( is_callable( $callback ) ? 'Closure' : get_class( $callback ) ) . "\n";
            }
        } else {
            echo "   No abilities found via direct property access\n";
        }

    } catch ( Exception $e ) {
        echo "   ❌ Gate container access failed: " . $e->getMessage() . "\n";
    }

    echo "\n=== FINAL CONCLUSION ===\n";
    if ( Gate::check( 'manage-categories' ) ) {
        echo "✅ Gate authorization is WORKING correctly\n";
        echo "✅ The user CAN access admin categories\n";
        echo "✅ Problem likely in web middleware or session\n";
    } else {
        echo "❌ Gate authorization is NOT working\n";
        echo "❌ The user CANNOT access admin categories\n";
        echo "❌ Check AuthServiceProvider gate definitions\n";
    }

} catch ( Exception $e ) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== END DIAGNOSTIC ===\n";
