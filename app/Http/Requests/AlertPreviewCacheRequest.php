<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\ServiceResult;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Form Request específico para operações de cache do sistema de preview de alertas.
 *
 * Funcionalidades implementadas:
 * - Validação específica para operações de cache
 * - Controle de segurança adicional
 * - Validação de regras de negócio específicas
 */
class AlertPreviewCacheRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Verificar se o usuário tem permissão específica para limpar cache
        $user = $this->user();

        if ( !$user ) {
            return false;
        }

        // Admin sempre pode limpar cache
        if ( $user->role === 'admin' ) {
            return true;
        }

        // Provider pode limpar cache se tiver e-mail verificado
        if ( $user->role === 'provider' ) {
            return $user->email_verified_at !== null;
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'operation'      => 'required|string|in:clear_all,clear_specific,clear_stats',
            'cache_keys'     => 'required_if:operation,clear_specific|array',
            'cache_keys.*'   => 'string|regex:/^[a-zA-Z0-9_]+$/',
            'confirm_action' => 'sometimes|boolean',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'operation'      => 'operação',
            'cache_keys'     => 'chaves de cache',
            'cache_keys.*'   => 'chave de cache',
            'confirm_action' => 'confirmação da ação',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'operation.required'     => 'A operação é obrigatória.',
            'operation.in'           => 'A operação selecionada não é válida.',
            'cache_keys.required_if' => 'As chaves de cache são obrigatórias para esta operação.',
            'cache_keys.array'       => 'As chaves de cache devem estar em formato de lista.',
            'cache_keys.*.regex'     => 'As chaves de cache devem conter apenas letras, números e underscore.',
            'confirm_action.boolean' => 'A confirmação deve ser verdadeira ou falsa.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation( Validator $validator ): void
    {
        $errors = $validator->errors();

        throw new HttpResponseException(
            response()->json( [
                'success' => false,
                'message' => 'Dados de validação inválidos para operação de cache',
                'errors'  => $errors->toArray(),
            ], 422 ),
        );
    }

    /**
     * Valida regras de negócio específicas para operações de cache.
     */
    public function validateBusinessRules(): ServiceResult
    {
        $user      = $this->user();
        $operation = $this->get( 'operation' );

        // Verificar se o usuário pode executar operações críticas
        if ( $operation === 'clear_all' && !$this->canPerformCriticalOperation( $user ) ) {
            return ServiceResult::error( 'Usuário não autorizado para operações críticas de cache' );
        }

        // Verificar limite de operações por hora
        if ( !$this->checkOperationLimit( $user ) ) {
            return ServiceResult::error( 'Limite de operações de cache excedido. Tente novamente mais tarde' );
        }

        return ServiceResult::success();
    }

    /**
     * Verifica se o usuário pode executar operações críticas.
     */
    private function canPerformCriticalOperation( $user ): bool
    {
        // Admin pode executar qualquer operação
        if ( $user->role === 'admin' ) {
            return true;
        }

        // Provider só pode executar operações básicas
        if ( $user->role === 'provider' ) {
            $operation = $this->get( 'operation' );
            return in_array( $operation, [ 'clear_specific', 'clear_stats' ] );
        }

        return false;
    }

    /**
     * Verifica limite de operações por hora.
     */
    private function checkOperationLimit( $user ): bool
    {
        $key        = "alert_preview_cache_operations:{$user->id}";
        $operations = cache()->get( $key, 0 );

        // Máximo 5 operações por hora para usuários não-admin
        $maxOperations = ( $user->role === 'admin' ) ? 50 : 5;

        return $operations < $maxOperations;
    }

}
