<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Validação para alteração de status de serviço.
 * Migração das regras do sistema legacy app/request/ServiceChangeStatusFormRequest.php.
 * Inclui validação de transições de status válidas e isolamento de tenant.
 *
 * @package App\Http\Requests
 * @author IA
 */
class ServiceChangeStatusFormRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer este request.
     * Verifica acesso ao tenant do serviço relacionado.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $user = Auth::user();
        if ( !$user || !$user->tenant_id ) {
            return false;
        }

        $serviceId = $this->input( 'service_id' );
        if ( !$serviceId ) {
            return false;
        }

        // Verifica se o serviço pertence ao tenant do usuário
        return \Illuminate\Support\Facades\DB::table( 'services' )
            ->join( 'budgets', 'services.budget_id', '=', 'budgets.id' )
            ->where( 'services.id', $serviceId )
            ->where( 'budgets.tenant_id', $user->tenant_id )
            ->exists();
    }

    /**
     * Regras de validação para alteração de status de serviço.
     *
     * @return array<string, array|Rule|string>
     */
    public function rules(): array
    {
        return [ 
            'service_id'   => [ 
                'required',
                'integer',
                'exists:services,id'
            ],
            'tenant_id'    => [ 
                'required',
                'integer',
                'exists:tenants,id'
            ],
            'status'       => [ 
                'required',
                Rule::in( [ 'draft', 'active', 'completed', 'cancelled', 'invoiced', 'paid' ] )
            ],
            'comment'      => [ 
                'nullable',
                'string',
                'max:1000'
            ],
            'reason'       => [ 
                'nullable',
                'string',
                'max:500',
                'required_if:status,cancelled'
            ],
            'invoice_date' => [ 
                'nullable',
                'date',
                'after_or_equal:today',
                'required_if:status,invoiced'
            ],
            'payment_date' => [ 
                'nullable',
                'date',
                'after_or_equal:invoice_date',
                'required_if:status,paid'
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
            'service_id.required'         => 'O ID do serviço é obrigatório.',
            'service_id.integer'          => 'O ID do serviço deve ser um número inteiro.',
            'service_id.exists'           => 'O serviço informado não existe.',
            'tenant_id.required'          => 'O ID do tenant é obrigatório.',
            'tenant_id.integer'           => 'O ID do tenant deve ser um número inteiro.',
            'tenant_id.exists'            => 'O tenant informado não existe.',
            'status.required'             => 'O status é obrigatório.',
            'status.in'                   => 'O status deve ser uma opção válida.',
            'comment.string'              => 'O comentário deve ser uma string.',
            'comment.max'                 => 'O comentário não pode ter mais de 1000 caracteres.',
            'reason.string'               => 'O motivo deve ser uma string.',
            'reason.max'                  => 'O motivo não pode ter mais de 500 caracteres.',
            'reason.required_if'          => 'O motivo é obrigatório para cancelamento.',
            'invoice_date.date'           => 'A data de faturamento deve ser uma data válida.',
            'invoice_date.after_or_equal' => 'A data de faturamento não pode ser anterior à data atual.',
            'invoice_date.required_if'    => 'A data de faturamento é obrigatória para status faturado.',
            'payment_date.date'           => 'A data de pagamento deve ser uma data válida.',
            'payment_date.after_or_equal' => 'A data de pagamento deve ser igual ou posterior à data de faturamento.',
            'payment_date.required_if'    => 'A data de pagamento é obrigatória para status pago.',
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
            'service_id'   => 'serviço',
            'tenant_id'    => 'tenant',
            'status'       => 'status do serviço',
            'comment'      => 'comentário',
            'reason'       => 'motivo da alteração',
            'invoice_date' => 'data de faturamento',
            'payment_date' => 'data de pagamento',
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
            'tenant_id'  => Auth::user()?->tenant_id ?? $this->tenant_id,
            'service_id' => (int) $this->service_id,
        ] );
    }

}