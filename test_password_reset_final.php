<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make( Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    echo "=== TESTE FINAL DE RESET DE SENHA ===\n\n";

    // 1. Verificar se a tabela existe
    echo "1. Verificando tabela password_reset_tokens...\n";
    $tables = DB::select( 'SHOW TABLES LIKE "password_reset_tokens"' );
    if ( empty( $tables ) ) {
        echo "❌ Tabela password_reset_tokens não encontrada!\n";
        exit( 1 );
    }
    echo "✅ Tabela password_reset_tokens existe\n";

    // 2. Verificar estrutura da tabela
    echo "\n2. Verificando estrutura da tabela...\n";
    $columns = DB::select( "DESCRIBE password_reset_tokens" );
    foreach ( $columns as $column ) {
        $nullInfo = ( $column->Null === 'NO' ) ? 'NOT NULL' : 'NULL';
        $keyInfo  = $column->Key ? " ({$column->Key})" : '';
        echo "   - {$column->Field}: {$column->Type} {$nullInfo}{$keyInfo}\n";
    }

    // 3. Encontrar um usuário para teste
    echo "\n3. Buscando usuário para teste...\n";
    $user = DB::table( 'users' )->first();
    if ( !$user ) {
        echo "❌ Nenhum usuário encontrado para teste\n";
        exit( 1 );
    }
    echo "✅ Usuário encontrado: {$user->email}\n";

    // 4. Testar processo completo usando o facade Password
    echo "\n4. Testando processo completo de reset de senha...\n";

    $testEmail = $user->email;

    // Limpar qualquer token existente
    DB::table( 'password_reset_tokens' )->where( 'email', $testEmail )->delete();
    echo "✅ Tokens anteriores removidos\n";

    // Tentar enviar link de reset
    $status = Password::sendResetLink( [ 'email' => $testEmail ] );
    echo "Status do envio: {$status}\n";

    if ( $status === Password::RESET_LINK_SENT ) {
        echo "✅ Link de reset enviado com sucesso!\n";

        // Verificar se o token foi criado na tabela
        $tokenRecord = DB::table( 'password_reset_tokens' )->where( 'email', $testEmail )->first();
        if ( $tokenRecord ) {
            echo "✅ Token criado na tabela: {$tokenRecord->token}\n";
            echo "✅ Created at: {$tokenRecord->created_at}\n";

            // Testar validação do token
            echo "\n5. Testando validação do token...\n";
            $tokenValidation = Password::tokenExists( $user, $tokenRecord->token );
            echo "Token válido: " . ( $tokenValidation ? '✅ Sim' : '❌ Não' ) . "\n";

        } else {
            echo "❌ Token não encontrado na tabela após envio\n";
        }

    } else {
        echo "❌ Falha no envio do link de reset\n";
        echo "Status recebido: {$status}\n";
    }

    // 6. Limpar dados de teste
    echo "\n6. Limpando dados de teste...\n";
    DB::table( 'password_reset_tokens' )->where( 'email', $testEmail )->delete();
    echo "✅ Dados de teste removidos\n";

    echo "\n=== TESTE CONCLUÍDO ===\n";

} catch ( Exception $e ) {
    echo "\n❌ ERRO durante o teste: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit( 1 );
}
