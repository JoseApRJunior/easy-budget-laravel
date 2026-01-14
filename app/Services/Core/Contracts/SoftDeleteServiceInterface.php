<?php

declare(strict_types=1);

namespace App\Services\Core\Contracts;

use App\Support\ServiceResult;

/**
 * Interface SoftDeleteServiceInterface
 * Contrato para serviços que suportam restauração de registros.
 */
interface SoftDeleteServiceInterface
{
    public function restore(int $id): ServiceResult;
}
