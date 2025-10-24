<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make( 'Illuminate\Contracts\Console\Kernel' )->bootstrap();

echo "=== DEBUGGING AUTHENTICATION ===\n";

// Check current session
echo "\nCurrent User (from session):\n";
$sessionData = session()->all();
var_dump( $sessionData );

echo "\nAuth User:\n";
$authUser = auth()->user();
var_dump( $authUser ? $authUser->toArray() : 'Nenhum usuário logado' );

if ( $authUser ) {
    echo "\nAuth User Provider:\n";
    $provider = $authUser->provider;
    var_dump( $provider ? $provider->toArray() : 'Provider não encontrado' );

    if ( $provider && $provider->commonData ) {
        echo "\nAuth User Provider Common Data:\n";
        var_dump( $provider->commonData->toArray() );
    }
}

echo "\n=== DATABASE STATE ===\n";

echo "\nCommon Data:\n";
$commonData = \App\Models\CommonData::where( 'company_name', 'like', '%Empresa%' )->first();
var_dump( $commonData ? $commonData->toArray() : 'Nenhum encontrado' );

echo "\nProvider:\n";
$provider = \App\Models\Provider::with( [ 'commonData' ] )->first();
var_dump( $provider ? $provider->toArray() : 'Nenhum encontrado' );

echo "\nAll Common Data:\n";
$allCommonData = \App\Models\CommonData::all();
foreach ( $allCommonData as $data ) {
    echo "ID: {$data->id}, Company: {$data->company_name}\n";
}

echo "\nAll Users:\n";
$allUsers = \App\Models\User::all();
foreach ( $allUsers as $user ) {
    echo "User ID: {$user->id}, Email: {$user->email}, Provider ID: " . ( $user->provider ? $user->provider->id : 'null' ) . "\n";
}

echo "\n=== PASSWORD VERIFICATION ===\n";
echo "\nTesting password for provider2@easybudget.net.br:\n";
$user = \App\Models\User::where( 'email', 'provider2@easybudget.net.br' )->first();
if ( $user ) {
    echo "User found: {$user->email}\n";
    echo "Password hash: " . $user->password . "\n";
    echo "Password verify 'Password1@': " . ( password_verify( 'Password1@', $user->password ) ? 'TRUE' : 'FALSE' ) . "\n";
    echo "User is active: " . ( $user->is_active ? 'TRUE' : 'FALSE' ) . "\n";
} else {
    echo "User not found\n";
}

echo "\n=== TESTING AUTHENTICATION MANUALLY ===\n";
try {
    $credentials = [
        'email'    => 'provider2@easybudget.net.br',
        'password' => 'Password1@'
    ];

    $user = \App\Models\User::where( 'email', $credentials[ 'email' ] )->first();

    if ( $user && password_verify( $credentials[ 'password' ], $user->password ) ) {
        echo "Manual authentication successful\n";
        echo "User: {$user->email}, Active: " . ( $user->is_active ? 'TRUE' : 'FALSE' ) . "\n";
    } else {
        echo "Manual authentication failed\n";
        if ( !$user ) {
            echo "User not found\n";
        } else {
            echo "Password verification failed\n";
        }
    }
} catch ( Exception $e ) {
    echo "Error during manual authentication: " . $e->getMessage() . "\n";
}
