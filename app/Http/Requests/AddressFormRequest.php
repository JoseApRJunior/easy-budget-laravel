<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validação para criação e atualização de endereços.
 * Migração das regras do sistema legacy app/request/AddressFormRequest.php.
 * Inclui validação específica para formato brasileiro de CEP.
 *
 * @package App\Http\Requests
 * @author IA
 */
class AddressFormRequest extends FormRequest
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
     * Regras de validação para criação/atualização de endereço.
     *
     * @return array<string, array|Rule|string>
     */
    public function rules(): array
    {
        return [ 
            'address'        => [ 
                'required',
                'string',
                'max:255'
            ],
            'address_number' => [ 
                'required',
                'string',
                'max:10'
            ],
            'complement'     => [ 
                'nullable',
                'string',
                'max:100'
            ],
            'neighborhood'   => [ 
                'required',
                'string',
                'max:100'
            ],
            'city'           => [ 
                'required',
                'string',
                'max:100'
            ],
            'state'          => [ 
                'required',
                'string',
                'size:2',
                Rule::in( [ 'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO' ] )
            ],
            'cep'            => [ 
                'required',
                'string',
                'size:9',
                'regex:/^[0-9]{5}-[0-9]{3}$/'
            ],
            'is_primary'     => [ 
                'nullable',
                'boolean'
            ],
            'tenant_id'      => [ 
                'required',
                'integer',
                'exists:tenants,id'
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
            'address.required'        => 'O endereço é obrigatório.',
            'address.string'          => 'O endereço deve ser uma string.',
            'address.max'             => 'O endereço não pode ter mais de 255 caracteres.',
            'address_number.required' => 'O número do endereço é obrigatório.',
            'address_number.string'   => 'O número do endereço deve ser uma string.',
            'address_number.max'      => 'O número do endereço não pode ter mais de 10 caracteres.',
            'complement.string'       => 'O complemento deve ser uma string.',
            'complement.max'          => 'O complemento não pode ter mais de 100 caracteres.',
            'neighborhood.required'   => 'O bairro é obrigatório.',
            'neighborhood.string'     => 'O bairro deve ser uma string.',
            'neighborhood.max'        => 'O bairro não pode ter mais de 100 caracteres.',
            'city.required'           => 'A cidade é obrigatória.',
            'city.string'             => 'A cidade deve ser uma string.',
            'city.max'                => 'A cidade não pode ter mais de 100 caracteres.',
            'state.required'          => 'O estado é obrigatório.',
            'state.string'            => 'O estado deve ser uma string.',
            'state.size'              => 'O estado deve ter exatamente 2 caracteres (UF).',
            'state.in'                => 'O estado selecionado não é válido.',
            'cep.required'            => 'O CEP é obrigatório.',
            'cep.string'              => 'O CEP deve ser uma string.',
            'cep.size'                => 'O CEP deve ter exatamente 9 caracteres no formato 00000-000.',
            'cep.regex'               => 'O CEP deve estar no formato 00000-000.',
            'is_primary.boolean'      => 'O endereço principal deve ser verdadeiro ou falso.',
            'tenant_id.required'      => 'O ID do tenant é obrigatório.',
            'tenant_id.integer'       => 'O ID do tenant deve ser um número inteiro.',
            'tenant_id.exists'        => 'O tenant informado não existe.',
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
            'address'        => 'endereço',
            'address_number' => 'número do endereço',
            'complement'     => 'complemento',
            'neighborhood'   => 'bairro',
            'city'           => 'cidade',
            'state'          => 'estado (UF)',
            'cep'            => 'CEP',
            'is_primary'     => 'endereço principal',
            'tenant_id'      => 'tenant',
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
            'cep'        => preg_replace( '/[^0-9]/', '', $this->cep ),
            'cep'        => substr( $this->cep, 0, 5 ) . '-' . substr( $this->cep, 5, 3 ),
            'state'      => strtoupper( $this->state ),
            'is_primary' => (bool) $this->is_primary,
            'tenant_id'  => (int) $this->tenant_id,
        ] );
    }

}