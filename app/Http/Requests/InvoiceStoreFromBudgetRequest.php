<?php

namespace App\Http\Requests;

use App\Enums\InvoiceStatus;
use Illuminate\Foundation\Http\FormRequest;

class InvoiceStoreFromBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'budget_code' => 'required|string|exists:budgets,code',
            'service_id' => 'required|integer|exists:services,id',
            'due_date' => 'required|date',
            'status' => 'required|string|in:'.implode(',', array_map(fn ($c) => $c->value, InvoiceStatus::cases())),
            'discount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.service_item_id' => 'required|integer|exists:service_items,id',
            'items.*.quantity' => 'nullable|numeric|min:0.01',
            'items.*.unit_value' => 'nullable|numeric|min:0.01',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('discount')) {
            $this->merge([
                'discount' => \App\Helpers\CurrencyHelper::unformat($this->discount),
            ]);
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
}
