<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make( Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TESTE FINAL DE CADASTRO (COM CORREÇÕES) ===\n\n";

try {
    // 1. Testar se o controlador funciona diretamente
    echo "1. TESTANDO CONTROLADOR DIRETAMENTE...\n";

    $data = [
        'first_name'            => 'Maria',
        'last_name'             => 'Santos',
        'email'                 => 'maria' . time() . '@test.com',
        'phone'                 => '(11) 98765-4321',
        'password'              => 'Teste123!',
        'password_confirmation' => 'Teste123!',
        'terms_accepted'        => '1'
    ];

    $controller = app( \App\Http\Controllers\Auth\EnhancedRegisteredUserController::class);
    $request    = new \Illuminate\Http\Request();
    $request->merge( $data );

    echo "Dados de teste: " . json_encode( $data, JSON_PRETTY_PRINT ) . "\n";

    $response = $controller->store( $request );

    if ( $response instanceof \Illuminate\Http\RedirectResponse ) {
        $statusCode = $response->getStatusCode();
        echo "✅ Status da resposta: $statusCode\n";

        if ( $response->isRedirect() ) {
            $redirectUrl = $response->getTargetUrl();
            echo "✅ Redirecionando para: $redirectUrl\n";

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

    echo "\n2. VERIFICAÇÃO FINAL DO BANCO:\n";
    echo "Tenants: " . App\Models\Tenant::count() . "\n";
    echo "Users: " . App\Models\User::count() . "\n";
    echo "Último usuário: " . App\Models\User::latest()->first()->email ?? 'Nenhum' . "\n";

} catch ( \Exception $e ) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
