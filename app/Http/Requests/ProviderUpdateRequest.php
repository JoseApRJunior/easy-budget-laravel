<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProviderUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function prepareForValidation(): void
    {
        // Se o email não estiver presente, mas o email_personal sim, mapeia para email
        if (! $this->has('email') && $this->has('email_personal')) {
            $this->merge(['email' => $this->email_personal]);
        }

        if ($this->has('cpf')) {
            $this->merge([
                'cpf' => preg_replace('/\D/', '', (string) $this->cpf),
            ]);
        }

        if ($this->has('cnpj')) {
            $this->merge([
                'cnpj' => preg_replace('/\D/', '', (string) $this->cnpj),
            ]);
        }

        if ($this->has('phone_personal')) {
            $this->merge([
                'phone_personal' => preg_replace('/\D/', '', (string) $this->phone_personal),
            ]);
        }

        if ($this->has('phone_business')) {
            $this->merge([
                'phone_business' => preg_replace('/\D/', '', (string) $this->phone_business),
            ]);
        }

        if ($this->has('cep')) {
            $this->merge([
                'cep' => preg_replace('/\D/', '', (string) $this->cep),
            ]);
        }

        if ($this->has('birth_date') && ! empty($this->birth_date)) {
            // Se a data vier apenas com a máscara (ex: __/__/____), remove
            if (preg_match('/^[_\/]+$/', (string) $this->birth_date)) {
                $this->merge(['birth_date' => null]);
            }
        }

        if ($this->has('founding_date') && ! empty($this->founding_date)) {
            // Se a data vier apenas com a máscara (ex: __/__/____), remove
            if (preg_match('/^[_\/]+$/', (string) $this->founding_date)) {
                $this->merge(['founding_date' => null]);
            }
        }
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
            'email' => 'required|email|max:100|unique:users,email,'.$userId,
            'person_type' => 'required|in:pf,pj',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',

            // CommonData validation rules
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'birth_date' => 'nullable|date_format:d/m/Y|before:today',
            'cnpj' => 'nullable|string|unique:common_datas,cnpj,'.$this->getCommonDataId(),
            'cpf' => 'nullable|string|unique:common_datas,cpf,'.$this->getCommonDataId(),
            'company_name' => 'nullable|string|max:255',
            'fantasy_name' => 'nullable|string|max:255',
            'founding_date' => 'nullable|date_format:d/m/Y|before_or_equal:today',
            'state_registration' => 'nullable|string|max:50',
            'municipal_registration' => 'nullable|string|max:50',
            'industry' => 'nullable|string|max:100',
            'company_size' => 'nullable|in:micro,pequena,media,grande',
            'description' => 'nullable|string|max:65535',
            'area_of_activity_id' => 'nullable|integer|exists:areas_of_activity,id',
            'profession_id' => 'nullable|integer|exists:professions,id',

            // Contact validation rules
            'email_business' => 'nullable|email|max:255|unique:contacts,email_business,'.$this->getContactId(),
            'phone_personal' => 'nullable|string|max:20',
            'phone_business' => 'nullable|string|max:20',
            'website' => 'nullable|string|max:255',

            // Address validation rules
            'address' => 'required|string|max:255',
            'address_number' => 'nullable|string|max:20',
            'neighborhood' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:2',
            'cep' => 'required|string|size:8',
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
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'O e-mail deve ter um formato válido.',
            'email.unique' => 'Este e-mail já está registrado.',
            'logo.image' => 'O arquivo deve ser uma imagem.',
            'logo.mimes' => 'A imagem deve ser do tipo: jpeg, png, jpg ou gif.',
            'logo.max' => 'A imagem não pode ser maior que 2MB.',
            'first_name.required' => 'O primeiro nome é obrigatório.',
            'last_name.required' => 'O sobrenome é obrigatório.',
            'cnpj.size' => 'O CNPJ deve ter 14 dígitos.',
            'cnpj.unique' => 'Este CNPJ já está registrado.',
            'cpf.size' => 'O CPF deve ter 11 dígitos.',
            'cpf.unique' => 'Este CPF já está registrado.',
            'area_of_activity_id.exists' => 'A área de atuação selecionada não é válida.',
            'profession_id.exists' => 'A profissão selecionada não é válida.',
            'address.required' => 'O endereço é obrigatório.',
            'neighborhood.required' => 'O bairro é obrigatório.',
            'city.required' => 'A cidade é obrigatória.',
            'state.required' => 'O estado é obrigatório.',
            'cep.required' => 'O CEP é obrigatório.',
            'cep.regex' => 'O CEP deve ter o formato 00000-000 ou 00000000.',
            'founding_date.date_format' => 'Digite uma data de fundação válida no formato DD/MM/AAAA.',
            'founding_date.before_or_equal' => 'A data de fundação deve ser anterior ou igual a hoje.',
            'company_size.in' => 'Tamanho da empresa deve ser: micro, pequena, media ou grande.',
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
