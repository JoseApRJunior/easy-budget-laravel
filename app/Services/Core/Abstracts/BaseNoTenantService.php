<?php

declare(strict_types=1);

namespace App\Services\Core\Abstracts;

use App\Enums\OperationStatus;
use App\Models\User;
use App\Services\Core\Traits\HasFilterNormalization;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Classe base abstrata para serviços que não utilizam Repositórios ou Tenant.
 * Útil para integrações de infraestrutura e serviços externos.
 */
abstract class BaseNoTenantService
{
    use HasFilterNormalization;

    /**
     * Helper para execução segura de operações com tratamento de erro padronizado.
     */
    protected function safeExecute(callable $callback, string $errorMessage = 'Erro ao processar operação.'): ServiceResult
    {
        try {
            $data = $callback();

            return $data instanceof ServiceResult ? $data : $this->success($data);
        } catch (Exception $e) {
            Log::error($errorMessage, [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(OperationStatus::ERROR, $errorMessage, null, $e);
        }
    }

    /**
     * Helper para retorno de sucesso.
     */
    protected function success(mixed $data = null, string $message = ''): ServiceResult
    {
        return ServiceResult::success($data, $message);
    }

    /**
     * Helper para retorno de erro.
     */
    protected function error(OperationStatus|string $status, string $message = '', mixed $data = null, ?Exception $exception = null): ServiceResult
    {
        $finalStatus = is_string($status) ? OperationStatus::ERROR : $status;
        $finalMessage = is_string($status) ? $status : $message;

        return ServiceResult::error($finalStatus, $finalMessage, $data, $exception);
    }

    /**
     * Retorna o usuário autenticado.
     */
    protected function authUser(): ?User
    {
        return Auth::user();
    }
}
