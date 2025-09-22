<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Validação para criação e atualização de faturas.
 * Migração das regras do sistema legacy app/request/InvoiceFormRequest.php.
 * Inclui validação de relacionamentos com serviço e cliente, além de cálculos.
 *
 * @package App\Http\Requests
 * @author IA
 */
class InvoiceFormRequest extends FormRequest
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
     * Regras de validação para criação/atualização de fatura.
     *
     * @return array<string, array|Rule|string>
     */
    public function rules(): array
    {
        $tenantId = Auth::user()?->tenant_id;

        return [ 
            'service_id'     => [ 
                'required',
                'integer',
                'exists:services,id'
            ],
            'customer_id'    => [ 
                'required',
                'integer',
                'exists:customers,id'
            ],
            'code'           => [ 
                'required',
                'string',
                'max:50',
                Rule::unique( 'invoices', 'code' )
                    ->where( function ($query) use ($tenantId) {
                        return $query->where( 'tenant_id', $tenantId );
                    } )
                    ->ignore( $this->invoice )
            ],
            'subtotal'       => [ 
                'required',
                'numeric',
                'min:0.01'
            ],
            'total'          => [ 
                'required',
                'numeric',
                'min:0.01',
                'gte:subtotal'
            ],
            'due_date'       => [ 
                'required',
                'date',
                'after_or_equal:today'
            ],
            'payment_method' => [ 
                'required',
                Rule::in( [ 'cash', 'credit_card', 'debit_card', 'bank_transfer', 'pix', 'boleto' ] )
            ],
            'discount'       => [ 
                'nullable',
                'numeric',
                'min:0',
                'max:100'
            ],
            'notes'          => [ 
                'nullable',
                'string',
                'max:1000'
            ],
            'status'         => [ 
                'nullable',
                Rule::in( [ 'draft', 'sent', 'paid', 'overdue', 'cancelled' ] )
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
            'service_id.required'     => 'O serviço é obrigatório.',
            'service_id.integer'      => 'O ID do serviço deve ser um número inteiro.',
            'service_id.exists'       => 'O serviço selecionado não existe.',
            'customer_id.required'    => 'O cliente é obrigatório.',
            'customer_id.integer'     => 'O ID do cliente deve ser um número inteiro.',
            'customer_id.exists'      => 'O cliente selecionado não existe.',
            'code.required'           => 'O código da fatura é obrigatório.',
            'code.string'             => 'O código da fatura deve ser uma string.',
            'code.max'                => 'O código da fatura não pode ter mais de 50 caracteres.',
            'code.unique'             => 'Este código de fatura já está sendo usado.',
            'subtotal.required'       => 'O subtotal é obrigatório.',
            'subtotal.numeric'        => 'O subtotal deve ser um número.',
            'subtotal.min'            => 'O subtotal deve ser maior que zero.',
            'total.required'          => 'O total é obrigatório.',
            'total.numeric'           => 'O total deve ser um número.',
            'total.min'               => 'O total deve ser maior que zero.',
            'total.gte'               => 'O total deve ser maior ou igual ao subtotal.',
            'due_date.required'       => 'A data de vencimento é obrigatória.',
            'due_date.date'           => 'A data de vencimento deve ser uma data válida.',
            'due_date.after_or_equal' => 'A data de vencimento não pode ser anterior à data atual.',
            'payment_method.required' => 'O método de pagamento é obrigatório.',
            'payment_method.in'       => 'O método de pagamento deve ser uma opção válida.',
            'discount.numeric'        => 'O desconto deve ser um número.',
            'discount.min'            => 'O desconto não pode ser negativo.',
            'discount.max'            => 'O desconto não pode ser superior a 100%.',
            'notes.string'            => 'As observações devem ser uma string.',
            'notes.max'               => 'As observações não podem ter mais de 1000 caracteres.',
            'status.in'               => 'O status da fatura deve ser uma opção válida.',
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
            'service_id'     => 'serviço',
            'customer_id'    => 'cliente',
            'code'           => 'código da fatura',
            'subtotal'       => 'subtotal',
            'total'          => 'total',
            'due_date'       => 'data de vencimento',
            'payment_method' => 'método de pagamento',
            'discount'       => 'desconto',
            'notes'          => 'observações',
            'status'         => 'status da fatura',
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
            'tenant_id'   => Auth::user()?->tenant_id,
            'service_id'  => (int) $this->service_id,
            'customer_id' => (int) $this->customer_id,
            'subtotal'    => (float) $this->subtotal,
            'total'       => (float) $this->total,
            'discount'    => $this->discount ? (float) $this->discount : null,
        ] );

        // Validação de negócio: total deve ser subtotal menos desconto
        if ( $this->subtotal && $this->discount !== null ) {
            $calculatedTotal = $this->subtotal * ( 1 - $this->discount / 100 );
            $this->merge( [ 'calculated_total' => round( $calculatedTotal, 2 ) ] );
        }
    }

    /**
     * Validar regras de negócio personalizadas após a validação básica.
     *
     * @return array<string, array>
     */
    public function withValidator( $validator ): void
    {
        $validator->after( function ($validator) {
            // Verificar se o total corresponde ao cálculo (subtotal - desconto)
            if ( $this->subtotal && $this->discount !== null ) {
                $calculatedTotal = $this->subtotal * ( 1 - $this->discount / 100 );
                $inputTotal      = (float) $this->total;

                if ( abs( $calculatedTotal - $inputTotal ) > 0.01 ) {
                    $validator->errors()->add( 'total', 'O total não corresponde ao cálculo do subtotal menos o desconto.' );
                }
            }

            // Verificar se o cliente pertence ao mesmo tenant
            if ( $this->customer_id ) {
                $customerTenant = \Illuminate\Support\Facades\DB::table( 'customers' )
                    ->join( 'common_data', 'customers.common_data_id', '=', 'common_data.id' )
                    ->where( 'customers.id', $this->customer_id )
                    ->value( 'common_data.tenant_id' );

                if ( $customerTenant !== Auth::user()?->tenant_id ) {
                    $validator->errors()->add( 'customer_id', 'O cliente selecionado não pertence ao seu tenant.' );
                }
            }
        } );
    }

}