<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BudgetFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $budgetId = $this->route( 'budget' ) ? $this->route( 'budget' )->id : null;

        return [ 
            'title'                  => 'required|string|max:255',
            'description'            => 'nullable|string|max:1000',
            'customer_id'            => 'required|integer|exists:customers,id',
            'status'                 => 'required|string|in:draft,sent,approved,rejected,completed,cancelled',
            'valid_until'            => 'nullable|date|after:today',
            'notes'                  => 'nullable|string|max:2000',
            'total_value'            => 'nullable|numeric|min:0',
            'services'               => 'required|array|min:1',
            'services.*.service_id'  => 'required|integer|exists:services,id',
            'services.*.quantity'    => 'required|integer|min:1',
            'services.*.unit_price'  => 'required|numeric|min:0',
            'services.*.description' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [ 
            'title.required'                 => 'O título do orçamento é obrigatório.',
            'title.string'                   => 'O título deve ser uma string.',
            'title.max'                      => 'O título não pode ter mais de 255 caracteres.',

            'description.string'             => 'A descrição deve ser uma string.',
            'description.max'                => 'A descrição não pode ter mais de 1000 caracteres.',

            'customer_id.required'           => 'O cliente é obrigatório.',
            'customer_id.integer'            => 'O ID do cliente deve ser um número inteiro.',
            'customer_id.exists'             => 'O cliente selecionado não existe.',

            'status.required'                => 'O status é obrigatório.',
            'status.string'                  => 'O status deve ser uma string.',
            'status.in'                      => 'O status deve ser um dos valores permitidos.',

            'valid_until.date'               => 'A data de validade deve ser uma data válida.',
            'valid_until.after'              => 'A data de validade deve ser posterior à data atual.',

            'notes.string'                   => 'As observações devem ser uma string.',
            'notes.max'                      => 'As observações não podem ter mais de 2000 caracteres.',

            'total_value.numeric'            => 'O valor total deve ser um número.',
            'total_value.min'                => 'O valor total deve ser maior ou igual a zero.',

            'services.required'              => 'Pelo menos um serviço deve ser adicionado.',
            'services.array'                 => 'Os serviços devem ser um array.',
            'services.min'                   => 'Pelo menos um serviço deve ser adicionado.',

            'services.*.service_id.required' => 'O ID do serviço é obrigatório.',
            'services.*.service_id.integer'  => 'O ID do serviço deve ser um número inteiro.',
            'services.*.service_id.exists'   => 'O serviço selecionado não existe.',

            'services.*.quantity.required'   => 'A quantidade é obrigatória.',
            'services.*.quantity.integer'    => 'A quantidade deve ser um número inteiro.',
            'services.*.quantity.min'        => 'A quantidade deve ser pelo menos 1.',

            'services.*.unit_price.required' => 'O preço unitário é obrigatório.',
            'services.*.unit_price.numeric'  => 'O preço unitário deve ser um número.',
            'services.*.unit_price.min'      => 'O preço unitário deve ser maior ou igual a zero.',

            'services.*.description.string'  => 'A descrição do serviço deve ser uma string.',
            'services.*.description.max'     => 'A descrição do serviço não pode ter mais de 500 caracteres.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [ 
            'title'                  => 'título',
            'description'            => 'descrição',
            'customer_id'            => 'cliente',
            'status'                 => 'status',
            'valid_until'            => 'data de validade',
            'notes'                  => 'observações',
            'total_value'            => 'valor total',
            'services'               => 'serviços',
            'services.*.service_id'  => 'ID do serviço',
            'services.*.quantity'    => 'quantidade',
            'services.*.unit_price'  => 'preço unitário',
            'services.*.description' => 'descrição do serviço',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Calcular o valor total se não foi fornecido
        if ( !$this->has( 'total_value' ) && $this->has( 'services' ) ) {
            $total = 0;
            foreach ( $this->services as $service ) {
                if ( isset( $service[ 'quantity' ] ) && isset( $service[ 'unit_price' ] ) ) {
                    $total += $service[ 'quantity' ] * $service[ 'unit_price' ];
                }
            }
            $this->merge( [ 'total_value' => $total ] );
        }
    }

}