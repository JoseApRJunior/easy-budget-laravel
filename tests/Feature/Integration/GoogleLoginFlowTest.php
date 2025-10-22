<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

/**
 * Testes de integração para fluxo completo de login Google OAuth
 *
 * Esta classe testa o fluxo completo de autenticação Google,
 * desde o redirecionamento até o login do usuário.
 */
class GoogleLoginFlowTest extends TestCase
{
    use RefreshDatabase;

    private $googleUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock do usuário Google
        $this->googleUser = Mockery::mock();
        $this->googleUser->shouldReceive( 'getId' )->andReturn( 'google-user-123' );
        $this->googleUser->shouldReceive( 'getName' )->andReturn( 'João Silva' );
        $this->googleUser->shouldReceive( 'getEmail' )->andReturn( 'joao.silva@gmail.com' );
        $this->googleUser->shouldReceive( 'getAvatar' )->andReturn( 'https://avatar.url' );
    }

    /**
     * Testa fluxo completo de login com Google para novo usuário
     *
     * @return void
     */
    public function test_complete_google_login_flow_for_new_user(): void
    {
        // Mock do Socialite
        Socialite::shouldReceive( 'driver->redirect' )->andReturn( redirect( '/google-callback' ) );
        Socialite::shouldReceive( 'driver->user' )->andReturn( $this->googleUser );

        // Acessa página inicial
        $response = $this->get( '/' );
        $response->assertStatus( 200 );

        // Inicia autenticação Google
        $response = $this->get( route( 'auth.google' ) );
        $response->assertStatus( 302 );
        $response->assertRedirect();

        // Simula callback do Google com dados do usuário
        $response = $this->get( route( 'auth.google.callback', [
            'state' => 'test-state',
            'code'  => 'test-code'
        ] ) );

        // Deve redirecionar para dashboard após login
        $response->assertRedirect( route( 'dashboard' ) );

        // Verifica se usuário foi criado no banco
        $this->assertDatabaseHas( 'users', [
            'email'             => 'joao.silva@gmail.com',
            'name'              => 'João Silva',
            'google_id'         => 'google-user-123',
            'avatar'            => 'https://avatar.url',
            'email_verified_at' => now()->toDateTimeString(),
        ] );

        // Verifica se usuário está logado
        $user = User::where( 'email', 'joao.silva@gmail.com' )->first();
        $this->assertTrue( Auth::check() );
        $this->assertEquals( $user->id, Auth::id() );
    }

    /**
     * Testa fluxo de login com Google para usuário existente
     *
     * @return void
     */
    public function test_google_login_flow_for_existing_user(): void
    {
        // Cria usuário existente
        $existingUser = User::factory()->create( [
            'email'     => 'joao.silva@gmail.com',
            'google_id' => 'google-user-123',
            'name'      => 'João Silva Antigo',
        ] );

        // Mock do Socialite
        Socialite::shouldReceive( 'driver->redirect' )->andReturn( redirect( '/google-callback' ) );
        Socialite::shouldReceive( 'driver->user' )->andReturn( $this->googleUser );

        // Inicia autenticação Google
        $response = $this->get( route( 'auth.google' ) );
        $response->assertStatus( 302 );

        // Simula callback do Google
        $response = $this->get( route( 'auth.google.callback', [
            'state' => 'test-state',
            'code'  => 'test-code'
        ] ) );

        // Deve redirecionar para dashboard
        $response->assertRedirect( route( 'dashboard' ) );

        // Verifica se dados foram atualizados
        $existingUser->refresh();
        $this->assertEquals( 'João Silva', $existingUser->name );
        $this->assertEquals( 'https://avatar.url', $existingUser->avatar );

        // Verifica se usuário está logado
        $this->assertTrue( Auth::check() );
        $this->assertEquals( $existingUser->id, Auth::id() );
    }

    /**
     * Testa tratamento de erro quando usuário cancela autenticação
     *
     * @return void
     */
    public function test_google_login_cancellation_handling(): void
    {
        // Simula cancelamento pelo usuário
        $response = $this->get( route( 'auth.google.callback', [
            'error'             => 'access_denied',
            'error_description' => 'User cancelled the authentication'
        ] ) );

        // Deve redirecionar para home com mensagem de erro
        $response->assertRedirect( route( 'home' ) );
        $response->assertSessionHas( 'error' );
    }

    /**
     * Testa tratamento de erro quando configuração Google está ausente
     *
     * @return void
     */
    public function test_google_login_without_configuration(): void
    {
        // Remove configuração do Google (simula ambiente não configurado)
        config( [ 'services.google.client_id' => null ] );

        $response = $this->get( route( 'auth.google' ) );

        // Deve redirecionar para home com mensagem de erro
        $response->assertRedirect( route( 'home' ) );
        $response->assertSessionHas( 'error' );
    }

    /**
     * Testa se e-mail verificado é definido automaticamente
     *
     * @return void
     */
    public function test_email_verified_at_is_set_automatically(): void
    {
        // Mock do Socialite
        Socialite::shouldReceive( 'driver->redirect' )->andReturn( redirect( '/google-callback' ) );
        Socialite::shouldReceive( 'driver->user' )->andReturn( $this->googleUser );

        // Inicia autenticação Google
        $response = $this->get( route( 'auth.google' ) );
        $response->assertStatus( 302 );

        // Simula callback do Google
        $response = $this->get( route( 'auth.google.callback', [
            'state' => 'test-state',
            'code'  => 'test-code'
        ] ) );

        // Verifica se email_verified_at foi definido
        $user = User::where( 'email', 'joao.silva@gmail.com' )->first();
        $this->assertNotNull( $user->email_verified_at );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

}
