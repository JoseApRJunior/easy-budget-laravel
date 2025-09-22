<?php

declare(strict_types=1);

namespace App\Services\Abstracts;

use App\Enums\OperationStatus;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\Auth;

/**
 * Classe base abstrata para todos os serviços.
 *
 * Fornece funcionalidades comuns como helpers para ServiceResult,
 * acesso ao usuário autenticado, geração de slugs e validações básicas.
 * Elimina duplicação de código e garante consistência em todos os serviços.
 */
abstract class BaseService
{
    /**
     * Retorna um ServiceResult de sucesso.
     *
     * @param mixed $data Dados da operação
     * @param string $message Mensagem personalizada (opcional)
     * @return ServiceResult
     */
    protected function success( mixed $data = null, string $message = '' ): ServiceResult
    {
        return ServiceResult::success( $data, $message );
    }

    /**
     * Retorna um ServiceResult de erro.
     *
     * @param OperationStatus|string $status Status da operação ou mensagem
     * @param string $message Mensagem de erro (opcional)
     * @param mixed $data Dados adicionais (opcional)
     * @param Exception|null $exception Exceção original (opcional)
     * @return ServiceResult
     */
    protected function error( OperationStatus|string $status, string $message = '', mixed $data = null, ?Exception $exception = null ): ServiceResult
    {
        return ServiceResult::error( $status, $message, $data, $exception );
    }

    /**
     * Obtém o ID do tenant do usuário autenticado.
     *
     * @return int|null ID do tenant ou null se não autenticado
     */
    protected function tenantId(): ?int
    {
        $user = Auth::user();
        if ( $user instanceof \App\Models\User ) {
            /** @phpstan-ignore-next-line */
            return $user->tenant_id;
        }
        return null;
    }

    /**
     * Obtém o usuário autenticado.
     *
     * @return \App\Models\User|null Instância do usuário ou null
     */
    protected function authUser(): ?\App\Models\User
    {
        $user = Auth::user();
        return $user instanceof \App\Models\User ? $user : null;
    }

    /**
     * Valida campos obrigatórios nos dados.
     *
     * @param array $data Dados a validar
     * @param array $requiredFields Campos obrigatórios
     * @return ServiceResult Resultado da validação
     */
    protected function validateRequired( array $data, array $requiredFields ): ServiceResult
    {
        $errors = [];
        foreach ( $requiredFields as $field ) {
            if ( !isset( $data[ $field ] ) || empty( $data[ $field ] ) ) {
                $errors[] = "O campo {$field} é obrigatório.";
            }
        }

        if ( !empty( $errors ) ) {
            return $this->error( OperationStatus::INVALID_DATA, implode( ', ', $errors ) );
        }

        return $this->success( null, 'Validação OK' );
    }

    /**
     * Valida o comprimento de um campo.
     *
     * @param string $value Valor a validar
     * @param int $min Minimo
     * @param int $max Máximo
     * @param string $field Nome do campo
     * @return ServiceResult
     */
    protected function validateLength( string $value, int $min, int $max, string $field ): ServiceResult
    {
        $length = strlen( $value );
        if ( $length < $min ) {
            return $this->error( OperationStatus::INVALID_DATA, "O campo {$field} deve ter pelo menos {$min} caracteres." );
        }
        if ( $length > $max ) {
            return $this->error( OperationStatus::INVALID_DATA, "O campo {$field} deve ter no máximo {$max} caracteres." );
        }
        return $this->success();
    }

    /**
     * Valida dados para um tenant específico.
     *
     * @param array $data Dados a validar
     * @param int $tenant_id ID do tenant
     * @param bool $is_update Se é uma atualização
     * @return ServiceResult Resultado da validação
     */
    abstract protected function validateForTenant( array $data, int $tenant_id, bool $is_update = false ): ServiceResult;

    // Outros helpers de validação podem ser adicionados conforme necessário
}
