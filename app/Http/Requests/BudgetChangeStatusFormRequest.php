<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Validação para alteração de status de orçamento.
 * Migração das regras do sistema legacy app/request/BudgetChangeStatusFormRequest.php.
 * Inclui validação de transições de status válidas e isolamento de tenant.
 *
 * @package App\Http\Requests
 * @author IA
 */
class BudgetChangeStatusFormRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer este request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $user = Auth::user();
        if ( !$user || !$user->tenant_id ) {
            return false;
        }

        // Verifica se o tenant_id corresponde ao do orçamento
        $budgetId = $this->input( 'budget_id' );
        $tenantId = $this->input( 'tenant_id', $user->tenant_id );

        return $user->tenant_id === $tenantId;
    }

    /**
     * Regras de validação para alteração de status de orçamento.
     *
     * @return array<string, array|Rule|string>
     */
    public function rules(): array
    {
        return [ 
            'budget_id' => [ 
                'required',
                'integer',
                'exists:budgets,id'
            ],
            'tenant_id' => [ 
                'required',
                'integer',
                'exists:tenants,id'
            ],
            'status'    => [ 
                'required',
                Rule::in( [ 'draft', 'sent', 'approved', 'rejected', 'completed', 'cancelled' ] )
            ],
            'comment'   => [ 
                'nullable',
                'string',
                'max:1000'
            ],
            'reason'    => [ 
                'nullable',
                'string',
                'max:500',
                'required_if:status,rejected,cancelled'
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
            'budget_id.required' => 'O ID do orçamento é obrigatório.',
            'budget_id.integer'  => 'O ID do orçamento deve ser um número inteiro.',
            'budget_id.exists'   => 'O orçamento informado não existe.',
            'tenant_id.required' => 'O ID do tenant é obrigatório.',
            'tenant_id.integer'  => 'O ID do tenant deve ser um número inteiro.',
            'tenant_id.exists'   => 'O tenant informado não existe.',
            'status.required'    => 'O status é obrigatório.',
            'status.in'          => 'O status deve ser uma opção válida.',
            'comment.string'     => 'O comentário deve ser uma string.',
            'comment.max'        => 'O comentário não pode ter mais de 1000 caracteres.',
            'reason.string'      => 'O motivo deve ser uma string.',
            'reason.max'         => 'O motivo não pode ter mais de 500 caracteres.',
            'reason.required_if' => 'O motivo é obrigatório para rejeição ou cancelamento.',
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
            'budget_id' => 'orçamento',
            'tenant_id' => 'tenant',
            'status'    => 'status do orçamento',
            'comment'   => 'comentário',
            'reason'    => 'motivo da alteração',
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
            'tenant_id' => Auth::user()?->tenant_id ?? $this->tenant_id,
        ] );
    }

}