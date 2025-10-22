<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\RegisterUserRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Testes unitários para RegisterUserRequest.
 *
 * Esta classe testa todas as regras de validação implementadas no
 * RegisterUserRequest, incluindo casos válidos e inválidos.
 */
class RegisterUserRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Dados válidos para teste de registro.
     */
    private array $validData = [
        'first_name'            => 'João',
        'last_name'             => 'Silva',
        'email'                 => 'joao.silva@example.com',
        'phone'                 => '(11) 99999-9999',
        'password'              => 'SenhaForte123@',
        'password_confirmation' => 'SenhaForte123@',
        'terms_accepted'        => '1',
    ];

    /**
     * Testa validação com dados completamente válidos.
     */
    public function test_valid_data_passes_validation(): void
    {
        $request = new RegisterUserRequest();
        $request->merge( $this->validData );

        $validator = Validator::make( $this->validData, $request->rules(), $request->messages(), $request->attributes() );
        $this->assertTrue( $validator->passes() );
    }

    /**
     * Testa validação com first_name inválido.
     */
    public function test_first_name_validation(): void
    {
        $testCases = [
            // Campo obrigatório
            [
                'data'            => array_merge( $this->validData, [ 'first_name' => '' ] ),
                'expected_errors' => [ 'first_name' => [ 'O nome é obrigatório.' ] ]
            ],
            // Muito curto
            [
                'data'            => array_merge( $this->validData, [ 'first_name' => 'A' ] ),
                'expected_errors' => [ 'first_name' => [ 'O nome deve ter pelo menos 2 caracteres.' ] ]
            ],
            // Muito longo
            [
                'data'            => array_merge( $this->validData, [ 'first_name' => str_repeat( 'A', 101 ) ] ),
                'expected_errors' => [ 'first_name' => [ 'O nome não pode ter mais de 100 caracteres.' ] ]
            ],
            // Caracteres inválidos
            [
                'data'            => array_merge( $this->validData, [ 'first_name' => 'João123!@#' ] ),
                'expected_errors' => [ 'first_name' => [ 'O nome deve conter apenas letras, números, espaços, pontos e hífens.' ] ]
            ],
        ];

        foreach ( $testCases as $case ) {
            $request   = new RegisterUserRequest();
            $validator = Validator::make( $case[ 'data' ], $request->rules(), $request->messages(), $request->attributes() );

            $this->assertFalse( $validator->passes() );
            $this->assertEquals( $case[ 'expected_errors' ], $validator->errors()->toArray() );
        }
    }

    /**
     * Testa validação com last_name inválido.
     */
    public function test_last_name_validation(): void
    {
        $testCases = [
            // Campo obrigatório
            [
                'data'            => array_merge( $this->validData, [ 'last_name' => '' ] ),
                'expected_errors' => [ 'last_name' => [ 'O sobrenome é obrigatório.' ] ]
            ],
            // Muito curto
            [
                'data'            => array_merge( $this->validData, [ 'last_name' => 'A' ] ),
                'expected_errors' => [ 'last_name' => [ 'O sobrenome deve ter pelo menos 2 caracteres.' ] ]
            ],
            // Muito longo
            [
                'data'            => array_merge( $this->validData, [ 'last_name' => str_repeat( 'A', 101 ) ] ),
                'expected_errors' => [ 'last_name' => [ 'O sobrenome não pode ter mais de 100 caracteres.' ] ]
            ],
            // Caracteres inválidos
            [
                'data'            => array_merge( $this->validData, [ 'last_name' => 'Silva123!@#' ] ),
                'expected_errors' => [ 'last_name' => [ 'O sobrenome deve conter apenas letras, números, espaços, pontos e hífens.' ] ]
            ],
        ];

        foreach ( $testCases as $case ) {
            $request   = new RegisterUserRequest();
            $validator = Validator::make( $case[ 'data' ], $request->rules(), $request->messages(), $request->attributes() );

            $this->assertFalse( $validator->passes() );
            $this->assertEquals( $case[ 'expected_errors' ], $validator->errors()->toArray() );
        }
    }

    /**
     * Testa validação com email inválido.
     */
    public function test_email_validation(): void
    {
        $testCases = [
            // Campo obrigatório
            [
                'data'            => array_merge( $this->validData, [ 'email' => '' ] ),
                'expected_errors' => [ 'email' => [ 'O e-mail é obrigatório.' ] ]
            ],
            // Email inválido
            [
                'data'            => array_merge( $this->validData, [ 'email' => 'email-invalido' ] ),
                'expected_errors' => [ 'email' => [ 'Digite um e-mail válido.' ] ]
            ],
            // Email com maiúsculas (deve converter para minúsculas)
            [
                'data'            => array_merge( $this->validData, [ 'email' => 'JOAO.SILVA@EXAMPLE.COM' ] ),
                'expected_errors' => [] // Deve passar, pois será convertido para minúsculas
            ],
            // Muito longo
            [
                'data'            => array_merge( $this->validData, [ 'email' => str_repeat( 'a', 250 ) . '@example.com' ] ),
                'expected_errors' => [ 'email' => [ 'O e-mail não pode ter mais de 255 caracteres.' ] ]
            ],
        ];

        foreach ( $testCases as $case ) {
            $request   = new RegisterUserRequest();
            $validator = Validator::make( $case[ 'data' ], $request->rules(), $request->messages(), $request->attributes() );

            if ( empty( $case[ 'expected_errors' ] ) ) {
                $this->assertTrue( $validator->passes() );
            } else {
                $this->assertFalse( $validator->passes() );
                $this->assertEquals( $case[ 'expected_errors' ], $validator->errors()->toArray() );
            }
        }
    }

    /**
     * Testa validação com telefone inválido.
     */
    public function test_phone_validation(): void
    {
        $testCases = [
            // Campo obrigatório
            [
                'data'            => array_merge( $this->validData, [ 'phone' => '' ] ),
                'expected_errors' => [ 'phone' => [ 'O telefone é obrigatório.' ] ]
            ],
            // Formato incorreto
            [
                'data'            => array_merge( $this->validData, [ 'phone' => '11999999999' ] ),
                'expected_errors' => [ 'phone' => [ 'Digite o telefone no formato (11) 99999-9999.' ] ]
            ],
            // Muito longo
            [
                'data'            => array_merge( $this->validData, [ 'phone' => str_repeat( '1', 25 ) ] ),
                'expected_errors' => [ 'phone' => [ 'O telefone não pode ter mais de 20 caracteres.' ] ]
            ],
        ];

        foreach ( $testCases as $case ) {
            $request   = new RegisterUserRequest();
            $validator = Validator::make( $case[ 'data' ], $request->rules(), $request->messages(), $request->attributes() );

            $this->assertFalse( $validator->passes() );
            $this->assertEquals( $case[ 'expected_errors' ], $validator->errors()->toArray() );
        }
    }

    /**
     * Testa validação com senha inválida.
     */
    public function test_password_validation(): void
    {
        $testCases = [
            // Campo obrigatório
            [
                'data'            => array_merge( $this->validData, [ 'password' => '' ] ),
                'expected_errors' => [ 'password' => [ 'A senha é obrigatória.' ] ]
            ],
            // Muito curta
            [
                'data'            => array_merge( $this->validData, [ 'password' => '1234567' ] ),
                'expected_errors' => [ 'password' => [ 'A senha deve ter pelo menos 8 caracteres.' ] ]
            ],
            // Muito longa
            [
                'data'            => array_merge( $this->validData, [ 'password' => str_repeat( 'A', 101 ) ] ),
                'expected_errors' => [ 'password' => [ 'A senha não pode ter mais de 100 caracteres.' ] ]
            ],
            // Sem letra minúscula
            [
                'data'            => array_merge( $this->validData, [ 'password' => 'SENHAFORTE123@' ] ),
                'expected_errors' => [ 'password' => [ 'A senha deve conter pelo menos uma letra minúscula, uma maiúscula, um número e um símbolo.' ] ]
            ],
            // Sem letra maiúscula
            [
                'data'            => array_merge( $this->validData, [ 'password' => 'senhaforte123@' ] ),
                'expected_errors' => [ 'password' => [ 'A senha deve conter pelo menos uma letra minúscula, uma maiúscula, um número e um símbolo.' ] ]
            ],
            // Sem número
            [
                'data'            => array_merge( $this->validData, [ 'password' => 'SenhaForte@' ] ),
                'expected_errors' => [ 'password' => [ 'A senha deve conter pelo menos uma letra minúscula, uma maiúscula, um número e um símbolo.' ] ]
            ],
            // Sem símbolo
            [
                'data'            => array_merge( $this->validData, [ 'password' => 'SenhaForte123' ] ),
                'expected_errors' => [ 'password' => [ 'A senha deve conter pelo menos uma letra minúscula, uma maiúscula, um número e um símbolo.' ] ]
            ],
            // Confirmação não coincide
            [
                'data'            => array_merge( $this->validData, [ 'password_confirmation' => 'OutraSenha123@' ] ),
                'expected_errors' => [ 'password' => [ 'A confirmação da senha não coincide.' ] ]
            ],
        ];

        foreach ( $testCases as $case ) {
            $request   = new RegisterUserRequest();
            $validator = Validator::make( $case[ 'data' ], $request->rules(), $request->messages(), $request->attributes() );

            $this->assertFalse( $validator->passes() );
            $this->assertEquals( $case[ 'expected_errors' ], $validator->errors()->toArray() );
        }
    }

    /**
     * Testa validação com termos não aceitos.
     */
    public function test_terms_accepted_validation(): void
    {
        $testCases = [
            // Campo obrigatório
            [
                'data'            => array_merge( $this->validData, [ 'terms_accepted' => '' ] ),
                'expected_errors' => [ 'terms_accepted' => [ 'Você deve aceitar os termos de serviço.' ] ]
            ],
            // Não aceito
            [
                'data'            => array_merge( $this->validData, [ 'terms_accepted' => '0' ] ),
                'expected_errors' => [ 'terms_accepted' => [ 'Você deve aceitar os termos de serviço.' ] ]
            ],
        ];

        foreach ( $testCases as $case ) {
            $request   = new RegisterUserRequest();
            $validator = Validator::make( $case[ 'data' ], $request->rules(), $request->messages(), $request->attributes() );

            $this->assertFalse( $validator->passes() );
            $this->assertEquals( $case[ 'expected_errors' ], $validator->errors()->toArray() );
        }
    }

    /**
     * Testa formatação automática de telefone.
     */
    public function test_phone_formatting(): void
    {
        $request = new RegisterUserRequest();
        $request->merge( [ 'phone' => '11999999999' ] );

        // Simula o processo de preparação para validação
        $request->setContainer( $this->app );
        $request->setRedirector( $this->app->make( 'redirect' ) );
        $request->prepareForValidation();

        $this->assertEquals( '(11) 99999-9999', $request->phone );
    }

    /**
     * Testa método getValidatedData().
     */
    public function test_get_validated_data(): void
    {
        $request = new RegisterUserRequest();
        $request->merge( $this->validData );

        // Executar validação primeiro
        $request->setContainer( $this->app );
        $request->setRedirector( $this->app->make( 'redirect' ) );
        $request->validateResolved();

        $validatedData = $request->getValidatedData();

        $expectedKeys = [ 'first_name', 'last_name', 'email', 'phone', 'password', 'terms_accepted' ];
        foreach ( $expectedKeys as $key ) {
            $this->assertArrayHasKey( $key, $validatedData );
            $this->assertEquals( $this->validData[ $key ], $validatedData[ $key ] );
        }

        // Não deve incluir password_confirmation
        $this->assertArrayNotHasKey( 'password_confirmation', $validatedData );
    }

    /**
     * Testa autorização do request.
     */
    public function test_authorization(): void
    {
        $request = new RegisterUserRequest();

        $this->assertTrue( $request->authorize() );
    }

    /**
     * Testa mensagens de erro customizadas.
     */
    public function test_custom_error_messages(): void
    {
        $request  = new RegisterUserRequest();
        $messages = $request->messages();

        $this->assertArrayHasKey( 'first_name.required', $messages );
        $this->assertArrayHasKey( 'email.unique', $messages );
        $this->assertArrayHasKey( 'password.regex', $messages );
        $this->assertArrayHasKey( 'terms_accepted.accepted', $messages );

        $this->assertEquals( 'O nome é obrigatório.', $messages[ 'first_name.required' ] );
        $this->assertEquals( 'Este e-mail já está cadastrado em nosso sistema.', $messages[ 'email.unique' ] );
    }

    /**
     * Testa nomes de atributos customizados.
     */
    public function test_custom_attributes(): void
    {
        $request    = new RegisterUserRequest();
        $attributes = $request->attributes();

        $this->assertEquals( 'nome', $attributes[ 'first_name' ] );
        $this->assertEquals( 'sobrenome', $attributes[ 'last_name' ] );
        $this->assertEquals( 'e-mail', $attributes[ 'email' ] );
        $this->assertEquals( 'telefone', $attributes[ 'phone' ] );
        $this->assertEquals( 'senha', $attributes[ 'password' ] );
        $this->assertEquals( 'termos de serviço', $attributes[ 'terms_accepted' ] );
    }

}
