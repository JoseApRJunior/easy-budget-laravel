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
        // Define permissões padrão se não fornecidas
        if (! $this->has('permissions')) {
            $this->merge(['permissions' => ['view', 'print', 'comment', 'approve']]);
        }

        // Define expiração padrão de 7 dias se não fornecida
        if (! $this->has('expires_at')) {
            $this->merge(['expires_at' => now()->addDays(7)]);
        }
    }
}
