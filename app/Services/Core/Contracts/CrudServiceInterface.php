<?php

declare(strict_types=1);

namespace App\Services\Core\Contracts;

use App\Support\ServiceResult;

/**
 * Interface CrudServiceInterface
 *
 * Contrato fundamental que agrega as especializações Read, Write, Batch e SoftDelete.
 * Seguindo o Princípio da Segregação de Interfaces (ISP), este contrato é agora uma composição.
 */
interface CrudServiceInterface extends BatchServiceInterface, ReadServiceInterface, SoftDeleteServiceInterface, WriteServiceInterface
{
    /**
     * Verifica se um recurso existe baseado nos critérios.
     */
    public function exists(array $criteria): ServiceResult;

    /**
     * Duplica um recurso existente com modificações opcionais.
     */
    public function duplicate(int $id, array $overrides = []): ServiceResult;

    /**
     * Obtém estatísticas básicas sobre os recursos.
     */
    public function getStats(array $filters = []): ServiceResult;
}
