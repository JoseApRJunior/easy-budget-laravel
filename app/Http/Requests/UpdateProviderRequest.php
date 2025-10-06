<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProviderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = auth()->id();

        return [
            // User validation rules
            'email'               => 'required|email|max:100|unique:users,email,' . $userId,
            'logo'                => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',

            // CommonData validation rules
            'first_name'          => 'required|string|max:100',
            'last_name'           => 'required|string|max:100',
            'birth_date'          => 'nullable|date|before:today',
            'cnpj'                => 'nullable|string|size:14|unique:common_datas,cnpj,' . $this->getCommonDataId(),
            'cpf'                 => 'nullable|string|size:11|unique:common_datas,cpf,' . $this->getCommonDataId(),
            'company_name'        => 'nullable|string|max:255',
            'description'         => 'nullable|string|max:65535',
            'area_of_activity_id' => 'nullable|integer|exists:area_of_activities,id',
            'profession_id'       => 'nullable|integer|exists:professions,id',

            // Contact validation rules
            'email_business'      => 'nullable|email|max:255|unique:contacts,email_business,' . $this->getContactId(),
            'phone'               => 'nullable|string|max:20',
            'phone_business'      => 'nullable|string|max:20',
            'website'             => 'nullable|url|max:255',

            // Address validation rules
            'address'             => 'required|string|max:255',
            'address_number'      => 'nullable|string|max:20',
            'neighborhood'        => 'required|string|max:100',
            'city'                => 'required|string|max:100',
            'state'               => 'required|string|max:2',
            'cep'                 => 'required|string|max:9|regex:/^\d{5}-?\d{3}$/',
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
            'email.required'             => 'O e-mail é obrigatório.',
            'email.email'                => 'O e-mail deve ter um formato válido.',
            'email.unique'               => 'Este e-mail já está registrado.',
            'logo.image'                 => 'O arquivo deve ser uma imagem.',
            'logo.mimes'                 => 'A imagem deve ser do tipo: jpeg, png, jpg ou gif.',
            'logo.max'                   => 'A imagem não pode ser maior que 2MB.',
            'first_name.required'        => 'O primeiro nome é obrigatório.',
            'last_name.required'         => 'O sobrenome é obrigatório.',
            'cnpj.size'                  => 'O CNPJ deve ter 14 dígitos.',
            'cnpj.unique'                => 'Este CNPJ já está registrado.',
            'cpf.size'                   => 'O CPF deve ter 11 dígitos.',
            'cpf.unique'                 => 'Este CPF já está registrado.',
            'area_of_activity_id.exists' => 'A área de atuação selecionada não é válida.',
            'profession_id.exists'       => 'A profissão selecionada não é válida.',
            'address.required'           => 'O endereço é obrigatório.',
            'neighborhood.required'      => 'O bairro é obrigatório.',
            'city.required'              => 'A cidade é obrigatória.',
            'state.required'             => 'O estado é obrigatório.',
            'cep.required'               => 'O CEP é obrigatório.',
            'cep.regex'                  => 'O CEP deve ter o formato 00000-000 ou 00000000.',
        ];
    }

    /**
     * Get the common data ID for unique validation.
     */
    private function getCommonDataId(): ?int
    {
        return auth()->user()->provider?->commonData?->id;
    }

    /**
     * Get the contact ID for unique validation.
     */
    private function getContactId(): ?int
    {
        return auth()->user()->provider?->contact?->id;
    }

}
