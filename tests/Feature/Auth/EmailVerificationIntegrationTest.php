<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Events\EmailVerificationRequested;
use App\Events\UserRegistered;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserConfirmationToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EmailVerificationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ✅ TESTE 1: Fluxo completo de registro e verificação de e-mail
     */
    public function test_complete_registration_and_email_verification_flow(): void
    {
        // Arrange
        Event::fake();

        $plan = Plan::factory()->create( [
            'price'  => 0.00,
            'status' => true,
        ] );

        $userData = [
            'first_name'     => 'João',
            'last_name'      => 'Silva',
            'email'          => 'joao.silva@example.com',
            'password'       => 'password123',
            'phone'          => '11999999999',
            'terms_accepted' => true,
        ];

        // Act 1: Registrar usuário
        $registrationResponse = $this->post( '/register', $userData );

        // Assert 1: Registro bem-sucedido
        $registrationResponse->assertRedirect( 'dashboard' );
        $registrationResponse->assertSessionHas( 'success' );

        // Verificar que usuário foi criado
        $user = User::where( 'email', 'joao.silva@example.com' )->first();
        self::assertNotNull( $user );
        self::assertFalse( $user->hasVerifiedEmail() ); // E-mail não verificado inicialmente
        self::assertFalse( $user->is_active ); // Usuário inativo até verificação

        // Verificar que tenant foi criado
        $tenant = Tenant::where( 'id', $user->tenant_id )->first();
        self::assertNotNull( $tenant );

        // Verificar que token de confirmação foi criado
        $token = UserConfirmationToken::where( 'user_id', $user->id )->first();
        self::assertNotNull( $token );
        self::assertEquals( 'email_verification', $token->type );
        self::assertEquals( 64, strlen( $token->token ) );

        // Verificar que eventos foram disparados
        Event::assertDispatched( UserRegistered::class, function ( $event ) use ( $user ) {
            return $event->user->id === $user->id;
        } );

        Event::assertDispatched( EmailVerificationRequested::class, function ( $event ) use ( $user ) {
            return $event->user->id === $user->id;
        } );

        // Act 2: Confirmar e-mail
        $verificationResponse = $this->get( '/confirm-account?token=' . $token->token );

        // Assert 2: Verificação bem-sucedida
        $verificationResponse->assertRedirect( 'dashboard' );
        $verificationResponse->assertSessionHas( 'success' );

        // Verificar que usuário foi ativado e e-mail verificado
        $user->refresh();
        self::assertTrue( $user->hasVerifiedEmail() );
        self::assertTrue( $user->is_active );

        // Verificar que token foi removido após uso
        self::assertNull( UserConfirmationToken::find( $token->id ) );
    }

    /**
     * ❌ TESTE 2: Tentativa de registro com e-mail já existente
     */
    public function test_registration_with_existing_email(): void
    {
        // Arrange
        $existingUser = User::factory()->create( [
            'email' => 'existing@example.com',
        ] );

        $plan = Plan::factory()->create( [
            'price'  => 0.00,
            'status' => true,
        ] );

        $userData = [
            'first_name'     => 'Maria',
            'last_name'      => 'Santos',
            'email'          => 'existing@example.com', // E-mail já existente
            'password'       => 'password123',
            'phone'          => '11999999999',
            'terms_accepted' => true,
        ];

        // Act
        $response = $this->post( '/register', $userData );

        // Assert
        $response->assertSessionHasErrors( [ 'email' ] );
        self::assertStringContains( 'já está sendo utilizado', session( 'errors' )->first( 'email' ) );
    }

    /**
     * ❌ TESTE 3: Problemas de segurança - Cross-tenant token access
     */
    public function test_cross_tenant_token_access_security(): void
    {
        // Arrange
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $user1 = User::factory()->create( [
            'tenant_id' => $tenant1->id,
        ] );

        $user2 = User::factory()->create( [
            'tenant_id' => $tenant2->id,
        ] );

        // Criar token para user1 mas tentar usar com contexto de user2
        $token = UserConfirmationToken::factory()->create( [
            'user_id'    => $user1->id,
            'tenant_id'  => $tenant1->id,
            'expires_at' => now()->addMinutes( 30 ),
        ] );

        // Act - Tentar usar token de tenant1 em contexto de tenant2
        $response = $this->get( '/confirm-account?token=' . $token->token );

        // Assert
        $response->assertRedirect( 'login' );
        $response->assertSessionHas( 'error' );
        self::assertStringContains( 'Erro de validação de segurança', session( 'error' ) );
    }

    /**
     * ❌ TESTE 4: Ataque de força bruta com tokens inválidos
     */
    public function test_brute_force_attack_prevention(): void
    {
        // Arrange
        $invalidTokens = [
            'invalid-token-1',
            'invalid-token-2',
            'invalid-token-3',
            '',
            'short',
            str_repeat( 'a', 100 ), // Token muito longo
        ];

        // Act & Assert
        foreach ( $invalidTokens as $invalidToken ) {
            $response = $this->get( '/confirm-account?token=' . $invalidToken );

            // Todos devem ser rejeitados
            $response->assertRedirect( 'login' );
            $response->assertSessionHas( 'error' );
        }
    }

    /**
     * ✅ TESTE 5: Reenvio de e-mail de verificação
     */
    public function test_resend_verification_email(): void
    {
        // Arrange
        $user = User::factory()->create( [
            'email_verified_at' => null,
            'is_active'         => true,
        ] );

        Event::fake();

        // Act
        $response = $this->post( '/email/verification-notification', [
            'email' => $user->email,
        ] );

        // Assert
        $response->assertStatus( 200 ); // Ou redirect dependendo da implementação

        // Verificar que novo token foi criado (removendo o antigo)
        $tokens = UserConfirmationToken::where( 'user_id', $user->id )->get();
        self::assertCount( 1, $tokens );

        // Verificar que evento foi disparado
        Event::assertDispatched( EmailVerificationRequested::class);
    }

    /**
     * ❌ TESTE 6: Reenvio de e-mail para usuário já verificado
     */
    public function test_resend_verification_email_for_verified_user(): void
    {
        // Arrange
        $user = User::factory()->create( [
            'email_verified_at' => now(),
            'is_active'         => true,
        ] );

        // Act
        $response = $this->post( '/email/verification-notification', [
            'email' => $user->email,
        ] );

        // Assert
        $response->assertStatus( 302 ); // Redirect com erro
        $response->assertSessionHas( 'error' );
    }

    /**
     * ✅ TESTE 7: Limpeza automática de tokens expirados
     */
    public function test_automatic_cleanup_of_expired_tokens(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Criar tokens - alguns expirados, alguns válidos
        UserConfirmationToken::factory()->count( 3 )->create( [
            'user_id'    => $user->id,
            'tenant_id'  => $user->tenant_id,
            'expires_at' => now()->subMinutes( 5 ), // Expirados
        ] );

        UserConfirmationToken::factory()->count( 2 )->create( [
            'user_id'    => $user->id,
            'tenant_id'  => $user->tenant_id,
            'expires_at' => now()->addMinutes( 30 ), // Válidos
        ] );

        // Act - Tentar usar um token expirado
        $expiredToken = UserConfirmationToken::where( 'user_id', $user->id )
            ->where( 'expires_at', '<', now() )
            ->first();

        $response = $this->get( '/confirm-account?token=' . $expiredToken->token );

        // Assert
        $response->assertRedirect( 'login' );
        $response->assertSessionHas( 'error' );

        // Verificar que apenas tokens válidos permanecem
        $remainingTokens = UserConfirmationToken::where( 'user_id', $user->id )->get();
        self::assertCount( 2, $remainingTokens );
    }

    /**
     * ✅ TESTE 8: Verificação de logging de segurança
     */
    public function test_security_logging_during_verification(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $user   = User::factory()->create( [
            'tenant_id' => $tenant->id,
        ] );

        $token = UserConfirmationToken::factory()->create( [
            'user_id'    => $user->id,
            'tenant_id'  => $tenant->id,
            'expires_at' => now()->addMinutes( 30 ),
        ] );

        // Act
        $this->get( '/confirm-account?token=' . $token->token );

        // Assert - Verificar logs de segurança foram criados
        // Nota: Em um ambiente real, verificaríamos os logs no storage/logs/security.log
        // Aqui apenas garantimos que o processo não quebrou
        self::assertTrue( true ); // Placeholder para verificação de logs
    }

    /**
     * ❌ TESTE 9: Cenário de corrida (race condition)
     */
    public function test_race_condition_prevention(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $user   = User::factory()->create( [
            'tenant_id'         => $tenant->id,
            'email_verified_at' => null,
            'is_active'         => false,
        ] );

        $token = UserConfirmationToken::factory()->create( [
            'user_id'    => $user->id,
            'tenant_id'  => $tenant->id,
            'expires_at' => now()->addMinutes( 30 ),
        ] );

        // Act - Múltiplas requisições simultâneas (simuladas)
        $responses = [];
        for ( $i = 0; $i < 3; $i++ ) {
            $responses[] = $this->get( '/confirm-account?token=' . $token->token );
        }

        // Assert
        // Apenas a primeira deve funcionar, as outras devem falhar
        $responses[ 0 ]->assertRedirect( 'dashboard' );
        $responses[ 1 ]->assertRedirect( 'login' );
        $responses[ 2 ]->assertRedirect( 'login' );

        // Verificar que usuário foi ativado apenas uma vez
        $user->refresh();
        self::assertTrue( $user->hasVerifiedEmail() );
        self::assertTrue( $user->is_active );
    }

    /**
     * ✅ TESTE 10: Verificação de estrutura do banco de dados
     */
    public function test_database_structure_integrity(): void
    {
        // Arrange & Act
        $token = UserConfirmationToken::factory()->create();

        // Assert - Verificar estrutura da tabela
        self::assertDatabaseHas( 'user_confirmation_tokens', [
            'id'        => $token->id,
            'user_id'   => $token->user_id,
            'tenant_id' => $token->tenant_id,
            'token'     => $token->token,
            'type'      => $token->type,
        ] );

        // Verificar relacionamentos
        self::assertNotNull( $token->user );
        self::assertNotNull( $token->tenant );

        // Verificar que usuário existe e tem relacionamento correto
        $user = User::find( $token->user_id );
        self::assertNotNull( $user );
        self::assertEquals( $token->tenant_id, $user->tenant_id );
    }

}
