<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider para validações customizadas do Easy Budget.
 *
 * Registra validadores personalizados para documentos brasileiros
 * como CPF, CNPJ e outros campos específicos do sistema.
 */
class ValidationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Registra validador de CPF usando ValidationHelper::isValidCpf()
        Validator::extend( 'cpf', function ( $attribute, $value, $parameters, $validator ) {
            return \App\Helpers\ValidationHelper::isValidCpf( $value );
        }, 'O CPF informado é inválido.' );

        // Registra validador de CNPJ usando ValidationHelper::isValidCnpj()
        Validator::extend( 'cnpj', function ( $attribute, $value, $parameters, $validator ) {
            return \App\Helpers\ValidationHelper::isValidCnpj( $value );
        }, 'O CNPJ informado é inválido.' );

        // Registra validador de telefone brasileiro
        Validator::extend( 'phone_br', function ( $attribute, $value, $parameters, $validator ) {
            return \App\Helpers\ValidationHelper::isValidPhone( $value );
        }, 'O telefone informado é inválido.' );

        // Registra validador de CEP brasileiro
        Validator::extend( 'cep_br', function ( $attribute, $value, $parameters, $validator ) {
            return \App\Helpers\ValidationHelper::isValidCep( $value );
        }, 'O CEP informado é inválido.' );
    }

}
