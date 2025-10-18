<?php

/**
 * Script para testar o sistema de e-mail refatorado através do Laravel Tinker.
 *
 * Este script testa especificamente:
 * - EmailVerificationMail
 * - WelcomeUserMail
 * - PasswordResetNotification
 *
 * Para executar:
 * php artisan tinker --execute="include 'test_emails_tinker.php'; testEmailSystem('juniorklan.ju@gmail.com',3);"
 */

use App\Mail\EmailVerificationMail;
use App\Mail\PasswordResetNotification;
use App\Mail\WelcomeUserMail;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserConfirmationToken;
use App\Services\Infrastructure\ConfirmationLinkService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

function testEmailSystem( string $email, int $tenantId = 3 ): array
{
    echo "🚀 Iniciando teste do sistema de e-mail refatorado\n";
    echo "📧 E-mail de destino: {$email}\n";
    echo "🏢 Tenant ID: {$tenantId}\n\n";

    // Buscar ou criar usuário de teste
    $user = getOrCreateTestUser( $email, $tenantId );

    // Buscar ou criar tenant
    $tenant = getOrCreateTenant( $tenantId );

    $results = [];

    try {
        // Teste 1: EmailVerificationMail
        echo "📋 Teste 1: EmailVerificationMail\n";
        $result1   = testEmailVerificationMail( $user, $tenant );
        $results[] = $result1;
        echo "\n";

        // Teste 2: WelcomeUserMail
        echo "📋 Teste 2: WelcomeUserMail\n";
        $result2   = testWelcomeUserMail( $user, $tenant );
        $results[] = $result2;
        echo "\n";

        // Teste 3: PasswordResetNotification
        echo "📋 Teste 3: PasswordResetNotification\n";
        $result3   = testPasswordResetNotification( $user );
        $results[] = $result3;
        echo "\n";

        // Relatório final
        displayResults( $results );

        return $results;

    } catch ( Throwable $e ) {
        echo "❌ Erro durante execução dos testes: " . $e->getMessage() . "\n";
        Log::error( 'Erro no teste de e-mail', [
            'error'     => $e->getMessage(),
            'trace'     => $e->getTraceAsString(),
            'email'     => $email,
            'tenant_id' => $tenantId,
        ] );

        return [];
    }
}

function getOrCreateTestUser( string $email, int $tenantId ): User
{
    $user = User::where( 'email', $email )->first();

    if ( !$user ) {
        echo "Usuário {$email} não encontrado. Criando usuário de teste...\n";

        $user = User::create( [
            'tenant_id'         => $tenantId,
            'email'             => $email,
            'password'          => bcrypt( 'test_password_123' ),
            'is_active'         => true,
            'email_verified_at' => now(),
        ] );

        echo "✅ Usuário de teste criado com ID: {$user->id}\n";
    } else {
        echo "✅ Usuário encontrado com ID: {$user->id}\n";
    }

    return $user;
}

function getOrCreateTenant( int $tenantId ): Tenant
{
    $tenant = Tenant::find( $tenantId );

    if ( !$tenant ) {
        echo "Tenant {$tenantId} não encontrado. Criando tenant de teste...\n";

        $tenant = Tenant::create( [
            'name'      => 'Tenant de Teste',
            'is_active' => true,
        ] );

        echo "✅ Tenant de teste criado com ID: {$tenant->id}\n";
    } else {
        echo "✅ Tenant encontrado: {$tenant->name}\n";
    }

    return $tenant;
}

function testEmailVerificationMail( User $user, Tenant $tenant ): array
{
    $startTime = microtime( true );

    try {
        echo "  📤 Enviando EmailVerificationMail...\n";

        // Criar token de confirmação se não existir
        $token = UserConfirmationToken::where( 'user_id', $user->id )
            ->where( 'expires_at', '>', now() )
            ->first();

        if ( !$token ) {
            $token = UserConfirmationToken::create( [
                'user_id'    => $user->id,
                'tenant_id'  => $tenant->id,
                'token'      => Str::random( 64 ),
                'expires_at' => now()->addMinutes( 30 ),
            ] );
        }

        // Enviar e-mail
        $mail = new EmailVerificationMail( $user, $tenant );
        Mail::to( $user->email )->send( $mail );

        $executionTime = round( ( microtime( true ) - $startTime ) * 1000, 2 );

        echo "  ✅ EmailVerificationMail enviado com sucesso em {$executionTime}ms\n";

        return [
            'type'           => 'EmailVerificationMail',
            'success'        => true,
            'execution_time' => $executionTime,
            'recipient'      => $user->email,
            'error'          => null,
        ];

    } catch ( Throwable $e ) {
        $executionTime = round( ( microtime( true ) - $startTime ) * 1000, 2 );

        echo "  ❌ Erro no EmailVerificationMail: {$e->getMessage()}\n";

        Log::error( 'Erro no teste de EmailVerificationMail', [
            'error'     => $e->getMessage(),
            'user_id'   => $user->id,
            'tenant_id' => $tenant->id,
        ] );

        return [
            'type'           => 'EmailVerificationMail',
            'success'        => false,
            'execution_time' => $executionTime,
            'recipient'      => $user->email,
            'error'          => $e->getMessage(),
        ];
    }
}

function testWelcomeUserMail( User $user, Tenant $tenant ): array
{
    $startTime = microtime( true );

    try {
        echo "  📤 Enviando WelcomeUserMail...\n";

        // Criar token de confirmação se não existir
        $token = UserConfirmationToken::where( 'user_id', $user->id )
            ->where( 'expires_at', '>', now() )
            ->first();

        if ( !$token ) {
            $token = UserConfirmationToken::create( [
                'user_id'    => $user->id,
                'tenant_id'  => $tenant->id,
                'token'      => Str::random( 64 ),
                'expires_at' => now()->addMinutes( 30 ),
            ] );
        }

        // Gerar link de confirmação
        $confirmationLinkService = app( ConfirmationLinkService::class);
        $confirmationLink        = $confirmationLinkService->buildConfirmationLink(
            $token->token,
            '/confirm-account',
            '/email/verify',
        );

        // Enviar e-mail
        $mail = new WelcomeUserMail( $user, $tenant, $confirmationLink );
        Mail::to( $user->email )->send( $mail );

        $executionTime = round( ( microtime( true ) - $startTime ) * 1000, 2 );

        echo "  ✅ WelcomeUserMail enviado com sucesso em {$executionTime}ms\n";

        return [
            'type'           => 'WelcomeUserMail',
            'success'        => true,
            'execution_time' => $executionTime,
            'recipient'      => $user->email,
            'error'          => null,
        ];

    } catch ( Throwable $e ) {
        $executionTime = round( ( microtime( true ) - $startTime ) * 1000, 2 );

        echo "  ❌ Erro no WelcomeUserMail: {$e->getMessage()}\n";

        Log::error( 'Erro no teste de WelcomeUserMail', [
            'error'     => $e->getMessage(),
            'user_id'   => $user->id,
            'tenant_id' => $tenant->id,
        ] );

        return [
            'type'           => 'WelcomeUserMail',
            'success'        => false,
            'execution_time' => $executionTime,
            'recipient'      => $user->email,
            'error'          => $e->getMessage(),
        ];
    }
}

function testPasswordResetNotification( User $user ): array
{
    $startTime = microtime( true );

    try {
        echo "  📤 Enviando PasswordResetNotification...\n";

        // Gerar token de reset
        $resetToken = Str::random( 64 );

        // Enviar e-mail
        $mail = new PasswordResetNotification( $user, $resetToken );
        Mail::to( $user->email )->send( $mail );

        $executionTime = round( ( microtime( true ) - $startTime ) * 1000, 2 );

        echo "  ✅ PasswordResetNotification enviado com sucesso em {$executionTime}ms\n";

        return [
            'type'           => 'PasswordResetNotification',
            'success'        => true,
            'execution_time' => $executionTime,
            'recipient'      => $user->email,
            'error'          => null,
        ];

    } catch ( Throwable $e ) {
        $executionTime = round( ( microtime( true ) - $startTime ) * 1000, 2 );

        echo "  ❌ Erro no PasswordResetNotification: {$e->getMessage()}\n";

        Log::error( 'Erro no teste de PasswordResetNotification', [
            'error'   => $e->getMessage(),
            'user_id' => $user->id,
        ] );

        return [
            'type'           => 'PasswordResetNotification',
            'success'        => false,
            'execution_time' => $executionTime,
            'recipient'      => $user->email,
            'error'          => $e->getMessage(),
        ];
    }
}

function displayResults( array $results ): void
{
    echo "\n📊 RELATÓRIO FINAL DOS TESTES\n\n";

    $totalTests      = count( $results );
    $successfulTests = count( array_filter( $results, fn( $result ) => $result[ 'success' ] ) );
    $totalTime       = array_sum( array_column( $results, 'execution_time' ) );

    // Tabela de resultados
    echo str_pad( 'Tipo de E-mail', 25 ) . str_pad( 'Status', 12 ) . str_pad( 'Tempo (ms)', 12 ) . str_pad( 'Destinatário', 30 ) . "Erro\n";
    echo str_repeat( '-', 100 ) . "\n";

    foreach ( $results as $result ) {
        echo str_pad( $result[ 'type' ], 25 ) .
            str_pad( $result[ 'success' ] ? '✅ Sucesso' : '❌ Falha', 12 ) .
            str_pad( $result[ 'execution_time' ] . 'ms', 12 ) .
            str_pad( $result[ 'recipient' ], 30 ) .
            ( $result[ 'error' ] ?? '-' ) . "\n";
    }

    echo "\n";
    echo "📈 Estatísticas:\n";
    echo "   Total de testes: {$totalTests}\n";
    echo "   Testes bem-sucedidos: {$successfulTests}\n";
    echo "   Taxa de sucesso: " . round( ( $successfulTests / $totalTests ) * 100, 1 ) . "%\n";
    echo "   Tempo total: {$totalTime}ms\n";
    echo "   Tempo médio: " . round( $totalTime / $totalTests, 2 ) . "ms\n";

    if ( $successfulTests === $totalTests ) {
        echo "\n🎉 Todos os testes foram executados com sucesso!\n";
    } else {
        echo "\n⚠️ Alguns testes falharam. Verifique os logs para mais detalhes.\n";
    }

    echo "\n📋 Verifique sua caixa de entrada e os logs do sistema para validar o funcionamento completo.\n";
}
