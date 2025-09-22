<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Validação para criação e atualização de dados comuns.
 * Migração das regras do sistema legacy app/request/CommonDataFormRequest.php.
 * Inclui validação específica para CPF/CNPJ brasileiro e relacionamentos.
 *
 * @package App\Http\Requests
 * @author IA
 */
class CommonDataFormRequest extends FormRequest
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
     * Regras de validação para criação/atualização de dados comuns.
     *
     * @return array<string, array|Rule|string>
     */
    public function rules(): array
    {
        $tenantId = Auth::user()?->tenant_id;

        return [ 
            'first_name'          => [ 
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Zà-úÀ-Ú\s]+$/u'
            ],
            'last_name'           => [ 
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Zà-úÀ-Ú\s]+$/u'
            ],
            'birth_date'          => [ 
                'nullable',
                'date',
                'before:today',
                'date_format:Y-m-d'
            ],
            'cpf'                 => [ 
                'nullable',
                'string',
                'size:14',
                'regex:/^\d{3}\.\d{3}\.\d{3}-\d{2}$/',
                Rule::unique( 'common_data', 'cpf' )
                    ->where( function ($query) use ($tenantId) {
                        return $query->where( 'tenant_id', $tenantId );
                    } )
                    ->ignore( $this->common_data )
            ],
            'cnpj'                => [ 
                'nullable',
                'string',
                'size:18',
                'regex:/^\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}$/',
                Rule::unique( 'common_data', 'cnpj' )
                    ->where( function ($query) use ($tenantId) {
                        return $query->where( 'tenant_id', $tenantId );
                    } )
                    ->ignore( $this->common_data )
            ],
            'company_name'        => [ 
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9à-úÀ-Ú\s\.,-]*$/u'
            ],
            'description'         => [ 
                'nullable',
                'string',
                'max:1000'
            ],
            'area_of_activity_id' => [ 
                'nullable',
                'integer',
                'exists:area_of_activities,id'
            ],
            'profession_id'       => [ 
                'nullable',
                'integer',
                'exists:professions,id'
            ],
            'tenant_id'           => [ 
                'required',
                'integer',
                'exists:tenants,id'
            ],
            'document_type'       => [ 
                'required_with:cpf,cnpj',
                Rule::in( [ 'cpf', 'cnpj', 'none' ] )
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
            'first_name.required'         => 'O primeiro nome é obrigatório.',
            'first_name.string'           => 'O primeiro nome deve ser uma string.',
            'first_name.max'              => 'O primeiro nome não pode ter mais de 100 caracteres.',
            'first_name.regex'            => 'O primeiro nome deve conter apenas letras e espaços.',
            'last_name.required'          => 'O sobrenome é obrigatório.',
            'last_name.string'            => 'O sobrenome deve ser uma string.',
            'last_name.max'               => 'O sobrenome não pode ter mais de 100 caracteres.',
            'last_name.regex'             => 'O sobrenome deve conter apenas letras e espaços.',
            'birth_date.date'             => 'A data de nascimento deve ser uma data válida.',
            'birth_date.before'           => 'A data de nascimento não pode ser futura.',
            'birth_date.date_format'      => 'A data de nascimento deve estar no formato YYYY-MM-DD.',
            'cpf.string'                  => 'O CPF deve ser uma string.',
            'cpf.size'                    => 'O CPF deve ter exatamente 14 caracteres no formato 000.000.000-00.',
            'cpf.regex'                   => 'O CPF deve estar no formato 000.000.000-00.',
            'cpf.unique'                  => 'Este CPF já está cadastrado para este tenant.',
            'cnpj.string'                 => 'O CNPJ deve ser uma string.',
            'cnpj.size'                   => 'O CNPJ deve ter exatamente 18 caracteres no formato 00.000.000/0000-00.',
            'cnpj.regex'                  => 'O CNPJ deve estar no formato 00.000.000/0000-00.',
            'cnpj.unique'                 => 'Este CNPJ já está cadastrado para este tenant.',
            'company_name.string'         => 'O nome da empresa deve ser uma string.',
            'company_name.max'            => 'O nome da empresa não pode ter mais de 255 caracteres.',
            'company_name.regex'          => 'O nome da empresa deve conter apenas caracteres alfanuméricos e símbolos básicos.',
            'description.string'          => 'A descrição deve ser uma string.',
            'description.max'             => 'A descrição não pode ter mais de 1000 caracteres.',
            'area_of_activity_id.integer' => 'O ID da área de atividade deve ser um número inteiro.',
            'area_of_activity_id.exists'  => 'A área de atividade selecionada não existe.',
            'profession_id.integer'       => 'O ID da profissão deve ser um número inteiro.',
            'profession_id.exists'        => 'A profissão selecionada não existe.',
            'tenant_id.required'          => 'O ID do tenant é obrigatório.',
            'tenant_id.integer'           => 'O ID do tenant deve ser um número inteiro.',
            'tenant_id.exists'            => 'O tenant informado não existe.',
            'document_type.required_with' => 'O tipo de documento é obrigatório quando CPF ou CNPJ é fornecido.',
            'document_type.in'            => 'O tipo de documento deve ser CPF, CNPJ ou nenhum.',
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
            'first_name'          => 'primeiro nome',
            'last_name'           => 'sobrenome',
            'birth_date'          => 'data de nascimento',
            'cpf'                 => 'CPF',
            'cnpj'                => 'CNPJ',
            'company_name'        => 'nome da empresa',
            'description'         => 'descrição',
            'area_of_activity_id' => 'área de atividade',
            'profession_id'       => 'profissão',
            'tenant_id'           => 'tenant',
            'document_type'       => 'tipo de documento',
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
            'tenant_id'    => Auth::user()?->tenant_id,
            'first_name'   => trim( ucwords( strtolower( $this->first_name ) ) ),
            'last_name'    => trim( ucwords( strtolower( $this->last_name ) ) ),
            'company_name' => trim( ucwords( strtolower( $this->company_name ) ) ),
            'cpf'          => $this->formatCpfCnpj( $this->cpf ),
            'cnpj'         => $this->formatCpfCnpj( $this->cnpj ),
        ] );

        // Define document_type baseado nos campos preenchidos
        if ( $this->cpf && !$this->cnpj ) {
            $this->merge( [ 'document_type' => 'cpf' ] );
        } elseif ( $this->cnpj && !$this->cpf ) {
            $this->merge( [ 'document_type' => 'cnpj' ] );
        } elseif ( !$this->cpf && !$this->cnpj ) {
            $this->merge( [ 'document_type' => 'none' ] );
        }
    }

    /**
     * Formata CPF ou CNPJ removendo caracteres especiais.
     *
     * @param string|null $document
     * @return string|null
     */
    private function formatCpfCnpj( ?string $document ): ?string
    {
        if ( !$document ) {
            return null;
        }

        // Remove todos os caracteres não numéricos
        $cleanDoc = preg_replace( '/[^0-9]/', '', $document );

        if ( strlen( $cleanDoc ) == 11 ) {
            // Formato CPF: 000.000.000-00
            return substr( $cleanDoc, 0, 3 ) . '.' . substr( $cleanDoc, 3, 3 ) . '.' .
                substr( $cleanDoc, 6, 3 ) . '-' . substr( $cleanDoc, 9 );
        } elseif ( strlen( $cleanDoc ) == 14 ) {
            // Formato CNPJ: 00.000.000/0000-00
            return substr( $cleanDoc, 0, 2 ) . '.' . substr( $cleanDoc, 2, 3 ) . '.' .
                substr( $cleanDoc, 5, 3 ) . '/' . substr( $cleanDoc, 8, 4 ) . '-' .
                substr( $cleanDoc, 12 );
        }

        return $document;
    }

}
