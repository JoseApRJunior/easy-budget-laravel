<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Validação para criação e atualização de contatos.
 * Migração das regras do sistema legacy app/request/ContactFormRequest.php.
 * Inclui validação de telefones brasileiros e unicidade de email por tenant.
 *
 * @package App\Http\Requests
 * @author IA
 */
class ContactFormRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer este request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $user = Auth::user();
        return $user && $user->tenant_id !== null;
    }

    /**
     * Regras de validação para criação/atualização de contato.
     *
     * @return array<string, array|Rule|string>
     */
    public function rules(): array
    {
        $tenantId = Auth::user()?->tenant_id;

        return [ 
            'email'          => [ 
                'required',
                'email:rfc,dns',
                'max:255',
                Rule::unique( 'contacts', 'email' )
                    ->where( function ($query) use ($tenantId) {
                        return $query->where( 'tenant_id', $tenantId );
                    } )
                    ->ignore( $this->contact )
            ],
            'phone'          => [ 
                'nullable',
                'string',
                'max:15',
                'regex:/^\(\d{2}\)\s?\d{4,5}-\d{4}$/'
            ],
            'email_business' => [ 
                'nullable',
                'email:rfc,dns',
                'max:255',
                Rule::unique( 'contacts', 'email_business' )
                    ->where( function ($query) use ($tenantId) {
                        return $query->where( 'tenant_id', $tenantId );
                    } )
                    ->ignore( $this->contact )
            ],
            'phone_business' => [ 
                'nullable',
                'string',
                'max:15',
                'regex:/^\(\d{2}\)\s?\d{4,5}-\d{4}$/'
            ],
            'website'        => [ 
                'nullable',
                'url',
                'max:255'
            ],
            'tenant_id'      => [ 
                'required',
                'integer',
                'exists:tenants,id'
            ],
            'is_primary'     => [ 
                'nullable',
                'boolean'
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
            'email.required'        => 'O email é obrigatório.',
            'email.email'           => 'O email deve ser um endereço válido.',
            'email.max'             => 'O email não pode ter mais de 255 caracteres.',
            'email.unique'          => 'Este email já está cadastrado para este tenant.',
            'phone.string'          => 'O telefone deve ser uma string.',
            'phone.max'             => 'O telefone não pode ter mais de 15 caracteres.',
            'phone.regex'           => 'O telefone deve estar no formato (11) 99999-9999.',
            'email_business.email'  => 'O email comercial deve ser um endereço válido.',
            'email_business.max'    => 'O email comercial não pode ter mais de 255 caracteres.',
            'email_business.unique' => 'Este email comercial já está cadastrado para este tenant.',
            'phone_business.string' => 'O telefone comercial deve ser uma string.',
            'phone_business.max'    => 'O telefone comercial não pode ter mais de 15 caracteres.',
            'phone_business.regex'  => 'O telefone comercial deve estar no formato (11) 99999-9999.',
            'website.url'           => 'O website deve ser uma URL válida.',
            'website.max'           => 'O website não pode ter mais de 255 caracteres.',
            'tenant_id.required'    => 'O ID do tenant é obrigatório.',
            'tenant_id.integer'     => 'O ID do tenant deve ser um número inteiro.',
            'tenant_id.exists'      => 'O tenant informado não existe.',
            'is_primary.boolean'    => 'O contato principal deve ser verdadeiro ou falso.',
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
            'email'          => 'email pessoal',
            'phone'          => 'telefone pessoal',
            'email_business' => 'email comercial',
            'phone_business' => 'telefone comercial',
            'website'        => 'website',
            'tenant_id'      => 'tenant',
            'is_primary'     => 'contato principal',
        ];
    }

    /**
     * Preparar os dados para validação.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge( [ 
            'tenant_id'      => Auth::user()?->tenant_id,
            'is_primary'     => (bool) $this->is_primary,
            'phone'          => $this->formatPhone( $this->phone ),
            'phone_business' => $this->formatPhone( $this->phone_business ),
        ] );
    }

    /**
     * Formata o telefone brasileiro removendo caracteres especiais.
     *
     * @param string|null $phone
     * @return string|null
     */
    private function formatPhone( ?string $phone ): ?string
    {
        if ( !$phone ) {
            return null;
        }

        // Remove todos os caracteres não numéricos
        $cleanPhone = preg_replace( '/[^0-9]/', '', $phone );

        if ( strlen( $cleanPhone ) == 11 ) {
            // Formato (11) 99999-9999
            return '(' . substr( $cleanPhone, 0, 2 ) . ') ' . substr( $cleanPhone, 2, 5 ) . '-' . substr( $cleanPhone, 7 );
        } elseif ( strlen( $cleanPhone ) == 10 ) {
            // Formato (11) 9999-9999
            return '(' . substr( $cleanPhone, 0, 2 ) . ') ' . substr( $cleanPhone, 2, 4 ) . '-' . substr( $cleanPhone, 6 );
        }

        return $phone;
    }

}