<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Contact;
use App\Models\Address;
use App\Models\CommonData;
use App\Services\Domain\CustomerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerServiceCreateTest extends TestCase
{
    use RefreshDatabase;

    private CustomerService $customerService;
    private User            $user;
    private Tenant          $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar tenant e usuário para teste
        $this->tenant = Tenant::factory()->create();
        $this->user   = User::factory()->create( [
            'tenant_id' => $this->tenant->id,
            'email'     => 'test@provider.com',
            'password'  => bcrypt( 'password' )
        ] );

        // Fazer login como provider
        auth()->login( $this->user );

        // Instanciar service
        $this->customerService = app( CustomerService::class);
    }

    /** @test */
    public function it_creates_persona_fisica_customer_successfully()
    {
        // Arrange
        $data = [
            'type'           => 'persona_fisica',
            'first_name'     => 'João',
            'last_name'      => 'Silva',
            'email'          => 'joao@email.com',
            'phone'          => '11987654321',
            'cep'            => '01310-100',
            'address'        => 'Av. Paulista',
            'address_number' => '100',
            'neighborhood'   => 'Bela Vista',
            'city'           => 'São Paulo',
            'state'          => 'SP',
            'birth_date'     => '1985-05-15'
        ];

        // Act
        $result = $this->customerService->create( $data );

        // Assert
        $this->assertTrue( $result->isSuccess() );
        $this->assertEquals( 'Cliente criado com sucesso', $result->getMessage() );

        $customer = $result->getData();
        $this->assertNotNull( $customer->id );
        $this->assertEquals( $this->tenant->id, $customer->tenant_id );

        // Verificar se relacionamentos foram criados
        $this->assertNotNull( $customer->commonData );
        $this->assertEquals( 'João', $customer->commonData->first_name );
        $this->assertEquals( 'Silva', $customer->commonData->last_name );
        $this->assertNotNull( $customer->contact );
        $this->assertEquals( 'joao@email.com', $customer->contact->email );
        $this->assertNotNull( $customer->address );
        $this->assertEquals( '01310-100', $customer->address->cep );
    }

    /** @test */
    public function it_creates_persona_juridica_customer_successfully()
    {
        // Arrange
        $data = [
            'type'                => 'persona_juridica',
            'company_name'        => 'Empresa Teste LTDA',
            'fantasy_name'        => 'Teste Company',
            'cnpj'                => '12.345.678/0001-90',
            'email'               => 'contato@empresateste.com.br',
            'phone'               => '1132345678',
            'cep'                 => '04567-890',
            'address'             => 'Rua das Empresas',
            'address_number'      => '500',
            'neighborhood'        => 'Centro',
            'city'                => 'São Paulo',
            'state'               => 'SP',
            'area_of_activity_id' => 1,
            'profession_id'       => 1
        ];

        // Act
        $result = $this->customerService->create( $data );

        // Assert
        $this->assertTrue( $result->isSuccess() );
        $this->assertEquals( 'Cliente criado com sucesso', $result->getMessage() );

        $customer = $result->getData();
        $this->assertNotNull( $customer->id );
        $this->assertEquals( $this->tenant->id, $customer->tenant_id );

        // Verificar se relacionamentos foram criados
        $this->assertNotNull( $customer->commonData );
        $this->assertEquals( 'Empresa Teste LTDA', $customer->commonData->company_name );
        $this->assertNotNull( $customer->contact );
        $this->assertEquals( 'contato@empresateste.com.br', $customer->contact->email );
        $this->assertNotNull( $customer->address );
        $this->assertEquals( '04567-890', $customer->address->cep );
    }

    /** @test */
    public function it_creates_customer_with_specific_tenant_id()
    {
        // Arrange
        $data = [
            'type'       => 'persona_fisica',
            'first_name' => 'Maria',
            'last_name'  => 'Santos',
            'email'      => 'maria@email.com',
            'phone'      => '11998765432'
        ];

        $otherTenant = Tenant::factory()->create();

        // Act - Criar com tenant específico
        $result = $this->customerService->createByTenantId( $data, $otherTenant->id );

        // Assert
        $this->assertTrue( $result->isSuccess() );

        $customer = $result->getData();
        $this->assertEquals( $otherTenant->id, $customer->tenant_id );
    }

    /** @test */
    public function it_handles_invalid_data_gracefully()
    {
        // Arrange - Dados inválidos
        $data = [
            'type'       => 'persona_fisica',
            'first_name' => '', // Nome vazio
            'email'      => 'email-invalido', // Email inválido
        ];

        // Act
        $result = $this->customerService->create( $data );

        // Assert
        $this->assertFalse( $result->isSuccess() );
        $this->assertNotEmpty( $result->getMessage() );
    }

    /** @test */
    public function it_validates_unique_email()
    {
        // Arrange
        $existingData = [
            'type'       => 'persona_fisica',
            'first_name' => 'Pedro',
            'last_name'  => 'Costa',
            'email'      => 'pedro@email.com',
            'phone'      => '11987654321'
        ];

        // Criar primeiro cliente
        $this->customerService->create( $existingData );

        // Tentar criar segundo cliente com mesmo email
        $duplicateData = [
            'type'       => 'persona_fisica',
            'first_name' => 'João',
            'last_name'  => 'Silva',
            'email'      => 'pedro@email.com', // Mesmo email
            'phone'      => '11987654322'
        ];

        // Act
        $result = $this->customerService->create( $duplicateData );

        // Assert
        $this->assertFalse( $result->isSuccess() );
        $this->assertStringContainsString( 'já está em uso', $result->getMessage() );
    }

    /** @test */
    public function it_creates_customer_without_optional_address_fields()
    {
        // Arrange - Dados mínimos
        $data = [
            'type'         => 'persona_fisica',
            'first_name'   => 'Ana',
            'last_name'    => 'Lima',
            'email'        => 'ana@email.com',
            'phone'        => '11987654321',
            'cep'          => '01310-100',
            'neighborhood' => 'Centro',
            'city'         => 'São Paulo',
            'state'        => 'SP'
        ];

        // Act
        $result = $this->customerService->create( $data );

        // Assert
        $this->assertTrue( $result->isSuccess() );

        $customer = $result->getData();
        $this->assertNotNull( $customer->address );
        $this->assertEquals( 'São Paulo', $customer->address->city );
    }

}
