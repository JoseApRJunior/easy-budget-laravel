<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest dedicado para validação de registro de usuários.
 *
 * Implementa validação robusta com regras específicas para:
 * - first_name e last_name obrigatórios
 * - Senha forte com requisitos de segurança
 * - Mensagens customizadas em português
 * - Validação de termos de serviço
 */
class RegisterUserRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer esta requisição.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação para o registro de usuário.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name'            => [
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:/^[a-zA-ZÀ-ÿ0-9\s\.\-]+$/u', // Letras, números, espaços, pontos e hífens
            ],
            'last_name'             => [
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:/^[a-zA-ZÀ-ÿ0-9\s\.\-]+$/u', // Letras, números, espaços, pontos e hífens
            ],
            'email'                 => [
                'required',
                'string',
                'lowercase',
                'email:rfc,dns',
                'max:255',
                'unique:users,email',
            ],
            'phone'                 => [
                'required',
                'string',
                'regex:/^\(\d{2}\) \d{4,5}-\d{4}$/', // Formato: (11) 99999-9999
                'max:20',
            ],
            'password'              => [
                'required',
                'confirmed',
                'min:8',
                'max:100',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
            ],
            'password_confirmation' => [
                'required',
                'string',
            ],
            'terms_accepted'        => [
                'required',
                'accepted',
            ],
        ];
    }

    /**
     * Mensagens de validação customizadas em português.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required'            => 'O nome é obrigatório.',
            'first_name.min'                 => 'O nome deve ter pelo menos 2 caracteres.',
            'first_name.max'                 => 'O nome não pode ter mais de 100 caracteres.',
            'first_name.regex'               => 'O nome deve conter apenas letras, números, espaços, pontos e hífens.',

            'last_name.required'             => 'O sobrenome é obrigatório.',
            'last_name.min'                  => 'O sobrenome deve ter pelo menos 2 caracteres.',
            'last_name.max'                  => 'O sobrenome não pode ter mais de 100 caracteres.',
            'last_name.regex'                => 'O sobrenome deve conter apenas letras, números, espaços, pontos e hífens.',

            'email.required'                 => 'O e-mail é obrigatório.',
            'email.email'                    => 'Digite um e-mail válido.',
            'email.lowercase'                => 'O e-mail deve estar em letras minúsculas.',
            'email.max'                      => 'O e-mail não pode ter mais de 255 caracteres.',
            'email.unique'                   => 'Este e-mail já está cadastrado em nosso sistema.',

            'phone.required'                 => 'O telefone é obrigatório.',
            'phone.regex'                    => 'Digite o telefone no formato (11) 99999-9999.',
            'phone.max'                      => 'O telefone não pode ter mais de 20 caracteres.',

            'password.required'              => 'A senha é obrigatória.',
            'password.confirmed'             => 'A confirmação da senha não coincide.',
            'password.min'                   => 'A senha deve ter pelo menos 8 caracteres.',
            'password.max'                   => 'A senha não pode ter mais de 100 caracteres.',
            'password.regex'                 => 'A senha deve conter pelo menos uma letra minúscula, uma maiúscula, um número e um símbolo.',

            'password_confirmation.required' => 'A confirmação da senha é obrigatória.',

            'terms_accepted.required'        => 'Você deve aceitar os termos de serviço.',
            'terms_accepted.accepted'        => 'Você deve aceitar os termos de serviço.',
        ];
    }

    /**
     * Nomes dos atributos para validação.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'first_name'            => 'nome',
            'last_name'             => 'sobrenome',
            'email'                 => 'e-mail',
            'phone'                 => 'telefone',
            'password'              => 'senha',
            'password_confirmation' => 'confirmação da senha',
            'terms_accepted'        => 'termos de serviço',
        ];
    }

    /**
     * Prepara os dados para validação.
     */
    protected function prepareForValidation(): void
    {
        // Formatar telefone se necessário
        if ( $this->phone ) {
            $this->request->set( 'phone', $this->formatPhone( $this->phone ) );
        }
    }

    /**
     * Formata o telefone para o padrão brasileiro.
     *
     * @param string $phone
     * @return string
     */
    private function formatPhone( string $phone ): string
    {
        // Remove todos os caracteres não numéricos
        $phone = preg_replace( '/\D/', '', $phone );

        // Se tiver 10 dígitos, adiciona o 9º dígito (São Paulo)
        if ( strlen( $phone ) === 10 ) {
            $phone = '9' . $phone;
        }

        // Formata no padrão (11) 99999-9999
        if ( strlen( $phone ) === 11 ) {
            return sprintf( '(%s) %s-%s',
                substr( $phone, 0, 2 ),
                substr( $phone, 2, 5 ),
                substr( $phone, 7 ),
            );
        }

        return $phone;
    }

    /**
     * Obtém os dados validados e preparados para o service.
     *
     * @return array
     */
    public function getValidatedData(): array
    {
        $validated = $this->validated();

        return [
            'first_name'     => $validated[ 'first_name' ],
            'last_name'      => $validated[ 'last_name' ],
            'email'          => $validated[ 'email' ],
            'phone'          => $validated[ 'phone' ],
            'password'       => $validated[ 'password' ],
            'terms_accepted' => (bool) $validated[ 'terms_accepted' ],
        ];
    }

}
