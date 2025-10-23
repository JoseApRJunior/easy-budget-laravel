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
            // Dados empresariais
            'company_name'        => [ 'nullable', 'string', 'max:255' ],
            'cnpj'                => [ 'nullable', 'string', 'size:14' ],
            'cpf'                 => [ 'nullable', 'string', 'size:11' ],
            'area_of_activity_id' => [ 'nullable', 'integer', 'exists:areas_of_activity,id' ],
            'profession_id'       => [ 'nullable', 'integer', 'exists:professions,id' ],
            'description'         => [ 'nullable', 'string', 'max:250' ],

            // Contato empresarial
            'email_business'      => [ 'nullable', 'email', 'max:255' ],
            'phone_business'      => [ 'nullable', 'string', 'max:20' ],
            'website'             => [ 'nullable', 'url', 'max:255' ],

            // Endereço
            'address'             => [ 'required', 'string', 'max:255' ],
            'address_number'      => [ 'nullable', 'string', 'max:20' ],
            'neighborhood'        => [ 'required', 'string', 'max:100' ],
            'city'                => [ 'required', 'string', 'max:100' ],
            'state'               => [ 'required', 'string', 'max:2' ],
            'cep'                 => [ 'required', 'string', 'max:9', 'regex:/^\d{5}-?\d{3}$/' ],

            // Logo da empresa
            'logo'                => [ 'nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048' ],
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
            'company_name.string'         => 'O nome da empresa deve ser um texto.',
            'company_name.max'            => 'O nome da empresa não pode ter mais de 255 caracteres.',
            'cnpj.size'                   => 'O CNPJ deve ter 14 dígitos.',
            'cpf.size'                    => 'O CPF deve ter 11 dígitos.',
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
