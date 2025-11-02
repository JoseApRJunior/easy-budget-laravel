<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;

use App\Events\SocialLoginWelcome;
use App\Listeners\SendSocialLoginWelcomeNotification;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Application\Auth\SocialAuthenticationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

/**
 * Testes de integração para o e-mail de boas-vindas do login social
 *
 * Esta classe testa especificamente o envio do e-mail de boas-vindas
 * para usuários que fazem login via provedores sociais (Google).
 */
class SocialLoginWelcomeTest extends TestCase
{
    use RefreshDatabase;

    private SocialAuthenticationService $socialAuthService;
    private                             $googleUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Configura tenant de teste para evitar problemas com escopo do tenant
        config( [ 'tenant.testing_id' => 1 ] );

        // Executa seeders necessários para o teste
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

        // Instancia o serviço real
        $this->socialAuthService = app( SocialAuthenticationService::class);
    }

    /**
     * Testa se o evento SocialLoginWelcome é disparado para novo usuário via Google
     *
     * @return void
     */
    public function test_social_login_welcome_event_dispatched_for_new_google_user(): void
    {
        // Cria tenant de teste
        $tenant = Tenant::factory()->create();

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

        // Espera que o evento seja disparado
        Event::fake();

        // Processa autenticação (deve criar novo usuário e disparar evento)
        $result = $this->socialAuthService->authenticateWithSocialProvider( 'google', $googleData );

        // Debug: verificar resultado da autenticação
        if ( !$result->isSuccess() ) {
            dump( 'Authentication failed:', $result->getMessage() );
        }

        // Verifica se operação foi bem-sucedida
        $this->assertTrue( $result->isSuccess(), 'Authentication should succeed for new Google user' );

        // Verifica se usuário foi criado
        $this->assertDatabaseHas( 'users', [
            'email'     => 'joao.silva@gmail.com',
            'name'      => 'João Silva',
            'google_id' => 'google-user-123',
        ] );

        // Verifica se evento foi disparado
        Event::assertDispatched( SocialLoginWelcome::class, function ( $event ) use ( $googleData ) {
            return $event->user->email === $googleData[ 'email' ] &&
                $event->provider === 'google' &&
                $event->tenant !== null;
        } );
    }

    /**
     * Testa se o evento NÃO é disparado para usuário existente via Google
     *
     * @return void
     */
    public function test_social_login_welcome_event_not_dispatched_for_existing_google_user(): void
    {
        // Cria usuário existente com Google ID
        $existingUser = User::factory()->create( [
            'email'     => 'joao.silva@gmail.com',
            'google_id' => 'google-user-123',
            'name'      => 'João Silva Existente',
        ] );

        // Mock do Socialite
        Socialite::shouldReceive( 'driver->user' )->andReturn( $this->googleUser );

        // Dados do Google para usuário existente
        $googleData = [
            'id'       => 'google-user-123',
            'name'     => 'João Silva Atualizado',
            'email'    => 'joao.silva@gmail.com',
            'avatar'   => 'https://avatar-atualizado.url',
            'verified' => true,
        ];

        // Espera que o evento NÃO seja disparado
        Event::fake();

        // Processa autenticação (deve atualizar usuário existente)
        $result = $this->socialAuthService->authenticateWithSocialProvider( 'google', $googleData );

        // Verifica se operação foi bem-sucedida
        $this->assertTrue( $result->isSuccess() );

        // Verifica se dados foram atualizados
        $existingUser->refresh();
        $this->assertEquals( 'João Silva Atualizado', $existingUser->name );

        // Verifica se evento NÃO foi disparado (apenas para novos usuários)
        Event::assertNotDispatched( SocialLoginWelcome::class);
    }

    /**
     * Testa se o evento NÃO é disparado para cadastro normal (não social)
     *
     * @return void
     */
    public function test_social_login_welcome_event_not_dispatched_for_regular_registration(): void
    {
        // Arrange - Mock para capturar eventos
        Event::fake();

        // Cria usuário diretamente (simulando cadastro normal, não social)
        $tenant = Tenant::factory()->create();
        $user   = User::factory()->create( [
            'tenant_id' => $tenant->id,
            'email'     => 'maria.santos@gmail.com',
        ] );

        // Verifica se usuário foi criado
        $this->assertDatabaseHas( 'users', [
            'email' => 'maria.santos@gmail.com',
        ] );

        // Verifica se evento NÃO foi disparado (cadastro normal, não social)
        Event::assertNotDispatched( SocialLoginWelcome::class);
    }

    /**
     * Testa se o listener processa o evento corretamente e envia e-mail
     *
     * @return void
     */
    public function test_social_login_welcome_listener_sends_email(): void
    {
        // Cria tenant e usuário para teste
        $tenant = Tenant::factory()->create();
        $user   = User::factory()->create( [
            'tenant_id' => $tenant->id,
            'email'     => 'teste@gmail.com',
            'name'      => 'Teste Usuário',
        ] );

        // Cria evento
        $event = new SocialLoginWelcome( $user, $tenant, 'google' );

        // Mock do Mail para capturar e-mail enviado
        Mail::fake();

        // Processa evento através do listener (resolve dependências automaticamente)
        $listener = app( SendSocialLoginWelcomeNotification::class);
        $listener->handle( $event );

        // Verifica se e-mail foi enfileirado (queued)
        Mail::assertQueued( \App\Mail\SocialLoginWelcomeMail::class, function ( $mail ) use ( $user ) {
            return $mail->hasTo( $user->email ) &&
                $mail->user->id === $user->id &&
                $mail->provider === 'google';
        } );
    }

    /**
     * Testa se o e-mail é enfileirado para processamento assíncrono
     *
     * @return void
     */
    public function test_social_login_welcome_email_is_queued(): void
    {
        // Cria tenant e usuário para teste
        $tenant = Tenant::factory()->create();
        $user   = User::factory()->create( [
            'tenant_id' => $tenant->id,
            'email'     => 'teste@gmail.com',
            'name'      => 'Teste Usuário',
        ] );

        // Cria evento
        $event = new SocialLoginWelcome( $user, $tenant, 'google' );

        // Mock do Mail para verificar se foi enfileirado
        Mail::fake();

        // Processa evento através do listener (resolve dependências automaticamente)
        $listener = app( SendSocialLoginWelcomeNotification::class);
        $listener->handle( $event );

        // Verifica se e-mail foi enfileirado (não enviado imediatamente)
        Mail::assertQueued( \App\Mail\SocialLoginWelcomeMail::class, function ( $mail ) use ( $user ) {
            return $mail->hasTo( $user->email ) &&
                $mail->user->id === $user->id &&
                $mail->provider === 'google';
        } );
    }

    /**
     * Testa se o evento funciona com diferentes provedores sociais
     *
     * @return void
     */
    public function test_social_login_welcome_event_works_with_different_providers(): void
    {
        // Cria tenant de teste
        $tenant = Tenant::factory()->create();

        // Mock do Socialite para Facebook
        $facebookUser = Mockery::mock();
        $facebookUser->shouldReceive( 'getId' )->andReturn( 'facebook-user-456' );
        $facebookUser->shouldReceive( 'getName' )->andReturn( 'Ana Costa' );
        $facebookUser->shouldReceive( 'getEmail' )->andReturn( 'ana.costa@facebook.com' );
        $facebookUser->shouldReceive( 'getAvatar' )->andReturn( 'https://facebook-avatar.url' );

        Socialite::shouldReceive( 'driver->user' )->andReturn( $facebookUser );

        // Dados do Facebook
        $facebookData = [
            'id'       => 'facebook-user-456',
            'name'     => 'Ana Costa',
            'email'    => 'ana.costa@facebook.com',
            'avatar'   => 'https://facebook-avatar.url',
            'verified' => true,
        ];

        // Espera que o evento seja disparado com provider correto
        Event::fake();

        // Processa autenticação
        $result = $this->socialAuthService->authenticateWithSocialProvider( 'facebook', $facebookData );

        // Verifica se operação foi bem-sucedida
        $this->assertTrue( $result->isSuccess() );

        // Verifica se evento foi disparado com provider correto
        Event::assertDispatched( SocialLoginWelcome::class, function ( $event ) use ( $facebookData ) {
            return $event->user->email === $facebookData[ 'email' ] &&
                $event->provider === 'facebook';
        } );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

}
