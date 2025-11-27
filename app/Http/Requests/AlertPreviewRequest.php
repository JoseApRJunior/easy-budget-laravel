<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\ServiceResult;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Form Request para validação de dados do sistema de preview de alertas.
 *
 * Funcionalidades implementadas:
 * - Validação robusta de tipos de alerta
 * - Validação de idiomas suportados
 * - Validação de contextos disponíveis
 * - Tratamento personalizado de erros
 * - Integração com ServiceResult
 */
class AlertPreviewRequest extends FormRequest
{
    /**
     * Tipos de alerta válidos.
     */
    private const VALID_ALERT_TYPES = ['success', 'error', 'warning', 'info'];

    /**
     * Idiomas válidos.
     */
    private const VALID_LOCALES = ['pt-BR', 'en', 'es'];

    /**
     * Contextos válidos.
     */
    private const VALID_CONTEXTS = ['web', 'email', 'mobile', 'toast'];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Autorização será tratada pelo middleware personalizado
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [];

        // Validação para tipo de alerta
        if ($this->has('alert_type') || $this->route('alertType')) {
            $rules['alert_type'] = [
                'string',
                'in:'.implode(',', self::VALID_ALERT_TYPES),
            ];
        }

        // Validação para locale
        if ($this->has('locale')) {
            $rules['locale'] = [
                'required',
                'string',
                'in:'.implode(',', self::VALID_LOCALES),
            ];
        }

        // Validação para contexto
        if ($this->has('context')) {
            $rules['context'] = [
                'required',
                'string',
                'in:'.implode(',', self::VALID_CONTEXTS),
            ];
        }

        // Validação para múltiplos tipos de alerta (comparação)
        if ($this->has('alert_types')) {
            $rules['alert_types'] = 'required|array|min:1';
            $rules['alert_types.*'] = 'string|in:'.implode(',', self::VALID_ALERT_TYPES);
        }

        // Validação para dados customizados
        if ($this->has('custom_data')) {
            $rules['custom_data'] = 'nullable|array';
            $rules['custom_data.title'] = 'nullable|string|max:255';
            $rules['custom_data.message'] = 'nullable|string|max:1000';
            $rules['custom_data.actions'] = 'nullable|array';
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'alert_type' => 'tipo de alerta',
            'locale' => 'idioma',
            'context' => 'contexto',
            'alert_types' => 'tipos de alerta',
            'alert_types.*' => 'tipo de alerta',
            'custom_data' => 'dados personalizados',
            'custom_data.title' => 'título personalizado',
            'custom_data.message' => 'mensagem personalizada',
            'custom_data.actions' => 'ações personalizadas',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'alert_type.required' => 'O tipo de alerta é obrigatório.',
            'alert_type.in' => 'O tipo de alerta selecionado não é válido.',
            'locale.required' => 'O idioma é obrigatório.',
            'locale.in' => 'O idioma selecionado não é suportado.',
            'context.required' => 'O contexto é obrigatório.',
            'context.in' => 'O contexto selecionado não é válido.',
            'alert_types.required' => 'Pelo menos um tipo de alerta deve ser selecionado.',
            'alert_types.min' => 'Pelo menos um tipo de alerta deve ser selecionado.',
            'alert_types.*.in' => 'Um ou mais tipos de alerta selecionados não são válidos.',
            'custom_data.array' => 'Os dados personalizados devem estar em formato válido.',
            'custom_data.title.max' => 'O título personalizado não pode ter mais de 255 caracteres.',
            'custom_data.message.max' => 'A mensagem personalizada não pode ter mais de 1000 caracteres.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors();

        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Dados de validação inválidos',
                'errors' => $errors->toArray(),
            ], 422),
        );
    }

    /**
     * Valida dados específicos de negócio.
     */
    public function validateBusinessRules(): ServiceResult
    {
        // Validação adicional: verificar se o usuário tem permissão para o contexto solicitado
        if ($this->has('context') && $this->get('context') === 'email') {
            if (! $this->userCanAccessEmailContext()) {
                return ServiceResult::error('Acesso ao contexto de e-mail não autorizado');
            }
        }

        // Validação adicional: verificar limite de tipos para comparação
        if ($this->has('alert_types') && count($this->get('alert_types')) > 4) {
            return ServiceResult::error('Máximo de 4 tipos de alerta permitidos para comparação');
        }

        return ServiceResult::success();
    }

    /**
     * Verifica se o usuário pode acessar o contexto de e-mail.
     */
    private function userCanAccessEmailContext(): bool
    {
        $user = $this->user();

        // Admin sempre pode acessar
        if ($user && $user->role === 'admin') {
            return true;
        }

        // Provider pode acessar se tiver e-mail verificado
        if ($user && $user->role === 'provider') {
            return $user->email_verified_at !== null;
        }

        return false;
    }

    /**
     * Obtém tipos de alerta válidos.
     */
    public static function getValidAlertTypes(): array
    {
        return self::VALID_ALERT_TYPES;
    }

    /**
     * Obtém idiomas válidos.
     */
    public static function getValidLocales(): array
    {
        return self::VALID_LOCALES;
    }

    /**
     * Obtém contextos válidos.
     */
    public static function getValidContexts(): array
    {
        return self::VALID_CONTEXTS;
    }
}
