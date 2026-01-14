<?php

declare(strict_types=1);

namespace App\Services\Core\Contracts;

use App\Support\ServiceResult;

/**
 * Interface ReadServiceInterface
 * Contrato especializado para operações de leitura de dados.
 */
interface ReadServiceInterface
{
    public function findById(int $id, array $with = []): ServiceResult;

    public function list(array $filters = []): ServiceResult;

    public function paginate(array $filters = [], int $perPage = 15, array $with = []): ServiceResult;

    public function count(array $filters = []): ServiceResult;

    public function findMany(array $ids, array $with = []): ServiceResult;

    public function findOneBy(array $criteria, array $with = []): ServiceResult;
}
