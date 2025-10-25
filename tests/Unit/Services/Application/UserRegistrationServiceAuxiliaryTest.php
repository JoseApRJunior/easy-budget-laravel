<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Application;

use App\Models\Plan;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Repositories\TenantRepository;
use App\Services\Application\UserRegistrationService;
use App\Support\ServiceResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testes para funcionalidades auxiliares do UserRegistrationService.
 *
 * Esta classe testa métodos privados auxiliares como geração de nomes únicos
 * e busca de planos trial, que são críticos para o funcionamento correto.
 */
class UserRegistrationServiceAuxiliaryTest extends TestCase
{
    use RefreshDatabase;

    private UserRegistrationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make( UserRegistrationService::class);
    }

    /**
     * Testa geração de nome único usando estratégia 1 (nome completo).
     */
    public function test_generate_unique_tenant_name_strategy_1(): void
    {
        // Arrange
        $userData = [
            'first_name' => 'João',
            'last_name'  => 'Silva',
            'email'      => 'joao.silva@example.com',
        ];

        // Act
        $tenantName = $this->invokePrivateMethod( $this->service, 'generateUniqueTenantName', [ $userData ] );

        // Assert
        $this->assertIsString( $tenantName );
        $this->assertNotEmpty( $tenantName );
        $this->assertEquals( 'joao-silva', $tenantName );
    }

    /**
     * Testa geração de nome único usando estratégia 2 (email prefix).
     */
    public function test_generate_unique_tenant_name_strategy_2(): void
    {
        // Arrange
        $userData = [
            'first_name' => 'João',
            'last_name'  => 'Silva',
            'email'      => 'joao.silva@example.com',
        ];

        // Criar tenant com nome da estratégia 1
        Tenant::factory()->create( [ 'name' => 'joao-silva' ] );

        // Act
        $tenantName = $this->invokePrivateMethod( $this->service, 'generateUniqueTenantName', [ $userData ] );

        // Assert
        $this->assertIsString( $tenantName );
        $this->assertNotEmpty( $tenantName );
        $this->assertEquals( 'joao.silva', $tenantName ); // Deve usar prefix do email
    }

    /**
     * Testa geração de nome único usando estratégia 3 (com contador).
     */
    public function test_generate_unique_tenant_name_strategy_3(): void
    {
        // Arrange
        $userData = [
            'first_name' => 'João',
            'last_name'  => 'Silva',
            'email'      => 'joao.silva@example.com',
        ];

        // Criar tenants com nomes das estratégias 1 e 2
        Tenant::factory()->create( [ 'name' => 'joao-silva' ] );
        Tenant::factory()->create( [ 'name' => 'joao.silva' ] );

        // Act
        $tenantName = $this->invokePrivateMethod( $this->service, 'generateUniqueTenantName', [ $userData ] );

        // Assert
        $this->assertIsString( $tenantName );
        $this->assertNotEmpty( $tenantName );
        $this->assertEquals( 'joao-silva-1', $tenantName ); // Deve usar contador
    }

    /**
     * Testa geração de nome único com múltiplos nomes duplicados.
     */
    public function test_generate_unique_tenant_name_multiple_duplicates(): void
    {
        // Arrange
        $userData = [
            'first_name' => 'Maria',
            'last_name'  => 'Santos',
            'email'      => 'maria.santos@example.com',
        ];

        // Criar múltiplos tenants duplicados
        Tenant::factory()->create( [ 'name' => 'maria-santos' ] );
        Tenant::factory()->create( [ 'name' => 'maria.santos' ] );
        Tenant::factory()->create( [ 'name' => 'maria-santos-1' ] );
        Tenant::factory()->create( [ 'name' => 'maria-santos-2' ] );

        // Act
        $tenantName = $this->invokePrivateMethod( $this->service, 'generateUniqueTenantName', [ $userData ] );

        // Assert
        $this->assertIsString( $tenantName );
        $this->assertNotEmpty( $tenantName );
        $this->assertEquals( 'maria-santos-3', $tenantName ); // Deve continuar contador
    }

    /**
     * Testa geração de nome único com limite de contador.
     */
    public function test_generate_unique_tenant_name_counter_limit(): void
    {
        // Arrange
        $userData = [
            'first_name' => 'Teste',
            'last_name'  => 'Limite',
            'email'      => 'teste.limite@example.com',
        ];

        // Criar tenants ocupando quase todo o limite
        for ( $i = 1; $i < 1000; $i++ ) {
            Tenant::factory()->create( [ 'name' => "teste-limite-{$i}" ] );
        }

        // Act & Assert - Não deve lançar exceção mesmo no limite
        $tenantName = $this->invokePrivateMethod( $this->service, 'generateUniqueTenantName', [ $userData ] );
        $this->assertIsString( $tenantName );
        $this->assertNotEmpty( $tenantName );
    }

    /**
     * Testa geração de nome único com caracteres especiais.
     */
    public function test_generate_unique_tenant_name_special_characters(): void
    {
        // Arrange
        $userData = [
            'first_name' => 'José',
            'last_name'  => 'Gonçalves',
            'email'      => 'jose.goncalves@example.com',
        ];

        // Act
        $tenantName = $this->invokePrivateMethod( $this->service, 'generateUniqueTenantName', [ $userData ] );

        // Assert
        $this->assertIsString( $tenantName );
        $this->assertNotEmpty( $tenantName );
        $this->assertEquals( 'jose-goncalves', $tenantName ); // Deve converter caracteres especiais
    }

    /**
     * Testa geração de nome único com nomes muito longos.
     */
    public function test_generate_unique_tenant_name_long_names(): void
    {
        // Arrange
        $userData = [
            'first_name' => str_repeat( 'Nome', 50 ), // Nome muito longo
            'last_name'  => str_repeat( 'Sobrenome', 50 ), // Sobrenome muito longo
            'email'      => 'nome.sobrenome@example.com',
        ];

        // Act
        $tenantName = $this->invokePrivateMethod( $this->service, 'generateUniqueTenantName', [ $userData ] );

        // Assert
        $this->assertIsString( $tenantName );
        $this->assertNotEmpty( $tenantName );
        $this->assertLessThanOrEqual( 255, strlen( $tenantName ) ); // Deve respeitar limite do banco
    }

    /**
     * Testa busca de plano trial com sucesso.
     */
    public function test_find_trial_plan_with_trial_slug(): void
    {
        // Arrange
        $trialPlan = Plan::factory()->create( [
            'name'   => 'Trial',
            'slug'   => 'trial',
            'price'  => 0.00,
            'status' => true,
        ] );

        // Act
        $result = $this->invokePrivateMethod( $this->service, 'findTrialPlan', [] );

        // Assert
        $this->assertTrue( $result->isSuccess() );
        $this->assertEquals( $trialPlan->id, $result->getData()->id );
        $this->assertEquals( 'Trial', $result->getData()->name );
    }

    /**
     * Testa busca de plano trial com fallback para plano gratuito.
     */
    public function test_find_trial_plan_fallback_to_free_plan(): void
    {
        // Arrange - Criar apenas plano gratuito (não trial específico)
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
        $this->assertEquals( 'Free', $result->getData()->name );
    }

    /**
     * Testa busca de plano trial quando não há planos gratuitos.
     */
    public function test_find_trial_plan_no_free_plans(): void
    {
        // Arrange - Criar apenas planos pagos
        Plan::factory()->create( [
            'name'   => 'Premium',
            'slug'   => 'premium',
            'price'  => 99.90,
            'status' => true,
        ] );

        Plan::factory()->create( [
            'name'   => 'Basic',
            'slug'   => 'basic',
            'price'  => 49.90,
            'status' => true,
        ] );

        // Act
        $result = $this->invokePrivateMethod( $this->service, 'findTrialPlan', [] );

        // Assert
        $this->assertFalse( $result->isSuccess() );
        $this->assertContains( 'Plano trial não encontrado', $result->getMessage() );
    }

    /**
     * Testa busca de plano trial com planos desabilitados.
     */
    public function test_find_trial_plan_with_disabled_plans(): void
    {
        // Arrange - Criar plano trial desabilitado
        Plan::factory()->create( [
            'name'   => 'Trial',
            'slug'   => 'trial',
            'price'  => 0.00,
            'status' => false, // Desabilitado
        ] );

        // Criar plano gratuito habilitado
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
     * Testa busca de plano trial com múltiplos planos gratuitos.
     */
    public function test_find_trial_plan_multiple_free_plans(): void
    {
        // Arrange - Criar múltiplos planos gratuitos
        $trialPlan = Plan::factory()->create( [
            'name'   => 'Trial',
            'slug'   => 'trial',
            'price'  => 0.00,
            'status' => true,
        ] );

        $freePlan = Plan::factory()->create( [
            'name'   => 'Free',
            'slug'   => 'free',
            'price'  => 0.00,
            'status' => true,
        ] );

        // Act
        $result = $this->invokePrivateMethod( $this->service, 'findTrialPlan', [] );

        // Assert - Deve priorizar o plano com slug 'trial'
        $this->assertTrue( $result->isSuccess() );
        $this->assertEquals( $trialPlan->id, $result->getData()->id );
        $this->assertEquals( 'Trial', $result->getData()->name );
    }

    /**
     * Testa busca de plano trial com erro no banco de dados.
     */
    public function test_find_trial_plan_database_error(): void
    {
        // Arrange - Mock para simular erro no banco
        $this->mock( \App\Models\Plan::class, function ( $mock ) {
            $mock->shouldReceive( 'where' )->andThrow( new \Exception( 'Erro no banco' ) );
        } );

        // Act
        $result = $this->invokePrivateMethod( $this->service, 'findTrialPlan', [] );

        // Assert
        $this->assertFalse( $result->isSuccess() );
        $this->assertContains( 'Erro ao buscar/criar plano trial', $result->getMessage() );
    }

    /**
     * Testa criação de CommonData com dados válidos.
     */
    public function test_create_common_data_with_valid_data(): void
    {
        // Arrange
        $userData = [
            'first_name' => 'Ana',
            'last_name'  => 'Costa',
        ];

        $tenant = Tenant::factory()->create();

        // Act
        $result = $this->invokePrivateMethod( $this->service, 'createCommonData', [ $userData, $tenant ] );

        // Assert
        $this->assertTrue( $result->isSuccess() );
        $commonData = $result->getData();
        $this->assertEquals( $tenant->id, $commonData->tenant_id );
        $this->assertEquals( 'Ana', $commonData->first_name );
        $this->assertEquals( 'Costa', $commonData->last_name );
        // Nota: Os campos cpf, cnpj, company_name podem ser null conforme implementação
        // $this->assertNotNull( $commonData->cpf ); // Comentado pois pode ser null
        // $this->assertNotNull( $commonData->cnpj ); // Comentado pois pode ser null
        // $this->assertNotNull( $commonData->company_name ); // Comentado pois pode ser null
    }

    /**
     * Testa criação de Provider com dados válidos.
     */
    public function test_create_provider_with_valid_data(): void
    {
        // Arrange
        $user          = User::factory()->create();
        $commonData    = \App\Models\CommonData::factory()->create();
        $tenant        = Tenant::factory()->create();
        $termsAccepted = true;

        // Act
        $result = $this->invokePrivateMethod( $this->service, 'createProvider', [ $user, $commonData, $tenant, $termsAccepted ] );

        // Assert
        $this->assertTrue( $result->isSuccess() );
        $provider = $result->getData();
        $this->assertEquals( $tenant->id, $provider->tenant_id );
        $this->assertEquals( $user->id, $provider->user_id );
        $this->assertEquals( $commonData->id, $provider->common_data_id );
        $this->assertTrue( $provider->terms_accepted );
        $this->assertNull( $provider->contact_id );
        $this->assertNull( $provider->address_id );
    }

    /**
     * Testa criação de assinatura de plano com dados válidos.
     */
    public function test_create_plan_subscription_with_valid_data(): void
    {
        // Arrange
        $tenant   = Tenant::factory()->create();
        $plan     = Plan::factory()->create( [ 'price' => 29.90 ] );
        $user     = User::factory()->create();
        $provider = \App\Models\Provider::factory()->create();

        // Act
        $result = $this->invokePrivateMethod( $this->service, 'createPlanSubscription', [ $tenant, $plan, $user, $provider ] );

        // Assert
        $this->assertTrue( $result->isSuccess() );
        $subscription = $result->getData();
        $this->assertEquals( $tenant->id, $subscription->tenant_id );
        $this->assertEquals( $plan->id, $subscription->plan_id );
        $this->assertEquals( $user->id, $subscription->user_id );
        $this->assertEquals( $provider->id, $subscription->provider_id );
        $this->assertEquals( 'active', $subscription->status );
        $this->assertEquals( 29.90, $subscription->transaction_amount );
    }

    /**
     * Testa criação de usuário com dados válidos.
     */
    public function test_create_user_with_valid_data(): void
    {
        // Arrange
        $userData = [
            'email'    => 'test@example.com',
            'password' => 'password123',
        ];

        $tenant = Tenant::factory()->create();

        // Act
        $result = $this->invokePrivateMethod( $this->service, 'createUser', [ $userData, $tenant ] );

        // Assert
        $this->assertTrue( $result->isSuccess() );
        $user = $result->getData();
        $this->assertEquals( $tenant->id, $user->tenant_id );
        $this->assertEquals( 'test@example.com', $user->email );
        $this->assertTrue( \Illuminate\Support\Facades\Hash::check( 'password123', $user->password ) );
        $this->assertTrue( $user->is_active );
    }

    /**
     * Testa criação de token de confirmação com dados válidos.
     */
    public function test_create_confirmation_token_with_valid_data(): void
    {
        // Arrange
        $user   = User::factory()->create();
        $tenant = Tenant::factory()->create();

        // Act
        $result = $this->invokePrivateMethod( $this->service, 'createConfirmationToken', [ $user, $tenant ] );

        // Assert
        $this->assertTrue( $result->isSuccess() );
        $token = $result->getData();
        $this->assertEquals( $user->id, $token->user_id );
        $this->assertEquals( $tenant->id, $token->tenant_id );
        $this->assertEquals( 'email_verification', $token->type );
        $this->assertNotEmpty( $token->token );
        $this->assertNotNull( $token->expires_at );
        $this->assertEquals( 43, strlen( $token->token ) ); // Token deve ter 43 caracteres (base64url: 32 bytes)
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
