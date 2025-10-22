<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Mockery;
use Tests\TestCase;

/**
 * Testes de integração para tratamento de erros do Google OAuth
 *
 * Esta classe testa os diferentes cenários de erro que podem ocorrer
 * durante o processo de autenticação Google OAuth.
 */
class GoogleAuthErrorTest extends TestCase
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
     * Testa tratamento quando usuário cancela autenticação no Google
     *
     * @return void
     */
    public function test_user_cancellation_handling(): void
    {
        // Simula callback com erro de cancelamento
        $response = $this->get( route( 'auth.google.callback', [
            'error'             => 'access_denied',
            'error_description' => 'User cancelled the authentication'
        ] ) );

        // Deve redirecionar para home com mensagem de erro
        $response->assertRedirect( route( 'home' ) );
        $response->assertSessionHas( 'error' );
    }

    /**
     * Testa tratamento quando há erro de configuração OAuth
     *
     * @return void
     */
    public function test_oauth_configuration_error_handling(): void
    {
        // Remove configuração do Google temporariamente
        config( [ 'services.google.client_id' => null ] );

        $response = $this->get( route( 'auth.google' ) );

        // Deve redirecionar para home com mensagem de erro
        $response->assertRedirect( route( 'home' ) );
        $response->assertSessionHas( 'error' );
    }

    /**
     * Testa tratamento quando Google retorna dados inválidos
     *
     * @return void
     */
    public function test_invalid_google_data_handling(): void
    {
        // Mock do Socialite retornando dados inválidos
        Socialite::shouldReceive( 'driver->user' )->andThrow(
            new \Exception( 'Invalid response from Google' ),
        );

        $response = $this->get( route( 'auth.google.callback', [
            'state' => 'test-state',
            'code'  => 'test-code'
        ] ) );

        // Deve redirecionar para home com mensagem de erro
        $response->assertRedirect( route( 'home' ) );
        $response->assertSessionHas( 'error' );
    }

    /**
     * Testa tratamento quando e-mail do Google já está em uso por outro usuário
     *
     * @return void
     */
    public function test_email_already_in_use_error_handling(): void
    {
        // Cria usuário existente com mesmo e-mail
        User::factory()->create( [
            'email' => 'joao.silva@gmail.com',
            'name'  => 'João Silva Existente',
        ] );

        // Mock do Socialite
        Socialite::shouldReceive( 'driver->user' )->andReturn( $this->googleUser );

        // Simula callback do Google
        $response = $this->get( route( 'auth.google.callback', [
            'state' => 'test-state',
            'code'  => 'test-code'
        ] ) );

        // Deve redirecionar para home com mensagem de erro específica
        $response->assertRedirect( route( 'home' ) );
        $response->assertSessionHas( 'error' );
    }

    /**
     * Testa tratamento quando há erro interno no sistema
     *
     * @return void
     */
    public function test_internal_error_handling(): void
    {
        // Mock do Socialite retornando dados que causam erro interno
        $googleUserWithError = Mockery::mock();
        $googleUserWithError->shouldReceive( 'getId' )->andReturn( 'google-user-error' );
        $googleUserWithError->shouldReceive( 'getName' )->andReturn( 'Teste Erro' );
        $googleUserWithError->shouldReceive( 'getEmail' )->andReturn( 'erro@teste.com' );
        $googleUserWithError->shouldReceive( 'getAvatar' )->andReturn( 'https://avatar.url' );

        Socialite::shouldReceive( 'driver->user' )->andReturn( $googleUserWithError );

        // Simula situação que causa erro interno (ex: problema no banco)
        $this->expectException( \Exception::class);

        // Tenta processar callback
        $this->get( route( 'auth.google.callback', [
            'state' => 'test-state',
            'code'  => 'test-code'
        ] ) );
    }

    /**
     * Testa tratamento quando Google retorna token inválido
     *
     * @return void
     */
    public function test_invalid_token_handling(): void
    {
        // Mock do Socialite retornando erro de token inválido
        Socialite::shouldReceive( 'driver->user' )->andThrow(
            new InvalidStateException(),
        );

        $response = $this->get( route( 'auth.google.callback', [
            'state' => 'invalid-state',
            'code'  => 'test-code'
        ] ) );

        // Deve redirecionar para home com mensagem de erro
        $response->assertRedirect( route( 'home' ) );
        $response->assertSessionHas( 'error' );
    }

    /**
     * Testa tratamento quando há erro de rede com Google
     *
     * @return void
     */
    public function test_network_error_handling(): void
    {
        // Mock do Socialite retornando erro de rede
        Socialite::shouldReceive( 'driver->user' )->andThrow(
            new \GuzzleHttp\Exception\RequestException(
                'Network error',
                new \GuzzleHttp\Psr7\Request( 'GET', 'https://google.com' ),
            ),
        );

        $response = $this->get( route( 'auth.google.callback', [
            'state' => 'test-state',
            'code'  => 'test-code'
        ] ) );

        // Deve redirecionar para home com mensagem de erro
        $response->assertRedirect( route( 'home' ) );
        $response->assertSessionHas( 'error' );
    }

    /**
     * Testa tratamento quando usuário não tem e-mail público no Google
     *
     * @return void
     */
    public function test_no_public_email_handling(): void
    {
        // Mock do usuário Google sem e-mail público
        $googleUserNoEmail = Mockery::mock();
        $googleUserNoEmail->shouldReceive( 'getId' )->andReturn( 'google-user-no-email' );
        $googleUserNoEmail->shouldReceive( 'getName' )->andReturn( 'Usuário Sem Email' );
        $googleUserNoEmail->shouldReceive( 'getEmail' )->andReturn( null );
        $googleUserNoEmail->shouldReceive( 'getAvatar' )->andReturn( 'https://avatar.url' );

        Socialite::shouldReceive( 'driver->user' )->andReturn( $googleUserNoEmail );

        $response = $this->get( route( 'auth.google.callback', [
            'state' => 'test-state',
            'code'  => 'test-code'
        ] ) );

        // Deve redirecionar para home com mensagem de erro
        $response->assertRedirect( route( 'home' ) );
        $response->assertSessionHas( 'error' );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

}
