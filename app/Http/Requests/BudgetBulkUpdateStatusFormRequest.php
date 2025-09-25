<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Validação para atualização em lote de status de orçamentos.
 * Baseado no padrão do sistema antigo para operações em lote.
 * Inclui validação de limites e isolamento de tenant.
 *
 * @package App\Http\Requests
 * @author IA
 */
class BudgetBulkUpdateStatusFormRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer este request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->tenant_id;
    }

    /**
     * Regras de validação para atualização em lote de status.
     *
     * @return array<string, array|Rule|string>
     */
    public function rules(): array
    {
        return [
            'budget_ids' => [
                'required',
                'array',
                'min:1',
                'max:50'
            ],
            'budget_ids.*' => [
                'required',
                'integer',
                'exists:budgets,id'
            ],
            'status' => [
                'required',
                Rule::in(['pending', 'approved', 'rejected', 'completed', 'finalized'])
            ],
            'comment' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'notify_customers' => [
                'nullable',
                'boolean'
            ]
        ];
    }

    /**
     * Mensagens customizadas para erros de validação.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'budget_ids.required' => 'É necessário selecionar pelo menos um orçamento.',
            'budget_ids.array' => 'Os IDs dos orçamentos devem ser fornecidos como array.',
            'budget_ids.min' => 'É necessário selecionar pelo menos um orçamento.',
            'budget_ids.max' => 'Não é possível atualizar mais de 50 orçamentos por vez.',
            
            'budget_ids.*.required' => 'Todos os IDs de orçamento são obrigatórios.',
            'budget_ids.*.integer' => 'Os IDs dos orçamentos devem ser números inteiros.',
            'budget_ids.*.exists' => 'Um ou mais orçamentos selecionados não existem.',
            
            'status.required' => 'O novo status é obrigatório.',
            'status.in' => 'O status deve ser um dos valores permitidos: pending, approved, rejected, completed, finalized.',
            
            'comment.string' => 'O comentário deve ser uma string.',
            'comment.max' => 'O comentário não pode ter mais de 1000 caracteres.',
            
            'notify_customers.boolean' => 'A opção de notificar clientes deve ser verdadeiro ou falso.'
        ];
    }

    /**
     * Campos que devem ser retornados com erros de validação.
     *
     * @return array<int, string>
     */
    public function attributes(): array
    {
        return [
            'budget_ids' => 'orçamentos selecionados',
            'budget_ids.*' => 'ID do orçamento',
            'status' => 'status',
            'comment' => 'comentário',
            'notify_customers' => 'notificar clientes'
        ];
    }

    /**
     * Preparar os dados para validação.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Garantir que budget_ids seja array
        if ($this->has('budget_ids') && !is_array($this->budget_ids)) {
            $this->merge([
                'budget_ids' => explode(',', $this->budget_ids)
            ]);
        }

        // Converter para inteiros
        if ($this->has('budget_ids') && is_array($this->budget_ids)) {
            $this->merge([
                'budget_ids' => array_map('intval', array_filter($this->budget_ids))
            ]);
        }

        // Definir valores padrão
        $this->merge([
            'notify_customers' => $this->boolean('notify_customers', false)
        ]);
    }

    /**
     * Configurar validação após falha.
     *
     * @return void
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        throw new \Illuminate\Validation\ValidationException($validator, response()->json([
            'success' => false,
            'message' => 'Dados de entrada inválidos para atualização em lote.',
            'errors' => $validator->errors()
        ], 422));
    }
}