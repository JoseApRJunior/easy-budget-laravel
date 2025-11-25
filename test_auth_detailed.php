<?php

require_once 'vendor/autoload.php';

$app    = require_once 'bootstrap/app.php';
$kernel = $app->make( Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "=== ADMIN USER TEST ===\n";

    // Check if admin user exists
    $admin = App\Models\User::where( 'email', 'admin@easybudget.net.br' )->first();

    if ( !$admin ) {
        echo "❌ Admin user NOT FOUND\n";
        exit( 1 );
    }

    echo "✅ Admin user found: " . $admin->email . "\n";
    echo "✅ User ID: " . $admin->id . "\n";
    echo "✅ Tenant ID: " . $admin->tenant_id . "\n";
    echo "✅ Is Active: " . ( $admin->is_active ? 'YES' : 'NO' ) . "\n";

    // Test isAdmin method
    $isAdmin = $admin->isAdmin();
    echo "✅ isAdmin() method result: " . ( $isAdmin ? 'YES' : 'NO' ) . "\n";

    // Test hasPermission method
    $hasPermission = $admin->hasPermission( 'manage-categories' );
    echo "✅ hasPermission('manage-categories') result: " . ( $hasPermission ? 'YES' : 'NO' ) . "\n";

    // Test Gate authorization directly
    $gateResult = Illuminate\Support\Facades\Gate::authorize( 'manage-categories', $admin );
    echo "✅ Gate authorize('manage-categories') result: SUCCESS\n";

    // Test permissions relationship
    $permissions = $admin->permissions()->get();
    echo "✅ Total permissions: " . $permissions->count() . "\n";

    // Check if manage-categories permission exists in database
    $manageCatsPerm = App\Models\Permission::where( 'name', 'manage-categories' )->first();
    if ( $manageCatsPerm ) {
        echo "✅ manage-categories permission exists in DB: " . $manageCatsPerm->name . "\n";
    } else {
        echo "❌ manage-categories permission NOT found in DB\n";
    }

    // Test user roles
    $roles = $admin->roles()->get();
    echo "✅ User has " . $roles->count() . " roles:\n";
    foreach ( $roles as $role ) {
        echo "   - " . $role->name . "\n";
    }

    echo "\n=== ALL TESTS PASSED ===\n";

} catch ( Exception $e ) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
