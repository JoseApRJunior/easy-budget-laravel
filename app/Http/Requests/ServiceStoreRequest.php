<?php

namespace App\Http\Requests;

use App\Enums\ServiceStatusEnum;
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
            'budget_code'        => [
                'required',
                'string',
                'exists:budgets,code'
            ],
            'category_id'        => [
                'required',
                'integer',
                'exists:categories,id'
            ],
            'status'             => [
                'required',
                'string',
                'in:' . implode( ',', array_map( fn( $case ) => $case->value, ServiceStatusEnum::cases() ) )
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
            'budget_code.required'        => 'Código do orçamento é obrigatório',
            'budget_code.exists'          => 'Orçamento não encontrado',
            'category_id.required'        => 'Categoria é obrigatória',
            'category_id.exists'          => 'Categoria não encontrada',
            'status.required'             => 'Status é obrigatório',
            'status.in'                   => 'Status inválido selecionado',
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

        // Buscar budget_id pelo código
        $budget              = Budget::where( 'code', $data[ 'budget_code' ] )->first();
        $data[ 'budget_id' ] = $budget->id;
        unset( $data[ 'budget_code' ] );

        return $data;
    }

}
