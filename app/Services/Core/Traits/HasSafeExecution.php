<?php

declare(strict_types=1);

namespace App\Services\Core\Traits;

use App\Enums\OperationStatus;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

/**
 * Trait para fornecer execução segura com tratamento de erros padronizado.
 */
trait HasSafeExecution
{
    /**
     * Helper para execução segura de operações com tratamento de erro padronizado.
     */
    protected function safeExecute(callable $callback, string $errorMessage = 'Erro ao processar operação.'): ServiceResult
    {
        try {
            $data = $callback();

            return $data instanceof ServiceResult ? $data : ServiceResult::success($data);
        } catch (QueryException $e) {
            Log::error($errorMessage, [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ServiceResult::error(
                OperationStatus::CONFLICT,
                'Erro de integridade de dados ou conflito no banco.',
                null,
                $e
            );
        } catch (Exception $e) {
            Log::error($errorMessage, [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ServiceResult::error(
                OperationStatus::ERROR,
                $errorMessage,
                null,
                $e
            );
        }
    }
}
