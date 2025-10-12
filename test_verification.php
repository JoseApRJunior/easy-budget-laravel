<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\URL;

// Simular um usuário para teste
$user = new User( [
    'id'         => 2,
    'email'      => 'test@example.com',
    'created_at' => now(),
] );

$user->email = 'test@example.com';

// Testar geração da URL de verificação
$notification    = new VerifyEmailNotification();
$verificationUrl = $notification->testVerificationUrl( $user );

echo "URL de verificação gerada:\n";
echo $verificationUrl . "\n\n";

// Testar se a assinatura é válida
$parsedUrl   = parse_url( $verificationUrl );
$queryParams = [];
if ( isset( $parsedUrl[ 'query' ] ) ) {
    parse_str( $parsedUrl[ 'query' ], $queryParams );
}

echo "Parâmetros da URL:\n";
print_r( $queryParams );

echo "\nTeste de validação da assinatura:\n";

// Simular verificação da assinatura
$signature = $queryParams[ 'signature' ] ?? '';
$expires   = $queryParams[ 'expires' ] ?? '';
$path      = $parsedUrl[ 'path' ] . '?' . http_build_query( [ 'id' => 2, 'hash' => sha1( 'test@example.com' ) ] );

echo "Signature: $signature\n";
echo "Expires: $expires\n";
echo "Path: $path\n";

// Verificar se a assinatura é válida usando o facade URL
try {
    $testUrl = route( 'verification.verify', [
        'id'        => 2,
        'hash'      => sha1( 'test@example.com' ),
        'signature' => $signature,
        'expires'   => $expires,
    ] );

    echo "\nAssinatura válida! URL completa: $testUrl\n";
} catch ( Exception $e ) {
    echo "\nErro na assinatura: " . $e->getMessage() . "\n";
}
