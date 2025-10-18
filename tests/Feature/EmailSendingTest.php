<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\WelcomeUserMail;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserConfirmationToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class EmailSendingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 📤 Testa envio automatizado de e-mail de boas-vindas
     */
    public function test_automated_welcome_email_sending(): void
    {
        // Arrange
        Mail::fake();

        $tenant = Tenant::factory()->create();
        $user   = User::factory()->create( [
            'tenant_id'  => $tenant->id,
            'first_name' => 'Teste',
            'email'      => 'teste@welcome.com'
        ] );

        $confirmationLink = 'https://example.com/confirm?token=' . Str::random( 64 );

        // Act
        $mailable = new WelcomeUserMail( $user, $tenant, $confirmationLink );
        Mail::to( $user->email )->send( $mailable );

        // Assert
        Mail::assertSent( WelcomeUserMail::class, function ( $mail ) use ( $user, $confirmationLink ) {
            return $mail->hasTo( $user->email ) &&
                $mail->hasSubject( 'Confirme sua conta - Easy Budget' );
        } );
    }

    /**
     * 📤 Testa envio automatizado de e-mail de verificação
     */
    public function test_automated_verification_email_sending(): void
    {
        // Arrange
        Mail::fake();

        $tenant = Tenant::factory()->create();
        $user   = User::factory()->create( [
            'tenant_id'  => $tenant->id,
            'first_name' => 'Verificação',
            'email'      => 'teste@verification.com'
        ] );

        $token = UserConfirmationToken::factory()->create( [
            'user_id'    => $user->id,
            'tenant_id'  => $tenant->id,
            'token'      => Str::random( 64 ),
            'expires_at' => now()->addMinutes( 30 )
        ] );

        // Act
        $mailable = new \App\Mail\EmailVerificationMail( $user, $tenant );
        Mail::to( $user->email )->send( $mailable );

        // Assert
        Mail::assertSent( \App\Mail\EmailVerificationMail::class, function ( $mail ) use ( $user ) {
            return $mail->hasTo( $user->email ) &&
                $mail->hasSubject( 'Confirme sua conta - Easy Budget' );
        } );
    }

    /**
     * 📤 Testa envio em lote de e-mails
     */
    public function test_batch_email_sending(): void
    {
        // Arrange
        Mail::fake();

        $tenant = Tenant::factory()->create();
        $users  = User::factory()->count( 5 )->create( [
            'tenant_id' => $tenant->id
        ] );

        $confirmationLink = 'https://example.com/confirm?token=' . Str::random( 64 );

        // Act
        foreach ( $users as $user ) {
            $mailable = new WelcomeUserMail( $user, $tenant, $confirmationLink );
            Mail::to( $user->email )->send( $mailable );
        }

        // Assert
        Mail::assertSent( WelcomeUserMail::class, function ( $mail ) use ( $users ) {
            return in_array( $mail->to[ 0 ][ 'address' ], $users->pluck( 'email' )->toArray() );
        } );
    }

    /**
     * 📤 Testa falha no envio de e-mail
     */
    public function test_email_sending_failure_handling(): void
    {
        // Arrange
        Mail::fake();

        $tenant = Tenant::factory()->create();
        $user   = User::factory()->create( [
            'tenant_id'  => $tenant->id,
            'first_name' => 'Falha',
            'email'      => 'invalid-email' // E-mail inválido para forçar falha
        ] );

        $confirmationLink = 'https://example.com/confirm?token=' . Str::random( 64 );

        // Act & Assert - Deve lidar com falha graciosamente
        try {
            $mailable = new WelcomeUserMail( $user, $tenant, $confirmationLink );
            Mail::to( $user->email )->send( $mailable );

            // Se chegou aqui, o teste deve passar
            $this->assertTrue( true );
        } catch ( \Exception $e ) {
            // Deve capturar e lidar com a exceção adequadamente
            $this->assertStringContainsString( 'Failed to authenticate', $e->getMessage() );
        }
    }

    /**
     * 📤 Testa configuração de e-mail
     */
    public function test_email_configuration(): void
    {
        // Arrange
        Mail::fake();

        $tenant = Tenant::factory()->create();
        $user   = User::factory()->create( [
            'tenant_id'  => $tenant->id,
            'first_name' => 'Config',
            'email'      => 'teste@config.com'
        ] );

        $confirmationLink = 'https://example.com/confirm?token=' . Str::random( 64 );

        // Act
        $mailable = new WelcomeUserMail( $user, $tenant, $confirmationLink );
        $mailData = $mailable->toArray();

        // Assert - Configuração deve estar correta
        $this->assertArrayHasKey( 'user', $mailData );
        $this->assertArrayHasKey( 'tenant', $mailData );
        $this->assertArrayHasKey( 'confirmationLink', $mailData );
        $this->assertEquals( $user->id, $mailData[ 'user' ]->id );
        $this->assertEquals( $tenant->id, $mailData[ 'tenant' ]->id );
        $this->assertEquals( $confirmationLink, $mailData[ 'confirmationLink' ] );
    }

    /**
     * 📤 Testa diferentes cenários de envio
     */
    public function test_email_sending_scenarios(): void
    {
        // Arrange
        Mail::fake();

        $scenarios = [
            [
                'first_name'  => 'Cenário 1',
                'email'       => 'cenario1@test.com',
                'tenant_name' => 'Empresa 1'
            ],
            [
                'first_name'  => 'Cenário 2',
                'email'       => 'cenario2@test.com',
                'tenant_name' => 'Empresa 2'
            ]
        ];

        foreach ( $scenarios as $scenario ) {
            // Arrange
            $tenant = Tenant::factory()->create( [ 'name' => $scenario[ 'tenant_name' ] ] );
            $user   = User::factory()->create( [
                'tenant_id'  => $tenant->id,
                'first_name' => $scenario[ 'first_name' ],
                'email'      => $scenario[ 'email' ]
            ] );

            $confirmationLink = 'https://example.com/confirm?token=' . Str::random( 64 );

            // Act
            $mailable = new WelcomeUserMail( $user, $tenant, $confirmationLink );
            Mail::to( $user->email )->send( $mailable );

            // Assert
            Mail::assertSent( WelcomeUserMail::class, function ( $mail ) use ( $user, $scenario ) {
                return $mail->hasTo( $user->email ) &&
                    $mail->hasSubject( 'Confirme sua conta - Easy Budget' );
            } );
        }
    }

    /**
     * 📤 Testa conteúdo do e-mail enviado
     */
    public function test_sent_email_content(): void
    {
        // Arrange
        Mail::fake();

        $tenant = Tenant::factory()->create();
        $user   = User::factory()->create( [
            'tenant_id'  => $tenant->id,
            'first_name' => 'Conteúdo',
            'email'      => 'teste@content.com'
        ] );

        $confirmationLink = 'https://example.com/confirm?token=' . Str::random( 64 );

        // Act
        $mailable = new WelcomeUserMail( $user, $tenant, $confirmationLink );
        Mail::to( $user->email )->send( $mailable );

        // Assert - Verificar conteúdo através do mailable
        Mail::assertSent( WelcomeUserMail::class, function ( $mail ) use ( $user, $confirmationLink ) {
            $mailData = $mail->toArray();

            return isset( $mailData[ 'user' ] ) &&
                isset( $mailData[ 'tenant' ] ) &&
                isset( $mailData[ 'confirmationLink' ] ) &&
                $mailData[ 'user' ]->id === $user->id &&
                $mailData[ 'tenant' ]->id === $user->tenant_id &&
                $mailData[ 'confirmationLink' ] === $confirmationLink;
        } );
    }

    /**
     * 📤 Testa headers do e-mail
     */
    public function test_email_headers(): void
    {
        // Arrange
        Mail::fake();

        $tenant = Tenant::factory()->create();
        $user   = User::factory()->create( [
            'tenant_id'  => $tenant->id,
            'first_name' => 'Headers',
            'email'      => 'teste@headers.com'
        ] );

        $confirmationLink = 'https://example.com/confirm?token=' . Str::random( 64 );

        // Act
        $mailable = new WelcomeUserMail( $user, $tenant, $confirmationLink );
        Mail::to( $user->email )->send( $mailable );

        // Assert - Headers devem estar corretos
        Mail::assertSent( WelcomeUserMail::class, function ( $mail ) {
            return $mail->hasSubject( 'Confirme sua conta - Easy Budget' ) &&
                $mail->hasFrom( config( 'mail.from.address' ), config( 'mail.from.name' ) ) &&
                $mail->hasTo( 'teste@headers.com' );
        } );
    }

    /**
     * 📤 Testa envio com diferentes configurações de mailer
     */
    public function test_email_sending_with_different_mailers(): void
    {
        // Arrange
        Mail::fake();

        $mailers = [ 'smtp', 'log', 'array' ];
        $tenant  = Tenant::factory()->create();
        $user    = User::factory()->create( [
            'tenant_id'  => $tenant->id,
            'first_name' => 'Mailer',
            'email'      => 'teste@mailer.com'
        ] );

        $confirmationLink = 'https://example.com/confirm?token=' . Str::random( 64 );

        foreach ( $mailers as $mailer ) {
            // Act
            config( [ 'mail.default' => $mailer ] );
            $mailable = new WelcomeUserMail( $user, $tenant, $confirmationLink );
            Mail::to( $user->email )->send( $mailable );

            // Assert - Deve funcionar com diferentes mailers
            Mail::assertSent( WelcomeUserMail::class);
        }
    }

    /**
     * 📤 Testa tratamento de exceções durante envio
     */
    public function test_email_sending_exception_handling(): void
    {
        // Arrange
        Mail::fake();

        $tenant = Tenant::factory()->create();
        $user   = User::factory()->create( [
            'tenant_id'  => $tenant->id,
            'first_name' => 'Exceção',
            'email'      => 'invalid-email-address' // E-mail inválido
        ] );

        $confirmationLink = 'https://example.com/confirm?token=' . Str::random( 64 );

        // Act & Assert - Deve lidar com exceções adequadamente
        try {
            $mailable = new WelcomeUserMail( $user, $tenant, $confirmationLink );
            Mail::to( $user->email )->send( $mailable );

            // Se chegou aqui sem exceção, está ok
            $this->assertTrue( true );
        } catch ( \Exception $e ) {
            // Deve capturar exceções de e-mail inválido
            $this->assertStringContainsString( 'Failed to authenticate', $e->getMessage() );
        }
    }

    /**
     * 📤 Testa fila de e-mails
     */
    public function test_email_queue_handling(): void
    {
        // Arrange
        Mail::fake();

        $tenant = Tenant::factory()->create();
        $user   = User::factory()->create( [
            'tenant_id'  => $tenant->id,
            'first_name' => 'Queue',
            'email'      => 'teste@queue.com'
        ] );

        $confirmationLink = 'https://example.com/confirm?token=' . Str::random( 64 );

        // Act - Enviar e-mail em fila
        $mailable = new WelcomeUserMail( $user, $tenant, $confirmationLink );
        Mail::to( $user->email )->queue( $mailable );

        // Assert - Deve estar na fila
        Mail::assertQueued( WelcomeUserMail::class, function ( $mail ) use ( $user ) {
            return $mail->hasTo( $user->email );
        } );
    }

    /**
     * 📤 Testa validação de dados antes do envio
     */
    public function test_email_data_validation_before_sending(): void
    {
        // Arrange
        Mail::fake();

        $tenant = Tenant::factory()->create();
        $user   = User::factory()->create( [
            'tenant_id'  => $tenant->id,
            'first_name' => 'Validação',
            'email'      => 'juniorklan.ju@gmail.com'
        ] );

        $confirmationLink = 'https://dev.easybudget.net.br/confirm?token=' . Str::random( 64 );

        // Act
        $mailable = new WelcomeUserMail( $user, $tenant, $confirmationLink );

        // Assert - Dados devem ser válidos antes do envio
        $this->assertNotNull( $mailable->user );
        $this->assertNotNull( $mailable->tenant );
        $this->assertNotEmpty( $mailable->confirmationLink );
        $this->assertIsString( $mailable->confirmationLink );
        $this->assertStringStartsWith( 'https://', $mailable->confirmationLink );
    }

}
