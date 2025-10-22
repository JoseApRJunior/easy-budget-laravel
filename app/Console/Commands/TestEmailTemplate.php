<?php

namespace App\Console\Commands;

use App\Mail\EmailVerificationMail;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Infrastructure\MailerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TestEmailTemplate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-email-template {email?} {--method= : Método de envio (laravel|custom)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testa envio de e-mail usando diferentes métodos para identificar problemas de template';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email  = $this->argument( 'email' ) ?? 'test@example.com';
        $method = $this->option( 'method' ) ?? 'both';

        $this->info( "🔍 Iniciando teste de template de e-mail..." );
        $this->info( "📧 E-mail de destino: {$email}" );
        $this->info( "🔧 Método: {$method}" );

        try {
            // Criar usuário de teste se não existir
            $user = $this->createTestUser( $email );

            if ( $method === 'laravel' || $method === 'both' ) {
                $this->testLaravelMethod( $user );
            }

            if ( $method === 'custom' || $method === 'both' ) {
                $this->testCustomMethod( $user );
            }

            $this->info( "✅ Teste concluído com sucesso!" );

        } catch ( \Exception $e ) {
            $this->error( "❌ Erro durante teste: " . $e->getMessage() );
            Log::error( 'Erro no teste de e-mail', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ] );
        }
    }

    private function createTestUser( string $email ): User
    {
        // Buscar ou criar usuário de teste
        $user = User::where( 'email', $email )->first();

        if ( !$user ) {
            $tenant = Tenant::first() ?? Tenant::create( [ 'name' => 'Test Tenant' ] );

            $user = User::create( [
                'tenant_id' => $tenant->id,
                'email'     => $email,
                'password'  => bcrypt( 'password' ),
                'is_active' => true,
            ] );
        }

        return $user;
    }

    private function testLaravelMethod( User $user ): void
    {
        $this->info( "🧪 Testando método padrão do Laravel..." );

        try {
            // Usar método padrão do Laravel
            $user->sendEmailVerificationNotification();

            $this->info( "✅ Método Laravel executado sem erro" );
            $this->warn( "⚠️  Verifique se o e-mail padrão do Laravel foi enviado" );

        } catch ( \Exception $e ) {
            $this->error( "❌ Erro no método Laravel: " . $e->getMessage() );
        }
    }

    private function testCustomMethod( User $user ): void
    {
        $this->info( "🧪 Testando método personalizado..." );

        try {
            // Usar método personalizado
            $mailerService = app( MailerService::class);
            $result        = $mailerService->sendEmailVerificationMail(
                $user,
                $user->tenant,
                config( 'app.url' ) . '/confirm-account?token=test-token-123',
            );

            if ( $result->isSuccess() ) {
                $this->info( "✅ Método personalizado executado com sucesso" );
                $this->info( "📋 Dados do resultado: " . json_encode( $result->getData() ) );
            } else {
                $this->error( "❌ Método personalizado falhou: " . $result->getMessage() );
            }

        } catch ( \Exception $e ) {
            $this->error( "❌ Erro no método personalizado: " . $e->getMessage() );
        }
    }

}
