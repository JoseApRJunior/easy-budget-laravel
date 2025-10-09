<?php

declare(strict_types=1);

namespace App\Contracts\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Interface para repositórios que trabalham com modelos globais
 *
 * Define métodos específicos para operações sem tenant scoping
 */
interface GlobalRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Busca todos os registros sem restrição de tenant
     */
    public function findAllGlobal(): Collection;

    /**
     * Busca registro por ID sem restrição de tenant
     */
    public function findGlobal( int $id ): ?Model;

    /**
     * Busca registros com filtros sem restrição de tenant
     */
    public function findByGlobal( array $criteria ): Collection;

    /**
     * Conta registros sem restrição de tenant
     */
    public function countGlobal( array $filters = [] ): int;

    /**
     * Cria registro global (sem tenant)
     */
    public function createGlobal( array $data ): Model;

    /**
     * Atualiza registro global
     */
    public function updateGlobal( int $id, array $data ): bool;

    /**
     * Remove registro global
     */
    public function deleteGlobal( int $id ): bool;

    /**
     * Busca registros paginados sem restrição de tenant
     */
    public function paginateGlobal( int $perPage = 15, array $filters = [] ): \Illuminate\Contracts\Pagination\LengthAwarePaginator;
}
