<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make( Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testando evento EmailVerificationRequested...\n";

try {
    $user = App\Models\User::first();

    if ( !$user ) {
        echo "Nenhum usuário encontrado para teste\n";
        exit( 1 );
    }

    echo "Usuário encontrado: {$user->id} - {$user->email}\n";

    // Disparar evento
    event( new App\Events\EmailVerificationRequested( $user, $user->tenant, 'test-token-123' ) );

    echo "Evento disparado com sucesso\n";

} catch ( Exception $e ) {
    echo "Erro ao testar evento: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
