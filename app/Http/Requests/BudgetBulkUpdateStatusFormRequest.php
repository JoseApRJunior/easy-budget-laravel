<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\BudgetStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * FormRequest para validação de atualização em lote do status de orçamentos.
 *
 * Implementa validação robusta para:
 * - budget_ids como array obrigatório com 1-100 IDs de orçamentos
 * - status válido baseado no enum BudgetStatus
 * - comment opcional com limite de 1000 caracteres
 * - notify_customers opcional como boolean
 */
class BudgetBulkUpdateStatusFormRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer esta requisição.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Regras de validação para atualização em lote de status.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'budget_ids' => [
                'required',
                'array',
                'min:1',
                'max:100',
            ],
            'budget_ids.*' => [
                'integer',
                'exists:budgets,id',
                'distinct',
            ],
            'status' => [
                'required',
                'string',
                Rule::in(array_column(BudgetStatus::cases(), 'value')),
            ],
            'comment' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'notify_customers' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * Mensagens de validação customizadas em português.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'budget_ids.required' => 'Os IDs dos orçamentos são obrigatórios.',
            'budget_ids.array' => 'Os IDs dos orçamentos devem ser um array.',
            'budget_ids.min' => 'Selecione pelo menos um orçamento.',
            'budget_ids.max' => 'Não é possível atualizar mais de 100 orçamentos de uma vez.',
            'budget_ids.*.integer' => 'Cada ID de orçamento deve ser um número inteiro.',
            'budget_ids.*.exists' => 'Orçamento não encontrado.',
            'budget_ids.*.distinct' => 'IDs de orçamentos duplicados não são permitidos.',

            'status.required' => 'O status é obrigatório.',
            'status.string' => 'O status deve ser uma string.',
            'status.in' => 'Status inválido.',

            'comment.string' => 'O comentário deve ser uma string.',
            'comment.max' => 'O comentário não pode ter mais de 1000 caracteres.',

            'notify_customers.boolean' => 'O campo "notificar clientes" deve ser verdadeiro ou falso.',
        ];
    }

    /**
     * Nomes dos atributos para validação.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'budget_ids' => 'IDs dos orçamentos',
            'status' => 'Status',
            'comment' => 'Comentário',
            'notify_customers' => 'Notificar clientes',
            'notifyCustomers' => 'Notificar clientes',
        ];
    }

    /**
     * Prepara os dados para validação.
     */
    protected function prepareForValidation(): void
    {
        // Converter string 'true'/'false' para boolean
        if ($this->notify_customers === 'true') {
            $this->merge(['notify_customers' => true]);
        } elseif ($this->notify_customers === 'false') {
            $this->merge(['notify_customers' => false]);
        }
    }

    /**
     * Obtém os dados validados e preparados para o service.
     */
    public function getValidatedData(): array
    {
        $validated = $this->validated();

        return [
            'budget_ids' => $validated['budget_ids'],
            'status' => BudgetStatus::from($validated['status']),
            'comment' => $validated['comment'] ?? null,
            'notify_customers' => (bool) ($validated['notify_customers'] ?? false),
        ];
    }
}
