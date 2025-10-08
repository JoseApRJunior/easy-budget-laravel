<?php

declare(strict_types=1);

namespace App\Contracts\Interfaces;

/**
 * Interface para repositórios com tenant
 */
interface TenantRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Busca registros por tenant
     */
    public function findByTenantId( int $tenantId ): \Illuminate\Database\Eloquent\Collection;

    /**
     * Busca registro por ID e tenant
     */
    public function findByIdAndTenantId( int $id, int $tenantId ): ?\Illuminate\Database\Eloquent\Model;
}
