<?php

require_once 'vendor/autoload.php';

$app    = require_once 'bootstrap/app.php';
$kernel = $app->make( Illuminate\Contracts\Http\Kernel::class);

$app->make( Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $admin = App\Models\User::where( 'email', 'admin@easybudget.net.br' )->first();

    if ( $admin ) {
        echo "Admin user found: " . $admin->email . "\n";
        echo "Admin user isAdmin: " . ( $admin->isAdmin() ? 'YES' : 'NO' ) . "\n";
        echo "Admin user has manage-categories permission: " . ( $admin->hasPermission( 'manage-categories' ) ? 'YES' : 'NO' ) . "\n";
        echo "Admin user hasAnyPermission test: " . ( $admin->hasAnyPermission( [ 'manage-categories', 'another-permission' ] ) ? 'YES' : 'NO' ) . "\n";
    } else {
        echo "Admin user not found\n";
    }

} catch ( Exception $e ) {
    echo "Error: " . $e->getMessage() . "\n";
}
