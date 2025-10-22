<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;

use App\Models\User;
use App\Services\Application\Auth\SocialAuthenticationService;
use App\Services\Infrastructure\OAuth\GoogleOAuthClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

/**
 * Testes de integração para sincronização de dados do perfil Google
 *
 * Esta classe testa a sincronização de dados do perfil do usuário
 * entre o Google e o sistema Easy Budget Laravel.
 */
class GoogleProfileSyncTest extends TestCase
{
    use RefreshDatabase;

    private                             $googleUser;
    private SocialAuthenticationService $socialAuthService;
    private GoogleOAuthClient           $googleOAuthClient;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock do usuário Google
        $this->googleUser = Mockery::mock();
        $this->googleUser->shouldReceive( 'getId' )->andReturn( 'google-user-123' );
        $this->googleUser->shouldReceive( 'getName' )->andReturn( 'João Silva Atualizado' );
        $this->googleUser->shouldReceive( 'getEmail' )->andReturn( 'joao.silva@gmail.com' );
        $this->googleUser->shouldReceive( 'getAvatar' )->andReturn( 'https://novo-avatar.url' );

        // Instancia serviços reais
        $this->googleOAuthClient = app( GoogleOAuthClient::class);
        $this->socialAuthService = app( SocialAuthenticationService::class);
    }

    /**
     * Testa sincronização de dados do perfil Google
     *
     * @return void
     */
    public function test_google_profile_data_sync(): void
    {
        // Cria usuário existente com dados antigos
        $user = User::factory()->create( [
            'email'     => 'joao.silva@gmail.com',
            'google_id' => 'google-user-123',
            'name'      => 'João Silva Antigo',
            'avatar'    => 'https://antigo-avatar.url',
        ] );

        // Mock do Socialite para retornar dados atualizados
        Socialite::shouldReceive( 'driver->user' )->andReturn( $this->googleUser );

        // Simula callback do Google com dados atualizados
        $googleData = [
            'id'       => 'google-user-123',
            'name'     => 'João Silva Atualizado',
            'email'    => 'joao.silva@gmail.com',
            'avatar'   => 'https://novo-avatar.url',
            'verified' => true,
        ];

        // Processa autenticação (deve atualizar dados existentes)
        $result = $this->socialAuthService->authenticateWithSocialProvider( 'google', $googleData );

        // Verifica se operação foi bem-sucedida
        $this->assertTrue( $result->isSuccess() );

        // Verifica se dados foram atualizados no banco
        $user->refresh();
        $this->assertEquals( 'João Silva Atualizado', $user->name );
        $this->assertEquals( 'https://novo-avatar.url', $user->avatar );
        $this->assertNotNull( $user->email_verified_at );
    }

    /**
     * Testa criação de novo usuário com dados do Google
     *
     * @return void
     */
    public function test_create_new_user_with_google_data(): void
    {
        // Mock do Socialite
        Socialite::shouldReceive( 'driver->user' )->andReturn( $this->googleUser );

        // Dados do Google para novo usuário
        $googleData = [
            'id'       => 'google-user-novo',
            'name'     => 'Maria Santos',
            'email'    => 'maria.santos@gmail.com',
            'avatar'   => 'https://avatar-maria.url',
            'verified' => true,
        ];

        // Processa autenticação (deve criar novo usuário)
        $result = $this->socialAuthService->authenticateWithSocialProvider( 'google', $googleData );

        // Verifica se operação foi bem-sucedida
        $this->assertTrue( $result->isSuccess() );

        // Verifica se usuário foi criado no banco
        $this->assertDatabaseHas( 'users', [
            'email'     => 'maria.santos@gmail.com',
            'name'      => 'Maria Santos',
            'google_id' => 'google-user-novo',
            'avatar'    => 'https://avatar-maria.url',
        ] );

        // Verifica se e-mail foi marcado como verificado
        $user = User::where( 'email', 'maria.santos@gmail.com' )->first();
        $this->assertNotNull( $user->email_verified_at );
    }

    /**
     * Testa tratamento quando e-mail do Google já está em uso
     *
     * @return void
     */
    public function test_google_email_already_in_use(): void
    {
        // Cria usuário existente com mesmo e-mail
        User::factory()->create( [
            'email' => 'joao.silva@gmail.com',
            'name'  => 'João Silva Existente',
        ] );

        // Dados do Google com mesmo e-mail
        $googleData = [
            'id'       => 'google-user-diferente',
            'name'     => 'João Silva Google',
            'email'    => 'joao.silva@gmail.com',
            'avatar'   => 'https://avatar-google.url',
            'verified' => true,
        ];

        // Tenta processar autenticação
        $result = $this->socialAuthService->authenticateWithSocialProvider( 'google', $googleData );

        // Deve falhar pois e-mail já está em uso
        $this->assertFalse( $result->isSuccess() );
        $this->assertEquals( 'E-mail já cadastrado', $result->getMessage() );
    }

    /**
     * Testa fallback para avatar padrão quando Google não retorna imagem
     *
     * @return void
     */
    public function test_avatar_fallback_when_google_returns_no_avatar(): void
    {
        // Mock do usuário Google sem avatar
        $googleUserNoAvatar = Mockery::mock();
        $googleUserNoAvatar->shouldReceive( 'getId' )->andReturn( 'google-user-no-avatar' );
        $googleUserNoAvatar->shouldReceive( 'getName' )->andReturn( 'Usuário Sem Avatar' );
        $googleUserNoAvatar->shouldReceive( 'getEmail' )->andReturn( 'semavatar@gmail.com' );
        $googleUserNoAvatar->shouldReceive( 'getAvatar' )->andReturn( null );

        Socialite::shouldReceive( 'driver->user' )->andReturn( $googleUserNoAvatar );

        // Dados do Google sem avatar
        $googleData = [
            'id'       => 'google-user-no-avatar',
            'name'     => 'Usuário Sem Avatar',
            'email'    => 'semavatar@gmail.com',
            'avatar'   => null,
            'verified' => true,
        ];

        // Processa autenticação
        $result = $this->socialAuthService->authenticateWithSocialProvider( 'google', $googleData );

        // Verifica se operação foi bem-sucedida
        $this->assertTrue( $result->isSuccess() );

        // Verifica se usuário foi criado com avatar null
        $this->assertDatabaseHas( 'users', [
            'email'     => 'semavatar@gmail.com',
            'name'      => 'Usuário Sem Avatar',
            'google_id' => 'google-user-no-avatar',
            'avatar'    => null,
        ] );
    }

    /**
     * Testa busca de usuário por ID social
     *
     * @return void
     */
    public function test_find_user_by_social_id(): void
    {
        // Cria usuário com Google ID
        $user = User::factory()->create( [
            'google_id' => 'google-user-123',
            'email'     => 'teste@gmail.com',
        ] );

        // Busca usuário por ID social
        $foundUser = $this->socialAuthService->findUserBySocialId( 'google', 'google-user-123' );

        // Verifica se encontrou o usuário correto
        $this->assertNotNull( $foundUser );
        $this->assertEquals( $user->id, $foundUser->id );
        $this->assertEquals( 'teste@gmail.com', $foundUser->email );
    }

    /**
     * Testa retorno null quando ID social não existe
     *
     * @return void
     */
    public function test_return_null_when_social_id_not_found(): void
    {
        // Busca usuário por ID social inexistente
        $foundUser = $this->socialAuthService->findUserBySocialId( 'google', 'google-user-inexistente' );

        // Deve retornar null
        $this->assertNull( $foundUser );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

}
