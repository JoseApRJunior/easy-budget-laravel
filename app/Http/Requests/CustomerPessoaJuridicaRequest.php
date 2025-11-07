<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para validação de cadastro de Pessoa Jurídica
 *
 * Implementa validação avançada para clientes pessoa jurídica com
 * suporte a dados empresariais e múltiplos contatos.
 */
class CustomerPessoaJuridicaRequest extends FormRequest
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
            // Regras estruturais do Customer (do Model)
            'tenant_id'              => 'sometimes|integer|exists:tenants,id',
            'status'                 => 'sometimes|string|in:active,inactive,deleted',

            // Dados básicos (CommonData)
            'first_name'             => 'required|string|max:100|regex:/^[a-zA-ZÀ-ÿ\s]+$/',
            'last_name'              => 'required|string|max:100|regex:/^[a-zA-ZÀ-ÿ\s]+$/',
            'company_name'           => 'required|string|max:255',
            'cpf'                    => 'nullable', // PJ não precisa de CPF
            'birth_date'             => 'nullable', // PJ não precisa de data de nascimento
            'area_of_activity_id'    => 'nullable|integer|exists:areas_of_activity,id',
            'profession_id'          => 'nullable|integer|exists:professions,id',
            'description'            => 'nullable|string|max:250',
            'website'                => 'nullable|url|max:255',

            // Dados de contato (Contact) - Sem validação de unicidade (compartilhado com Provider)
            'email_personal'         => 'required|email|max:255',
            'phone_personal'         => 'required|string|regex:/^\(\d{2}\)\s\d{4,5}-\d{4}$/',
            'email_business'         => 'nullable|email|max:255',
            'phone_business'         => 'nullable|string|regex:/^\(\d{2}\)\s\d{4,5}-\d{4}$/',

            // Dados específicos PJ
            'fantasy_name'           => 'nullable|string|max:255',
            'state_registration'     => 'nullable|string|max:50',
            'municipal_registration' => 'nullable|string|max:50',
            'founding_date'          => 'nullable|date_format:d/m/Y|before:today|after:1800-01-01',
            'company_email'          => 'nullable|email|max:255',
            'company_phone'          => 'nullable|string|regex:/^\(?\d{2}\)?\s?\d{4,5}-?\d{4}$/',
            'company_website'        => 'nullable|url|max:255',
            'industry'               => 'nullable|string|max:255',
            'company_size'           => 'nullable|string|in:micro,pequena,media,grande',
            'notes'                  => 'nullable|string|max:1000',

            // CNPJ com validação customizada via Helper
            'cnpj'                   => [
                'required',
                'string',
                'regex:/^\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}$/',
                function ( $attribute, $value, $fail ) {
                    $cleanCnpj = preg_replace( '/[^0-9]/', '', $value );
                    if ( strlen( $cleanCnpj ) !== 14 || !\App\Helpers\ValidationHelper::isValidCnpj( $cleanCnpj ) ) {
                        $fail( 'CNPJ inválido.' );
                    }
                },
            ],

            // Endereço (Address)
            'cep'                    => 'required|string|size:9|regex:/^\d{5}-?\d{3}$/',
            'address'                => 'required|string|max:255',
            'address_number'         => 'nullable|string|max:20',
            'neighborhood'           => 'required|string|max:100',
            'city'                   => 'required|string|max:100',
            'state'                  => 'required|string|size:2|alpha|not_in:Selecione',

            // Tags
            'tags'                   => 'nullable|array',
            'tags.*'                 => 'integer|exists:customer_tags,id',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'company_name'             => 'razão social',
            'fantasy_name'             => 'nome fantasia',
            'cnpj'                     => 'CNPJ',
            'state_registration'       => 'inscrição estadual',
            'municipal_registration'   => 'inscrição municipal',
            'founding_date'            => 'data de fundação',
            'company_email'            => 'email empresarial',
            'company_phone'            => 'telefone empresarial',
            'company_website'          => 'website',
            'industry'                 => 'setor de atuação',
            'company_size'             => 'porte da empresa',
            'contact_person_name'      => 'nome do contato',
            'contact_person_role'      => 'cargo do contato',
            'contact_person_email'     => 'email do contato',
            'contact_person_phone'     => 'telefone do contato',
            'customer_type'            => 'tipo de cliente',
            'priority_level'           => 'nível de prioridade',
            'status'                   => 'status',
            'area_of_activity_id'      => 'área de atuação',
            'addresses'                => 'endereços',
            'addresses.*.type'         => 'tipo de endereço',
            'addresses.*.cep'          => 'CEP',
            'addresses.*.street'       => 'logradouro',
            'addresses.*.number'       => 'número',
            'addresses.*.complement'   => 'complemento',
            'addresses.*.neighborhood' => 'bairro',
            'addresses.*.city'         => 'cidade',
            'addresses.*.state'        => 'estado',
            'contacts'                 => 'contatos',
            'contacts.*.type'          => 'tipo de contato',
            'contacts.*.label'         => 'rótulo do contato',
            'contacts.*.value'         => 'valor do contato',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'company_name.required'        => 'A razão social é obrigatória.',
            'company_name.regex'           => 'A razão social deve conter apenas letras, números e símbolos comuns.',
            'fantasy_name.regex'           => 'O nome fantasia deve conter apenas letras, números e símbolos comuns.',
            'document.required'            => 'O CNPJ é obrigatório.',
            'document.size'                => 'O CNPJ deve ter 14 dígitos.',
            'document.regex'               => 'Digite um CNPJ válido (apenas números).',
            'document.unique'              => 'Este CNPJ já está cadastrado.',
            'company_email.required'       => 'O email empresarial é obrigatório.',
            'company_email.email'          => 'Digite um email empresarial válido.',
            'company_email.unique'         => 'Este email empresarial já está cadastrado.',
            'company_phone.required'       => 'O telefone empresarial é obrigatório.',
            'company_phone.regex'          => 'Digite um telefone empresarial válido no formato (00) 00000-0000.',
            'company_website.url'          => 'Digite uma URL válida para o website.',
            'founding_date.before'         => 'A data de fundação deve ser anterior a hoje.',
            'founding_date.after'          => 'A data de fundação deve ser posterior a 1800.',
            'state_registration.regex'     => 'A inscrição estadual deve conter apenas números.',
            'municipal_registration.regex' => 'A inscrição municipal deve conter apenas números.',
            'addresses.required'           => 'Pelo menos um endereço deve ser informado.',
            'addresses.min'                => 'Pelo menos um endereço deve ser informado.',
            'addresses.*.cep.size'         => 'O CEP deve ter 9 caracteres (formato: 00000-000).',
            'addresses.*.cep.regex'        => 'Digite um CEP válido.',
            'addresses.*.state.size'       => 'O estado deve ter 2 caracteres.',
            'addresses.*.state.alpha'      => 'O estado deve conter apenas letras.',
            'state.not_in'                 => 'Por favor, selecione um estado válido.',
            'contacts.required'            => 'Pelo menos um contato deve ser informado.',
            'contacts.min'                 => 'Pelo menos um contato deve ser informado.',
            'contacts.*.value.required_if' => 'O valor do contato é obrigatório para este tipo.',
            'contacts.*.value.email'       => 'Digite um email válido.',
            'contacts.*.value.unique'      => 'Este email já está cadastrado.',
            'contacts.*.value.regex'       => 'Digite um telefone válido no formato (00) 00000-0000.',
            'contacts.*.value.url'         => 'Digite uma URL válida.',
        ];
    }

}
