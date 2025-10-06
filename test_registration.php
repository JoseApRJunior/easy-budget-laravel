<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make( Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Iniciando teste de cadastro...\n";

try {
    // Dados de teste
    $data = [
        'first_name'            => 'João',
        'last_name'             => 'Silva',
        'email'                 => 'teste' . time() . '@example.com',
        'phone'                 => '(11) 99999-9999',
        'password'              => 'Senha123!',
        'password_confirmation' => 'Senha123!',
        'terms_accepted'        => '1'
    ];

    echo "Dados de teste: " . json_encode( $data, JSON_PRETTY_PRINT ) . "\n";

    // Criar instância do controlador
    $controller = app( \App\Http\Controllers\Auth\EnhancedRegisteredUserController::class);

    // Criar request mock
    $request = new \Illuminate\Http\Request();
    $request->merge( $data );

    echo "Executando registro...\n";

    // Executar o método store
    $response = $controller->store( $request );

    if ( $response instanceof \Illuminate\Http\RedirectResponse ) {
        $statusCode = $response->getStatusCode();
        echo "Status da resposta: $statusCode\n";

        if ( $response->isRedirect() ) {
            $redirectUrl = $response->getTargetUrl();
            echo "Redirecionando para: $redirectUrl\n";

            if ( $response->getSession() ) {
                $successMessage = $response->getSession()->get( 'success' );
                $errors         = $response->getSession()->get( 'errors' );

                if ( $successMessage ) {
                    echo "✅ SUCESSO: $successMessage\n";
                }

                if ( $errors ) {
                    echo "❌ ERROS:\n";
                    foreach ( $errors->all() as $error ) {
                        echo "  - $error\n";
                    }
                }
            }
        }
    } else {
        echo "❌ Resposta inesperada: " . get_class( $response ) . "\n";
    }

} catch ( \Throwable $e ) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";

    // Tentar mostrar logs recentes
    if ( file_exists( 'storage/logs/laravel.log' ) ) {
        echo "\n--- ÚLTIMAS LINHAS DO LOG ---\n";
        $logContent = file_get_contents( 'storage/logs/laravel.log' );
        $lines      = explode( "\n", $logContent );
        $lastLines  = array_slice( $lines, -15 );
        foreach ( $lastLines as $line ) {
            if ( trim( $line ) !== '' ) {
                echo $line . "\n";
            }
        }
    }
}

echo "Teste concluído.\n";
