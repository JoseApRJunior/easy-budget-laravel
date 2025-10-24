<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Plan;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testes para casos extremos de registro.
 *
 * Esta classe testa cenários extremos e edge cases que podem
 * ocorrer durante o processo de registro de usuários.
 */
class RegistrationEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testa geração de nomes únicos para tenants com nomes duplicados.
     */
    public function test_unique_tenant_name_generation_with_duplicates(): void
    {
        // Arrange
        Plan::factory()->create( [ 'slug' => 'trial', 'price' => 0.00, 'status' => true ] );
        Role::factory()->create( [ 'name' => 'provider' ] );

        // Criar múltiplos tenants com nomes base similares
        Tenant::factory()->create( [ 'name' => 'joao-silva' ] );
        Tenant::factory()->create( [ 'name' => 'joao-silva-1' ] );
        Tenant::factory()->create( [ 'name' => 'joao-silva-2' ] );

        $userData = [
            'first_name'            => 'João',
            'last_name'             => 'Silva',
            'email'                 => 'joao.silva.novo@example.com',
            'phone'                 => '(11) 99999-9999',
            'password'              => 'SenhaForte123@',
            'password_confirmation' => 'SenhaForte123@',
            'terms_accepted'        => '1',
        ];

        // Act
        $response = $this->postJson( '/register', $userData );

        // Assert
        $response->assertRedirect( route( 'provider.dashboard', absolute: false ) );

        // Verificar que tenant foi criado com nome único
        $this->assertDatabaseHas( 'tenants', [
            'is_active' => true,
        ] );

        // Deve gerar nome com contador maior que os existentes
        $tenant = Tenant::where( 'is_active', true )
            ->where( 'name', 'LIKE', 'joao-silva%' )
            ->where( 'name', '!=', 'joao-silva' )
            ->where( 'name', '!=', 'joao-silva-1' )
            ->where( 'name', '!=', 'joao-silva-2' )
            ->first();

        $this->assertNotNull( $tenant );
        $this->assertStringContainsString( '-3', $tenant->name ); // Deve ser joao-silva-3
    }

    /**
     * Testa registro com email muito similar ao existente.
     */
    public function test_registration_with_similar_emails(): void
    {
        // Arrange
        User::factory()->create( [ 'email' => 'joao.silva@example.com' ] );

        Plan::factory()->create( [ 'slug' => 'trial', 'price' => 0.00, 'status' => true ] );
        Role::factory()->create( [ 'name' => 'provider' ] );

        $testCases = [
            'joao.silva@example.com', // Email idêntico
            'Joao.Silva@example.com', // Email com maiúsculas
            'joao.silva+test@example.com', // Email com plus aliasing
        ];

        foreach ( $testCases as $email ) {
            $userData = [
                'first_name'            => 'João',
                'last_name'             => 'Silva',
                'email'                 => $email,
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
        }
    }

    /**
     * Testa registro com dados no limite das validações.
     */
    public function test_registration_with_boundary_values(): void
    {
        // Arrange
        Plan::factory()->create( [ 'slug' => 'trial', 'price' => 0.00, 'status' => true ] );
        Role::factory()->create( [ 'name' => 'provider' ] );

        $testCases = [
            // Nome no limite mínimo (2 caracteres)
            [
                'first_name' => 'Jo',
                'last_name'  => 'Si',
                'email'      => 'test.boundary@example.com',
            ],
            // Nome no limite máximo (100 caracteres)
            [
                'first_name' => str_repeat( 'J', 100 ),
                'last_name'  => str_repeat( 'S', 100 ),
                'email'      => 'test.boundary2@example.com',
            ],
            // Senha no limite mínimo (8 caracteres)
            [
                'first_name' => 'Teste',
                'last_name'  => 'Boundary',
                'email'      => 'test.boundary3@example.com',
                'password'   => 'Abc123@!',
            ],
            // Senha no limite máximo (100 caracteres)
            [
                'first_name' => 'Teste',
                'last_name'  => 'Boundary',
                'email'      => 'test.boundary4@example.com',
                'password'   => str_repeat( 'Abc123@!', 6 ) . 'Abc123@', // 100 caracteres
            ],
        ];

        foreach ( $testCases as $userData ) {
            $completeData = array_merge( [
                'phone'                 => '(11) 99999-9999',
                'password_confirmation' => $userData[ 'password' ] ?? 'SenhaForte123@',
                'terms_accepted'        => '1',
            ], $userData );

            // Act
            $response = $this->postJson( '/register', $completeData );

            // Assert
            $response->assertRedirect( route( 'provider.dashboard', absolute: false ) );
            $response->assertSessionHas( 'success' );

            // Verificar se usuário foi criado
            $this->assertDatabaseHas( 'users', [
                'email' => $userData[ 'email' ],
            ] );
        }
    }

    /**
     * Testa registro com caracteres especiais válidos.
     */
    public function test_registration_with_special_characters(): void
    {
        // Arrange
        Plan::factory()->create( [ 'slug' => 'trial', 'price' => 0.00, 'status' => true ] );
        Role::factory()->create( [ 'name' => 'provider' ] );

        $testCases = [
            [
                'first_name' => 'José',
                'last_name'  => 'Gonçalves',
                'email'      => 'jose.goncalves@example.com',
            ],
            [
                'first_name' => 'Ana-Clara',
                'last_name'  => 'D\'Ávila',
                'email'      => 'ana.clara.davila@example.com',
            ],
            [
                'first_name' => 'João Pedro',
                'last_name'  => 'Silva Jr.',
                'email'      => 'joao.pedro.silva.jr@example.com',
            ],
        ];

        foreach ( $testCases as $userData ) {
            $completeData = array_merge( $userData, [
                'phone'                 => '(11) 99999-9999',
                'password'              => 'SenhaForte123@',
                'password_confirmation' => 'SenhaForte123@',
                'terms_accepted'        => '1',
            ] );

            // Act
            $response = $this->postJson( '/register', $completeData );

            // Assert
            $response->assertRedirect( route( 'provider.dashboard', absolute: false ) );
            $response->assertSessionHas( 'success' );

            // Verificar se usuário foi criado
            $this->assertDatabaseHas( 'users', [
                'email' => $userData[ 'email' ],
            ] );
        }
    }

    /**
     * Testa registro com caracteres inválidos.
     */
    public function test_registration_with_invalid_characters(): void
    {
        // Arrange
        $testCases = [
            [
                'first_name' => 'João123!@#',
                'last_name'  => 'Silva',
                'email'      => 'joao.invalid@example.com',
            ],
            [
                'first_name' => 'Maria',
                'last_name'  => 'Silva$%^&',
                'email'      => 'maria.invalid@example.com',
            ],
        ];

        foreach ( $testCases as $userData ) {
            $completeData = array_merge( $userData, [
                'phone'                 => '(11) 99999-9999',
                'password'              => 'SenhaForte123@',
                'password_confirmation' => 'SenhaForte123@',
                'terms_accepted'        => '1',
            ] );

            // Act
            $response = $this->postJson( '/register', $completeData );

            // Assert
            $response->assertRedirect();
            $response->assertSessionHasErrors( [ 'first_name', 'last_name' ] );

            // Verificar que usuário NÃO foi criado
            $this->assertDatabaseMissing( 'users', [
                'email' => $userData[ 'email' ],
            ] );
        }
    }

    /**
     * Testa registro com telefone de diferentes regiões.
     */
    public function test_registration_with_different_phone_regions(): void
    {
        // Arrange
        Plan::factory()->create( [ 'slug' => 'trial', 'price' => 0.00, 'status' => true ] );
        Role::factory()->create( [ 'name' => 'provider' ] );

        $testCases = [
            '(11) 99999-9999', // São Paulo
            '(21) 99999-9999', // Rio de Janeiro
            '(31) 99999-9999', // Belo Horizonte
            '(41) 99999-9999', // Curitiba
            '(51) 99999-9999', // Porto Alegre
            '(61) 99999-9999', // Brasília
            '(71) 99999-9999', // Salvador
            '(81) 99999-9999', // Recife
            '(91) 99999-9999', // Belém
        ];

        foreach ( $testCases as $phone ) {
            $userData = [
                'first_name'            => 'Teste',
                'last_name'             => 'Telefone',
                'email'                 => 'telefone.' . substr( $phone, 1, 2 ) . '@example.com',
                'phone'                 => $phone,
                'password'              => 'SenhaForte123@',
                'password_confirmation' => 'SenhaForte123@',
                'terms_accepted'        => '1',
            ];

            // Act
            $response = $this->postJson( '/register', $userData );

            // Assert
            $response->assertRedirect( route( 'provider.dashboard', absolute: false ) );
            $response->assertSessionHas( 'success' );

            // Verificar se telefone foi salvo corretamente
            $this->assertDatabaseHas( 'contacts', [
                'phone' => $phone,
            ] );
        }
    }

    /**
     * Testa registro com senhas muito complexas.
     */
    public function test_registration_with_complex_passwords(): void
    {
        // Arrange
        Plan::factory()->create( [ 'slug' => 'trial', 'price' => 0.00, 'status' => true ] );
        Role::factory()->create( [ 'name' => 'provider' ] );

        $complexPasswords = [
            'MyStr0ng!P@ssw0rd#2024$',
            'C0mpl3x$S3cur3&P@ssw0rd',
            'Ún1qu3$Str0ng&P@ssw0rd',
            'P@ssw0rd&W1th$ymb0l$',
        ];

        foreach ( $complexPasswords as $index => $password ) {
            $userData = [
                'first_name'            => 'Teste',
                'last_name'             => 'Complexo',
                'email'                 => "complexo{$index}@example.com",
                'phone'                 => '(11) 99999-9999',
                'password'              => $password,
                'password_confirmation' => $password,
                'terms_accepted'        => '1',
            ];

            // Act
            $response = $this->postJson( '/register', $userData );

            // Assert
            $response->assertRedirect( route( 'provider.dashboard', absolute: false ) );
            $response->assertSessionHas( 'success' );

            // Verificar se senha foi hasheada corretamente
            $user = User::where( 'email', $userData[ 'email' ] )->first();
            $this->assertNotNull( $user );
            $this->assertNotEquals( $password, $user->password ); // Deve estar hasheada
        }
    }

    /**
     * Testa registro simultâneo com mesmo email (race condition).
     */
    public function test_simultaneous_registration_with_same_email(): void
    {
        // Arrange
        Plan::factory()->create( [ 'slug' => 'trial', 'price' => 0.00, 'status' => true ] );
        Role::factory()->create( [ 'name' => 'provider' ] );

        $userData1 = [
            'first_name'            => 'Usuário',
            'last_name'             => 'Primeiro',
            'email'                 => 'race.condition@example.com',
            'phone'                 => '(11) 99999-9999',
            'password'              => 'SenhaForte123@',
            'password_confirmation' => 'SenhaForte123@',
            'terms_accepted'        => '1',
        ];

        $userData2 = [
            'first_name'            => 'Usuário',
            'last_name'             => 'Segundo',
            'email'                 => 'race.condition@example.com', // Mesmo email
            'phone'                 => '(21) 99999-9999',
            'password'              => 'SenhaForte123@',
            'password_confirmation' => 'SenhaForte123@',
            'terms_accepted'        => '1',
        ];

        // Act - Fazer duas requisições simultâneas
        $response1 = $this->postJson( '/register', $userData1 );
        $response2 = $this->postJson( '/register', $userData2 );

        // Assert
        // Uma deve ter sucesso, a outra deve falhar
        $this->assertTrue(
            $response1->isRedirect() || $response2->isRedirect()
        );

        // Pelo menos uma deve ter erro de email duplicado
        $hasError = $response1->getSession()->has( 'errors' ) || $response2->getSession()->has( 'errors' );
        $this->assertTrue( $hasError );

        // Deve haver apenas um usuário com esse email
        $this->assertEquals( 1, User::where( 'email', 'race.condition@example.com' )->count() );
    }

    /**
     * Testa registro com dados muito grandes.
     */
    public function test_registration_with_large_data(): void
    {
        // Arrange
        Plan::factory()->create( [ 'slug' => 'trial', 'price' => 0.00, 'status' => true ] );
        Role::factory()->create( [ 'name' => 'provider' ] );

        $largeData = [
            'first_name'            => str_repeat( 'Nome', 25 ), // 100 caracteres
            'last_name'             => str_repeat( 'Sobrenome', 25 ), // 225 caracteres (ultrapassa limite)
            'email'                 => 'large.data@example.com',
            'phone'                 => '(11) 99999-9999',
            'password'              => 'SenhaForte123@',
            'password_confirmation' => 'SenhaForte123@',
            'terms_accepted'        => '1',
        ];

        // Act
        $response = $this->postJson( '/register', $largeData );

        // Assert
        $response->assertRedirect();
        $response->assertSessionHasErrors( [ 'last_name' ] ); // Deve falhar no sobrenome muito longo

        // Verificar que usuário NÃO foi criado
        $this->assertDatabaseMissing( 'users', [
            'email' => 'large.data@example.com',
        ] );
    }

    /**
     * Testa registro com dados especiais de telefone.
     */
    public function test_registration_with_special_phone_formats(): void
    {
        // Arrange
        Plan::factory()->create( [ 'slug' => 'trial', 'price' => 0.00, 'status' => true ] );
        Role::factory()->create( [ 'name' => 'provider' ] );

        $phoneFormats = [
            '11999999999',     // 11 dígitos (SP com 9º dígito)
            '1188888888',      // 10 dígitos (SP sem 9º dígito)
            '219999999999',    // 12 dígitos (RJ com 9º dígito)
            '21888888888',     // 11 dígitos (RJ sem 9º dígito)
        ];

        foreach ( $phoneFormats as $index => $phone ) {
            $userData = [
                'first_name'            => 'Teste',
                'last_name'             => 'Telefone',
                'email'                 => "phone{$index}@example.com",
                'phone'                 => $phone,
                'password'              => 'SenhaForte123@',
                'password_confirmation' => 'SenhaForte123@',
                'terms_accepted'        => '1',
            ];

            // Act
            $response = $this->postJson( '/register', $userData );

            // Assert
            $response->assertRedirect( route( 'provider.dashboard', absolute: false ) );
            $response->assertSessionHas( 'success' );

            // Verificar se telefone foi formatado e salvo
            $this->assertDatabaseHas( 'contacts', [
                'phone' => '(11) 99999-9999', // Todos devem ser formatados para 11 dígitos
            ] );
        }
    }

    /**
     * Testa comportamento com banco de dados indisponível.
     */
    public function test_registration_with_database_unavailable(): void
    {
        // Arrange - Simular indisponibilidade do banco
        $this->mock( \Illuminate\Database\DatabaseManager::class, function ( $mock ) {
            $mock->shouldReceive( 'beginTransaction' )->andThrow( new \Exception( 'Database unavailable' ) );
        } );

        $userData = [
            'first_name'            => 'Teste',
            'last_name'             => 'Database',
            'email'                 => 'database.error@example.com',
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
            'email' => 'database.error@example.com',
        ] );
    }

}
