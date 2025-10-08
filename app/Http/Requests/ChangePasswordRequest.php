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
        return [
            'current_password'      => 'required|string',
            'password'              => 'required|string|min:8|max:255|confirmed|different:current_password',
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
        return [
            'current_password.required'      => 'A senha atual é obrigatória.',
            'password.required'              => 'A nova senha é obrigatória.',
            'password.min'                   => 'A nova senha deve ter pelo menos 8 caracteres.',
            'password.confirmed'             => 'A confirmação da senha não corresponde.',
            'password.different'             => 'A nova senha deve ser diferente da senha atual.',
            'password_confirmation.required' => 'A confirmação da senha é obrigatória.',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator( $validator ): void
    {
        $validator->after( function ( $validator ) {
            $user = auth()->user();

            // Verificar se a senha atual está correta
            if ( !Hash::check( $this->current_password, $user->password ) ) {
                $validator->errors()->add( 'current_password', 'A senha atual está incorreta.' );
            }
        } );
    }

}
