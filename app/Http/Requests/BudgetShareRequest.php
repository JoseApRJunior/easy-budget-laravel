<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para criação de compartilhamentos de orçamento
 */
class BudgetShareRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'budget_id' => 'required|exists:budgets,id',
            'recipient_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'expires_at' => 'nullable|date|after:now',
            'permissions' => 'nullable|array|min:1',
            'permissions.*' => 'in:view,print,comment,approve,reject',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'budget_id.required' => 'O orçamento é obrigatório.',
            'budget_id.exists' => 'O orçamento selecionado é inválido.',
            'recipient_name.required' => 'O nome do destinatário é obrigatório.',
            'recipient_name.max' => 'O nome do destinatário não pode ter mais de 255 caracteres.',
            'email.required' => 'O email é obrigatório.',
            'email.email' => 'O email deve ser um endereço de email válido.',
            'email.max' => 'O email não pode ter mais de 255 caracteres.',
            'expires_at.date' => 'A data de expiração deve ser uma data válida.',
            'expires_at.after' => 'A data de expiração deve ser futura.',
            'permissions.array' => 'As permissões devem ser um array.',
            'permissions.min' => 'Deve haver pelo menos uma permissão.',
            'permissions.*.in' => 'Permissão inválida. As permissões válidas são: view, print, comment, approve, reject.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'email' => 'email',
            'expires_at' => 'data de expiração',
            'permissions' => 'permissões',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Converte permissões do formato do formulário (can_view => 1) para o formato do serviço (view, print, etc)
        if ($this->has('permissions') && is_array($this->permissions)) {
            $mappedPermissions = [];
            $mapping = [
                'can_view' => 'view',
                'can_print' => 'print',
                'can_comment' => 'comment',
                'can_approve' => 'approve',
                'can_reject' => 'reject',
            ];

            foreach ($mapping as $key => $value) {
                if (isset($this->permissions[$key]) && $this->permissions[$key] == '1') {
                    $mappedPermissions[] = $value;
                }
            }

            // Se for um array de strings vindo de outro lugar, mantém
            if (empty($mappedPermissions) && ! empty($this->permissions)) {
                $mappedPermissions = array_values(array_filter($this->permissions, fn ($p) => in_array($p, array_values($mapping))));
            }

            if (! empty($mappedPermissions)) {
                $this->merge(['permissions' => $mappedPermissions]);
            }
        }

        // Define permissões padrão se não fornecidas
        if (! $this->has('permissions') || empty($this->permissions)) {
            $this->merge(['permissions' => ['view', 'print', 'comment', 'approve']]);
        }

        // Define expiração padrão de 7 dias se não fornecida
        if (! $this->has('expires_at')) {
            $this->merge(['expires_at' => now()->addDays(7)->toDateTimeString()]);
        }
    }
}
