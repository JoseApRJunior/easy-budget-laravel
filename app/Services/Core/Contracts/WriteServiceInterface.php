<?php

declare(strict_types=1);

namespace App\Services\Core\Contracts;

use App\Support\ServiceResult;

/**
 * Interface WriteServiceInterface
 * Contrato especializado para operações de escrita e persistência simple.
 */
interface WriteServiceInterface
{
    public function create(array $data): ServiceResult;

    public function update(int $id, array $data): ServiceResult;

    public function delete(int $id): ServiceResult;
}
