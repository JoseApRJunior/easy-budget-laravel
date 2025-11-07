<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\BudgetStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request para criação de orçamentos
 */
class BudgetStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $tenantId = auth()->user()->tenant_id ?? 0;

        return [
            'customer_id'        => [
                'required',
                'integer',
                Rule::exists( 'customers', 'id' )->where( 'tenant_id', $tenantId ),
            ],
            'status'             => [
                'nullable',
                Rule::in( array_column( BudgetStatus::cases(), 'value' ) ),
            ],
            'discount'           => 'nullable|numeric|min:0|max:999999.99',
            'description'        => 'nullable|string|max:1000',
            'due_date'           => 'nullable|date|after:today',
            'payment_terms'      => 'nullable|string|max:2000',
            'items'              => 'nullable|array|min:1',
            'items.*.product_id' => [
                'required_with:items',
                'integer',
                Rule::exists( 'products', 'id' )->where( 'tenant_id', $tenantId ),
            ],
            'items.*.unit_value' => 'required_with:items|numeric|min:0|max:999999.99',
            'items.*.quantity'   => 'required_with:items|integer|min:1|max:9999',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'customer_id.required'             => 'O cliente é obrigatório.',
            'customer_id.exists'               => 'Cliente não encontrado.',

            'status.in'                        => 'Status inválido.',
            'discount.numeric'                 => 'O desconto deve ser um valor numérico.',
            'discount.min'                     => 'O desconto não pode ser negativo.',
            'discount.max'                     => 'O desconto não pode ser maior que R$ 999.999,99.',
            'description.max'                  => 'A descrição não pode ter mais de 1000 caracteres.',
            'due_date.after'                   => 'A data de vencimento deve ser futura.',
            'payment_terms.max'                => 'Os termos de pagamento não podem ter mais de 2000 caracteres.',
            'items.required'                   => 'Pelo menos um item deve ser adicionado.',
            'items.*.product_id.required_with' => 'O produto é obrigatório para cada item.',
            'items.*.product_id.exists'        => 'Produto não encontrado.',
            'items.*.unit_value.required_with' => 'O valor unitário é obrigatório para cada item.',
            'items.*.unit_value.numeric'       => 'O valor unitário deve ser numérico.',
            'items.*.unit_value.min'           => 'O valor unitário não pode ser negativo.',
            'items.*.unit_value.max'           => 'O valor unitário não pode ser maior que R$ 999.999,99.',
            'items.*.quantity.required_with'   => 'A quantidade é obrigatória para cada item.',
            'items.*.quantity.integer'         => 'A quantidade deve ser um número inteiro.',
            'items.*.quantity.min'             => 'A quantidade deve ser pelo menos 1.',
            'items.*.quantity.max'             => 'A quantidade não pode ser maior que 9999.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'customer_id'        => 'cliente',
            'status'             => 'status',
            'discount'           => 'desconto',
            'description'        => 'descrição',
            'due_date'           => 'data de vencimento',
            'payment_terms'      => 'termos de pagamento',
            'items'              => 'itens',
            'items.*.product_id' => 'produto',
            'items.*.unit_value' => 'valor unitário',
            'items.*.quantity'   => 'quantidade',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Converte valores vazios para null
        if ( $this->discount === '' ) {
            $this->merge( [ 'discount' => null ] );
        }
        if ( $this->due_date === '' ) {
            $this->merge( [ 'due_date' => null ] );
        }
        if ( $this->description === '' ) {
            $this->merge( [ 'description' => null ] );
        }
        if ( $this->payment_terms === '' ) {
            $this->merge( [ 'payment_terms' => null ] );
        }
    }

}
