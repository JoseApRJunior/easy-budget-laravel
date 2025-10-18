<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make( Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    echo "Testando funcionalidade de reset de senha...\n";

    // Verificar se existe algum usuário para testar
    $user = DB::table( 'users' )->first();
    if ( !$user ) {
        echo "❌ Nenhum usuário encontrado para teste\n";
        echo "Criando usuário de teste...\n";

        // Criar tenant primeiro
        $tenantId = DB::table( 'tenants' )->insertGetId( [
            'name'       => 'Teste Reset Password',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now()
        ] );

        // Criar usuário de teste
        $userId = DB::table( 'users' )->insertGetId( [
            'tenant_id'  => $tenantId,
            'email'      => 'test-reset@example.com',
            'password'   => bcrypt( 'password123' ),
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now()
        ] );

        $user = DB::table( 'users' )->find( $userId );
        echo "✅ Usuário de teste criado: {$user->email}\n";
    } else {
        echo "✅ Usuário encontrado: {$user->email}\n";
    }

    // Testar inserção direta na tabela password_reset_tokens
    echo "\nTestando inserção na tabela password_reset_tokens...\n";

    $testEmail = $user->email;
    $testToken = 'test-token-' . time();

    try {
        DB::table( 'password_reset_tokens' )->insert( [
            'email'      => $testEmail,
            'token'      => $testToken,
            'created_at' => now()
        ] );
        echo "✅ Token inserido com sucesso\n";

        // Verificar se o token foi inserido
        $tokenData = DB::table( 'password_reset_tokens' )->where( 'email', $testEmail )->first();
        if ( $tokenData ) {
            echo "✅ Token encontrado no banco: {$tokenData->token}\n";

            // Testar consulta usando o facade Password
            echo "\nTestando consulta via Password facade...\n";

            $passwordFacade = app( 'Illuminate\Contracts\Auth\PasswordBroker' );
            $tokenRecord    = $passwordFacade->getRepository()->recentlyCreatedToken( $user );

            if ( $tokenRecord ) {
                echo "✅ Token encontrado via Password facade\n";
            } else {
                echo "❌ Token não encontrado via Password facade\n";
            }

        } else {
            echo "❌ Token não encontrado após inserção\n";
        }

        // Limpar token de teste
        DB::table( 'password_reset_tokens' )->where( 'email', $testEmail )->delete();
        echo "✅ Token de teste removido\n";

    } catch ( Exception $e ) {
        echo "❌ Erro ao testar tabela password_reset_tokens: " . $e->getMessage() . "\n";
        echo "Arquivo: " . $e->getFile() . "\n";
        echo "Linha: " . $e->getLine() . "\n";
    }

    // Testar o processo completo de reset de senha
    echo "\nTestando processo completo de reset de senha...\n";

    try {
        $status = Password::sendResetLink( [ 'email' => $testEmail ] );
        echo "Status do envio de link: " . $status . "\n";

        if ( $status === Password::RESET_LINK_SENT ) {
            echo "✅ Link de reset enviado com sucesso\n";
        } else {
            echo "❌ Falha no envio do link de reset\n";
        }

    } catch ( Exception $e ) {
        echo "❌ Erro no processo de reset: " . $e->getMessage() . "\n";
        echo "Arquivo: " . $e->getFile() . "\n";
        echo "Linha: " . $e->getLine() . "\n";
    }

} catch ( Exception $e ) {
    echo "Erro geral: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}
