<?php

namespace App\Http\Requests;

use App\Enums\InvoiceStatus;
use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;

class InvoiceStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_code' => [
                'required',
                'string',
                'exists:services,code',
            ],
            'service_id' => [
                'nullable',
                'integer',
                'exists:services,id',
            ],
            'customer_id' => [
                'required',
                'integer',
                'exists:customers,id',
            ],
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'status' => [
                'required',
                'string',
                'in:'.implode(',', array_map(fn ($case) => $case->value, InvoiceStatus::cases())),
            ],
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_value' => 'required|numeric|min:0.01',
        ];
    }

    public function messages(): array
    {
        return [
            'service_code.required' => 'Código do serviço é obrigatório',
            'service_code.exists' => 'Serviço não encontrado',
            'customer_id.required' => 'Cliente é obrigatório',
            'customer_id.exists' => 'Cliente não encontrado',
            'issue_date.required' => 'Data de emissão é obrigatória',
            'due_date.required' => 'Data de vencimento é obrigatória',
            'due_date.after_or_equal' => 'Data de vencimento deve ser igual ou posterior à data de emissão',
            'status.required' => 'Status é obrigatório',
            'status.in' => 'Status inválido selecionado',
            'items.required' => 'Itens da fatura são obrigatórios',
            'items.min' => 'Deve ter pelo menos 1 item',
            'items.*.product_id.required' => 'Produto é obrigatório em cada item',
            'items.*.product_id.exists' => 'Produto não encontrado',
            'items.*.quantity.min' => 'Quantidade deve ser maior que zero',
            'items.*.unit_value.min' => 'Valor unitário deve ser maior que zero',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Buscar service_id pelo código
        if ($this->has('service_code')) {
            $service = Service::where('code', $this->service_code)->first();
            if ($service) {
                $this->merge(['service_id' => $service->id]);
            }
        }

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
}
