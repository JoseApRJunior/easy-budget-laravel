<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Validação para seleção de status de orçamento pelo cliente.
 * Utilizado no workflow de mudança de status via token público.
 * Migração das regras do sistema legacy app/request/BudgetChooseStatusFormRequest.php.
 *
 * @package App\Http\Requests
 * @author IA
 */
class BudgetChooseStatusFormRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer este request.
     * Verifica se o token é válido para o orçamento específico.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $budgetCode = $this->input( 'budget_code' );
        $token      = $this->input( 'token' );

        if ( !$budgetCode || !$token ) {
            return false;
        }

        // Verifica se existe um orçamento com o código e token válido
        $exists = DB::table( 'budgets' )
            ->where( 'code', $budgetCode )
            ->where( 'confirmation_token', $token )
            ->where( 'status', 'sent' ) // Apenas orçamentos enviados podem ser respondidos
            ->where( 'expires_at', '>', now() )
            ->exists();

        return $exists;
    }

    /**
     * Regras de validação para seleção de status de orçamento pelo cliente.
     *
     * @return array<string, array|Rule|string>
     */
    public function rules(): array
    {
        return [ 
            'budget_code' => [ 
                'required',
                'string',
                'max:50',
                'exists:budgets,code'
            ],
            'token'       => [ 
                'required',
                'string',
                'min:32',
                'max:255'
            ],
            'status'      => [ 
                'required',
                Rule::in( [ 'approved', 'rejected' ] )
            ],
            'comment'     => [ 
                'nullable',
                'string',
                'max:1000'
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
            'budget_code.required' => 'O código do orçamento é obrigatório.',
            'budget_code.string'   => 'O código do orçamento deve ser uma string.',
            'budget_code.max'      => 'O código do orçamento não pode ter mais de 50 caracteres.',
            'budget_code.exists'   => 'O orçamento com este código não foi encontrado.',
            'token.required'       => 'O token de acesso é obrigatório.',
            'token.string'         => 'O token deve ser uma string válida.',
            'token.min'            => 'O token deve ter pelo menos 32 caracteres.',
            'token.max'            => 'O token não pode ter mais de 255 caracteres.',
            'status.required'      => 'A seleção de status é obrigatória.',
            'status.in'            => 'O status selecionado não é válido.',
            'comment.string'       => 'O comentário deve ser uma string.',
            'comment.max'          => 'O comentário não pode ter mais de 1000 caracteres.',
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
            'budget_code' => 'código do orçamento',
            'token'       => 'token de acesso',
            'status'      => 'status do orçamento',
            'comment'     => 'comentário do cliente',
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
            'budget_code' => trim( strtoupper( $this->input( 'budget_code' ) ) ),
        ] );
    }

}