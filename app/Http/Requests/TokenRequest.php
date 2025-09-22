<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validação para operações relacionadas a tokens de confirmação e recuperação de senha.
 * Migração das regras do sistema legacy app/request/TokenRequest.php.
 *
 * @package App\Http\Requests
 * @author IA
 */
class TokenRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer este request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação para o request.
     * Inclui validação para token de confirmação de usuário e recuperação de senha.
     *
     * @return array<string, array|Rule|string>
     */
    public function rules(): array
    {
        return [ 
            'token'                 => [ 
                'required',
                'string',
                'min:32',
                'max:255',
                'regex:/^[a-f0-9]{32}$/',
            ],
            'email'                 => [ 
                'required_with:token',
                'email:rfc,dns',
                'max:255',
            ],
            'password'              => [ 
                'required_with:token',
                'string',
                'min:8',
                'confirmed',
                Rule::password()->letters()->mixedCase()->numbers(),
            ],
            'password_confirmation' => [ 
                'required_with:password',
                'same:password',
            ],
        ];
    }

    /**
     * Mensagens de erro personalizadas em português.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [ 
            'token.required'                      => 'O token é obrigatório.',
            'token.string'                        => 'O token deve ser uma string válida.',
            'token.min'                           => 'O token deve ter pelo menos 32 caracteres.',
            'token.max'                           => 'O token não pode ter mais de 255 caracteres.',
            'token.regex'                         => 'O token deve conter apenas caracteres hexadecimais.',
            'email.required_with'                 => 'O email é obrigatório quando o token é fornecido.',
            'email.email'                         => 'O email deve ser um endereço de email válido.',
            'email.max'                           => 'O email não pode ter mais de 255 caracteres.',
            'password.required_with'              => 'A senha é obrigatória quando o token é fornecido.',
            'password.string'                     => 'A senha deve ser uma string.',
            'password.min'                        => 'A senha deve ter pelo menos 8 caracteres.',
            'password.confirmed'                  => 'A confirmação da senha não corresponde.',
            'password.letters'                    => 'A senha deve conter pelo menos uma letra.',
            'password.mixedCase'                  => 'A senha deve conter letras maiúsculas e minúsculas.',
            'password.numbers'                    => 'A senha deve conter pelo menos um número.',
            'password_confirmation.required_with' => 'A confirmação da senha é obrigatória.',
            'password_confirmation.same'          => 'A confirmação da senha deve corresponder à senha.',
        ];
    }

    /**
     * Campos que devem ser retornados com erros de validação.
     *
     * @return array<int, string>
     */
    public function attributes(): array
    {
        return [ 
            'token'                 => 'token de confirmação',
            'email'                 => 'email',
            'password'              => 'senha',
            'password_confirmation' => 'confirmação da senha',
        ];
    }

}