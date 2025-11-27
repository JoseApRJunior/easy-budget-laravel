<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportGenerateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:budget,customer,product,service',
            'format' => 'required|in:pdf,excel,csv',
            'filters' => 'required|array',
            'filters.start_date' => 'nullable|date',
            'filters.end_date' => 'nullable|date|after_or_equal:filters.start_date',
            'filters.customer_name' => 'nullable|string|max:255',
            'filters.status' => 'nullable|string',
            'filters.min_total' => 'nullable|numeric|min:0',
            'filters.max_total' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'O tipo de relatório é obrigatório.',
            'type.in' => 'Tipo de relatório inválido.',
            'format.required' => 'O formato é obrigatório.',
            'format.in' => 'Formato inválido.',
            'filters.required' => 'Os filtros são obrigatórios.',
            'filters.end_date.after_or_equal' => 'A data final deve ser posterior ou igual à inicial.',
        ];
    }
}
