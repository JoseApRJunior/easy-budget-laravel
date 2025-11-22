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
                'in:' . implode( ',', array_map( fn( $case ) => $case->value, ServiceStatus::cases() ) )
            ],
            'description'        => 'nullable|string|max:1000',
            'due_date'           => 'nullable|date|after:today',
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
            'service_statuses_id.required'=> 'Status é obrigatório',
            'service_statuses_id.in'      => 'Status inválido selecionado',
            'description.max'             => 'Descrição não pode exceder 1000 caracteres',
            'due_date.after'              => 'Data de vencimento deve ser posterior a hoje',
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

}
