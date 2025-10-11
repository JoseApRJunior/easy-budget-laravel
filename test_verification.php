<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make( Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $user = App\Models\User::find( 1 );
    echo 'Testando sendEmailVerificationNotification para usuário: ' . $user->email . PHP_EOL;

    if ( !$user ) {
        echo 'Usuário não encontrado!' . PHP_EOL;
        exit( 1 );
    }

    // Verificar se o usuário já tem email verificado
    if ( $user->hasVerifiedEmail() ) {
        echo 'Usuário já tem email verificado!' . PHP_EOL;
        exit( 0 );
    }

    // Tentar enviar a notificação
    $user->sendEmailVerificationNotification();
    echo 'Notificação enviada com sucesso!' . PHP_EOL;

} catch ( Exception $e ) {
    echo 'Erro: ' . $e->getMessage() . PHP_EOL;
    echo 'Arquivo: ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
    echo 'Stack trace: ' . $e->getTraceAsString() . PHP_EOL;
}
