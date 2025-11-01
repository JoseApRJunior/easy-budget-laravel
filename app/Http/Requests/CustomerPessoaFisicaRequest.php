<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para validação de cadastro de Pessoa Física
 *
 * Implementa validação avançada para clientes pessoa física com
 * suporte a múltiplos endereços e contatos.
 */
class CustomerPessoaFisicaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Implementar lógica de autorização conforme necessidade
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Dados básicos do cliente
            'first_name'          => 'required|string|max:255|regex:/^[a-zA-ZÀ-ÿ\s]+$/',
            'last_name'           => 'required|string|max:255|regex:/^[a-zA-ZÀ-ÿ\s]+$/',
            'email_personal'      => 'required|email|max:255',
            'phone_personal'      => 'required|string|regex:/^\(\d{2}\)\s\d{4,5}-\d{4}$/',
            'document'            => 'required|string|size:11|regex:/^\d{11}$/',
            'birth_date'          => 'nullable|date|before:today|after:1900-01-01',
            'area_of_activity_id' => 'nullable|integer|exists:areas_of_activity,id',
            'profession_id'       => 'nullable|integer|exists:professions,id',
            'description'         => 'nullable|string|max:250',
            'website'             => 'nullable|url|max:255',
            'phone_business'      => 'nullable|string|regex:/^\(\d{2}\)\s\d{4,5}-\d{4}$/',
            'email_business'      => 'nullable|email|max:255',

            // Tags
            'tags'                => 'nullable|array',
            'tags.*'              => 'integer|exists:customer_tags,id',

            // Endereço (1 endereço obrigatório)
            'cep'                 => 'required|string|size:9|regex:/^\d{5}-?\d{3}$/',
            'address'             => 'required|string|max:255',
            'address_number'      => 'nullable|string|max:20',
            'neighborhood'        => 'required|string|max:100',
            'city'                => 'required|string|max:100',
            'state'               => 'required|string|size:2|alpha',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'first_name'          => 'nome',
            'last_name'           => 'sobrenome',
            'email_personal'      => 'email pessoal',
            'phone_personal'      => 'telefone pessoal',
            'document'            => 'CPF',
            'birth_date'          => 'data de nascimento',
            'area_of_activity_id' => 'área de atuação',
            'profession_id'       => 'profissão',
            'description'         => 'descrição profissional',
            'website'             => 'website',
            'phone_business'      => 'telefone comercial',
            'email_business'      => 'email comercial',
            'cep'                 => 'CEP',
            'address'             => 'endereço',
            'address_number'      => 'número',
            'neighborhood'        => 'bairro',
            'city'                => 'cidade',
            'state'               => 'estado',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'first_name.required'     => 'O nome é obrigatório.',
            'first_name.regex'        => 'O nome deve conter apenas letras e espaços.',
            'last_name.required'      => 'O sobrenome é obrigatório.',
            'last_name.regex'         => 'O sobrenome deve conter apenas letras e espaços.',
            'email.required'          => 'O email é obrigatório.',
            'email.email'             => 'Digite um email válido.',
            'email_personal.required' => 'O email pessoal é obrigatório.',
            'email_personal.email'    => 'Digite um email pessoal válido.',
            'phone_personal.required' => 'O telefone pessoal é obrigatório.',
            'phone_personal.regex'    => 'Digite um telefone pessoal válido no formato (00) 00000-0000.',
            'document.required'       => 'O CPF é obrigatório.',
            'document.size'           => 'O CPF deve ter 11 dígitos.',
            'document.regex'          => 'Digite um CPF válido (apenas números).',
            'birth_date.before'       => 'A data de nascimento deve ser anterior a hoje.',
            'birth_date.after'        => 'A data de nascimento deve ser posterior a 1900.',
            'description.max'         => 'A descrição profissional deve ter no máximo 250 caracteres.',
            'website.url'             => 'Digite uma URL válida.',
            'phone_business.regex'    => 'Digite um telefone comercial válido no formato (00) 00000-0000.',
            'email_business.email'    => 'Digite um email comercial válido.',
            'email_business.email'    => 'Digite um email comercial válido.',
            'cep.required'            => 'O CEP é obrigatório.',
            'cep.size'                => 'O CEP deve ter 9 caracteres (formato: 00000-000).',
            'cep.regex'               => 'Digite um CEP válido.',
            'address.required'        => 'O endereço é obrigatório.',
            'neighborhood.required'   => 'O bairro é obrigatório.',
            'city.required'           => 'A cidade é obrigatória.',
            'state.required'          => 'O estado é obrigatório.',
            'state.size'              => 'O estado deve ter 2 caracteres.',
            'state.alpha'             => 'O estado deve conter apenas letras.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Formatar CPF removendo caracteres especiais
        if ( $this->document ) {
            $this->merge( [
                'document' => preg_replace( '/\D/', '', $this->document ),
            ] );
        }

        // Formatar CEP removendo caracteres especiais
        if ( $this->cep ) {
            $this->merge( [
                'cep' => preg_replace( '/\D/', '', $this->cep ),
            ] );
        }

        // Formatar telefones removendo caracteres especiais
        $phoneFields = [ 'phone_personal', 'phone_business' ];
        foreach ( $phoneFields as $field ) {
            if ( $this->$field ) {
                $this->merge( [
                    $field => preg_replace( '/\D/', '', $this->$field ),
                ] );
            }
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator( $validator ): void
    {
        $validator->after( function ( $validator ) {
            // Validação adicional: CPF deve ser válido
            if ( $this->document && !$this->isValidCpf( $this->document ) ) {
                $validator->errors()->add( 'document', 'O CPF informado não é válido.' );
            }
        } );
    }

    /**
     * Validate Brazilian CPF.
     */
    private function isValidCpf( string $cpf ): bool
    {
        // Remove non-numeric characters
        $cpf = preg_replace( '/\D/', '', $cpf );

        // CPF must have 11 digits
        if ( strlen( $cpf ) !== 11 ) {
            return false;
        }

        // Check if all digits are the same
        if ( preg_match( '/^(\d)\1+$/', $cpf ) ) {
            return false;
        }

        // Calculate first check digit
        $sum = 0;
        for ( $i = 0; $i < 9; $i++ ) {
            $sum  += (int) $cpf[ $i ] * ( 10 - $i );
        }

        $remainder = $sum % 11;
        $digit1    = $remainder < 2 ? 0 : 11 - $remainder;

        if ( (int) $cpf[ 9 ] !== $digit1 ) {
            return false;
        }

        // Calculate second check digit
        $sum = 0;
        for ( $i = 0; $i < 10; $i++ ) {
            $sum  += (int) $cpf[ $i ] * ( 11 - $i );
        }

        $remainder = $sum % 11;
        $digit2    = $remainder < 2 ? 0 : 11 - $remainder;

        return (int) $cpf[ 10 ] === $digit2;
    }

}
