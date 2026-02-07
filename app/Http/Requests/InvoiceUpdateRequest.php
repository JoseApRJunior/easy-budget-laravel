<?php

namespace App\Http\Requests;

use App\Enums\InvoiceStatus;
use Illuminate\Foundation\Http\FormRequest;

class InvoiceUpdateRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Desformatar valores monetários globais
        $monetaryFields = ['subtotal', 'discount', 'total'];
        foreach ($monetaryFields as $field) {
            if ($this->has($field)) {
                $this->merge([$field => \App\Helpers\CurrencyHelper::unformat($this->input($field))]);
            }
        }

        if ($this->has('items')) {
            $items = $this->items;
            foreach ($items as $key => $item) {
                if (isset($item['unit_value'])) {
                    $items[$key]['unit_value'] = \App\Helpers\CurrencyHelper::unformat($item['unit_value']);
                }
            }
            $this->merge(['items' => $items]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $invoiceId = $this->route('invoice'); // Assume que a rota tem um parâmetro 'invoice' com o ID da fatura

        return [
            'customer_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:customers,id',
            ],
            'issue_date' => 'sometimes|required|date',
            'due_date' => 'sometimes|required|date|after_or_equal:issue_date',
            'status' => [
                'sometimes',
                'required',
                'string',
                'in:'.implode(',', array_map(fn ($case) => $case->value, InvoiceStatus::cases())),
            ],
            'items' => 'sometimes|required|array|min:1',
            'items.*.id' => 'nullable|integer|exists:invoice_items,id',
            'items.*.product_id' => 'required_without:items.*.id|integer|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_value' => 'required|numeric|min:0.01',
            'items.*.action' => 'nullable|in:create,update,delete',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Cliente é obrigatório',
            'customer_id.exists' => 'Cliente não encontrado',
            'issue_date.required' => 'Data de emissão é obrigatória',
            'due_date.required' => 'Data de vencimento é obrigatória',
            'due_date.after_or_equal' => 'Data de vencimento deve ser igual ou posterior à data de emissão',
            'status.required' => 'Status é obrigatório',
            'status.in' => 'Status inválido selecionado',
            'items.required' => 'Itens da fatura são obrigatórios',
            'items.min' => 'Deve ter pelo menos 1 item',
            'items.*.product_id.required' => 'Produto é obrigatório',
            'items.*.product_id.exists' => 'Produto não encontrado',
            'items.*.quantity.min' => 'Quantidade deve ser maior que zero',
            'items.*.unit_value.min' => 'Valor unitário deve ser maior que zero',
            'items.*.action.in' => 'Ação inválida para item',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validar que pelo menos um item está sendo criado/atualizado (não apenas deletado)
            $items = $this->items;
            $hasValidItems = false;

            if (is_array($items)) {
                foreach ($items as $item) {
                    if (($item['action'] ?? 'create') !== 'delete') {
                        $hasValidItems = true;
                        break;
                    }
                }
            } else {
                // Se 'items' não for um array, significa que não foi fornecido ou é inválido,
                // e a validação 'required|array|min:1' já deve ter falhado.
                // Se for 'sometimes', então não é obrigatório.
                $hasValidItems = true; // Se não foi fornecido, não precisamos validar itens ativos
            }

            if (! $hasValidItems && isset($this->items)) { // Apenas se 'items' foi fornecido e não tem itens válidos
                $validator->errors()->add('items', 'Deve ter pelo menos 1 item ativo');
            }
        });
    }
}
