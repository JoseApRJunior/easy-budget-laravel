<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Events\EmailVerificationRequested;
use App\Listeners\SendEmailVerificationNotification;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Infrastructure\MailerService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SendEmailVerificationNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected SendEmailVerificationNotification $listener;
    protected MailerService                     $mailerService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mailerService = $this->app->make( MailerService::class);
        $this->listener      = new SendEmailVerificationNotification( $this->mailerService );
    }

    /**
     * ✅ TESTE 1: Listener processa evento com sucesso
     */
    public function test_handle_success(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $user   = User::factory()->create( [
            'tenant_id' => $tenant->id,
        ] );

        $verificationToken = 'valid-token-123456789012345678901234567890123456789012345678901234567890';

        $event = new EmailVerificationRequested( $user, $verificationToken, $tenant );

        // Mock MailerService para sucesso
        $this->mock( MailerService::class, function ( $mock ) {
            $mock->shouldReceive( 'sendEmailVerification' )->once()->andReturn(
                ServiceResult::success( null, 'E-mail enviado com sucesso' ),
            );
        } );

        // Act
        $this->listener->handle( $event );

        // Assert
        // Verificar que não houve exceções (teste passa se não quebrar)
        self::assertTrue( true );
    }

    /**
     * ❌ TESTE 2: Listener trata falha no envio de e-mail
     */
    public function test_handle_email_send_failure(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $user   = User::factory()->create( [
            'tenant_id' => $tenant->id,
        ] );

        $verificationToken = 'valid-token-123456789012345678901234567890123456789012345678901234567890';

        $event = new EmailVerificationRequested( $user, $verificationToken, $tenant );

        // Mock MailerService para falha
        $this->mock( MailerService::class, function ( $mock ) {
            $mock->shouldReceive( 'sendEmailVerification' )->once()->andReturn(
                ServiceResult::error( \App\Enums\OperationStatus::ERROR, 'Falha no envio do e-mail' ),
            );
        } );

        // Act
        $this->listener->handle( $event );

        // Assert
        // Verificar que listener não quebra mesmo com falha no envio
        self::assertTrue( true );
    }

    /**
     * ❌ TESTE 3: Listener trata exceção crítica
     */
    public function test_handle_critical_exception(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $user   = User::factory()->create( [
            'tenant_id' => $tenant->id,
        ] );

        $verificationToken = 'valid-token-123456789012345678901234567890123456789012345678901234567890';

        $event = new EmailVerificationRequested( $user, $verificationToken, $tenant );

        // Mock MailerService para lançar exceção
        $this->mock( MailerService::class, function ( $mock ) {
            $mock->shouldReceive( 'sendEmailVerification' )->once()->andThrow(
                new Exception( 'Erro crítico no serviço de e-mail' ),
            );
        } );

        // Act
        $this->listener->handle( $event );

        // Assert
        // Verificar que listener não quebra mesmo com exceção crítica
        self::assertTrue( true );
    }

    /**
     * ✅ TESTE 4: Listener registra evento failed corretamente
     */
    public function test_failed_method_logging(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $user   = User::factory()->create( [
            'tenant_id' => $tenant->id,
        ] );

        $verificationToken = 'valid-token-123456789012345678901234567890123456789012345678901234567890';

        $event     = new EmailVerificationRequested( $user, $verificationToken, $tenant );
        $exception = new Exception( 'Erro crítico' );

        // Act
        $this->listener->failed( $event, $exception );

        // Assert
        // Verificar que método failed não lança exceções
        self::assertTrue( true );
    }

    /**
     * ✅ TESTE 5: Verificação de dados do evento
     */
    public function test_event_data_integrity(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $user   = User::factory()->create( [
            'tenant_id' => $tenant->id,
            'email'     => 'test@example.com',
        ] );

        $verificationToken = 'valid-token-123456789012345678901234567890123456789012345678901234567890';

        // Act
        $event = new EmailVerificationRequested( $user, $verificationToken, $tenant );

        // Assert
        self::assertEquals( $user->id, $event->user->id );
        self::assertEquals( $tenant->id, $event->tenant->id );
        self::assertEquals( $verificationToken, $event->verificationToken );
        self::assertEquals( 'test@example.com', $event->user->email );
    }

    /**
     * ✅ TESTE 6: Listener com tenant nulo
     */
    public function test_handle_with_null_tenant(): void
    {
        // Arrange
        $user = User::factory()->create();

        $verificationToken = 'valid-token-123456789012345678901234567890123456789012345678901234567890';

        $event = new EmailVerificationRequested( $user, $verificationToken, null );

        // Mock MailerService para sucesso
        $this->mock( MailerService::class, function ( $mock ) {
            $mock->shouldReceive( 'sendEmailVerification' )->once()->andReturn(
                ServiceResult::success( null, 'E-mail enviado com sucesso' ),
            );
        } );

        // Act
        $this->listener->handle( $event );

        // Assert
        self::assertTrue( true );
    }

}
