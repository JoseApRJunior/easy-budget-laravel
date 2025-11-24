<?php

namespace App\Http\Requests;

use App\Enums\ServiceStatus;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class ServiceStoreRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer esta requisição.
     */
    public function authorize(): bool
    {
        return \Illuminate\Support\Facades\Auth::check();
    }

    /**
     * Retorna as regras de validação que se aplicam à requisição.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'budget_id'          => [
                'required',
                'integer',
                'exists:budgets,id'
            ],
            'category_id'        => [
                'required',
                'integer',
                'exists:categories,id'
            ],
            'service_statuses_id' => [
                'required',
                'string',
                'in:' . implode(',', array_map(fn($case) => $case->value, ServiceStatus::cases()))
            ],
            'discount'           => 'nullable|numeric|min:0',
            'description'        => 'nullable|string|max:1000',
            'due_date'           => 'nullable|date|after_or_equal:today',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity'   => 'required|numeric|min:0.01',
            'items.*.unit_value' => 'required|numeric|min:0.01'
        ];
    }

    /**
     * Retorna as mensagens de erro customizadas para validador.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'budget_id.required'          => 'Orçamento é obrigatório',
            'budget_id.exists'            => 'Orçamento não encontrado',
            'category_id.required'        => 'Categoria é obrigatória',
            'category_id.exists'          => 'Categoria não encontrada',
            'service_statuses_id.required' => 'Status é obrigatório',
            'service_statuses_id.in'      => 'Status inválido selecionado',
            'description.max'             => 'Descrição não pode exceder 1000 caracteres',
            'due_date.after_or_equal'     => 'Data de vencimento deve ser hoje ou posterior',
            'items.required'              => 'Itens do serviço são obrigatórios',
            'items.min'                   => 'Deve ter pelo menos 1 item',
            'items.*.product_id.required' => 'Produto é obrigatório em cada item',
            'items.*.product_id.exists'   => 'Produto não encontrado',
            'items.*.quantity.min'        => 'Quantidade deve ser maior que zero',
            'items.*.unit_value.min'      => 'Valor unitário deve ser maior que zero'
        ];
    }

    /**
     * Retorna os dados validados e processados para uso no service.
     *
     * @return array<string, mixed>
     */
    public function getValidatedData(): array
    {
        $data = parent::validated();
        $data['status'] = $data['service_statuses_id'] ?? ServiceStatus::DRAFT->value;
        unset($data['service_statuses_id']);

        return $data;
    }

    /**
     * Normaliza dados antes da validação (datas e valores mascarados).
     */
    protected function prepareForValidation(): void
    {
        // due_date: converter dd/mm/aaaa para yyyy-mm-dd e tratar vazio
        if (isset($this->due_date)) {
            $due = trim((string) $this->due_date);
            if ($due === '') {
                $this->merge(['due_date' => null]);
            } else {
                if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $due, $m)) {
                    $iso = $m[3] . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT);
                    $this->merge(['due_date' => $iso]);
                }
            }
        }

        // Normalizar itens: remover máscara BRL de unit_value e garantir número
        if (is_array($this->items ?? null)) {
            $items = $this->items;
            foreach ($items as $i => $item) {
                if (isset($item['unit_value'])) {
                    $digits = preg_replace('/\D/', '', (string) $item['unit_value']);
                    $num = ((int) ($digits ?: '0')) / 100;
                    $items[$i]['unit_value'] = $num;
                }
                if (isset($item['quantity'])) {
                    $items[$i]['quantity'] = (float) $item['quantity'];
                }
            }
            $this->merge(['items' => $items]);
        }

        if (isset($this->discount)) {
            $digits = preg_replace('/\D/', '', (string) $this->discount);
            $num    = ((int) ($digits ?: '0')) / 100;
            $this->merge(['discount' => $num]);
        }
    }
}
