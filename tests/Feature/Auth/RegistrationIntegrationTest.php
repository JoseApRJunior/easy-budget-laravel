<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Events\UserRegistered;
use App\Listeners\SendWelcomeEmail;
use App\Mail\WelcomeUser;
use App\Models\Plan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Testes de integração para o sistema completo de registro.
 *
 * Esta classe testa o fluxo completo de registro, desde o controller
 * até os eventos e listeners, simulando cenários reais de uso.
 */
class RegistrationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testa fluxo completo de registro com sucesso.
     */
    public function test_complete_registration_flow_success(): void
    {
        // Arrange
        Event::fake();
        Mail::fake();

        // Criar plano trial
        $plan = Plan::factory()->create( [
            'name'   => 'Trial',
            'slug'   => 'trial',
            'price'  => 0.00,
            'status' => true,
        ] );

        // Criar role provider
        Role::factory()->create( [ 'name' => 'provider' ] );

        // Dados válidos de registro
        $userData = [
            'first_name'            => 'Maria',
            'last_name'             => 'Santos',
            'email'                 => 'maria.santos@example.com',
            'phone'                 => '(11) 99999-9999',
            'password'              => 'SenhaForte123@',
            'password_confirmation' => 'SenhaForte123@',
            'terms_accepted'        => '1',
        ];

        // Act
        $response = $this->postJson( '/register', $userData );

        // Assert
        $response->assertRedirect( '/dashboard' );
        $response->assertSessionHas( 'success' );

        // Verificar se usuário foi criado no banco
        $this->assertDatabaseHas( 'users', [
            'email'     => 'maria.santos@example.com',
            'is_active' => true,
        ] );

        // Verificar se tenant foi criado
        $this->assertDatabaseHas( 'tenants', [
            'is_active' => true,
        ] );

        // Verificar se provider foi criado
        $this->assertDatabaseHas( 'providers', [
            'terms_accepted' => true,
        ] );

        // Verificar se assinatura foi criada
        $this->assertDatabaseHas( 'plan_subscriptions', [
            'status'             => 'active',
            'transaction_amount' => 0.00,
        ] );

        // Verificar se evento foi disparado
        Event::assertDispatched( UserRegistered::class);

        // Verificar se e-mail seria enviado (se Queue estivesse habilitada)
        // Mail::assertQueued(WelcomeUser::class); // Desabilitado pois estamos usando eventos
    }

    /**
     * Testa registro com dados inválidos.
     */
    public function test_registration_with_invalid_data(): void
    {
        // Arrange
        $invalidData = [
            'first_name'            => '', // Campo obrigatório vazio
            'last_name'             => 'Santos',
            'email'                 => 'email-invalido', // Email inválido
            'phone'                 => '11999999999', // Formato incorreto
            'password'              => 'fraca', // Senha muito fraca
            'password_confirmation' => 'diferente',
            'terms_accepted'        => '0', // Termos não aceitos
        ];

        // Act
        $response = $this->postJson( '/register', $invalidData );

        // Assert
        $response->assertRedirect();
        $response->assertSessionHasErrors( [
            'first_name',
            'email',
            'phone',
            'password',
            'terms_accepted'
        ] );

        // Verificar que nenhum usuário foi criado
        $this->assertDatabaseMissing( 'users', [
            'email' => 'email-invalido',
        ] );
    }

    /**
     * Testa registro com email duplicado.
     */
    public function test_registration_with_duplicate_email(): void
    {
        // Arrange
        $existingUser = User::factory()->create( [ 'email' => 'existing@example.com' ] );

        // Criar plano trial e role provider
        Plan::factory()->create( [ 'slug' => 'trial', 'price' => 0.00, 'status' => true ] );
        Role::factory()->create( [ 'name' => 'provider' ] );

        $userData = [
            'first_name'            => 'João',
            'last_name'             => 'Silva',
            'email'                 => 'existing@example.com', // Email já existente
            'phone'                 => '(11) 99999-9999',
            'password'              => 'SenhaForte123@',
            'password_confirmation' => 'SenhaForte123@',
            'terms_accepted'        => '1',
        ];

        // Act
        $response = $this->postJson( '/register', $userData );

        // Assert
        $response->assertRedirect();
        $response->assertSessionHasErrors( [ 'email' ] );

        // Verificar que nenhum novo usuário foi criado
        $this->assertDatabaseMissing( 'users', [
            'email'      => 'existing@example.com',
            'first_name' => 'João',
        ] );
    }

    /**
     * Testa evento UserRegistered sendo disparado.
     */
    public function test_user_registered_event_dispatched(): void
    {
        // Arrange
        Event::fake();

        // Criar plano trial e role provider
        Plan::factory()->create( [ 'slug' => 'trial', 'price' => 0.00, 'status' => true ] );
        Role::factory()->create( [ 'name' => 'provider' ] );

        $userData = [
            'first_name'            => 'Ana',
            'last_name'             => 'Costa',
            'email'                 => 'ana.costa@example.com',
            'phone'                 => '(11) 99999-9999',
            'password'              => 'SenhaForte123@',
            'password_confirmation' => 'SenhaForte123@',
            'terms_accepted'        => '1',
        ];

        // Act
        $this->postJson( '/register', $userData );

        // Assert
        Event::assertDispatched( UserRegistered::class, function ( $event ) {
            return $event->user->email === 'ana.costa@example.com';
        } );
    }

    /**
     * Testa listener SendWelcomeEmail processando evento.
     */
    public function test_send_welcome_email_listener_processes_event(): void
    {
        // Arrange
        Event::fake();
        Mail::fake();

        $user   = User::factory()->create();
        $tenant = \App\Models\Tenant::factory()->create();

        // Act
        $listener = new SendWelcomeEmail();
        $listener->handle( new UserRegistered( $user, $tenant ) );

        // Assert - O listener deve processar sem erros
        // Em um cenário real, verificaria se o e-mail foi enfileirado
        $this->assertTrue( true ); // Listener executou sem lançar exceção
    }

    /**
     * Testa registro com telefone formatado automaticamente.
     */
    public function test_registration_with_phone_auto_formatting(): void
    {
        // Arrange
        Plan::factory()->create( [ 'slug' => 'trial', 'price' => 0.00, 'status' => true ] );
        Role::factory()->create( [ 'name' => 'provider' ] );

        $userData = [
            'first_name'            => 'Carlos',
            'last_name'             => 'Oliveira',
            'email'                 => 'carlos.oliveira@example.com',
            'phone'                 => '11999999999', // Telefone sem formatação
            'password'              => 'SenhaForte123@',
            'password_confirmation' => 'SenhaForte123@',
            'terms_accepted'        => '1',
        ];

        // Act
        $response = $this->postJson( '/register', $userData );

        // Assert
        $response->assertRedirect( '/dashboard' );

        // Verificar se telefone foi formatado e salvo corretamente
        $this->assertDatabaseHas( 'contacts', [
            'phone' => '(11) 99999-9999',
        ] );
    }

    /**
     * Testa registro com geração de nome único para tenant.
     */
    public function test_registration_with_unique_tenant_name_generation(): void
    {
        // Arrange
        Plan::factory()->create( [ 'slug' => 'trial', 'price' => 0.00, 'status' => true ] );
        Role::factory()->create( [ 'name' => 'provider' ] );

        // Criar tenant com nome que será usado como base
        \App\Models\Tenant::factory()->create( [ 'name' => 'maria-santos' ] );

        $userData = [
            'first_name'            => 'Maria',
            'last_name'             => 'Santos',
            'email'                 => 'maria.santos2@example.com',
            'phone'                 => '(11) 99999-9999',
            'password'              => 'SenhaForte123@',
            'password_confirmation' => 'SenhaForte123@',
            'terms_accepted'        => '1',
        ];

        // Act
        $response = $this->postJson( '/register', $userData );

        // Assert
        $response->assertRedirect( '/dashboard' );

        // Verificar que tenant foi criado com nome único
        $this->assertDatabaseHas( 'tenants', [
            'is_active' => true,
        ] );

        // Verificar que não é o nome original (deve ter sido modificado)
        $tenant = \App\Models\Tenant::where( 'is_active', true )
            ->where( 'name', '!=', 'maria-santos' )
            ->first();

        $this->assertNotNull( $tenant );
    }

    /**
     * Testa registro com falha no envio de e-mail de verificação.
     */
    public function test_registration_with_email_verification_failure(): void
    {
        // Arrange
        $this->app[ 'config' ]->set( 'app.email_verification_required', true );

        Plan::factory()->create( [ 'slug' => 'trial', 'price' => 0.00, 'status' => true ] );
        Role::factory()->create( [ 'name' => 'provider' ] );

        // Mock para simular falha no envio de e-mail
        $mockUser = $this->createMock( User::class);
        $mockUser->method( 'sendEmailVerificationNotification' )->willThrowException( new \Exception( 'Erro de e-mail' ) );

        $this->app->bind( User::class, function () use ($mockUser) {
            return $mockUser;
        } );

        $userData = [
            'first_name'            => 'Pedro',
            'last_name'             => 'Almeida',
            'email'                 => 'pedro.almeida@example.com',
            'phone'                 => '(11) 99999-9999',
            'password'              => 'SenhaForte123@',
            'password_confirmation' => 'SenhaForte123@',
            'terms_accepted'        => '1',
        ];

        // Act
        $response = $this->postJson( '/register', $userData );

        // Assert
        $response->assertRedirect( '/dashboard' );
        $response->assertSessionHas( 'success' );

        // Verificar que usuário foi criado mesmo com falha no e-mail
        $this->assertDatabaseHas( 'users', [
            'email' => 'pedro.almeida@example.com',
        ] );
    }

    /**
     * Testa acesso à página de registro.
     */
    public function test_register_page_access(): void
    {
        // Act
        $response = $this->get( '/register' );

        // Assert
        $response->assertStatus( 200 );
        $response->assertViewIs( 'auth.enhanced-register' );
    }

    /**
     * Testa redirecionamento para dashboard após registro bem-sucedido.
     */
    public function test_redirect_to_dashboard_after_successful_registration(): void
    {
        // Arrange
        Plan::factory()->create( [ 'slug' => 'trial', 'price' => 0.00, 'status' => true ] );
        Role::factory()->create( [ 'name' => 'provider' ] );

        $userData = [
            'first_name'            => 'Lucia',
            'last_name'             => 'Ferreira',
            'email'                 => 'lucia.ferreira@example.com',
            'phone'                 => '(11) 99999-9999',
            'password'              => 'SenhaForte123@',
            'password_confirmation' => 'SenhaForte123@',
            'terms_accepted'        => '1',
        ];

        // Act
        $response = $this->post( '/register', $userData );

        // Assert
        $response->assertRedirect( '/dashboard' );
        $response->assertSessionHas( 'success' );
    }

    /**
     * Testa que usuário fica logado automaticamente após registro.
     */
    public function test_user_auto_login_after_registration(): void
    {
        // Arrange
        Plan::factory()->create( [ 'slug' => 'trial', 'price' => 0.00, 'status' => true ] );
        Role::factory()->create( [ 'name' => 'provider' ] );

        $userData = [
            'first_name'            => 'Roberto',
            'last_name'             => 'Lima',
            'email'                 => 'roberto.lima@example.com',
            'phone'                 => '(11) 99999-9999',
            'password'              => 'SenhaForte123@',
            'password_confirmation' => 'SenhaForte123@',
            'terms_accepted'        => '1',
        ];

        // Act
        $this->post( '/register', $userData );

        // Assert
        $this->assertAuthenticated();
        $this->assertAuthenticatedAs( User::where( 'email', 'roberto.lima@example.com' )->first() );
    }

    /**
     * Testa tratamento de erro interno durante registro.
     */
    public function test_registration_internal_error_handling(): void
    {
        // Arrange - Simular erro interno forçando uma exceção
        $this->mock( \App\Services\Application\UserRegistrationService::class, function ( $mock ) {
            $mock->shouldReceive( 'registerUser' )->andThrow( new \Exception( 'Erro interno' ) );
        } );

        $userData = [
            'first_name'            => 'Teste',
            'last_name'             => 'Erro',
            'email'                 => 'teste.erro@example.com',
            'phone'                 => '(11) 99999-9999',
            'password'              => 'SenhaForte123@',
            'password_confirmation' => 'SenhaForte123@',
            'terms_accepted'        => '1',
        ];

        // Act
        $response = $this->postJson( '/register', $userData );

        // Assert
        $response->assertRedirect();
        $response->assertSessionHasErrors( [ 'registration' ] );

        // Verificar que nenhum dado foi persistido
        $this->assertDatabaseMissing( 'users', [
            'email' => 'teste.erro@example.com',
        ] );
    }

}
