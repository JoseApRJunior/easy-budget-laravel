<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make( Illuminate\Contracts\Http\Kernel::class);

// Testar usuário admin
$user = App\Models\User::find( 2 );

if ( !$user ) {
    echo "User ID 2 não encontrado!\n";
    exit( 1 );
}

echo "=== TESTE DE AUTORIZAÇÃO ===\n";
echo "User ID: " . $user->id . "\n";
echo "User Email: " . $user->email . "\n";
echo "User isAdmin: " . ( $user->isAdmin() ? 'Yes' : 'No' ) . "\n";
echo "User hasPermission(manage-categories): " . ( $user->hasPermission( 'manage-categories' ) ? 'Yes' : 'No' ) . "\n";

// Testar CategoryPolicy
try {
    $policy = new App\Policies\CategoryPolicy();
    $result = $policy->viewAny( $user );
    echo "CategoryPolicy::viewAny(): " . ( $result ? 'Yes' : 'No' ) . "\n";
} catch ( Exception $e ) {
    echo "CategoryPolicy error: " . $e->getMessage() . "\n";
}

echo "\n=== FIM DO TESTE ===\n";
