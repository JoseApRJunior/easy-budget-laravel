<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Application;

use App\Enums\OperationStatus;
use App\Events\UserRegistered;
use App\Models\CommonData;
use App\Models\Plan;
use App\Models\PlanSubscription;
use App\Models\Provider;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserConfirmationToken;
use App\Repositories\ProviderRepository;
use App\Repositories\TenantRepository;
use App\Repositories\UserConfirmationTokenRepository;
use App\Repositories\UserRepository;
use App\Services\Application\EmailVerificationService;
use App\Services\Application\ProviderManagementService;
use App\Services\Application\UserConfirmationTokenService;
use App\Services\Application\UserRegistrationService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Testes unitários para UserRegistrationService.
 *
 * Esta classe testa toda a lógica de negócio implementada no
 * UserRegistrationService, incluindo casos de sucesso e falha.
 */
class UserRegistrationServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserRegistrationService         $service;
    private UserRepository                  $userRepository;
    private UserConfirmationTokenService    $userConfirmationTokenService;
    private UserConfirmationTokenRepository $userConfirmationTokenRepository;
    private ProviderManagementService       $providerManagementService;
    private EmailVerificationService        $emailVerificationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository                  = $this->app->make( UserRepository::class);
        $this->userConfirmationTokenService    = $this->app->make( UserConfirmationTokenService::class);
        $this->userConfirmationTokenRepository = $this->app->make( UserConfirmationTokenRepository::class);
        $this->providerManagementService       = $this->app->make( ProviderManagementService::class);
        $this->emailVerificationService        = $this->app->make( EmailVerificationService::class);

        $this->service = new UserRegistrationService(
            $this->userRepository,
            $this->userConfirmationTokenService,
            $this->userConfirmationTokenRepository,
            $this->providerManagementService,
            $this->emailVerificationService,
        );
    }

    /**
     * Dados válidos para teste de registro.
     */
    private function getValidUserData(): array
    {
        return [
            'first_name'     => 'João',
            'last_name'      => 'Silva',
            'email'          => 'joao.silva@example.com',
            'phone'          => '(11) 99999-9999',
            'password'       => 'SenhaForte123@',
            'terms_accepted' => true,
        ];
    }

    /**
     * Testa registro completo com sucesso.
     */
    public function test_successful_user_registration(): void
    {
        // Arrange
        Event::fake();
        $userData = $this->getValidUserData();

        // Criar plano trial
        $plan = Plan::factory()->create( [
            'name'   => 'Trial',
            'slug'   => 'trial',
            'price'  => 0.00,
            'status' => true,
        ] );

        // Criar role provider
        $providerRole = Role::factory()->create( [ 'name' => 'provider' ] );

        // Act
        $result = $this->service->registerUser( $userData );

        // Assert
        $this->assertTrue( $result->isSuccess() );
        $this->assertEquals( 'Usuário registrado com sucesso.', $result->getMessage() );

        $data = $result->getData();
        $this->assertArrayHasKey( 'user', $data );
        $this->assertArrayHasKey( 'tenant', $data );
        $this->assertArrayHasKey( 'provider', $data );
        $this->assertArrayHasKey( 'plan', $data );
        $this->assertArrayHasKey( 'subscription', $data );
        $this->assertArrayHasKey( 'auto_logged_in', $data );
        $this->assertArrayHasKey( 'message', $data );

        // Verificar se usuário foi criado corretamente
        $user = $data[ 'user' ];
        $this->assertInstanceOf( User::class, $user );
        $this->assertEquals( $userData[ 'email' ], $user->email );
        $this->assertTrue( Hash::check( $userData[ 'password' ], $user->password ) );
        $this->assertTrue( $user->is_active );

        // Verificar se tenant foi criado
        $tenant = $data[ 'tenant' ];
        $this->assertInstanceOf( Tenant::class, $tenant );
        $this->assertTrue( $tenant->is_active );

        // Verificar se provider foi criado
        $provider = $data[ 'provider' ];
        $this->assertInstanceOf( Provider::class, $provider );
        $this->assertEquals( $user->id, $provider->user_id );
        $this->assertEquals( $tenant->id, $provider->tenant_id );
        $this->assertTrue( $provider->terms_accepted );

        // Verificar se assinatura foi criada
        $subscription = $data[ 'subscription' ];
        $this->assertInstanceOf( PlanSubscription::class, $subscription );
        $this->assertEquals( $tenant->id, $subscription->tenant_id );
        $this->assertEquals( $user->id, $subscription->user_id );
        $this->assertEquals( $provider->id, $subscription->provider_id );
        $this->assertEquals( $plan->id, $subscription->plan_id );
        $this->assertEquals( 'active', $subscription->status );

        // Verificar se usuário está logado
        $this->assertTrue( $data[ 'auto_logged_in' ] );
        $this->assertEquals( $user->id, Auth::id() );

        // Verificar se evento foi disparado
        Event::assertDispatched( UserRegistered::class, function ( $event ) use ( $user, $tenant ) {
            return $event->user->id === $user->id && $event->tenant->id === $tenant->id;
        } );
    }

    /**
     * Testa registro social - evento UserRegistered NÃO deve ser disparado.
     */
    public function test_social_registration_does_not_dispatch_user_registered_event(): void
    {
        // Arrange
        Event::fake();
        $userData = $this->getValidUserData();

        // Criar plano trial
        $plan = Plan::factory()->create( [
            'name'   => 'Trial',
            'slug'   => 'trial',
            'price'  => 0.00,
            'status' => true,
        ] );

        // Criar role provider
        $providerRole = Role::factory()->create( [ 'name' => 'provider' ] );

        // Act
        $result = $this->service->registerUser( $userData, true ); // isSocialRegistration = true

        // Assert
        $this->assertTrue( $result->isSuccess() );

        $data   = $result->getData();
        $user   = $data[ 'user' ];
        $tenant = $data[ 'tenant' ];

        // Verificar se usuário foi criado
        $this->assertInstanceOf( User::class, $user );
        $this->assertEquals( $userData[ 'email' ], $user->email );

        // Verificar se tenant foi criado
        $this->assertInstanceOf( Tenant::class, $tenant );

        // Verificar que evento UserRegistered NÃO foi disparado para registro social
        Event::assertNotDispatched( UserRegistered::class);
    }

    /**
     * Testa registro com dados obrigatórios ausentes.
     */
    public function test_registration_with_missing_required_data(): void
    {
        // Arrange
        $incompleteData = [
            'first_name' => 'João',
            // faltando last_name, email, password, phone, terms_accepted
        ];

        // Act
        $result = $this->service->registerUser( $incompleteData );

        // Assert
        $this->assertFalse( $result->isSuccess() );
        $this->assertEquals( OperationStatus::INVALID_DATA, $result->getStatus() );
        $this->assertStringContainsString( 'Dados obrigatórios ausentes', $result->getMessage() );
    }

    /**
     * Testa geração de nome único para tenant.
     */
    public function test_unique_tenant_name_generation(): void
    {
        // Arrange
        $userData = $this->getValidUserData();

        // Act & Assert - Estratégia 1: Nome completo
        $tenantName1 = $this->invokePrivateMethod( $this->service, 'generateUniqueTenantName', [ $userData ] );
        $this->assertIsString( $tenantName1 );
        $this->assertNotEmpty( $tenantName1 );

        // Criar tenant com nome gerado
        Tenant::factory()->create( [ 'name' => $tenantName1 ] );

        // Act & Assert - Estratégia 2: Email prefix (deve gerar nome diferente)
        $tenantName2 = $this->invokePrivateMethod( $this->service, 'generateUniqueTenantName', [ $userData ] );
        $this->assertNotEquals( $tenantName1, $tenantName2 );

        // Criar segundo tenant
        Tenant::factory()->create( [ 'name' => $tenantName2 ] );

        // Act & Assert - Estratégia 3: Nome com contador
        $tenantName3 = $this->invokePrivateMethod( $this->service, 'generateUniqueTenantName', [ $userData ] );
        $this->assertNotEquals( $tenantName1, $tenantName3 );
        $this->assertNotEquals( $tenantName2, $tenantName3 );
        $this->assertStringContainsString( '-', $tenantName3 ); // Deve conter contador
    }

    /**
     * Testa busca de plano trial.
     */
    public function test_find_trial_plan_success(): void
    {
        // Arrange
        $plan = Plan::factory()->create( [
            'name'   => 'Trial',
            'slug'   => 'trial',
            'price'  => 0.00,
            'status' => true,
        ] );

        // Act
        $result = $this->invokePrivateMethod( $this->service, 'findTrialPlan', [] );

        // Assert
        $this->assertTrue( $result->isSuccess() );
        $this->assertEquals( $plan->id, $result->getData()->id );
    }

    /**
     * Testa busca de plano trial quando não existe plano trial específico.
     */
    public function test_find_trial_plan_fallback_to_free_plan(): void
    {
        // Arrange - Criar apenas plano gratuito (não trial)
        $freePlan = Plan::factory()->create( [
            'name'   => 'Free',
            'slug'   => 'free',
            'price'  => 0.00,
            'status' => true,
        ] );

        // Act
        $result = $this->invokePrivateMethod( $this->service, 'findTrialPlan', [] );

        // Assert
        $this->assertTrue( $result->isSuccess() );
        $this->assertEquals( $freePlan->id, $result->getData()->id );
    }

    /**
     * Testa busca de plano trial quando não existe nenhum plano gratuito.
     */
    public function test_find_trial_plan_no_free_plan(): void
    {
        // Arrange - Criar apenas plano pago
        Plan::factory()->create( [
            'name'   => 'Premium',
            'slug'   => 'premium',
            'price'  => 99.90,
            'status' => true,
        ] );

        // Act
        $result = $this->invokePrivateMethod( $this->service, 'findTrialPlan', [] );

        // Assert
        $this->assertFalse( $result->isSuccess() );
        $this->assertEquals( OperationStatus::ERROR, $result->getStatus() );
        $this->assertStringContainsString( 'Plano trial não encontrado', $result->getMessage() );
    }

    /**
     * Testa criação de CommonData.
     */
    public function test_create_common_data_success(): void
    {
        // Arrange
        $userData = $this->getValidUserData();
        $tenant   = Tenant::factory()->create();

        // Act
        $result = $this->invokePrivateMethod( $this->service, 'createCommonData', [ $userData, $tenant ] );

        // Assert
        $this->assertTrue( $result->isSuccess() );
        $commonData = $result->getData();
        $this->assertInstanceOf( CommonData::class, $commonData );
        $this->assertEquals( $tenant->id, $commonData->tenant_id );
        $this->assertEquals( $userData[ 'first_name' ], $commonData->first_name );
        $this->assertEquals( $userData[ 'last_name' ], $commonData->last_name );
    }

    /**
     * Testa criação de Provider.
     */
    public function test_create_provider_success(): void
    {
        // Arrange
        $user          = User::factory()->create();
        $commonData    = CommonData::factory()->create();
        $tenant        = Tenant::factory()->create();
        $termsAccepted = true;

        // Act
        $result = $this->invokePrivateMethod( $this->service, 'createProvider', [ $user, $commonData, $tenant, $termsAccepted ] );

        // Assert
        $this->assertTrue( $result->isSuccess() );
        $provider = $result->getData();
        $this->assertInstanceOf( Provider::class, $provider );
        $this->assertEquals( $tenant->id, $provider->tenant_id );
        $this->assertEquals( $user->id, $provider->user_id );
        $this->assertEquals( $commonData->id, $provider->common_data_id );
        $this->assertTrue( $provider->terms_accepted );
    }

    /**
     * Testa associação de role provider.
     */
    public function test_assign_provider_role_success(): void
    {
        // Arrange
        $user         = User::factory()->create();
        $tenant       = Tenant::factory()->create();
        $providerRole = Role::factory()->create( [ 'name' => 'provider' ] );

        // Act
        $result = $this->invokePrivateMethod( $this->service, 'assignProviderRole', [ $user, $tenant ] );

        // Assert
        $this->assertTrue( $result->isSuccess() );
        $this->assertTrue( $user->roles()->where( 'name', 'provider' )->exists() );
    }

    /**
     * Testa associação de role quando role provider não existe.
     */
    public function test_assign_provider_role_not_found(): void
    {
        // Arrange
        $user   = User::factory()->create();
        $tenant = Tenant::factory()->create();

        // Act
        $result = $this->invokePrivateMethod( $this->service, 'assignProviderRole', [ $user, $tenant ] );

        // Assert
        $this->assertFalse( $result->isSuccess() );
        $this->assertEquals( OperationStatus::ERROR, $result->getStatus() );
        $this->assertStringContainsString( 'Role provider não encontrado', $result->getMessage() );
    }

    /**
     * Testa criação de assinatura de plano.
     */
    public function test_create_plan_subscription_success(): void
    {
        // Arrange
        $tenant   = Tenant::factory()->create();
        $plan     = Plan::factory()->create( [ 'price' => 0.00 ] );
        $user     = User::factory()->create();
        $provider = Provider::factory()->create();

        // Act
        $result = $this->invokePrivateMethod( $this->service, 'createPlanSubscription', [ $tenant, $plan, $user, $provider ] );

        // Assert
        $this->assertTrue( $result->isSuccess() );
        $subscription = $result->getData();
        $this->assertInstanceOf( PlanSubscription::class, $subscription );
        $this->assertEquals( $tenant->id, $subscription->tenant_id );
        $this->assertEquals( $plan->id, $subscription->plan_id );
        $this->assertEquals( $user->id, $subscription->user_id );
        $this->assertEquals( $provider->id, $subscription->provider_id );
        $this->assertEquals( 'active', $subscription->status );
        $this->assertEquals( 0.00, $subscription->transaction_amount );
    }

    /**
     * Testa registro com email duplicado.
     */
    public function test_registration_with_duplicate_email(): void
    {
        // Arrange
        $existingUser = User::factory()->create( [ 'email' => 'joao.silva@example.com' ] );
        $userData     = $this->getValidUserData();

        // Criar plano trial e role provider
        Plan::factory()->create( [ 'slug' => 'trial', 'price' => 0.00, 'status' => true ] );
        Role::factory()->create( [ 'name' => 'provider' ] );

        // Act
        $result = $this->service->registerUser( $userData );

        // Assert
        $this->assertFalse( $result->isSuccess() );
        $this->assertStringContainsString( 'Erro ao criar usuário', $result->getMessage() );
    }

    /**
     * Testa registro com falha na criação de tenant.
     */
    public function test_registration_tenant_creation_failure(): void
    {
        // Arrange - Mock para simular falha na criação de tenant
        $this->mock( TenantRepository::class, function ( $mock ) {
            $mock->shouldReceive( 'save' )->andThrow( new Exception( 'Erro no banco de dados' ) );
        } );

        $userData = $this->getValidUserData();

        // Act
        $result = $this->service->registerUser( $userData );

        // Assert
        $this->assertFalse( $result->isSuccess() );
        $this->assertStringContainsString( 'Erro ao criar tenant', $result->getMessage() );
    }

    /**
     * Testa registro com falha na criação de usuário.
     */
    public function test_registration_user_creation_failure(): void
    {
        // Arrange
        $userData = $this->getValidUserData();

        // Criar plano trial e role provider
        Plan::factory()->create( [ 'slug' => 'trial', 'price' => 0.00, 'status' => true ] );
        Role::factory()->create( [ 'name' => 'provider' ] );

        // Mock para simular falha na criação de usuário
        $this->mock( UserRepository::class, function ( $mock ) {
            $mock->shouldReceive( 'save' )->andThrow( new Exception( 'Erro no banco de dados' ) );
        } );

        // Act
        $result = $this->service->registerUser( $userData );

        // Assert
        $this->assertFalse( $result->isSuccess() );
        $this->assertStringContainsString( 'Erro ao criar usuário', $result->getMessage() );
    }

    /**
     * Testa registro com transação revertida.
     */
    public function test_registration_with_rollback(): void
    {
        // Arrange
        $userData = $this->getValidUserData();

        // Criar plano trial e role provider
        Plan::factory()->create( [ 'slug' => 'trial', 'price' => 0.00, 'status' => true ] );
        Role::factory()->create( [ 'name' => 'provider' ] );

        // Mock para simular falha após criação parcial
        $this->mock( ProviderRepository::class, function ( $mock ) {
            $mock->shouldReceive( 'save' )->andThrow( new Exception( 'Erro no banco de dados' ) );
        } );

        // Act
        $result = $this->service->registerUser( $userData );

        // Assert
        $this->assertFalse( $result->isSuccess() );
        $this->assertEquals( OperationStatus::ERROR, $result->getStatus() );
        $this->assertStringContainsString( 'Erro interno do servidor', $result->getMessage() );

        // Verificar que nenhum dado foi persistido
        $this->assertDatabaseMissing( 'tenants', [ 'name' => 'joao-silva' ] );
        $this->assertDatabaseMissing( 'users', [ 'email' => $userData[ 'email' ] ] );
    }

    /**
     * Testa método requestPasswordReset com sucesso.
     */
    public function test_request_password_reset_success(): void
    {
        // Arrange
        Event::fake();
        $user  = User::factory()->create( [ 'email' => 'test@example.com' ] );
        $email = 'test@example.com';

        // Act
        $result = $this->service->requestPasswordReset( $email );

        // Assert
        $this->assertTrue( $result->isSuccess() );
        $this->assertStringContainsString( 'instruções de redefinição', $result->getMessage() );

        // Verificar se token foi criado
        $this->assertDatabaseHas( 'user_confirmation_tokens', [
            'user_id' => $user->id,
            'type'    => 'password_reset',
        ] );

        // Verificar se evento foi disparado
        Event::assertDispatched( \App\Events\PasswordResetRequested::class);
    }

    /**
     * Testa método requestPasswordReset com email inexistente.
     */
    public function test_request_password_reset_email_not_found(): void
    {
        // Arrange
        $email = 'nonexistent@example.com';

        // Act
        $result = $this->service->requestPasswordReset( $email );

        // Assert
        $this->assertTrue( $result->isSuccess() ); // Não revela se email existe por segurança
        $this->assertStringContainsString( 'Se o e-mail existir', $result->getMessage() );

        // Verificar que nenhum token foi criado
        $this->assertDatabaseMissing( 'user_confirmation_tokens', [
            'type' => 'password_reset',
        ] );
    }

    /**
     * Testa método requestPasswordReset com erro interno.
     */
    public function test_request_password_reset_internal_error(): void
    {
        // Arrange - Mock para simular erro
        $this->mock( UserRepository::class, function ( $mock ) {
            $mock->shouldReceive( 'findByEmail' )->andThrow( new Exception( 'Erro no banco' ) );
        } );

        $email = 'test@example.com';

        // Act
        $result = $this->service->requestPasswordReset( $email );

        // Assert
        $this->assertFalse( $result->isSuccess() );
        $this->assertEquals( OperationStatus::ERROR, $result->getStatus() );
        $this->assertStringContainsString( 'Erro ao processar solicitação', $result->getMessage() );
    }

    /**
     * Testa criação de token de confirmação.
     */
    public function test_create_confirmation_token_success(): void
    {
        // Arrange
        $user   = User::factory()->create();
        $tenant = Tenant::factory()->create();

        // Act
        $result = $this->invokePrivateMethod( $this->service, 'createConfirmationToken', [ $user, $tenant ] );

        // Assert
        $this->assertTrue( $result->isSuccess() );
        $token = $result->getData();
        $this->assertInstanceOf( UserConfirmationToken::class, $token );
        $this->assertEquals( $user->id, $token->user_id );
        $this->assertEquals( $tenant->id, $token->tenant_id );
        $this->assertEquals( \App\Enums\TokenType::EMAIL_VERIFICATION, $token->type );
        $this->assertNotEmpty( $token->token );
        $this->assertNotNull( $token->expires_at );
    }

    /**
     * Método auxiliar para invocar métodos privados em testes.
     */
    private function invokePrivateMethod( object $object, string $method, array $parameters = [] ): mixed
    {
        $reflection    = new \ReflectionClass( $object );
        $privateMethod = $reflection->getMethod( $method );
        $privateMethod->setAccessible( true );

        return $privateMethod->invokeArgs( $object, $parameters );
    }

}
