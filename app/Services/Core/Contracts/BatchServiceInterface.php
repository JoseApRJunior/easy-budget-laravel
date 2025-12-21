<?php
declare(strict_types=1);

namespace App\Services\Core\Contracts;

use App\Support\ServiceResult;

/**
 * Interface BatchServiceInterface
 * Contrato especializado para operações em lote/massa.
 */
interface BatchServiceInterface
{
    public function updateMany(array $ids, array $data): ServiceResult;
    public function deleteMany(array $ids): ServiceResult;
    public function deleteByCriteria(array $criteria): ServiceResult;
}
