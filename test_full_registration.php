<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make( Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TESTE COMPLETO DE CADASTRO (SIMULANDO NAVEGADOR) ===\n\n";

try {
    // 1. Primeiro vamos verificar se conseguimos acessar a página
    echo "1. TESTANDO ACESSO À PÁGINA DE REGISTRO...\n";
    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_URL, "http://127.0.0.1:8000/register" );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, false );
    curl_setopt( $ch, CURLOPT_COOKIEJAR, 'cookies.jar' );
    curl_setopt( $ch, CURLOPT_COOKIEFILE, 'cookies.jar' );

    $response = curl_exec( $ch );
    $httpCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

    if ( $httpCode === 200 ) {
        echo "✅ Página de registro acessível (HTTP $httpCode)\n";

        // 2. Extrair o token CSRF da página
        if ( preg_match( '/<meta name="csrf-token" content="([^"]+)"/', $response, $matches ) ) {
            $csrfToken = $matches[ 1 ];
            echo "✅ Token CSRF extraído: " . substr( $csrfToken, 0, 20 ) . "...\n";
        } else {
            echo "❌ Token CSRF não encontrado na página\n";
            exit( 1 );
        }

    } else {
        echo "❌ Erro ao acessar página de registro (HTTP $httpCode)\n";
        exit( 1 );
    }
    curl_close( $ch );

    // 3. Preparar dados do formulário
    $postData = [
        '_token'                => $csrfToken,
        'first_name'            => 'Maria',
        'last_name'             => 'Santos',
        'email'                 => 'maria' . time() . '@test.com',
        'phone'                 => '(11) 98765-4321',
        'password'              => 'Teste123!',
        'password_confirmation' => 'Teste123!',
        'terms_accepted'        => '1'
    ];

    echo "\n2. ENVIANDO FORMULÁRIO DE CADASTRO...\n";
    echo "Dados: " . json_encode( $postData, JSON_PRETTY_PRINT ) . "\n";

    // 4. Enviar formulário
    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_URL, "http://127.0.0.1:8000/register" );
    curl_setopt( $ch, CURLOPT_POST, true );
    curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $postData ) );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
    curl_setopt( $ch, CURLOPT_COOKIEJAR, 'cookies.jar' );
    curl_setopt( $ch, CURLOPT_COOKIEFILE, 'cookies.jar' );
    curl_setopt( $ch, CURLOPT_MAXREDIRS, 5 );

    $response = curl_exec( $ch );
    $httpCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
    $finalUrl = curl_getinfo( $ch, CURLINFO_EFFECTIVE_URL );

    echo "\n3. RESULTADO DO CADASTRO:\n";
    echo "Status HTTP: $httpCode\n";
    echo "URL Final: $finalUrl\n";

    if ( $httpCode >= 200 && $httpCode < 300 ) {
        echo "✅ Cadastro realizado com sucesso!\n";

        if ( strpos( $finalUrl, 'dashboard' ) !== false ) {
            echo "✅ Redirecionamento para dashboard correto!\n";
        } else {
            echo "⚠️  Redirecionamento para URL inesperada: $finalUrl\n";
        }

        // Verificar se há mensagem de sucesso na resposta
        if ( strpos( $response, 'Registro realizado com sucesso' ) !== false ) {
            echo "✅ Mensagem de sucesso encontrada na página!\n";
        } else {
            echo "⚠️  Mensagem de sucesso não encontrada na página\n";
        }

    } else {
        echo "❌ Erro no cadastro (HTTP $httpCode)\n";

        // Tentar extrair mensagem de erro
        if ( preg_match( '/<div class="alert alert-danger[^"]*">(.*?)<\/div>/s', $response, $matches ) ) {
            echo "Mensagem de erro encontrada: " . strip_tags( $matches[ 1 ] ) . "\n";
        }
    }

    curl_close( $ch );

    // 5. Verificar estado final do banco
    echo "\n4. VERIFICAÇÃO FINAL DO BANCO:\n";
    echo "Tenants: " . App\Models\Tenant::count() . "\n";
    echo "Users: " . App\Models\User::count() . "\n";
    echo "Último usuário criado: " . App\Models\User::latest()->first()->email ?? 'Nenhum' . "\n";

} catch ( Exception $e ) {
    echo "❌ ERRO GERAL: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
