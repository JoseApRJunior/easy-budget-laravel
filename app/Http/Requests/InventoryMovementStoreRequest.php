<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InventoryMovementStoreRequest extends FormRequest
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
        return [
            'type' => 'required|in:in,out',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|min:5|max:500',
            'reference_id' => 'nullable|integer',
            'reference_type' => 'nullable|string|max:50',
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
            'type.required' => 'O tipo de movimento é obrigatório.',
            'type.in' => 'O tipo de movimento deve ser "in" (entrada) ou "out" (saída).',
            'quantity.required' => 'A quantidade é obrigatória.',
            'quantity.integer' => 'A quantidade deve ser um número inteiro.',
            'quantity.min' => 'A quantidade deve ser no mínimo 1.',
            'reason.required' => 'O motivo do ajuste é obrigatório.',
            'reason.string' => 'O motivo deve ser um texto.',
            'reason.min' => 'O motivo deve ter no mínimo 5 caracteres.',
            'reason.max' => 'O motivo deve ter no máximo 500 caracteres.',
            'reference_id.integer' => 'A referência ID deve ser um número inteiro.',
            'reference_type.string' => 'O tipo de referência deve ser um texto.',
            'reference_type.max' => 'O tipo de referência deve ter no máximo 50 caracteres.',
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
            'type' => 'tipo de movimento',
            'quantity' => 'quantidade',
            'reason' => 'motivo',
            'reference_id' => 'ID de referência',
            'reference_type' => 'tipo de referência',
        ];
    }
}