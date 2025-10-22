<?php

declare(strict_types=1);

namespace Tests\Feature\Contract;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Testes de contrato para rotas de autenticação Google OAuth
 *
 * Esta classe testa se as rotas de autenticação Google seguem
 * os contratos estabelecidos e retornam as respostas esperadas.
 */
class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testa se a rota de redirecionamento para Google existe e está acessível
     *
     * @return void
     */
    public function test_google_redirect_route_exists(): void
    {
        $response = $this->get( route( 'auth.google' ) );

        // Deve redirecionar (status 302) para o Google
        $response->assertStatus( 302 );
        $response->assertRedirect();
    }

    /**
     * Testa se a rota de callback do Google existe
     *
     * @return void
     */
    public function test_google_callback_route_exists(): void
    {
        $response = $this->get( route( 'auth.google.callback' ) );

        // Deve redirecionar para home (302) quando não há parâmetros do Google
        // O controller trata corretamente callbacks sem parâmetros redirecionando
        $response->assertStatus( 302 );
        $response->assertRedirect( route( 'home' ) );
    }

    /**
     * Testa se as rotas de autenticação Google estão registradas corretamente
     *
     * @return void
     */
    public function test_google_auth_routes_are_registered(): void
    {
        // Testa se consegue resolver as rotas pelo nome
        $this->assertTrue( Route::has( 'auth.google' ) );
        $this->assertTrue( Route::has( 'auth.google.callback' ) );

        // Testa se as rotas têm os métodos HTTP corretos
        $googleRoute   = Route::getRoutes()->getByName( 'auth.google' );
        $callbackRoute = Route::getRoutes()->getByName( 'auth.google.callback' );

        $this->assertEquals( 'GET', $googleRoute->methods()[ 0 ] );
        $this->assertEquals( 'GET', $callbackRoute->methods()[ 0 ] );
    }

    /**
     * Testa se a rota de redirecionamento requer autenticação
     *
     * @return void
     */
    public function test_google_redirect_does_not_require_authentication(): void
    {
        $response = $this->get( route( 'auth.google' ) );

        // Deve estar acessível sem autenticação
        $response->assertStatus( 302 );
        $response->assertRedirect();
    }

    /**
     * Testa se a estrutura da resposta está correta
     *
     * @return void
     */
    public function test_google_redirect_response_structure(): void
    {
        $response = $this->get( route( 'auth.google' ) );

        // Deve conter header de Location (redirecionamento)
        $response->assertHeader( 'Location' );

        // O header Location deve conter o domínio do Google
        $locationHeader = $response->headers->get( 'Location' );
        $this->assertStringContainsString( 'accounts.google.com', $locationHeader );
    }

}
