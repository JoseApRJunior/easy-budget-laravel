<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make( 'Illuminate\Contracts\Console\Kernel' )->bootstrap();

echo "=== USER STATUS CHECK ===\n\n";

try {
    // Check database tables
    echo "Checking database tables...\n";
    $tables = [ 'users', 'roles', 'user_roles', 'tenants', 'providers' ];
    foreach ( $tables as $table ) {
        try {
            $count = \Illuminate\Support\Facades\DB::table( $table )->count();
            echo "  {$table}: {$count} records\n";
        } catch ( Exception $e ) {
            echo "  {$table}: ERROR - " . $e->getMessage() . "\n";
        }
    }
    echo "\n";

    // Get all users
    $users = \App\Models\User::all();

    if ( $users->isEmpty() ) {
        echo "No users found in database.\n";
        exit( 1 );
    }

    foreach ( $users as $user ) {
        echo "User ID: {$user->id}\n";
        echo "Email: {$user->email}\n";
        echo "Email Verified At: " . ( $user->email_verified_at ? $user->email_verified_at : 'NULL' ) . "\n";
        echo "Is Active: " . ( $user->is_active ? 'Yes' : 'No' ) . "\n";

        // Check tenant
        $tenant = $user->tenant;
        if ( $tenant ) {
            echo "Tenant: {$tenant->name} (ID: {$tenant->id})\n";
        } else {
            echo "Tenant: NULL\n";
        }

        // Check provider
        $provider = \App\Models\Provider::where( 'user_id', $user->id )->first();
        if ( $provider ) {
            echo "Provider: Yes (ID: {$provider->id})\n";
            echo "  Provider is active: " . ( $provider->is_active ? 'Yes' : 'No' ) . "\n";
        } else {
            echo "Provider: No\n";
        }

        // Check user roles
        $roles = $user->getTenantScopedRoles();
        if ( $roles->count() > 0 ) {
            $roleNames = $roles->pluck( 'name' )->toArray();
            echo "Roles: " . implode( ', ', $roleNames ) . "\n";
        } else {
            echo "Roles: Not set\n";
        }

        // Check all user roles (including non-tenant scoped)
        $allRoles = $user->roles;
        if ( $allRoles->count() > 0 ) {
            $allRoleNames = $allRoles->pluck( 'name' )->toArray();
            echo "All Roles: " . implode( ', ', $allRoleNames ) . "\n";
        } else {
            echo "All Roles: Not set\n";
        }

        // Check user roles by tenant
        echo "User Roles by Tenant:\n";
        $userRoles = \App\Models\UserRole::where( 'user_id', $user->id )->with( 'role', 'tenant' )->get();
        foreach ( $userRoles as $userRole ) {
            echo "  Tenant: {$userRole->tenant->name} (ID: {$userRole->tenant_id})\n";
            echo "  Role: {$userRole->role->name}\n";
        }

        echo "\n" . str_repeat( '-', 50 ) . "\n\n";
    }

} catch ( Exception $e ) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
