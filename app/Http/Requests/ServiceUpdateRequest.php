<?php

namespace App\Http\Requests;

use App\Enums\ServiceStatus;
use App\Models\ServiceItem;
use Illuminate\Foundation\Http\FormRequest;

class ServiceUpdateRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer esta requisição.
     */
    public function authorize(): bool
    {
        return \Illuminate\Support\Facades\Auth::check();
    }

    /**
     * Prepara os dados para validação.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'discount' => \App\Helpers\CurrencyHelper::unformat($this->discount),
        ]);

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

    /**
     * Retorna as regras de validação que se aplicam à requisição.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => [
                'required',
                'integer',
                'exists:categories,id',
            ],
            'status' => [
                'required',
                'string',
                'in:'.implode(',', array_map(fn ($case) => $case->value, ServiceStatus::cases())),
            ],
            'discount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'due_date' => 'nullable|date|after_or_equal:today',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|integer|exists:service_items,id',
            'items.*.product_id' => 'required_without:items.*.id|integer|exists:products,id',
            'items.*.quantity' => 'required_unless:items.*.action,delete|numeric|min:0.01',
            'items.*.unit_value' => 'required_unless:items.*.action,delete|numeric|min:0.01',
            'items.*.action' => 'nullable|in:create,update,delete',
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
            'category_id.required' => 'Categoria é obrigatória',
            'category_id.exists' => 'Categoria não encontrada',
            'status.required' => 'Status é obrigatório',
            'status.in' => 'Status inválido selecionado',
            'description.max' => 'Descrição não pode exceder 1000 caracteres',
            'due_date.after_or_equal' => 'Data de vencimento deve ser hoje ou posterior',
            'items.required' => 'Itens do serviço são obrigatórios',
            'items.min' => 'Deve ter pelo menos 1 item',
            'items.*.id.exists' => 'Item não encontrado',
            'items.*.product_id.required' => 'Produto é obrigatório',
            'items.*.product_id.exists' => 'Produto não encontrado',
            'items.*.quantity.min' => 'Quantidade deve ser maior que zero',
            'items.*.unit_value.min' => 'Valor unitário deve ser maior que zero',
            'items.*.action.in' => 'Ação inválida para item',
        ];
    }

    /**
     * Validações adicionais após a validação básica.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validar que pelo menos um item está sendo criado/atualizado (não apenas deletado)
            $items = $this->items;
            $hasValidItems = false;

            foreach ($items as $index => $item) {
                if (($item['action'] ?? 'create') !== 'delete') {
                    $hasValidItems = true;
                    break;
                }
            }

            if (! $hasValidItems) {
                $validator->errors()->add('items', 'Deve ter pelo menos 1 item ativo');
            }

            // Validar unicidade de product_id quando não está deletando
            $productIds = [];
            foreach ($items as $index => $item) {
                if (($item['action'] ?? 'create') !== 'delete' && isset($item['product_id'])) {
                    $productId = $item['product_id'];
                    if (in_array($productId, $productIds)) {
                        $validator->errors()->add(
                            "items.{$index}.product_id",
                            'Produto duplicado no serviço',
                        );
                    }
                    $productIds[] = $productId;
                }
            }

            // Validar que items com ID existem e pertencem ao tenant atual
            foreach ($items as $index => $item) {
                if (isset($item['id']) && $item['id']) {
                    $serviceItem = ServiceItem::find($item['id']);
                    if (! $serviceItem) {
                        $validator->errors()->add(
                            "items.{$index}.id",
                            'Item não encontrado',
                        );
                    } elseif ($serviceItem->tenant_id !== (auth()->user()->tenant_id ?? null)) {
                        $validator->errors()->add(
                            "items.{$index}.id",
                            'Item não pertence à sua empresa',
                        );
                    }
                }
            }
        });
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
                    $iso = $m[3].'-'.str_pad($m[2], 2, '0', STR_PAD_LEFT).'-'.str_pad($m[1], 2, '0', STR_PAD_LEFT);
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

        // Normalizar campos monetários do serviço
        foreach (['discount', 'total'] as $moneyField) {
            if (isset($this->$moneyField)) {
                $digits = preg_replace('/\D/', '', (string) $this->$moneyField);
                $num = ((int) ($digits ?: '0')) / 100;
                $this->merge([$moneyField => $num]);
            }
        }
    }

    /**
     * Retorna os dados validados e processados para uso no service.
     *
     * @return array<string, mixed>
     */
    public function getValidatedData(): array
    {
        $data = parent::validated();

        // Separar items por ação para facilitar processamento no service
        $data['items_to_create'] = [];
        $data['items_to_update'] = [];
        $data['items_to_delete'] = [];

        foreach ($data['items'] as $item) {
            $action = $item['action'] ?? 'create';

            if ($action === 'delete' && isset($item['id'])) {
                $data['items_to_delete'][] = $item['id'];
            } elseif ($action === 'update' && isset($item['id'])) {
                $data['items_to_update'][] = [
                    'id' => $item['id'],
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_value' => $item['unit_value'],
                ];
            } else { // create
                $data['items_to_create'][] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_value' => $item['unit_value'],
                ];
            }
        }

        // Remover items do array principal pois já foram processados
        unset($data['items']);

        return $data;
    }
}
