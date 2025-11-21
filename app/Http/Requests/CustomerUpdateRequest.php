<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

/**
 * Form Request para validação de atualização de clientes (Pessoa Física e Jurídica)
 *
 * Implementa validação unificada para clientes, diferenciando pessoa física e jurídica
 * através do campo 'person_type'.
 */
class CustomerUpdateRequest extends FormRequest
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

        // Limpar dados de acordo com o tipo de pessoa
        $this->cleanDataByPersonType();
    }

    /**
     * Limpar dados de acordo com o tipo de pessoa (PF ou PJ).
     */
    private function cleanDataByPersonType(): void
    {
        $personType = $this->input( 'person_type' );

        if ( $personType === 'pf' ) {
            // Para pessoa física, remover campos de pessoa jurídica
            $this->removeInput( [
                'company_name',
                'cnpj',
                'fantasy_name',
                'founding_date',
                'state_registration',
                'municipal_registration',
                'industry',
                'company_size'
            ] );
        } elseif ( $personType === 'pj' ) {
            // Para pessoa jurídica, remover campos específicos de pessoa física
            $this->removeInput( [ 'cpf', 'birth_date', 'profession_id' ] );
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $tenantId = auth()->user()->tenant_id;

        return [
            // Identificação do tipo de pessoa
            'person_type'            => 'required|in:pf,pj',

            // Regras estruturais do Customer (do Model)
            'status'                 => 'sometimes|in:active,inactive,deleted',

            // Dados básicos (CommonData) - sempre obrigatórios
            'first_name'             => 'required|string|max:100',
            'last_name'              => 'required|string|max:100',

            // Dados específicos por tipo de pessoa
            // Pessoa Física
            'cpf'                    => 'sometimes|required_if:person_type,pf|string|max:14',
            'birth_date'             => 'sometimes|required_if:person_type,pf|date|before:today|after:1900-01-01',
            'profession_id'          => 'sometimes|required_if:person_type,pf|exists:professions,id',

            // Pessoa Jurídica
            'company_name'           => 'sometimes|required_if:person_type,pj|string|max:255',
            'cnpj'                   => 'sometimes|required_if:person_type,pj|string|max:18',
            'fantasy_name'           => 'nullable|string|max:255',
            'founding_date'          => 'nullable|date|before:today',
            'state_registration'     => 'nullable|string|max:50',
            'municipal_registration' => 'nullable|string|max:50',
            'industry'               => 'nullable|string|max:100',
            'company_size'           => 'nullable|in:micro,small,medium,large,enterprise',

            // Dados comuns
            'area_of_activity_id'    => 'sometimes|exists:areas_of_activity,id',
            'description'            => 'nullable|string|max:500',

            // Contatos - pessoais obrigatórios, empresariais opcionais
            'email_personal'         => 'required|email|max:255',
            'phone_personal'         => 'required|string|regex:/^\(\d{2}\) \d{4,5}-\d{4}$/',
            'email_business'         => 'nullable|email|max:255',
            'phone_business'         => 'nullable|string|regex:/^\(\d{2}\) \d{4,5}-\d{4}$/',
            'website'                => 'nullable|url|max:255',

            // Endereço - todos obrigatórios
            'cep'                    => 'required|digits:8',
            'address'                => 'required|string|max:255',
            'address_number'         => 'nullable|string|max:20',
            'neighborhood'           => 'required|string|max:100',
            'city'                   => 'required|string|max:100',
            'state'                  => 'required|string|size:2',

            // Campos extras (opcionais)
            'notes'                  => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            // Mensagens para tipo de pessoa
            'person_type.required'       => 'Selecione o tipo de pessoa.',
            'person_type.in'             => 'Tipo de pessoa deve ser PF (Pessoa Física) ou PJ (Pessoa Jurídica).',

            // Mensagens para campos obrigatórios
            'first_name.required'        => 'O nome é obrigatório.',
            'last_name.required'         => 'O sobrenome é obrigatório.',
            'email_personal.required'    => 'O e-mail pessoal é obrigatório.',
            'email_personal.email'       => 'Digite um e-mail válido.',
            'phone_personal.required'    => 'O telefone é obrigatório.',
            'phone_personal.regex'       => 'Digite um telefone válido no formato (00) 00000-0000.',
            'phone_business.regex'       => 'Digite um telefone comercial válido no formato (00) 00000-0000.',
            'cep.required'               => 'O CEP é obrigatório.',
            'address.required'           => 'O endereço é obrigatório.',
            'neighborhood.required'      => 'O bairro é obrigatório.',
            'city.required'              => 'A cidade é obrigatória.',
            'state.required'             => 'O estado é obrigatório.',

            // Mensagens para CPF/CNPJ
            'cpf.required_if'            => 'O CPF é obrigatório para pessoa física.',
            'cpf.cpf'                    => 'CPF inválido.',
            'cnpj.required_if'           => 'O CNPJ é obrigatório para pessoa jurídica.',
            'cnpj.cnpj'                  => 'CNPJ inválido.',

            // Mensagens para data de nascimento
            'birth_date.required_if'     => 'A data de nascimento é obrigatória para pessoa física.',
            'birth_date.date'            => 'Digite uma data de nascimento válida.',
            'birth_date.before'          => 'A data de nascimento deve ser anterior a hoje.',
            'birth_date.after'           => 'Data de nascimento deve ser posterior a 1900.',

            // Mensagens para empresa
            'company_name.required_if'   => 'A razão social é obrigatória para pessoa jurídica.',
            'founding_date.date'         => 'Digite uma data de fundação válida.',
            'founding_date.before'       => 'A data de fundação deve ser anterior a hoje.',
            'company_size.in'            => 'Tamanho da empresa deve ser: micro, small, medium, large ou enterprise.',

            // Mensagens para dados comuns
            'area_of_activity_id.exists' => 'Área de atividade inválida.',
            'profession_id.required_if'  => 'A profissão é obrigatória para pessoa física.',
            'profession_id.exists'       => 'Profissão inválida.',

            // Mensagens para CEP
            'cep.digits'                 => 'O CEP deve ter exatamente 8 dígitos.',

            // Mensagens para estado
            'state.size'                 => 'O estado deve ter exatamente 2 caracteres (ex: SP, RJ).',

            // Mensagens para website
            'website.url'                => 'Digite um website válido (inclua http:// ou https://).',

            // Mensagens para campos específicos de pessoa jurídica
            'state_registration.max'     => 'Inscrição estadual deve ter no máximo 50 caracteres.',
            'municipal_registration.max' => 'Inscrição municipal deve ter no máximo 50 caracteres.',
            'industry.max'               => 'Ramo de atividade deve ter no máximo 100 caracteres.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'first_name'             => 'nome',
            'last_name'              => 'sobrenome',
            'email_personal'         => 'e-mail pessoal',
            'phone_personal'         => 'telefone pessoal',
            'email_business'         => 'e-mail comercial',
            'phone_business'         => 'telefone comercial',
            'company_name'           => 'razão social',
            'cnpj'                   => 'CNPJ',
            'cpf'                    => 'CPF',
            'birth_date'             => 'data de nascimento',
            'founding_date'          => 'data de fundação',
            'area_of_activity_id'    => 'área de atividade',
            'profession_id'          => 'profissão',
            'company_size'           => 'tamanho da empresa',
            'state_registration'     => 'inscrição estadual',
            'municipal_registration' => 'inscrição municipal',
            'industry'               => 'ramo de atividade',
            'address'                => 'endereço',
            'address_number'         => 'número',
            'neighborhood'           => 'bairro',
            'city'                   => 'cidade',
            'state'                  => 'estado',
            'cep'                    => 'CEP',
            'website'                => 'website',
            'description'            => 'descrição',
            'notes'                  => 'observações',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation( \Illuminate\Contracts\Validation\Validator $validator )
    {
        Log::warning( 'Customer validation failed', [
            'user_id'      => auth()->id(),
            'tenant_id'    => auth()->user()->tenant_id,
            'errors'       => $validator->errors(),
            'request_data' => $this->except( [ 'password', 'password_confirmation' ] ),
        ] );

        parent::failedValidation( $validator );
    }

}
