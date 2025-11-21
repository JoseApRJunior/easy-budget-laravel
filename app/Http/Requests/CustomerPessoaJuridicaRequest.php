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
    private ?int $excludeCustomerId = null;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Obter ID do customer se estiver em rota de atualização
        $this->excludeCustomerId = $this->route( 'customer' )?->id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $tenantId = auth()->user()->tenant_id;

        return [
            // Regras estruturais do Customer (do Model)
            'status'                 => 'sometimes|in:active,inactive,deleted',

            // Dados básicos (CommonData)
            'first_name'             => 'required|string|max:100',
            'last_name'              => 'required|string|max:100',
            'company_name'           => 'required|string|max:255',
            'cpf'                    => 'nullable', // PJ não precisa de CPF
            'birth_date'             => 'nullable', // PJ não precisa de data de nascimento
            'area_of_activity_id'    => 'required|integer|exists:areas_of_activity,id',
            'profession_id'          => 'nullable|integer|exists:professions,id',
            'description'            => 'nullable|string|max:500',
            'website'                => 'nullable|url|max:255',

            // Dados de contato (Contact) - COM VALIDAÇÃO DE UNICIDADE
            'email_personal'         => 'required|email|max:255',
            'phone_personal'         => 'required|string|regex:/^\(\d{2}\) \d{4,5}-\d{4}$/',
            'email_business'         => [
                'required',
                'email',
                'max:255',
                function ( $attribute, $value, $fail ) use ( $tenantId ) {
                    $customerRepo = app( \App\Repositories\CustomerRepository::class);
                    if ( !$customerRepo->isEmailUnique( $value, $tenantId, $this->excludeCustomerId ) ) {
                        $fail( 'Este e-mail empresarial já está em uso por outro cliente.' );
                    }
                }
            ],
            'phone_business'         => 'nullable|string|regex:/^\(\d{2}\) \d{4,5}-\d{4}$/',

            // CNPJ com validação customizada + UNICIDADE
            'cnpj'                   => [
                'required',
                'string',
                'regex:/^(?:\d{14}|\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2})$/', // Permite CNPJ com ou sem máscara
                function ( $attribute, $value, $fail ) use ( $tenantId ) {
                    // Limpar CNPJ (apenas números)
                    $cleanCnpj = preg_replace( '/[^0-9]/', '', $value );

                    // Validar estrutura (apenas números)
                    if ( strlen( $cleanCnpj ) !== 14 ) {
                        $digitsFound = strlen( $cleanCnpj );
                        $fail( "O CNPJ deve conter exatamente 14 dígitos. Formato aceito: 00.000.000/0000-00 ou 14 dígitos. Digitados: {$digitsFound} dígitos." );
                        return;
                    }

                    // Validar algoritmo
                    if ( !\App\Helpers\ValidationHelper::isValidCnpj( $cleanCnpj ) ) {
                        $fail( 'O CNPJ informado não é válido matematicamente. Por favor, insira um CNPJ real (14 dígitos) ou use um CNPJ de teste válido.' );
                        return;
                    }

                    // Validar unicidade
                    $customerRepo = app( \App\Repositories\CustomerRepository::class);
                    if ( !$customerRepo->isCnpjUnique( $cleanCnpj, $tenantId, $this->excludeCustomerId ) ) {
                        $fail( 'Este CNPJ já está em uso por outro cliente.' );
                    }
                }
            ],

            // Dados específicos PJ (BusinessData)
            'fantasy_name'           => 'nullable|string|max:255',
            'state_registration'     => 'nullable|string|max:50',
            'municipal_registration' => 'nullable|string|max:50',
            'founding_date'          => 'nullable|string',
            'industry'               => 'nullable|string|max:255',
            'company_size'           => 'nullable|in:micro,pequena,media,grande',
            'notes'                  => 'nullable|string|max:1000',

            // Endereço (Address)
            'cep'                    => 'required|string|regex:/^\d{5}-?\d{3}$/',
            'address'                => 'required|string|max:255',
            'address_number'         => 'nullable|string|max:20',
            'neighborhood'           => 'required|string|max:100',
            'city'                   => 'required|string|max:100',
            'state'                  => 'required|string|size:2|alpha',
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
            'first_name.required'          => 'O nome do responsável é obrigatório.',
            'last_name.required'           => 'O sobrenome do responsável é obrigatório.',
            'company_name.required'        => 'A razão social é obrigatória.',
            'cnpj.required'                => 'O CNPJ é obrigatório para pessoa jurídica.',
            'cnpj.regex'                   => 'O CNPJ deve conter 14 dígitos numéricos.',
            'cnpj.unique'                  => 'Este CNPJ já está em uso por outro cliente.',
            'email_personal.required'      => 'O e-mail é obrigatório.',
            'email_personal.email'         => 'Digite um e-mail válido.',
            'email_personal.unique'        => 'Este e-mail já está em uso por outro cliente.',
            'email_business.required'      => 'O e-mail empresarial é obrigatório.',
            'email_business.email'         => 'Digite um e-mail empresarial válido.',
            'email_business.unique'        => 'Este e-mail empresarial já está em uso por outro cliente.',
            'phone_personal.required'      => 'O telefone é obrigatório.',
            'phone_personal.regex'         => 'Digite um telefone válido no formato (00) 00000-0000.',
            'phone_business.regex'         => 'Digite um telefone comercial válido no formato (00) 00000-0000.',
            'area_of_activity_id.required' => 'A área de atuação é obrigatória para pessoa jurídica.',
            'area_of_activity_id.exists'   => 'A área de atuação selecionada não existe.',
            'founding_date.before'         => 'A data de fundação deve ser anterior a hoje.',
            'founding_date.after'          => 'A data de fundação deve ser posterior a 1800.',
            'company_size.in'              => 'O porte da empresa deve ser: micro, pequena, média ou grande.',
            'notes.max'                    => 'As observações devem ter no máximo 1000 caracteres.',
            'website.url'                  => 'Digite uma URL válida.',
            'cep.required'                 => 'O CEP é obrigatório.',
            'cep.regex'                    => 'Digite um CEP válido no formato 00000-000.',
            'address.required'             => 'O endereço é obrigatório.',
            'neighborhood.required'        => 'O bairro é obrigatório.',
            'city.required'                => 'A cidade é obrigatória.',
            'state.required'               => 'O estado é obrigatório.',
            'state.size'                   => 'O estado deve ter 2 caracteres.',
            'state.alpha'                  => 'O estado deve conter apenas letras.',
        ];
    }

}
