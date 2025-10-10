<?php
declare(strict_types=1);

namespace App\Services\Contracts;

use App\Support\ServiceResult;

/**
 * Interface CommandServiceInterface
 *
 * Contrato para operações de Escrita e Execução de comandos (CREATE/UPDATE/DELETE).
 */
interface CommandServiceInterface
{
    /**
     * Executa operações em lote para melhor performance.
     */
    public function batchOperation( array $operations, string $operation ): ServiceResult;
}
