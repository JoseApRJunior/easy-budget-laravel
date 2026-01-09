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
     * Get custom validator for budget status checks.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Get the budget ID from the route
            $budgetId = $this->route('budget')?->id ?? $this->route('id');

            if (! $budgetId) {
                return;
            }

            // Fetch the budget with its tenant scope
            $budget = \App\Models\Budget::where('id', $budgetId)
                ->where('tenant_id', auth()->user()->tenant_id)
                ->first();

            if (! $budget) {
                $validator->errors()->add('budget', 'Orçamento não encontrado.');

                return;
            }

            // Block edits on non-DRAFT budgets
            if ($budget->status !== \App\Enums\BudgetStatus::DRAFT->value) {
                $validator->errors()->add('status', 'Orçamentos enviados (pendentes), aprovados ou concluídos não podem ser editados. Para alterar, crie um novo orçamento ou rejeite o atual.');
            }
        });
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
                Rule::in(BudgetStatus::values()),
            ],
            'total' => 'required|numeric|min:0|max:9999999.99',
            'discount' => 'nullable|numeric|min:0|max:999999.99',
            'description' => 'nullable|string|max:1000',
            'due_date' => 'nullable|date|after_or_equal:today',
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
            'due_date.date' => 'A data de vencimento deve ser uma data válida.',
            'due_date.after_or_equal' => 'A data de vencimento deve ser hoje ou uma data posterior.',
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
        // Unformat currency values
        if ($this->has('discount')) {
            $this->merge([
                'discount' => \App\Helpers\CurrencyHelper::unformat($this->discount),
            ]);
        }
        if ($this->has('total')) {
            $this->merge([
                'total' => \App\Helpers\CurrencyHelper::unformat($this->total),
            ]);
        }

        // Unformat item values
        if ($this->has('items')) {
            $items = $this->items;
            foreach ($items as $key => $item) {
                if (isset($item['unit_value'])) {
                    $items[$key]['unit_value'] = \App\Helpers\CurrencyHelper::unformat($item['unit_value']);
                }
                if (isset($item['total'])) {
                    $items[$key]['total'] = \App\Helpers\CurrencyHelper::unformat($item['total']);
                }
            }
            $this->merge(['items' => $items]);
        }

        // Converte valores vazios para null
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
