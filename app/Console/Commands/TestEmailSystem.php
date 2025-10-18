<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\EmailVerificationMail;
use App\Mail\WelcomeUserMail;
use App\Mail\PasswordResetNotification;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserConfirmationToken;
use App\Services\Infrastructure\ConfirmationLinkService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Comando para testar o sistema de e-mail refatorado.
 *
 * Este comando testa especificamente:
 * - EmailVerificationMail
 * - WelcomeUserMail
 * - PasswordResetNotification
 *
 * Envia e-mails para o endereço especificado e gera relatório detalhado.
 */
class TestEmailSystem extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:test-email-system
                            {email : E-mail para envio dos testes}
                            {--tenant-id=1 : ID do tenant para contexto multi-tenant}';

    /**
     * The console command description.
     */
    protected $description = 'Testa o sistema de e-mail refatorado enviando diferentes tipos de e-mail';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email    = $this->argument( 'email' );
        $tenantId = (int) $this->option( 'tenant-id' );

        $this->info( '🚀 Iniciando teste do sistema de e-mail refatorado' );
        $this->info( "📧 E-mail de destino: {$email}" );
        $this->info( "🏢 Tenant ID: {$tenantId}" );
        $this->newLine();

        // Buscar ou criar usuário de teste
        $user = $this->getOrCreateTestUser( $email, $tenantId );

        // Buscar ou criar tenant
        $tenant = $this->getOrCreateTenant( $tenantId );

        $results = [];

        try {
            // Teste 1: EmailVerificationMail
            $this->info( '📋 Teste 1: EmailVerificationMail' );
            $result1   = $this->testEmailVerificationMail( $user, $tenant );
            $results[] = $result1;
            $this->newLine();

            // Teste 2: WelcomeUserMail
            $this->info( '📋 Teste 2: WelcomeUserMail' );
            $result2   = $this->testWelcomeUserMail( $user, $tenant );
            $results[] = $result2;
            $this->newLine();

            // Teste 3: PasswordResetNotification
            $this->info( '📋 Teste 3: PasswordResetNotification' );
            $result3   = $this->testPasswordResetNotification( $user );
            $results[] = $result3;
            $this->newLine();

            // Relatório final
            $this->displayResults( $results );

            return self::SUCCESS;

        } catch ( \Throwable $e ) {
            $this->error( '❌ Erro durante execução dos testes: ' . $e->getMessage() );
            Log::error( 'Erro no teste de e-mail', [
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
                'email'     => $email,
                'tenant_id' => $tenantId,
            ] );

            return self::FAILURE;
        }
    }

    /**
     * Obtém ou cria usuário de teste.
     */
    private function getOrCreateTestUser( string $email, int $tenantId ): User
    {
        $user = User::where( 'email', $email )->first();

        if ( !$user ) {
            $this->warn( "Usuário {$email} não encontrado. Criando usuário de teste..." );

            $user = User::create( [
                'tenant_id'         => $tenantId,
                'email'             => $email,
                'password'          => bcrypt( 'test_password_123' ),
                'is_active'         => true,
                'email_verified_at' => now(),
            ] );

            $this->info( "✅ Usuário de teste criado com ID: {$user->id}" );
        } else {
            $this->info( "✅ Usuário encontrado com ID: {$user->id}" );
        }

        return $user;
    }

    /**
     * Obtém ou cria tenant.
     */
    private function getOrCreateTenant( int $tenantId ): Tenant
    {
        $tenant = Tenant::find( $tenantId );

        if ( !$tenant ) {
            $this->warn( "Tenant {$tenantId} não encontrado. Criando tenant de teste..." );

            $tenant = Tenant::create( [
                'name'      => 'Tenant de Teste',
                'is_active' => true,
            ] );

            $this->info( "✅ Tenant de teste criado com ID: {$tenant->id}" );
        } else {
            $this->info( "✅ Tenant encontrado: {$tenant->name}" );
        }

        return $tenant;
    }

    /**
     * Testa EmailVerificationMail.
     */
    private function testEmailVerificationMail( User $user, Tenant $tenant ): array
    {
        $startTime = microtime( true );

        try {
            $this->line( '  📤 Enviando EmailVerificationMail...' );

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

            $this->line( "  ✅ EmailVerificationMail enviado com sucesso em {$executionTime}ms" );

            return [
                'type'           => 'EmailVerificationMail',
                'success'        => true,
                'execution_time' => $executionTime,
                'recipient'      => $user->email,
                'error'          => null,
            ];

        } catch ( \Throwable $e ) {
            $executionTime = round( ( microtime( true ) - $startTime ) * 1000, 2 );

            $this->line( "  ❌ Erro no EmailVerificationMail: {$e->getMessage()}" );

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

    /**
     * Testa WelcomeUserMail.
     */
    private function testWelcomeUserMail( User $user, Tenant $tenant ): array
    {
        $startTime = microtime( true );

        try {
            $this->line( '  📤 Enviando WelcomeUserMail...' );

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

            $this->line( "  ✅ WelcomeUserMail enviado com sucesso em {$executionTime}ms" );

            return [
                'type'           => 'WelcomeUserMail',
                'success'        => true,
                'execution_time' => $executionTime,
                'recipient'      => $user->email,
                'error'          => null,
            ];

        } catch ( \Throwable $e ) {
            $executionTime = round( ( microtime( true ) - $startTime ) * 1000, 2 );

            $this->line( "  ❌ Erro no WelcomeUserMail: {$e->getMessage()}" );

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

    /**
     * Testa PasswordResetNotification.
     */
    private function testPasswordResetNotification( User $user ): array
    {
        $startTime = microtime( true );

        try {
            $this->line( '  📤 Enviando PasswordResetNotification...' );

            // Gerar token de reset
            $resetToken = Str::random( 64 );

            // Enviar e-mail
            $mail = new PasswordResetNotification( $user, $resetToken );
            Mail::to( $user->email )->send( $mail );

            $executionTime = round( ( microtime( true ) - $startTime ) * 1000, 2 );

            $this->line( "  ✅ PasswordResetNotification enviado com sucesso em {$executionTime}ms" );

            return [
                'type'           => 'PasswordResetNotification',
                'success'        => true,
                'execution_time' => $executionTime,
                'recipient'      => $user->email,
                'error'          => null,
            ];

        } catch ( \Throwable $e ) {
            $executionTime = round( ( microtime( true ) - $startTime ) * 1000, 2 );

            $this->line( "  ❌ Erro no PasswordResetNotification: {$e->getMessage()}" );

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

    /**
     * Exibe resultados dos testes.
     */
    private function displayResults( array $results ): void
    {
        $this->info( '📊 RELATÓRIO FINAL DOS TESTES' );
        $this->newLine();

        $totalTests      = count( $results );
        $successfulTests = count( array_filter( $results, fn( $result ) => $result[ 'success' ] ) );
        $totalTime       = array_sum( array_column( $results, 'execution_time' ) );

        // Tabela de resultados
        $this->table(
            [ 'Tipo de E-mail', 'Status', 'Tempo (ms)', 'Destinatário', 'Erro' ],
            array_map( function ( $result ) {
                return [
                    $result[ 'type' ],
                    $result[ 'success' ] ? '✅ Sucesso' : '❌ Falha',
                    $result[ 'execution_time' ] . 'ms',
                    $result[ 'recipient' ],
                    $result[ 'error' ] ?? '-',
                ];
            }, $results ),
        );

        $this->newLine();

        // Estatísticas finais
        $this->line( "📈 <fg=cyan>Estatísticas:</>" );
        $this->line( "   Total de testes: <fg=yellow>{$totalTests}</>" );
        $this->line( "   Testes bem-sucedidos: <fg=green>{$successfulTests}</>" );
        $this->line( "   Taxa de sucesso: <fg=" . ( $successfulTests === $totalTests ? 'green' : 'red' ) . round( ( $successfulTests / $totalTests ) * 100, 1 ) . '%</>' );
        $this->line( "   Tempo total: <fg=yellow>{$totalTime}ms</>" );
        $this->line( "   Tempo médio: <fg=yellow>" . round( $totalTime / $totalTests, 2 ) . 'ms</>' );

        if ( $successfulTests === $totalTests ) {
            $this->info( '🎉 Todos os testes foram executados com sucesso!' );
        } else {
            $this->warn( '⚠️ Alguns testes falharam. Verifique os logs para mais detalhes.' );
        }

        $this->info( '📋 Verifique sua caixa de entrada e os logs do sistema para validar o funcionamento completo.' );
    }

}
