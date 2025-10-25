<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Provider;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserConfirmationToken;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CustomVerifyEmailControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ✅ TESTE 1: Fluxo feliz - Confirmação de e-mail com token válido
     */
    public function test_confirm_account_success(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $user   = User::factory()->create( [
            'tenant_id'         => $tenant->id,
            'email_verified_at' => null,
            'is_active'         => false,
        ] );

        // Criar role provider
        $role = \App\Models\Role::firstOrCreate(
            [ 'name' => 'provider' ],
            [ 'description' => 'Provider Role' ],
        );

        // Associar role ao usuário
        \App\Models\UserRole::create( [
            'user_id'   => $user->id,
            'role_id'   => $role->id,
            'tenant_id' => $tenant->id,
        ] );

        // Criar provider
        \App\Models\Provider::create( [
            'tenant_id'      => $tenant->id,
            'user_id'        => $user->id,
            'terms_accepted' => true,
        ] );

        $token = UserConfirmationToken::factory()->create( [
            'user_id'    => $user->id,
            'tenant_id'  => $tenant->id,
            'expires_at' => now()->addMinutes( 30 ),
        ] );

        // Act
        $response = $this->get( '/confirm-account?token=' . $token->token );

        // Assert
        $response->assertRedirect( route( 'provider.dashboard', absolute: false ) );
        $response->assertSessionHas( 'success' );

        // Verificar que usuário foi ativado e e-mail verificado
        $user->refresh();
        self::assertTrue( $user->hasVerifiedEmail() );
        self::assertTrue( $user->is_active );

        // Verificar que token foi removido
        self::assertNull( UserConfirmationToken::find( $token->id ) );
    }

    /**
     * ❌ TESTE 2: Token ausente na query string
     */
    public function test_confirm_account_missing_token(): void
    {
        // Act
        $response = $this->get( '/confirm-account' );

        // Assert
        $response->assertRedirect( 'login' );
        $response->assertSessionHas( 'error' );
        self::assertStringContainsString( 'Token de verificação ausente', session( 'error' ) );
    }

    /**
     * ❌ TESTE 3: Token inválido (não existe no banco)
     */
    public function test_confirm_account_invalid_token(): void
    {
        // Act
        $response = $this->get( '/confirm-account?token=invalid-token-123' );

        // Assert
        $response->assertRedirect( 'login' );
        $response->assertSessionHas( 'error' );
        self::assertStringContainsString( 'Token de verificação inválido', session( 'error' ) );
    }

    /**
     * ❌ TESTE 4: Token expirado
     */
    public function test_confirm_account_expired_token(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $user   = User::factory()->create( [
            'tenant_id' => $tenant->id,
        ] );

        // Criar role provider
        $role = \App\Models\Role::firstOrCreate(
            [ 'name' => 'provider' ],
            [ 'description' => 'Provider Role' ],
        );

        // Associar role ao usuário
        \App\Models\UserRole::create( [
            'user_id'   => $user->id,
            'role_id'   => $role->id,
            'tenant_id' => $tenant->id,
        ] );

        // Criar provider
        \App\Models\Provider::create( [
            'tenant_id'      => $tenant->id,
            'user_id'        => $user->id,
            'terms_accepted' => true,
        ] );

        $token = UserConfirmationToken::factory()->create( [
            'user_id'    => $user->id,
            'tenant_id'  => $tenant->id,
            'expires_at' => now()->subMinutes( 5 ), // Token expirado
        ] );

        // Act
        $response = $this->get( '/confirm-account?token=' . $token->token );

        // Assert
        $response->assertRedirect( 'login' );
        $response->assertSessionHas( 'error' );
        self::assertStringContainsString( 'Token de verificação inválido ou expirado', session( 'error' ) );

        // Verificar que token foi removido
        self::assertNull( UserConfirmationToken::find( $token->id ) );
    }

    /**
     * ❌ TESTE 5: Usuário não encontrado
     */
    public function test_confirm_account_user_not_found(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $user   = User::factory()->create( [
            'tenant_id' => $tenant->id,
        ] );

        // Criar role provider
        $role = \App\Models\Role::firstOrCreate(
            [ 'name' => 'provider' ],
            [ 'description' => 'Provider Role' ],
        );

        // Associar role ao usuário
        \App\Models\UserRole::create( [
            'user_id'   => $user->id,
            'role_id'   => $role->id,
            'tenant_id' => $tenant->id,
        ] );

        // Criar provider
        \App\Models\Provider::create( [
            'tenant_id'      => $tenant->id,
            'user_id'        => $user->id,
            'terms_accepted' => true,
        ] );

        // Criar token primeiro
        $token = UserConfirmationToken::factory()->create( [
            'user_id'    => $user->id,
            'tenant_id'  => $tenant->id,
            'expires_at' => now()->addMinutes( 30 ),
        ] );

        // Verificar se o token foi criado corretamente
        $this->assertNotNull( $token->id );
        $this->assertNotNull( $token->token );

        // Tornar usuário inativo em vez de deletar (para evitar CASCADE delete do token)
        $user->update( [ 'is_active' => false ] );

        // Act
        $response = $this->get( '/confirm-account?token=' . $token->token );

        // Assert
        $response->assertRedirect( 'login' );
        $response->assertSessionHas( 'error' );
        self::assertStringContainsString( 'Usuário não encontrado', session( 'error' ) );
    }

    /**
     * ❌ TESTE 6: Problema de multi-tenant (token de tenant diferente)
     */
    public function test_confirm_account_tenant_mismatch(): void
    {
        // Arrange
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();
        $user    = User::factory()->create( [
            'tenant_id' => $tenant1->id,
        ] );

        // Criar role provider
        $role = \App\Models\Role::firstOrCreate(
            [ 'name' => 'provider' ],
            [ 'description' => 'Provider Role' ],
        );

        // Associar role ao usuário
        \App\Models\UserRole::create( [
            'user_id'   => $user->id,
            'role_id'   => $role->id,
            'tenant_id' => $tenant1->id,
        ] );

        // Criar provider
        \App\Models\Provider::create( [
            'tenant_id'      => $tenant1->id,
            'user_id'        => $user->id,
            'terms_accepted' => true,
        ] );

        $token = UserConfirmationToken::factory()->create( [
            'user_id'    => $user->id,
            'tenant_id'  => $tenant2->id, // Tenant diferente
            'expires_at' => now()->addMinutes( 30 ),
        ] );

        // Act
        $response = $this->get( '/confirm-account?token=' . $token->token );

        // Assert
        $response->assertRedirect( 'login' );
        $response->assertSessionHas( 'error' );
        self::assertStringContainsString( 'Erro de validação de segurança', session( 'error' ) );
    }

    /**
     * ❌ TESTE 7: Tentativa de reutilizar token já usado
     */
    public function test_confirm_account_token_already_used(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $user   = User::factory()->create( [
            'tenant_id'         => $tenant->id,
            'email_verified_at' => now(),
            'is_active'         => true,
        ] );

        // Criar role provider
        $role = Role::firstOrCreate(
            [ 'name' => 'provider' ],
            [ 'description' => 'Provider Role' ],
        );

        // Associar role ao usuário
        UserRole::create( [
            'user_id'   => $user->id,
            'role_id'   => $role->id,
            'tenant_id' => $tenant->id,
        ] );

        // Criar provider
        Provider::create( [
            'tenant_id'      => $tenant->id,
            'user_id'        => $user->id,
            'terms_accepted' => true,
        ] );

        $token = UserConfirmationToken::factory()->create( [
            'user_id'    => $user->id,
            'tenant_id'  => $tenant->id,
            'expires_at' => now()->addMinutes( 30 ),
        ] );

        // Primeiro uso do token (deve funcionar)
        $response1 = $this->get( '/confirm-account?token=' . $token->token );
        $response1->assertRedirect( route( 'provider.dashboard', absolute: false ) );

        // Segundo uso do mesmo token (já foi removido)
        $response2 = $this->get( '/confirm-account?token=' . $token->token );

        // Assert
        $response2->assertRedirect( 'login' );
        $response2->assertSessionHas( 'error' );
        self::assertStringContainsString( 'Token de verificação inválido', session( 'error' ) );
    }

    /**
     * ✅ TESTE 8: Exibir página de verificação
     */
    public function test_show_verification_page(): void
    {
        // Arrange
        $validToken = generateSecureToken( 32, 'base64url' );

        // Act
        $response = $this->get( '/email/verify?token=' . $validToken );

        // Assert
        $response->assertStatus( 200 );
        $response->assertViewIs( 'auth.verify-email' );
        $response->assertViewHas( 'token', $validToken );
    }

    /**
     * ❌ TESTE 9: Página de verificação sem token
     */
    public function test_show_verification_page_without_token(): void
    {
        // Act
        $response = $this->get( '/email/verify' );

        // Assert
        $response->assertStatus( 200 );
        $response->assertViewIs( 'auth.verify-email' );
        $response->assertViewHas( 'error', 'Token de verificação ausente.' );
    }

    /**
     * ✅ TESTE 10: Verificação automática de evento Verified
     */
    public function test_verified_event_is_dispatched(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $user   = User::factory()->create( [
            'tenant_id'         => $tenant->id,
            'email_verified_at' => null,
            'is_active'         => true,
        ] );

        // Criar role provider
        $role = \App\Models\Role::firstOrCreate(
            [ 'name' => 'provider' ],
            [ 'description' => 'Provider Role' ],
        );

        // Associar role ao usuário
        \App\Models\UserRole::create( [
            'user_id'   => $user->id,
            'role_id'   => $role->id,
            'tenant_id' => $tenant->id,
        ] );

        // Criar provider
        \App\Models\Provider::create( [
            'tenant_id'      => $tenant->id,
            'user_id'        => $user->id,
            'terms_accepted' => true,
        ] );

        $token = UserConfirmationToken::factory()->create( [
            'user_id'    => $user->id,
            'tenant_id'  => $tenant->id,
            'expires_at' => now()->addMinutes( 30 ),
        ] );

        Event::fake();

        // Act
        $this->get( '/confirm-account?token=' . $token->token );

        // Assert
        Event::assertDispatched( \Illuminate\Auth\Events\Verified::class, function ( $event ) use ( $user ) {
            return $event->user->id === $user->id;
        } );
    }

    /**
     * ❌ TESTE 11: Tratamento de exceção durante verificação
     */
    public function test_confirm_account_exception_handling(): void
    {
        // Arrange - Criar cenário que pode gerar exceção
        $tenant = Tenant::factory()->create();
        $user   = User::factory()->create( [
            'tenant_id'         => $tenant->id,
            'email_verified_at' => null,
            'is_active'         => false,
        ] );

        // Criar role provider
        $role = Role::firstOrCreate(
            [ 'name' => 'provider' ],
            [ 'description' => 'Provider Role' ],
        );

        // Associar role ao usuário
        UserRole::create( [
            'user_id'   => $user->id,
            'role_id'   => $role->id,
            'tenant_id' => $tenant->id,
        ] );

        // Criar provider
        Provider::create( [
            'tenant_id'      => $tenant->id,
            'user_id'        => $user->id,
            'terms_accepted' => true,
        ] );

        $token = UserConfirmationToken::factory()->create( [
            'user_id'    => $user->id,
            'tenant_id'  => $tenant->id,
            'expires_at' => now()->addMinutes( 30 ),
        ] );

        // Mock para gerar exceção no markEmailAsVerified
        $userMock = \Mockery::mock( $user )->makePartial();
        $userMock->shouldReceive( 'markEmailAsVerified' )->andThrow( new \Exception( 'Database error' ) );

        // Mock do repository para retornar o usuário mockado
        $repositoryMock = \Mockery::mock( \App\Repositories\UserRepository::class)->makePartial();
        $repositoryMock->shouldReceive( 'find' )->andReturn( $userMock );
        $this->app->instance( \App\Repositories\UserRepository::class, $repositoryMock );

        // Mock do UserConfirmationTokenRepository para retornar o token válido
        $tokenRepositoryMock = \Mockery::mock( \App\Repositories\UserConfirmationTokenRepository::class)->makePartial();
        $tokenRepositoryMock->shouldReceive( 'findByToken' )->andReturn( $token );
        $tokenRepositoryMock->shouldReceive( 'delete' )->andReturn( true );
        $this->app->instance( \App\Repositories\UserConfirmationTokenRepository::class, $tokenRepositoryMock );

        // Act
        $response = $this->get( '/confirm-account?token=' . $token->token );

        // Assert
        $response->assertRedirect( route( 'provider.dashboard', absolute: false ) );
        $response->assertSessionHas( 'error' );
        self::assertStringContainsString( 'Erro interno durante a verificação', session( 'error' ) );
    }

}
