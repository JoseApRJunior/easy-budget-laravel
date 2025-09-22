<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validação para operações relacionadas a email.
 * Utilizado para reenvio de confirmação e recuperação de senha.
 * Migração das regras do sistema legacy app/request/EmailRequest.php.
 *
 * @package App\Http\Requests
 * @author IA
 */
class EmailRequest extends FormRequest
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
     * Regras de validação para o request de email.
     *
     * @return array<string, array|string>
     */
    public function rules(): array
    {
        return [ 
            'email' => [ 
                'required',
                'email:rfc,dns',
                'max:255',
                'exists:users,email'
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
            'email.required' => 'O email é obrigatório.',
            'email.email'    => 'O email deve ser um endereço válido.',
            'email.max'      => 'O email não pode ter mais de 255 caracteres.',
            'email.exists'   => 'Não existe usuário cadastrado com este email.',
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
            'email' => 'endereço de email',
        ];
    }

}