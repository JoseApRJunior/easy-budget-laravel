<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make( Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testando processo completo de registro de usuário...\n";

try {
    // Dados de teste para registro
    $userData = [
        'first_name'     => 'Teste',
        'last_name'      => 'Usuario',
        'email'          => 'teste_' . time() . '@exemplo.com',
        'password'       => 'senha123',
        'phone'          => '11999999999',
        'terms_accepted' => true,
    ];

    echo "Dados de teste: " . $userData[ 'email' ] . "\n";

    // Obter o service de registro
    $registrationService = app( App\Services\Application\UserRegistrationService::class);

    // Executar registro
    $result = $registrationService->registerUser( $userData );

    if ( $result->isSuccess() ) {
        $data = $result->getData();
        echo "✅ Registro realizado com sucesso!\n";
        echo "Usuário: " . $data[ 'user' ]->email . "\n";
        echo "Tenant: " . $data[ 'tenant' ]->name . "\n";
        echo "Evento UserRegistered foi disparado automaticamente.\n";
        echo "E-mail de boas-vindas deve ter sido enviado para: " . $data[ 'user' ]->email . "\n";
    } else {
        echo "❌ Falha no registro: " . $result->getMessage() . "\n";
    }

} catch ( Exception $e ) {
    echo "❌ Erro durante o teste: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
