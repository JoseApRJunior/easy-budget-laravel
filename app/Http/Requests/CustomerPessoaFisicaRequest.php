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
            // Regras estruturais do Customer (do Model)
            'tenant_id'           => 'sometimes|integer|exists:tenants,id',
            'status'              => 'sometimes|string|in:active,inactive,deleted',

            // Dados básicos (CommonData)
            'first_name'          => 'required|string|max:100|regex:/^[a-zA-ZÀ-ÿ\s]+$/',
            'last_name'           => 'required|string|max:100|regex:/^[a-zA-ZÀ-ÿ\s]+$/',
            'birth_date'          => 'nullable|date_format:d/m/Y|before:today|after:1900-01-01',
            'area_of_activity_id' => 'nullable|integer|exists:areas_of_activity,id',
            'profession_id'       => 'nullable|integer|exists:professions,id',
            'description'         => 'nullable|string|max:250',
            'website'             => 'nullable|url|max:255',

            // Dados de contato (Contact) - Sem validação de unicidade (compartilhado com Provider)
            'email_personal'      => 'required|email|max:255',
            'phone_personal'      => 'required|string|regex:/^\(\d{2}\)\s\d{4,5}-\d{4}$/',
            'email_business'      => 'nullable|email|max:255',
            'phone_business'      => 'nullable|string|regex:/^\(\d{2}\)\s\d{4,5}-\d{4}$/',

            // CPF com validação customizada via Helper
            'cpf'                 => [
                'required',
                'string',
                'regex:/^\d{3}\.\d{3}\.\d{3}-\d{2}$/',
                function ($attribute, $value, $fail) {
                    $cleanCpf = preg_replace('/[^0-9]/', '', $value);
                    if (strlen($cleanCpf) !== 11 || !\App\Helpers\ValidationHelper::isValidCpf($cleanCpf)) {
                        $fail('CPF inválido.');
                    }
                },
            ],

            // Endereço (Address)
            'cep'                 => 'required|string|regex:/^\d{5}-?\d{3}$/',
            'address'             => 'required|string|max:255',
            'address_number'      => 'nullable|string|max:20',
            'neighborhood'        => 'required|string|max:100',
            'city'                => 'required|string|max:100',
            'state'               => 'required|string|size:2|alpha',

            // Tags
            'tags'                => 'nullable|array',
            'tags.*'              => 'integer|exists:customer_tags,id',
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

}
