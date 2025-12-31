<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\BudgetStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request para atualização de orçamentos
 */
class BudgetUpdateRequest extends FormRequest
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
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')->where('tenant_id', $tenantId),
            ],
            'status' => [
                'nullable',
                Rule::in(array_column(BudgetStatus::cases(), 'value')),
            ],
            'total' => 'required|numeric|min:0|max:9999999.99',
            'discount' => 'nullable|numeric|min:0|max:999999.99',
            'description' => 'nullable|string|max:1000',
            'due_date' => 'nullable|date',
            'payment_terms' => 'nullable|string|max:2000',
            'items' => 'nullable|array',
            'services' => 'nullable|array',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'O cliente é obrigatório.',
            'customer_id.exists' => 'Cliente não encontrado.',
            'total.required' => 'O valor total é obrigatório.',
            'total.numeric' => 'O valor total deve ser um número.',
            'discount.numeric' => 'O desconto deve ser um valor numérico.',
            'discount.min' => 'O desconto não pode ser negativo.',
            'description.max' => 'A descrição não pode ter mais de 1000 caracteres.',
            'payment_terms.max' => 'Os termos de pagamento não podem ter mais de 2000 caracteres.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'customer_id' => 'cliente',
            'status' => 'status',
            'discount' => 'desconto',
            'description' => 'descrição',
            'due_date' => 'data de vencimento',
            'payment_terms' => 'termos de pagamento',
            'items' => 'itens',
            'items.*.product_id' => 'produto',
            'items.*.unit_value' => 'valor unitário',
            'items.*.quantity' => 'quantidade',
            'items.*.total' => 'total',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Converte valores vazios para null
        if ($this->discount === '') {
            $this->merge(['discount' => null]);
        }
        if ($this->due_date === '') {
            $this->merge(['due_date' => null]);
        }
        if ($this->description === '') {
            $this->merge(['description' => null]);
        }
        if ($this->payment_terms === '') {
            $this->merge(['payment_terms' => null]);
        }
    }
}
