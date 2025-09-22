<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Validação para criação e atualização de serviços.
 * Migração das regras do sistema legacy app/request/ServiceFormRequest.php.
 * Inclui validação de relacionamento com orçamento, categoria e tenant.
 *
 * @package App\Http\Requests
 * @author IA
 */
class ServiceFormRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer este request.
     * Verifica acesso ao tenant do orçamento relacionado.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $user = Auth::user();
        if ( !$user || !$user->tenant_id ) {
            return false;
        }

        $budgetId = $this->input( 'budget_id' );
        if ( !$budgetId ) {
            return false;
        }

        // Verifica se o orçamento pertence ao tenant do usuário
        return \Illuminate\Support\Facades\DB::table( 'budgets' )
            ->where( 'id', $budgetId )
            ->where( 'tenant_id', $user->tenant_id )
            ->exists();
    }

    /**
     * Regras de validação para criação/atualização de serviço.
     *
     * @return array<string, array|Rule|string>
     */
    public function rules(): array
    {
        return [ 
            'budget_id'   => [ 
                'required',
                'integer',
                'exists:budgets,id'
            ],
            'category_id' => [ 
                'required',
                'integer',
                'exists:categories,id'
            ],
            'description' => [ 
                'required',
                'string',
                'max:1000'
            ],
            'code'        => [ 
                'nullable',
                'string',
                'max:50',
                Rule::unique( 'services' )->where( function ($query) {
                    return $query->where( 'budget_id', $this->budget_id );
                } )->ignore( $this->service )
            ],
            'discount'    => [ 
                'nullable',
                'numeric',
                'min:0',
                'max:100'
            ],
            'total'       => [ 
                'required',
                'numeric',
                'min:0.01'
            ],
            'due_date'    => [ 
                'required',
                'date',
                'after_or_equal:today'
            ],
            'notes'       => [ 
                'nullable',
                'string',
                'max:500'
            ],
            'is_active'   => [ 
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
            'budget_id.required'      => 'O orçamento é obrigatório.',
            'budget_id.integer'       => 'O ID do orçamento deve ser um número inteiro.',
            'budget_id.exists'        => 'O orçamento selecionado não existe.',
            'category_id.required'    => 'A categoria é obrigatória.',
            'category_id.integer'     => 'O ID da categoria deve ser um número inteiro.',
            'category_id.exists'      => 'A categoria selecionada não existe.',
            'description.required'    => 'A descrição do serviço é obrigatória.',
            'description.string'      => 'A descrição deve ser uma string.',
            'description.max'         => 'A descrição não pode ter mais de 1000 caracteres.',
            'code.string'             => 'O código deve ser uma string.',
            'code.max'                => 'O código não pode ter mais de 50 caracteres.',
            'code.unique'             => 'Este código já está sendo usado em outro serviço deste orçamento.',
            'discount.numeric'        => 'O desconto deve ser um número.',
            'discount.min'            => 'O desconto não pode ser negativo.',
            'discount.max'            => 'O desconto não pode ser superior a 100%.',
            'total.required'          => 'O valor total é obrigatório.',
            'total.numeric'           => 'O valor total deve ser um número.',
            'total.min'               => 'O valor total deve ser maior que zero.',
            'due_date.required'       => 'A data de vencimento é obrigatória.',
            'due_date.date'           => 'A data de vencimento deve ser uma data válida.',
            'due_date.after_or_equal' => 'A data de vencimento não pode ser anterior à data atual.',
            'notes.string'            => 'As observações devem ser uma string.',
            'notes.max'               => 'As observações não podem ter mais de 500 caracteres.',
            'is_active.boolean'       => 'O status de ativo deve ser verdadeiro ou falso.',
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
            'budget_id'   => 'orçamento',
            'category_id' => 'categoria',
            'description' => 'descrição do serviço',
            'code'        => 'código do serviço',
            'discount'    => 'desconto',
            'total'       => 'valor total',
            'due_date'    => 'data de vencimento',
            'notes'       => 'observações',
            'is_active'   => 'status ativo',
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
            'budget_id'   => (int) $this->budget_id,
            'category_id' => (int) $this->category_id,
            'is_active'   => (bool) $this->is_active,
            'discount'    => $this->discount ? (float) $this->discount : null,
            'total'       => (float) $this->total,
        ] );
    }

}