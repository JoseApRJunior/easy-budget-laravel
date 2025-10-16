<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make( \Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Services\Application\EmailVerificationService;

echo "=== TESTE COMPLETO DO FLUXO DE VERIFICAÇÃO DE E-MAIL ===\n\n";

// 1. Buscar usuário para teste
$user = User::first();
if ( !$user ) {
    echo "❌ Nenhum usuário encontrado para teste\n";
    exit( 1 );
}

echo "✓ Usuário encontrado: {$user->email} (ID: {$user->id})\n";

// 2. Instanciar serviço de verificação
$service = app( EmailVerificationService::class);
echo "✓ Serviço EmailVerificationService instanciado\n";

// 3. Executar método resendConfirmationEmail
echo "\n--- Executando resendConfirmationEmail ---\n";
$result = $service->resendConfirmationEmail( $user );

if ( $result->isSuccess() ) {
    echo "✅ Serviço executado com sucesso!\n";
    echo "📧 Mensagem: " . $result->getMessage() . "\n";

    $data = $result->getData();
    if ( isset( $data[ 'token' ] ) ) {
        echo "🔑 Token criado: " . substr( $data[ 'token' ], 0, 10 ) . "...\n";
    }
} else {
    echo "❌ Erro no serviço: " . $result->getMessage() . "\n";
    echo "🔍 Status: " . ( $result->getStatus() ? $result->getStatus()->value ?? $result->getStatus() : 'N/A' ) . "\n";
}

echo "\n=== TESTE CONCLUÍDO ===\n";

// 4. Verificar logs recentes
echo "\n--- Últimas entradas do log ---\n";
$logPath = __DIR__ . '/storage/logs/laravel.log';
if ( file_exists( $logPath ) ) {
    $lines       = file( $logPath );
    $recentLines = array_slice( $lines, -20 ); // Últimas 20 linhas

    foreach ( $recentLines as $line ) {
        if (
            strpos( $line, 'SendEmailVerification' ) !== false ||
            strpos( $line, 'EmailVerificationRequested' ) !== false ||
            strpos( $line, 'TESTE:' ) !== false
        ) {
            echo "📋 " . trim( $line ) . "\n";
        }
    }
} else {
    echo "📋 Log não encontrado\n";
}
