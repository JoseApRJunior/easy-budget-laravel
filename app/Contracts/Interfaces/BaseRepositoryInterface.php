<?php

declare(strict_types=1);

namespace App\Contracts\Interfaces;

use Illuminate\Database\Eloquent\Collection;

/**
 * Interface base para repositórios
 */
interface BaseRepositoryInterface extends RepositoryInterface
{
    /**
     * Busca registros com paginação
     */
    public function paginate( int $perPage = 15, array $filters = [] ): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    /**
     * Busca registros com filtros
     */
    public function findBy( array $criteria ): Collection;

    /**
     * Conta registros com filtros
     */
    public function count( array $filters = [] ): int;
}
