<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BudgetChangeStatusFormRequest extends FormRequest
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
            'budget_id' => ['required', 'integer', 'exists:budgets,id'],
            'budget_code' => ['required', 'string', 'max:50'],
            'current_status_id' => ['required', 'integer'],
            'current_status_name' => ['required', 'string', 'max:100'],
            'current_status_slug' => ['required', 'string', 'max:50'],
            'action' => ['required', 'string', 'max:50'],
            'email_notification' => ['nullable', 'boolean'],
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
            'budget_id.required' => 'O ID do orçamento é obrigatório.',
            'budget_id.integer' => 'O ID do orçamento deve ser um número válido.',
            'budget_id.exists' => 'O orçamento não existe.',
            'budget_code.required' => 'O código do orçamento é obrigatório.',
            'budget_code.string' => 'O código do orçamento deve ser um texto.',
            'budget_code.max' => 'O código do orçamento não pode ter mais de 50 caracteres.',
            'current_status_id.required' => 'O ID do status atual é obrigatório.',
            'current_status_id.integer' => 'O ID do status atual deve ser um número válido.',
            'current_status_name.required' => 'O nome do status atual é obrigatório.',
            'current_status_name.string' => 'O nome do status atual deve ser um texto.',
            'current_status_name.max' => 'O nome do status atual não pode ter mais de 100 caracteres.',
            'current_status_slug.required' => 'O slug do status atual é obrigatório.',
            'current_status_slug.string' => 'O slug do status atual deve ser um texto.',
            'current_status_slug.max' => 'O slug do status atual não pode ter mais de 50 caracteres.',
            'action.required' => 'A ação é obrigatória.',
            'action.string' => 'A ação deve ser um texto.',
            'action.max' => 'A ação não pode ter mais de 50 caracteres.',
            'email_notification.boolean' => 'A notificação por e-mail deve ser verdadeiro ou falso.',
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
            'budget_id' => 'ID do orçamento',
            'budget_code' => 'código do orçamento',
            'current_status_id' => 'ID do status atual',
            'current_status_name' => 'nome do status atual',
            'current_status_slug' => 'slug do status atual',
            'action' => 'ação',
            'email_notification' => 'notificação por e-mail',
        ];
    }
}
