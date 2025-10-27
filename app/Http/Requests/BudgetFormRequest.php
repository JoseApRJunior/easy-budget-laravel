<?php

declare(strict_types=1);

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
        return [
            'customer_id'   => [ 'required', 'integer', 'exists:customers,id' ],
            'due_date'      => [ 'required', 'date', 'after:today' ],
            'description'   => [ 'nullable', 'string', 'max:500' ],
            'payment_terms' => [ 'nullable', 'string', 'max:1000' ],
            'attachment'    => [ 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120' ], // 5MB max
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
            'customer_id.required' => 'O cliente é obrigatório.',
            'customer_id.integer'  => 'O cliente deve ser um número válido.',
            'customer_id.exists'   => 'O cliente selecionado não existe.',
            'due_date.required'    => 'A data de vencimento é obrigatória.',
            'due_date.date'        => 'A data de vencimento deve ser uma data válida.',
            'due_date.after'       => 'A data de vencimento deve ser posterior à data atual.',
            'description.string'   => 'A descrição deve ser um texto.',
            'description.max'      => 'A descrição não pode ter mais de 500 caracteres.',
            'payment_terms.string' => 'Os termos de pagamento devem ser um texto.',
            'payment_terms.max'    => 'Os termos de pagamento não podem ter mais de 1000 caracteres.',
            'attachment.file'      => 'O anexo deve ser um arquivo.',
            'attachment.mimes'     => 'O anexo deve ser um arquivo PDF, JPG, JPEG ou PNG.',
            'attachment.max'       => 'O anexo não pode ser maior que 5MB.',
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
            'customer_id'   => 'cliente',
            'due_date'      => 'data de vencimento',
            'description'   => 'descrição',
            'payment_terms' => 'termos de pagamento',
            'attachment'    => 'anexo',
        ];
    }

}
