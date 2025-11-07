<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ProviderBusinessUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Dados pessoais
            'first_name'          => [ 'required', 'string', 'max:100' ],
            'last_name'           => [ 'required', 'string', 'max:100' ],
            'birth_date'          => [ 'nullable', 'date_format:d/m/Y', 'before:today' ],
            'email_personal'      => [ 'nullable', 'email', 'max:255' ],
            'phone_personal'      => [ 'nullable', 'string', 'max:20' ],

            // Dados empresariais
            'company_name'        => [ 'nullable', 'string', 'max:255' ],
            'cnpj'                => [ 'nullable', 'string' ],
            'cpf'                 => [ 'nullable', 'string' ],
            'area_of_activity_id' => [ 'nullable', 'integer', 'exists:areas_of_activity,id' ],
            'profession_id'       => [ 'nullable', 'integer', 'exists:professions,id' ],
            'description'         => [ 'nullable', 'string', 'max:250' ],

            // Contato empresarial
            'email_business'      => [ 'nullable', 'email', 'max:255' ],
            'phone_business'      => [ 'nullable', 'string', 'max:20' ],
            'website'             => [ 'nullable', 'url', 'max:255' ],

            // Endereço
            'address'             => [ 'nullable', 'string', 'max:255' ],
            'address_number'      => [ 'nullable', 'string', 'max:20' ],
            'neighborhood'        => [ 'nullable', 'string', 'max:100' ],
            'city'                => [ 'nullable', 'string', 'max:100' ],
            'state'               => [ 'nullable', 'string', 'max:2' ],
            'cep'                 => [ 'nullable', 'string', 'max:9', 'regex:/^\d{5}-?\d{3}$/' ],

            // Logo da empresa
            'logo'                => [ 'nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048' ],

            // Dados empresariais (PJ)
            'fantasy_name'           => [ 'nullable', 'string', 'max:255' ],
            'state_registration'     => [ 'nullable', 'string', 'max:50' ],
            'municipal_registration' => [ 'nullable', 'string', 'max:50' ],
            'founding_date'          => [ 'nullable', 'date_format:d/m/Y' ],
            'industry'               => [ 'nullable', 'string', 'max:255' ],
            'company_size'           => [ 'nullable', 'in:micro,pequena,media,grande' ],
            'notes'                  => [ 'nullable', 'string' ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required'         => 'O nome é obrigatório.',
            'first_name.string'           => 'O nome deve ser um texto.',
            'first_name.max'              => 'O nome não pode ter mais de 100 caracteres.',
            'last_name.required'          => 'O sobrenome é obrigatório.',
            'last_name.string'            => 'O sobrenome deve ser um texto.',
            'last_name.max'               => 'O sobrenome não pode ter mais de 100 caracteres.',
            'birth_date.date_format'      => 'A data de nascimento deve estar no formato DD/MM/YYYY.',
            'birth_date.before'           => 'A data de nascimento deve ser anterior a hoje.',
            'email_personal.email'        => 'O e-mail pessoal deve ter um formato válido.',
            'email_personal.max'          => 'O e-mail pessoal não pode ter mais de 255 caracteres.',
            'phone_personal.string'       => 'O telefone pessoal deve ser um texto.',
            'phone_personal.max'          => 'O telefone pessoal não pode ter mais de 20 caracteres.',
            'company_name.string'         => 'O nome da empresa deve ser um texto.',
            'company_name.max'            => 'O nome da empresa não pode ter mais de 255 caracteres.',
            'cnpj.string'                 => 'O CNPJ deve ser um texto.',
            'cpf.string'                  => 'O CPF deve ser um texto.',
            'area_of_activity_id.integer' => 'A área de atuação deve ser um número inteiro.',
            'area_of_activity_id.exists'  => 'A área de atuação selecionada não é válida.',
            'profession_id.integer'       => 'A profissão deve ser um número inteiro.',
            'profession_id.exists'        => 'A profissão selecionada não é válida.',
            'description.string'          => 'A descrição deve ser um texto.',
            'description.max'             => 'A descrição não pode ter mais de 250 caracteres.',
            'email_business.email'        => 'O e-mail empresarial deve ter um formato válido.',
            'email_business.max'          => 'O e-mail empresarial não pode ter mais de 255 caracteres.',
            'phone_business.string'       => 'O telefone empresarial deve ser um texto.',
            'phone_business.max'          => 'O telefone empresarial não pode ter mais de 20 caracteres.',
            'website.url'                 => 'O website deve ser uma URL válida.',
            'website.max'                 => 'O website não pode ter mais de 255 caracteres.',
            'address.required'            => 'O endereço é obrigatório.',
            'address.string'              => 'O endereço deve ser um texto.',
            'address.max'                 => 'O endereço não pode ter mais de 255 caracteres.',
            'address_number.string'       => 'O número do endereço deve ser um texto.',
            'address_number.max'          => 'O número do endereço não pode ter mais de 20 caracteres.',
            'neighborhood.required'       => 'O bairro é obrigatório.',
            'neighborhood.string'         => 'O bairro deve ser um texto.',
            'neighborhood.max'            => 'O bairro não pode ter mais de 100 caracteres.',
            'city.required'               => 'A cidade é obrigatória.',
            'city.string'                 => 'A cidade deve ser um texto.',
            'city.max'                    => 'A cidade não pode ter mais de 100 caracteres.',
            'state.required'              => 'O estado é obrigatório.',
            'state.string'                => 'O estado deve ser um texto.',
            'state.max'                   => 'O estado não pode ter mais de 2 caracteres.',
            'cep.required'                => 'O CEP é obrigatório.',
            'cep.string'                  => 'O CEP deve ser um texto.',
            'cep.max'                     => 'O CEP não pode ter mais de 9 caracteres.',
            'cep.regex'                   => 'O CEP deve ter o formato 00000-000 ou 00000000.',
            'logo.image'                  => 'O arquivo deve ser uma imagem.',
            'logo.mimes'                  => 'A imagem deve ser do tipo: jpeg, png, jpg, gif ou webp.',
            'logo.max'                    => 'A imagem não pode ser maior que 2MB.',
        ];
    }

}
