<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use App\Enums\CustomerStatus;
use App\Http\Requests\CustomerPessoaFisicaRequest;
use App\Http\Requests\CustomerPessoaJuridicaRequest;
use App\Http\Requests\CustomerUpdateRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerFormRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_pessoa_fisica_request_validates_cpf()
    {
        $request = new CustomerPessoaFisicaRequest();

        // Testar CPF inválido
        $validator = $this->app[ 'validator' ]->make(
            [ 'cpf' => '123.456.789-00' ],
            $request->rules(),
            $request->messages(),
        );

        $this->assertTrue( $validator->fails() );
        $this->assertArrayHasKey( 'cpf', $validator->errors()->toArray() );
    }

    public function test_customer_pessoa_fisica_request_validates_email()
    {
        $request = new CustomerPessoaFisicaRequest();

        // Testar email inválido
        $validator = $this->app[ 'validator' ]->make(
            [ 'email' => 'email-invalido' ],
            $request->rules(),
            $request->messages(),
        );

        $this->assertTrue( $validator->fails() );
        $this->assertArrayHasKey( 'email', $validator->errors()->toArray() );
    }

    public function test_customer_pessoa_fisica_request_validates_phone()
    {
        $request = new CustomerPessoaFisicaRequest();

        // Testar telefone inválido
        $validator = $this->app[ 'validator' ]->make(
            [ 'phone' => '123-456-7890' ],
            $request->rules(),
            $request->messages(),
        );

        $this->assertTrue( $validator->fails() );
        $this->assertArrayHasKey( 'phone', $validator->errors()->toArray() );
    }

    public function test_customer_pessoa_juridica_request_validates_cnpj()
    {
        $request = new CustomerPessoaJuridicaRequest();

        // Testar CNPJ inválido
        $validator = $this->app[ 'validator' ]->make(
            [ 'cnpj' => '12.345.678/0001-90' ],
            $request->rules(),
            $request->messages(),
        );

        $this->assertTrue( $validator->fails() );
        $this->assertArrayHasKey( 'cnpj', $validator->errors()->toArray() );
    }

    public function test_customer_pessoa_juridica_request_validates_required_fields()
    {
        $request = new CustomerPessoaJuridicaRequest();

        // Testar campos obrigatórios em branco
        $validator = $this->app[ 'validator' ]->make(
            [
                'first_name'          => '',
                'last_name'           => '',
                'company_name'        => '',
                'email'               => '',
                'phone'               => '',
                'email_business'      => '',
                'cnpj'                => '',
                'area_of_activity_id' => ''
            ],
            $request->rules(),
            $request->messages(),
        );

        $this->assertTrue( $validator->fails() );
    }

    public function test_customer_update_request_validates_status()
    {
        $request = new CustomerUpdateRequest();

        // Testar status inválido
        $validator = $this->app[ 'validator' ]->make(
            [ 'status' => 'invalid_status' ],
            $request->rules(),
            $request->messages(),
        );

        $this->assertTrue( $validator->fails() );
        $this->assertArrayHasKey( 'status', $validator->errors()->toArray() );
    }

    public function test_customer_update_request_validates_cep()
    {
        $request = new CustomerUpdateRequest();

        // Testar CEP inválido
        $validator = $this->app[ 'validator' ]->make(
            [ 'cep' => '12345' ],
            $request->rules(),
            $request->messages(),
        );

        $this->assertTrue( $validator->fails() );
        $this->assertArrayHasKey( 'cep', $validator->errors()->toArray() );
    }

    public function test_validation_helpers_exist()
    {
        $this->assertTrue( class_exists( \App\Helpers\ValidationHelper::class) );

        // Testar métodos estáticos do ValidationHelper
        $this->assertTrue( \App\Helpers\ValidationHelper::isValidCpf( '12345678901' ) );
        $this->assertFalse( \App\Helpers\ValidationHelper::isValidCpf( '123' ) );
        $this->assertTrue( \App\Helpers\ValidationHelper::isValidCnpj( '12345678000195' ) );
        $this->assertFalse( \App\Helpers\ValidationHelper::isValidCnpj( '123' ) );
    }

    public function test_customer_repository_has_validation_methods()
    {
        $customerRepo = $this->app->make( \App\Repositories\CustomerRepository::class);

        $this->assertTrue( method_exists( $customerRepo, 'isEmailUnique' ) );
        $this->assertTrue( method_exists( $customerRepo, 'isCpfUnique' ) );
        $this->assertTrue( method_exists( $customerRepo, 'isCnpjUnique' ) );
    }

}
