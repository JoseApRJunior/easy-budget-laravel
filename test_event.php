<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make( Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Verificando usuários e tenants...\n";

$user   = App\Models\User::first();
$tenant = App\Models\Tenant::first();

echo "User: " . ( $user ? $user->email : 'null' ) . "\n";
echo "Tenant: " . ( $tenant ? $tenant->name : 'null' ) . "\n";

if ( $user && $tenant ) {
    echo "\nTestando evento UserRegistered...\n";
    event( new App\Events\UserRegistered( $user, $tenant ) );
    echo "Evento disparado!\n";
} else {
    echo "\nNão há usuários ou tenants suficientes para o teste.\n";
}
