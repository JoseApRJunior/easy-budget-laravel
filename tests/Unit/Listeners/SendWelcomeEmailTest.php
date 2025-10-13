<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Events\UserRegistered;
use App\Listeners\SendWelcomeEmail;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Infrastructure\MailerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Testes unitários para o listener SendWelcomeEmail.
 *
 * Esta classe testa o comportamento do listener SendWelcomeEmail,
 * incluindo casos de sucesso e falha no envio de e-mails.
 */
class SendWelcomeEmailTest extends TestCase
{
    use RefreshDatabase;

    private SendWelcomeEmail $listener;
    private MailerService    $mailerService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mailerService = $this->app->make( MailerService::class);
        $this->listener      = new SendWelcomeEmail();
    }

    /**
     * Testa processamento bem-sucedido do evento.
     */
    public function test_handle_successful_email_sending(): void
    {
        // Arrange
        $user = User::factory()->create( [
            'email'      => 'test@example.com',
            'first_name' => 'Teste',
        ] );

        $tenant = Tenant::factory()->create( [
            'name' => 'test-tenant',
        ] );

        $event = new UserRegistered( $user, $tenant );

        // Mock para simular sucesso no envio
        $this->mock( MailerService::class, function ( $mock ) {
            $mock->shouldReceive( 'sendWelcomeEmail' )->once()->andReturn(
                \App\Support\ServiceResult::success( [ 'queued_at' => now() ], 'E-mail enviado com sucesso' ),
            );
        } );

        // Act
        $this->listener->handle( $event );

        // Assert - Não deve lançar exceção
        $this->assertTrue( true );
    }

    /**
     * Testa processamento com falha no envio de e-mail.
     */
    public function test_handle_email_sending_failure(): void
    {
        // Arrange
        $user = User::factory()->create( [
            'email' => 'test@example.com',
        ] );

        $tenant = Tenant::factory()->create();

        $event = new UserRegistered( $user, $tenant );

        // Mock para simular falha no envio
        $this->mock( MailerService::class, function ( $mock ) {
            $mock->shouldReceive( 'sendWelcomeEmail' )->once()->andReturn(
                \App\Support\ServiceResult::error( \App\Enums\OperationStatus::ERROR, 'Erro no envio de e-mail' ),
            );
        } );

        // Act & Assert
        $this->expectException( \Exception::class);
        $this->expectExceptionMessage( 'Falha no envio de e-mail de boas-vindas' );

        $this->listener->handle( $event );
    }

    /**
     * Testa processamento com exceção inesperada.
     */
    public function test_handle_unexpected_exception(): void
    {
        // Arrange
        Log::spy();

        $user   = User::factory()->create();
        $tenant = Tenant::factory()->create();
        $event  = new UserRegistered( $user, $tenant );

        // Mock para simular exceção inesperada
        $this->mock( MailerService::class, function ( $mock ) {
            $mock->shouldReceive( 'sendWelcomeEmail' )->once()->andThrow( new \Exception( 'Erro inesperado' ) );
        } );

        // Act & Assert
        $this->expectException( \Exception::class);
        $this->expectExceptionMessage( 'Erro inesperado' );

        $this->listener->handle( $event );

        // Verificar se erro foi logado
        Log::shouldHaveReceived( 'error' )->once();
    }

    /**
     * Testa configuração de tentativas de retry.
     */
    public function test_retry_configuration(): void
    {
        // Assert
        $this->assertEquals( 3, $this->listener->tries );
        $this->assertEquals( 30, $this->listener->backoff );
    }

    /**
     * Testa método failed quando listener falha.
     */
    public function test_failed_method_logging(): void
    {
        // Arrange
        Log::spy();

        $user = User::factory()->create( [
            'email' => 'failed@example.com',
        ] );

        $tenant    = Tenant::factory()->create();
        $event     = new UserRegistered( $user, $tenant );
        $exception = new \Exception( 'Erro crítico' );

        // Act
        $this->listener->failed( $event, $exception );

        // Assert
        Log::shouldHaveReceived( 'critical' )
            ->once()
            ->with( 'Listener SendWelcomeEmail falhou após todas as tentativas', [
                'user_id'  => $user->id,
                'email'    => $user->email,
                'error'    => 'Erro crítico',
                'attempts' => 3,
            ] );
    }

    /**
     * Testa processamento com usuário sem método de verificação.
     */
    public function test_handle_user_without_verification_method(): void
    {
        // Arrange
        $user   = User::factory()->create();
        $tenant = Tenant::factory()->create();
        $event  = new UserRegistered( $user, $tenant );

        // Mock para simular sucesso mesmo sem método de verificação
        $this->mock( MailerService::class, function ( $mock ) {
            $mock->shouldReceive( 'sendWelcomeEmail' )->once()->andReturn(
                \App\Support\ServiceResult::success( [ 'queued_at' => now() ], 'E-mail enviado' ),
            );
        } );

        // Act
        $this->listener->handle( $event );

        // Assert - Deve funcionar normalmente
        $this->assertTrue( true );
    }

    /**
     * Testa processamento com tenant nulo.
     */
    public function test_handle_with_null_tenant(): void
    {
        // Arrange
        $user  = User::factory()->create();
        $event = new UserRegistered( $user, null );

        // Mock para simular sucesso
        $this->mock( MailerService::class, function ( $mock ) {
            $mock->shouldReceive( 'sendWelcomeEmail' )->once()->andReturn(
                \App\Support\ServiceResult::success( [ 'queued_at' => now() ], 'E-mail enviado' ),
            );
        } );

        // Act
        $this->listener->handle( $event );

        // Assert - Deve funcionar normalmente
        $this->assertTrue( true );
    }

    /**
     * Testa implementação da interface ShouldQueue.
     */
    public function test_should_queue_interface(): void
    {
        // Assert
        $this->assertContains( \Illuminate\Contracts\Queue\ShouldQueue::class, class_implements( $this->listener ) );
    }

    /**
     * Testa processamento com dados de usuário complexos.
     */
    public function test_handle_with_complex_user_data(): void
    {
        // Arrange
        $user = User::factory()->create( [
            'email'      => 'complex.user@example.com',
            'first_name' => 'Complex',
            'last_name'  => 'User',
            'is_active'  => true,
            'tenant_id'  => 123,
        ] );

        $tenant = Tenant::factory()->create( [
            'name'      => 'complex-tenant',
            'is_active' => true,
        ] );

        $event = new UserRegistered( $user, $tenant );

        // Mock para simular sucesso
        $this->mock( MailerService::class, function ( $mock ) {
            $mock->shouldReceive( 'sendWelcomeEmail' )->once()->andReturn(
                \App\Support\ServiceResult::success( [ 'queued_at' => now() ], 'E-mail enviado' ),
            );
        } );

        // Act
        $this->listener->handle( $event );

        // Assert - Deve processar corretamente dados complexos
        $this->assertTrue( true );
    }

    /**
     * Testa logging detalhado durante processamento.
     */
    public function test_detailed_logging_during_processing(): void
    {
        // Arrange
        Log::spy();

        $user   = User::factory()->create();
        $tenant = Tenant::factory()->create();
        $event  = new UserRegistered( $user, $tenant );

        // Mock para simular sucesso
        $this->mock( MailerService::class, function ( $mock ) {
            $mock->shouldReceive( 'sendWelcomeEmail' )->once()->andReturn(
                \App\Support\ServiceResult::success( [ 'queued_at' => now() ], 'E-mail enviado' ),
            );
        } );

        // Act
        $this->listener->handle( $event );

        // Assert - Verificar logs de informação
        Log::shouldHaveReceived( 'info' )
            ->once()
            ->with( 'Processando evento UserRegistered para envio de e-mail de boas-vindas', [
                'user_id'   => $user->id,
                'email'     => $user->email,
                'tenant_id' => $tenant->id,
            ] );

        Log::shouldHaveReceived( 'info' )
            ->once()
            ->with( 'E-mail de boas-vindas enviado com sucesso via evento', [
                'user_id'   => $user->id,
                'email'     => $user->email,
                'queued_at' => \Mockery::type( 'Carbon\Carbon' ),
            ] );
    }

}
