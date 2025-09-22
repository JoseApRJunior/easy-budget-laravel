<?php

namespace design_patern\design_pattern_no_tenant\request;

use core\request\AbstractFormRequest;
use Respect\Validation\Rules\AllOf;
use Respect\Validation\Rules\Key;
use Respect\Validation\Validator as v;

/**
 * Padrão de FormRequest NoTenant - Easy Budget
 *
 * PADRÕES IMPLEMENTADOS:
 * ✅ Herda de AbstractFormRequest - Estrutura padrão do projeto
 * ✅ Usa Respect\Validation - Sistema de validação do projeto
 * ✅ Método execute() - Implementação obrigatória
 * ✅ Comentários em português brasileiro - Padrão do projeto
 * ✅ Validação sem campos de tenant - Adequado para entidades globais
 *
 * BENEFÍCIOS:
 * - Centralização das regras de validação
 * - Reutilização em múltiplos controllers
 * - Mensagens de erro padronizadas
 * - Validação consistente de dados
 * - Simplicidade sem controle de tenant
 */
class DesignPatternNoTenantFormRequest extends AbstractFormRequest
{
    /**
     * Executa a validação dos dados do formulário.
     *
     * @return bool Retorna true se os dados forem válidos, false caso contrário
     */
    protected function execute(): bool
    {
        // Cria o validador principal
        $validator = v::create();

        // Validação do campo 'name' (obrigatório, entre 2 e 100 caracteres)
        $validator->addRule(
            new Key(
                'name',
                new AllOf(
                    v::notEmpty()->setTemplate( 'O nome da entidade é obrigatório.' ),
                    v::stringType()->setTemplate( 'O nome deve ser um texto válido.' ),
                    v::length( 2, 100 )->setTemplate( 'O nome deve ter entre 2 e 100 caracteres.' ),
                ),
            ),
        );

        // Validação do campo 'slug' (opcional, formato específico)
        $validator->addRule(
            new Key(
                'slug',
                v::optional(
                    new AllOf(
                        v::stringType()->setTemplate( 'O slug deve ser um texto válido.' ),
                        v::length( 2, 100 )->setTemplate( 'O slug deve ter entre 2 e 100 caracteres.' ),
                        v::regex( '/^[a-z0-9-]+$/' )->setTemplate( 'O slug deve conter apenas letras minúsculas, números e hífens.' ),
                    ),
                ),
            ),
        );

        // Validação do campo 'description' (opcional, máximo 500 caracteres)
        $validator->addRule(
            new Key(
                'description',
                v::optional(
                    new AllOf(
                        v::stringType()->setTemplate( 'A descrição deve ser um texto válido.' ),
                        v::length( null, 500 )->setTemplate( 'A descrição deve ter no máximo 500 caracteres.' ),
                    ),
                ),
            ),
        );

        // Validação do campo 'active' (opcional, boolean)
        $validator->addRule(
            new Key(
                'active',
                v::optional(
                    v::in( [ '0', '1', 0, 1, true, false ] )->setTemplate( 'O status ativo deve ser verdadeiro ou falso.' ),
                ),
            ),
        );

        // Executa a validação usando o método da classe pai
        return $this->isValidated( $validator );
    }

}

/*
EXEMPLOS DE USO:

// 1. Uso simples no Controller (método store)
public function store(): Response
{
    if ( !DesignPatternNoTenantFormRequest::validate( $this->request ) ) {
        return Redirect::redirect( '/admin/design-patterns/create' )
            ->withMessage( 'error', 'Dados inválidos. Verifique os campos e tente novamente.' );
    }

    $data = $this->request->all();
    // ... continuar com criação
}

// 2. Uso no método update
public function update( string $id ): Response
{
    if ( !DesignPatternNoTenantFormRequest::validate( $this->request ) ) {
        return Redirect::redirect( "/admin/design-patterns/{$id}/edit" )
            ->withMessage( 'error', 'Dados inválidos. Verifique os campos e tente novamente.' );
    }

    $data = $this->request->all();
    $result = $this->designPatternService->update( (int) $id, $data );
    // ... continuar
}

// 3. Validação customizada (herdando a classe)
class CustomFormRequest extends DesignPatternNoTenantFormRequest
{
    protected function execute(): bool
    {
        // Executa validação padrão
        if ( !parent::execute() ) {
            return false;
        }

        // Adicionar validações customizadas aqui
        $validator = v::create();
        $validator->addRule( new Key( 'custom_field', v::notEmpty() ) );

        return $this->isValidated( $validator );
    }
}

// 4. Formatação de dados após validação
if ( DesignPatternNoTenantFormRequest::validate( $this->request ) ) {
    $data = $this->request->all();

    // Preparar dados opcionais
    if ( empty( $data['slug'] ) ) {
        unset( $data['slug'] ); // Será gerado automaticamente
    }

    if ( empty( $data['description'] ) ) {
        unset( $data['description'] );
    }

    // Converter active para boolean
    if ( isset( $data['active'] ) ) {
        $data['active'] = in_array( $data['active'], [ '1', 1, true ], true );
    }

    // Usar dados preparados
    $result = $this->designPatternService->create( $data );
}
*/
