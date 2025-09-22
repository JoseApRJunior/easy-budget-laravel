<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserCreateFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [ 
            'name'      => 'required|string|max:255',
            'email'     => 'required|string|email|max:255|unique:users',
            'password'  => 'required|string|min:8|confirmed',
            'phone'     => 'nullable|string|max:20',
            'tenant_id' => 'required|integer|exists:tenants,id',
            'role'      => 'required|string|in:admin,provider,user',
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
            'name.required'      => 'O nome é obrigatório.',
            'name.string'        => 'O nome deve ser uma string.',
            'name.max'           => 'O nome não pode ter mais de 255 caracteres.',

            'email.required'     => 'O email é obrigatório.',
            'email.string'       => 'O email deve ser uma string.',
            'email.email'        => 'O email deve ser um endereço de email válido.',
            'email.max'          => 'O email não pode ter mais de 255 caracteres.',
            'email.unique'       => 'Este email já está sendo usado.',

            'password.required'  => 'A senha é obrigatória.',
            'password.string'    => 'A senha deve ser uma string.',
            'password.min'       => 'A senha deve ter pelo menos 8 caracteres.',
            'password.confirmed' => 'A confirmação da senha não corresponde.',

            'phone.string'       => 'O telefone deve ser uma string.',
            'phone.max'          => 'O telefone não pode ter mais de 20 caracteres.',

            'tenant_id.required' => 'O tenant é obrigatório.',
            'tenant_id.integer'  => 'O tenant deve ser um número inteiro.',
            'tenant_id.exists'   => 'O tenant selecionado não existe.',

            'role.required'      => 'O papel é obrigatório.',
            'role.string'        => 'O papel deve ser uma string.',
            'role.in'            => 'O papel deve ser admin, provider ou user.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [ 
            'name'      => 'nome',
            'email'     => 'email',
            'password'  => 'senha',
            'phone'     => 'telefone',
            'tenant_id' => 'tenant',
            'role'      => 'papel',
        ];
    }

}