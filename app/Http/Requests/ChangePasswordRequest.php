<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class ChangePasswordRequest extends FormRequest
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
        $user = auth()->user();

        // Se usuário não tem senha (Google OAuth), não requer current_password
        if (is_null($user->password)) {
            return [
                'password' => 'required|string|min:8|max:255|confirmed',
                'password_confirmation' => 'required|string',
            ];
        }

        // Usuário normal com senha - requer validação da senha atual
        return [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|max:255|confirmed|different:current_password',
            'password_confirmation' => 'required|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $user = auth()->user();
        $isGoogleUser = is_null($user->password);

        $messages = [
            'password.required' => 'A nova senha é obrigatória.',
            'password.min' => 'A nova senha deve ter pelo menos 8 caracteres.',
            'password.confirmed' => 'A confirmação da senha não corresponde.',
            'password_confirmation.required' => 'A confirmação da senha é obrigatória.',
        ];

        // Só adicionar mensagens de current_password se o usuário tiver senha
        if (! $isGoogleUser) {
            $messages['current_password.required'] = 'A senha atual é obrigatória.';
            $messages['password.different'] = 'A nova senha deve ser diferente da senha atual.';
        }

        return $messages;
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = auth()->user();

            // Só validar senha atual se o usuário tiver senha (não é Google OAuth)
            if (! is_null($user->password) && isset($this->current_password)) {
                if (! Hash::check($this->current_password, $user->password)) {
                    $validator->errors()->add('current_password', 'A senha atual está incorreta.');
                }
            }
        });
    }
}
