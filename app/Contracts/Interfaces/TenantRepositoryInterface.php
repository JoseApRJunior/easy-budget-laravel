<?php

declare(strict_types=1);

namespace App\Contracts\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Interface para repositórios que trabalham com modelos tenant-scoped
 *
 * Define métodos específicos para operações com tenant_id
 */
interface TenantRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Busca registros por tenant
     */
    public function findByTenantId( int $tenantId ): Collection;

    /**
     * Busca registro por ID e tenant
     */
    public function findByIdAndTenantId( int $id, int $tenantId ): ?Model;

    /**
     * Busca registros com filtros específicos do tenant
     */
    public function findByTenantIdWithFilters( int $tenantId, array $filters = [] ): Collection;

    /**
     * Conta registros de um tenant específico
     */
    public function countByTenantId( int $tenantId, array $filters = [] ): int;

    /**
     * Cria registro associado a um tenant específico
     */
    public function createForTenant( array $data, int $tenantId ): Model;

    /**
     * Atualiza registro verificando se pertence ao tenant
     */
    public function updateForTenant( int $id, array $data, int $tenantId ): bool;

    /**
     * Remove registro verificando se pertence ao tenant
     */
    public function deleteForTenant( int $id, int $tenantId ): bool;

    /**
     * Busca registros paginados de um tenant específico
     */
    public function paginateByTenant( int $tenantId, int $perPage = 15, array $filters = [] ): \Illuminate\Contracts\Pagination\LengthAwarePaginator;
}
