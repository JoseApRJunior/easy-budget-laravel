<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request de validação para criação de planos
 */
class PlanStoreRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'slug'        => [
                'required', 'string', 'max:50',
                \Illuminate\Validation\Rule::unique( 'plans' )
            ],
            'description' => 'nullable|string|max:500',
            'price'       => 'required|numeric|min:0',
            'status'      => 'boolean',
            'features'    => 'nullable|array',
            'max_budgets' => 'required|integer|min:0',
            'max_clients' => 'required|integer|min:0'
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
            'name.required'        => 'O nome é obrigatório.',
            'slug.unique'          => 'O slug informado já está em uso.',
            'price.required'       => 'O preço é obrigatório.',
            'price.numeric'        => 'O preço deve ser numérico.',
            'price.min'            => 'O preço deve ser no mínimo 0.',
            'max_budgets.required' => 'O máximo de orçamentos é obrigatório.',
            'max_clients.required' => 'O máximo de clientes é obrigatório.'
        ];
    }

}
