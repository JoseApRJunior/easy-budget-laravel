<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Application;

use App\Enums\OperationStatus;
use App\Models\User;
use App\Models\UserConfirmationToken;
use App\Repositories\UserConfirmationTokenRepository;
use App\Repositories\UserRepository;
use App\Services\Application\EmailVerificationService;
use App\Services\Application\UserConfirmationTokenService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class EmailVerificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected EmailVerificationService        $emailVerificationService;
    protected UserConfirmationTokenRepository $userConfirmationTokenRepository;
    protected UserRepository                  $userRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userConfirmationTokenRepository = $this->app->make( UserConfirmationTokenRepository::class);
        $this->userRepository                  = $this->app->make( UserRepository::class);
        $this->emailVerificationService        = $this->app->make( EmailVerificationService::class);
    }

    public function test_create_confirmation_token_success(): void
    {
        // Arrange
        $user = User::factory()->create( [
            'email_verified_at' => null,
            'is_active'         => true,
        ] );

        Event::fake();

        // Act
        $result = $this->emailVerificationService->createConfirmationToken( $user );

        // Assert
        self::assertTrue( $result->isSuccess() );
        self::assertArrayHasKey( 'token', $result->getData() );
        self::assertArrayHasKey( 'expires_at', $result->getData() );
        self::assertArrayHasKey( 'user', $result->getData() );

        // Verificar se token foi criado no banco
        $token = UserConfirmationToken::where( 'user_id', $user->id )->first();
        self::assertNotNull( $token );
        self::assertEquals( $user->id, $token->user_id );
        self::assertEquals( $user->tenant_id, $token->tenant_id );
        self::assertEquals( 43, strlen( $token->token ) ); // base64url format: 32 bytes = 43 caracteres
        self::assertEquals( \App\Enums\TokenType::EMAIL_VERIFICATION, $token->type );

        // Verificar se evento foi disparado
        Event::assertDispatched( \App\Events\EmailVerificationRequested::class, function ( $event ) use ( $user ) {
            return $event->user->id === $user->id;
        } );
    }

    public function test_create_confirmation_token_removes_old_tokens(): void
    {
        // Arrange
        $user = User::factory()->create( [
            'email_verified_at' => null,
            'is_active'         => true,
        ] );

        // Criar token antigo
        UserConfirmationToken::factory()->create( [
            'user_id'    => $user->id,
            'tenant_id'  => $user->tenant_id,
            'expires_at' => now()->addMinutes( 30 ),
        ] );

        // Act
        $result = $this->emailVerificationService->createConfirmationToken( $user );

        // Assert
        self::assertTrue( $result->isSuccess() );

        // Verificar que apenas um token existe (o novo)
        $tokens = UserConfirmationToken::where( 'user_id', $user->id )->get();
        self::assertCount( 1, $tokens );
    }

    public function test_resend_confirmation_email_for_unverified_user(): void
    {
        // Arrange
        $user = User::factory()->create( [
            'email_verified_at' => null,
            'is_active'         => true,
        ] );

        Event::fake();

        // Act
        $result = $this->emailVerificationService->resendConfirmationEmail( $user );

        // Assert
        self::assertTrue( $result->isSuccess() );

        // Verificar se evento foi disparado
        Event::assertDispatched( \App\Events\EmailVerificationRequested::class);
    }

    public function test_resend_confirmation_email_for_already_verified_user(): void
    {
        // Arrange
        $user = User::factory()->create( [
            'email_verified_at' => now(),
            'is_active'         => true,
        ] );

        // Act
        $result = $this->emailVerificationService->resendConfirmationEmail( $user );

        // Assert
        self::assertFalse( $result->isSuccess() );
        self::assertEquals( OperationStatus::CONFLICT, $result->getStatus() );
        self::assertStringContainsString( 'já foi verificado', $result->getMessage() );
    }

    public function test_resend_confirmation_email_for_inactive_user(): void
    {
        // Arrange
        $user = User::factory()->create( [
            'email_verified_at' => null,
            'is_active'         => false,
        ] );

        // Act
        $result = $this->emailVerificationService->resendConfirmationEmail( $user );

        // Assert
        self::assertFalse( $result->isSuccess() );
        self::assertEquals( OperationStatus::CONFLICT, $result->getStatus() );
        self::assertStringContainsString( 'inativo', $result->getMessage() );
    }

    public function test_find_valid_token_success(): void
    {
        // Arrange
        $user  = User::factory()->create();
        $token = UserConfirmationToken::factory()->create( [
            'user_id'    => $user->id,
            'tenant_id'  => $user->tenant_id,
            'expires_at' => now()->addMinutes( 30 ),
        ] );

        // Act
        $result = $this->emailVerificationService->findValidToken( $token->token );

        // Assert
        self::assertTrue( $result->isSuccess() );
        self::assertArrayHasKey( 'token', $result->getData() );
        self::assertArrayHasKey( 'user', $result->getData() );
        self::assertEquals( $token->id, $result->getData()[ 'token' ]->id );
        self::assertEquals( $user->id, $result->getData()[ 'user' ]->id );
    }

    public function test_find_valid_token_not_found(): void
    {
        // Act
        $result = $this->emailVerificationService->findValidToken( 'invalid-token' );

        // Assert
        self::assertFalse( $result->isSuccess() );
        self::assertEquals( OperationStatus::NOT_FOUND, $result->getStatus() );
        self::assertStringContainsString( 'não encontrado', $result->getMessage() );
    }

    public function test_find_valid_token_expired(): void
    {
        // Arrange
        $user  = User::factory()->create();
        $token = UserConfirmationToken::factory()->create( [
            'user_id'    => $user->id,
            'tenant_id'  => $user->tenant_id,
            'expires_at' => now()->subMinutes( 5 ), // Token expirado
        ] );

        // Act
        $result = $this->emailVerificationService->findValidToken( $token->token );

        // Assert
        self::assertFalse( $result->isSuccess() );
        self::assertEquals( OperationStatus::CONFLICT, $result->getStatus() );
        self::assertStringContainsString( 'expirado', $result->getMessage() );

        // Verificar que token foi removido
        self::assertNull( UserConfirmationToken::find( $token->id ) );
    }

    public function test_find_valid_token_user_not_found(): void
    {
        // Arrange
        $user  = User::factory()->create();
        $token = UserConfirmationToken::factory()->create( [
            'user_id'    => $user->id,
            'tenant_id'  => $user->tenant_id,
            'expires_at' => now()->addMinutes( 30 ),
        ] );

        // Remover usuário para simular cenário de usuário não encontrado
        $user->delete();

        // Act
        $result = $this->emailVerificationService->findValidToken( $token->token );

        // Assert
        self::assertFalse( $result->isSuccess() );
        self::assertEquals( OperationStatus::NOT_FOUND, $result->getStatus() );
        self::assertStringContainsString( 'não encontrado', $result->getMessage() );
    }

    public function test_remove_token_success(): void
    {
        // Arrange
        $token = UserConfirmationToken::factory()->create();

        // Act
        $result = $this->emailVerificationService->removeToken( $token );

        // Assert
        self::assertTrue( $result->isSuccess() );
        self::assertNull( UserConfirmationToken::find( $token->id ) );
    }

    public function test_cleanup_expired_tokens(): void
    {
        // Arrange
        UserConfirmationToken::factory()->count( 3 )->create( [
            'expires_at' => now()->subMinutes( 5 ), // Tokens expirados
        ] );

        UserConfirmationToken::factory()->count( 2 )->create( [
            'expires_at' => now()->addMinutes( 30 ), // Tokens válidos
        ] );

        // Act
        $result = $this->emailVerificationService->cleanupExpiredTokens();

        // Assert
        self::assertTrue( $result->isSuccess() );
        self::assertEquals( 3, $result->getData()[ 'tokens_removed' ] );

        // Verificar que apenas tokens válidos permanecem
        self::assertEquals( 2, UserConfirmationToken::count() );
    }

    public function test_create_confirmation_token_exception_handling(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Mock UserConfirmationTokenService para retornar erro
        $mockService = Mockery::mock( UserConfirmationTokenService::class);
        $mockService->shouldReceive( 'createToken' )->andReturn(
            ServiceResult::error( OperationStatus::ERROR, 'Erro interno ao criar token de confirmação. Tente novamente.' ),
        );

        app()->instance( UserConfirmationTokenService::class, $mockService );

        // Recriar o serviço para usar o mock
        $this->emailVerificationService = app( EmailVerificationService::class);

        // Act
        $result = $this->emailVerificationService->createConfirmationToken( $user );

        // Assert
        self::assertFalse( $result->isSuccess() );
        self::assertEquals( OperationStatus::ERROR, $result->getStatus() );
        self::assertStringContainsString( 'Erro interno', $result->getMessage() );
    }

    public function test_find_valid_token_exception_handling(): void
    {
        // Arrange
        $mockRepository = Mockery::mock( UserConfirmationTokenRepository::class);
        $mockRepository->shouldReceive( 'findByToken' )->andThrow( new Exception( 'Database error' ) );

        app()->instance( UserConfirmationTokenRepository::class, $mockRepository );

        // Recriar o serviço para usar o mock
        $this->emailVerificationService = app( EmailVerificationService::class);

        // Act
        $result = $this->emailVerificationService->findValidToken( 'valid-token' );

        // Assert
        self::assertFalse( $result->isSuccess() );
        self::assertEquals( OperationStatus::ERROR, $result->getStatus() );
        self::assertStringContainsString( 'Erro interno', $result->getMessage() );
    }

}
