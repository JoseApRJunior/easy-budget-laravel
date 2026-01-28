<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request para aprovação de orçamentos por clientes
 */
class BudgetApprovalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Qualquer pessoa com token válido pode aprovar
        return $this->hasValidToken();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $budget = $this->route('budget');
        $currentStatus = $budget?->status;

        return [
            'action' => [
                'required',
                Rule::in(['approve', 'reject']),
            ],
            'comments' => 'nullable|string|max:1000',
            'alternative_proposal' => 'nullable|string|max:2000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'action.required' => 'A ação é obrigatória.',
            'action.in' => 'Ação inválida. Use aprovar ou rejeitar.',
            'comments.max' => 'Os comentários não podem ter mais de 1000 caracteres.',
            'alternative_proposal.max' => 'A proposta alternativa não pode ter mais de 2000 caracteres.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'action' => 'ação',
            'comments' => 'comentários',
            'alternative_proposal' => 'proposta alternativa',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Converte valores vazios para null
        if ($this->comments === '') {
            $this->merge(['comments' => null]);
        }
        if ($this->alternative_proposal === '') {
            $this->merge(['alternative_proposal' => null]);
        }
    }

    /**
     * Check if the request has a valid token for budget access
     */
    private function hasValidToken(): bool
    {
        $token = $this->route('token');
        $budget = $this->route('budget');

        if (! $token || ! $budget) {
            return false;
        }

        // Verifica se existe um compartilhamento válido com este token para este orçamento
        $share = \App\Models\BudgetShare::where('budget_id', $budget->id)
            ->where('share_token', $token)
            ->where('is_active', true)
            ->first();

        if (! $share) {
            return false;
        }

        if ($share->expires_at && now()->gt($share->expires_at)) {
            return false;
        }

        return true;
    }
}
