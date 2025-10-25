<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;

use App\Events\SocialAccountLinked;
use App\Listeners\SendSocialAccountLinkedNotification;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserConfirmationToken;
use App\Services\Application\Auth\SocialAuthenticationService;
use App\Services\Application\EmailVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

/**
 * Testes de integração para o sistema de vinculação de contas sociais
 *
 * Esta classe testa especificamente o fluxo de vinculação de contas
 * quando um usuário existente tenta fazer login com Google OAuth.
 */
class SocialAccountLinkingTest extends TestCase
{
    use RefreshDatabase;

    private SocialAuthenticationService $socialAuthService;
    private EmailVerificationService    $emailVerificationService;
    private                             $googleUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Configura tenant de teste
        config( [ 'tenant.testing_id' => 1 ] );

        // Executa seeders necessários
        $this->seed( [
            \Database\Seeders\PlanSeeder::class,
            \Database\Seeders\RoleSeeder::class,
            \Database\Seeders\PermissionSeeder::class,
        ] );

        // Mock do usuário Google
        $this->googleUser = Mockery::mock();
        $this->googleUser->shouldReceive( 'getId' )->andReturn( 'google-user-123' );
        $this->googleUser->shouldReceive( 'getName' )->andReturn( 'João Silva' );
        $this->googleUser->shouldReceive( 'getEmail' )->andReturn( 'joao.silva@gmail.com' );
        $this->googleUser->shouldReceive( 'getAvatar' )->andReturn( 'https://avatar.url' );

        // Instancia os serviços
        $this->socialAuthService        = app( SocialAuthenticationService::class);
        $this->emailVerificationService = app( EmailVerificationService::class);
    }

    /**
     * Testa se usuário existente pode vincular conta Google
     *
     * @return void
     */
    public function test_existing_user_can_link_google_account(): void
    {
        // Cria usuário existente sem Google ID
        $existingUser = User::factory()->create( [
            'email'     => 'joao.silva@gmail.com',
            'name'      => 'João Silva Existente',
            'google_id' => null,
        ] );

        // Mock do Socialite
        Socialite::shouldReceive( 'driver->user' )->andReturn( $this->googleUser );

        // Dados do Google
        $googleData = [
            'id'       => 'google-user-123',
            'name'     => 'João Silva',
            'email'    => 'joao.silva@gmail.com',
            'avatar'   => 'https://avatar.url',
            'verified' => true,
        ];

        // Espera que o evento seja disparado
        Event::fake();

        // Processa autenticação (deve criar token de confirmação)
        $result = $this->socialAuthService->authenticateWithSocialProvider( 'google', $googleData );

        // Verifica se operação foi bem-sucedida
        $this->assertTrue( $result->isSuccess(), 'Authentication should succeed for existing user linking' );

        // Verifica se dados NÃO foram atualizados ainda (apenas token criado)
        $existingUser->refresh();
        $this->assertNull( $existingUser->google_id, 'Google ID should not be updated until confirmation' );
        $this->assertEquals( 'João Silva Existente', $existingUser->name, 'Name should not be updated until confirmation' );

        // Verifica se evento de confirmação foi disparado (não o de vinculação final)
        Event::assertDispatched( SocialAccountLinked::class, function ( $event ) use ( $existingUser ) {
            return $event->user->id === $existingUser->id &&
                $event->provider === 'google' &&
                $event->token !== null;
        } );

        // Busca token de confirmação criado
        $confirmationToken = UserConfirmationToken::where( 'user_id', $existingUser->id )
            ->where( 'type', 'social_linking' )
            ->first();

        $this->assertNotNull( $confirmationToken, 'Confirmation token should be created' );
        $this->assertEquals( 'social_linking', $confirmationToken->type );

        // Simula confirmação do token
        $confirmResult = $this->socialAuthService->confirmSocialAccountLinking( $confirmationToken->token );

        // Verifica se confirmação foi bem-sucedida
        $this->assertTrue( $confirmResult->isSuccess(), 'Token confirmation should succeed' );

        // Agora verifica se dados foram atualizados
        $existingUser->refresh();
        $this->assertEquals( 'google-user-123', $existingUser->google_id );
        $this->assertEquals( 'João Silva', $existingUser->name );
        $this->assertNotNull( $existingUser->email_verified_at );
    }

    /**
     * Testa se e-mail de confirmação é enviado para vinculação
     *
     * @return void
     */
    public function test_confirmation_email_sent_for_account_linking(): void
    {
        // Cria usuário existente
        $existingUser = User::factory()->create( [
            'email'     => 'joao.silva@gmail.com',
            'name'      => 'João Silva Existente',
            'google_id' => null,
        ] );

        // Mock do Socialite
        Socialite::shouldReceive( 'driver->user' )->andReturn( $this->googleUser );

        // Dados do Google
        $googleData = [
            'id'       => 'google-user-123',
            'name'     => 'João Silva',
            'email'    => 'joao.silva@gmail.com',
            'avatar'   => 'https://avatar.url',
            'verified' => true,
        ];

        // Mock do Mail para capturar e-mail
        Mail::fake();

        // Processa autenticação
        $result = $this->socialAuthService->authenticateWithSocialProvider( 'google', $googleData );

        // Verifica se operação foi bem-sucedida
        $this->assertTrue( $result->isSuccess() );

        // Verifica se e-mail foi enfileirado
        Mail::assertQueued( \App\Mail\SocialAccountLinkedMail::class, function ( $mail ) use ( $existingUser ) {
            return $mail->hasTo( $existingUser->email ) &&
                $mail->getUser()->id === $existingUser->id &&
                $mail->provider === 'google' &&
                $mail->token !== null;
        } );
    }

    /**
     * Testa se token de confirmação é criado corretamente
     *
     * @return void
     */
    public function test_confirmation_token_created_for_linking(): void
    {
        // Cria usuário existente
        $existingUser = User::factory()->create( [
            'email'     => 'joao.silva@gmail.com',
            'name'      => 'João Silva Existente',
            'google_id' => null,
        ] );

        // Mock do Socialite
        Socialite::shouldReceive( 'driver->user' )->andReturn( $this->googleUser );

        // Dados do Google
        $googleData = [
            'id'       => 'google-user-123',
            'name'     => 'João Silva',
            'email'    => 'joao.silva@gmail.com',
            'avatar'   => 'https://avatar.url',
            'verified' => true,
        ];

        // Processa autenticação
        $result = $this->socialAuthService->authenticateWithSocialProvider( 'google', $googleData );

        // Verifica se operação foi bem-sucedida
        $this->assertTrue( $result->isSuccess() );

        // Verifica se token foi criado
        $this->assertDatabaseHas( 'user_confirmation_tokens', [
            'user_id'   => $existingUser->id,
            'tenant_id' => $existingUser->tenant_id,
            'type'      => 'social_linking',
        ] );

        // Busca o token criado
        $token = UserConfirmationToken::where( 'user_id', $existingUser->id )
            ->where( 'type', 'social_linking' )
            ->first();

        $this->assertNotNull( $token );
        $this->assertNotNull( $token->token );
        $this->assertEquals( 'social_linking', $token->type );
        $this->assertNotNull( $token->metadata );
    }

    /**
     * Testa se confirmação via token funciona corretamente
     *
     * @return void
     */
    public function test_token_confirmation_works_correctly(): void
    {
        // Cria usuário existente
        $existingUser = User::factory()->create( [
            'email'     => 'joao.silva@gmail.com',
            'name'      => 'João Silva Existente',
            'google_id' => null,
        ] );

        // Cria token de confirmação
        $token = UserConfirmationToken::factory()
            ->forUser( $existingUser )
            ->socialLinking()
            ->create( [
                'metadata' => json_encode( [
                    'provider'      => 'google',
                    'social_id'     => 'google-user-123',
                    'social_name'   => 'João Silva',
                    'social_email'  => 'joao.silva@gmail.com',
                    'social_avatar' => 'https://avatar.url',
                    'user_data'     => [
                        'id'     => 'google-user-123',
                        'name'   => 'João Silva',
                        'email'  => 'joao.silva@gmail.com',
                        'avatar' => 'https://avatar.url',
                    ],
                ] ),
            ] );

        // Faz requisição GET para confirmar
        $response = $this->get( "/auth/social/confirm-linking/{$token->token}" );

        // Verifica redirecionamento para dashboard
        $response->assertRedirect( route( 'provider.dashboard' ) );

        // Verifica se dados foram atualizados
        $existingUser->refresh();
        $this->assertEquals( 'google-user-123', $existingUser->google_id );
        $this->assertEquals( 'João Silva', $existingUser->name );

        // Verifica se token foi removido após uso
        $this->assertDatabaseMissing( 'user_confirmation_tokens', [
            'id' => $token->id,
        ] );

        // Verifica se mensagem de sucesso foi exibida
        $response->assertSessionHas( 'success' );
    }

    /**
     * Testa se token inválido é rejeitado
     *
     * @return void
     */
    public function test_invalid_token_is_rejected(): void
    {
        // Faz requisição GET com token inválido
        $response = $this->get( '/auth/social/confirm-linking/invalid-token' );

        // Verifica redirecionamento para home
        $response->assertRedirect( route( 'home' ) );

        // Verifica se mensagem de erro foi exibida
        $response->assertSessionHas( 'error', 'Link de confirmação inválido ou expirado. Tente fazer login novamente.' );
    }

    /**
     * Testa se token expirado é rejeitado
     *
     * @return void
     */
    public function test_expired_token_is_rejected(): void
    {
        // Cria usuário existente
        $existingUser = User::factory()->create( [
            'email'     => 'joao.silva@gmail.com',
            'name'      => 'João Silva Existente',
            'google_id' => null,
        ] );

        // Cria token expirado
        $token = UserConfirmationToken::factory()
            ->forUser( $existingUser )
            ->socialLinking()
            ->expired()
            ->create();

        // Faz requisição GET para confirmar
        $response = $this->get( "/auth/social/confirm-linking/{$token->token}" );

        // Verifica redirecionamento para home
        $response->assertRedirect( route( 'home' ) );

        // Verifica se mensagem de erro foi exibida
        $response->assertSessionHas( 'error', 'Link de confirmação expirado. Solicite uma nova vinculação de conta.' );
    }

    /**
     * Testa se token de tipo errado é rejeitado
     *
     * @return void
     */
    public function test_wrong_token_type_is_rejected(): void
    {
        // Cria usuário existente
        $existingUser = User::factory()->create( [
            'email'     => 'joao.silva@gmail.com',
            'name'      => 'João Silva Existente',
            'google_id' => null,
        ] );

        // Cria token de verificação de e-mail (tipo errado)
        $token = UserConfirmationToken::factory()
            ->forUser( $existingUser )
            ->create( [
                'type' => 'email_verification',
            ] );

        // Faz requisição GET para confirmar
        $response = $this->get( "/auth/social/confirm-linking/{$token->token}" );

        // Verifica redirecionamento para home
        $response->assertRedirect( route( 'home' ) );

        // Verifica se mensagem de erro foi exibida
        $response->assertSessionHas( 'error', 'Link de confirmação inválido. Solicite uma nova vinculação de conta.' );
    }

    /**
     * Testa se listener processa evento e envia e-mail
     *
     * @return void
     */
    public function test_listener_processes_event_and_sends_email(): void
    {
        // Cria usuário e tenant
        $tenant = Tenant::factory()->create();
        $user   = User::factory()->create( [
            'tenant_id' => $tenant->id,
            'email'     => 'teste@gmail.com',
            'name'      => 'Teste Usuário',
        ] );

        // Dados sociais
        $socialData = [
            'name'   => 'João Silva',
            'email'  => 'joao.silva@gmail.com',
            'avatar' => 'https://avatar.url',
        ];

        // Cria evento
        $event = new SocialAccountLinked( $user, 'google', $socialData, 'test-token-123' );

        // Mock do Mail
        Mail::fake();

        // Processa evento através do listener
        $listener = app( SendSocialAccountLinkedNotification::class);
        $listener->handle( $event );

        // Verifica se e-mail foi enfileirado
        Mail::assertQueued( \App\Mail\SocialAccountLinkedMail::class, function ( $mail ) use ( $user ) {
            return $mail->hasTo( $user->email ) &&
                $mail->provider === 'google' &&
                $mail->token === 'test-token-123';
        } );
    }

    /**
     * Testa se token pertence ao usuário correto (segurança)
     *
     * @return void
     */
    public function test_token_must_belong_to_correct_user(): void
    {
        // Cria dois usuários
        $user1 = User::factory()->create( [
            'email'     => 'user1@gmail.com',
            'name'      => 'User 1',
            'google_id' => null,
        ] );

        $user2 = User::factory()->create( [
            'email'     => 'user2@gmail.com',
            'name'      => 'User 2',
            'google_id' => null,
        ] );

        // Cria token para user1
        $token = UserConfirmationToken::factory()
            ->forUser( $user1 )
            ->socialLinking()
            ->create();

        // Verifica se token pertence ao user1
        $this->assertEquals( $user1->id, $token->user_id );
        $this->assertNotEquals( $user2->id, $token->user_id );

        // Verifica se token tem tenant correto
        $this->assertEquals( $user1->tenant_id, $token->tenant_id );
    }

    /**
     * Testa se novo usuário é criado quando e-mail não existe
     *
     * @return void
     */
    public function test_new_user_created_when_email_does_not_exist(): void
    {
        // Mock do Socialite
        Socialite::shouldReceive( 'driver->user' )->andReturn( $this->googleUser );

        // Dados do Google para novo usuário
        $googleData = [
            'id'       => 'google-user-123',
            'name'     => 'João Silva',
            'email'    => 'joao.silva@gmail.com',
            'avatar'   => 'https://avatar.url',
            'verified' => true,
        ];

        // Processa autenticação
        $result = $this->socialAuthService->authenticateWithSocialProvider( 'google', $googleData );

        // Verifica se operação foi bem-sucedida
        $this->assertTrue( $result->isSuccess() );

        // Verifica se usuário foi criado com Google ID
        $this->assertDatabaseHas( 'users', [
            'email'     => 'joao.silva@gmail.com',
            'name'      => 'João Silva',
            'google_id' => 'google-user-123',
        ] );

        // Verifica se NÃO foi criado token de confirmação (novo usuário não precisa)
        $this->assertDatabaseMissing( 'user_confirmation_tokens', [
            'type' => 'social_linking',
        ] );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

}
