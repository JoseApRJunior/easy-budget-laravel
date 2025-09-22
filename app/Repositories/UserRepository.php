<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repositório para operações de usuário tenant-aware.
 *
 * Estende AbstractRepository para isolamento de tenant.
 */
class UserRepository extends AbstractRepository
{
    /**
     * @var string Model class
     */
    protected string $modelClass = User::class;

    /**
     * Encontra usuário por ID e tenant.
     *
     * @param int $tenantId
     * @param int $tenantId
     * @return User|null
     */
    public function findByIdAndTenantId( int $id, int $tenantId ): ?User
    {
        return $this->findByIdAndTenantId( $id, $tenantId );
    }

    /**
     * Lista usuários por tenant com filtros.
     *
     * @param int $tenantId
     * @param array $filters
     * @param array|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return array
     */
    public function listByTenantId( int $tenantId, array $filters = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array
    {
        return $this->findAllByTenantId( $tenantId, $filters, $orderBy, $limit, $offset );
    }

    /**
     * Conta admins por tenant.
     *
     * @param int $tenantId
     * @return int
     */
    public function countAdminsByTenantId( int $tenantId ): int
    {
        return $this->model::where( 'tenant_id', $tenantId )
            ->where( 'role', 'admin' ) // Assumir campo role
            ->count();
    }

    /**
     * Verifica se slug existe por tenant.
     *
     * @param string $slug
     * @param int $tenantId
     * @param int|null $excludeId
     * @return bool
     */
    public function existsBySlugAndTenantId( string $slug, int $tenantId, ?int $excludeId = null ): bool
    {
        $query = $this->model::where( 'tenant_id', $tenantId )->where( 'slug', $slug );
        if ( $excludeId ) {
            $query->where( 'id', '!=', $excludeId );
        }
        return $query->exists();
    }

    // Outros métodos conforme necessário, ex: existsByEmailAndTenantId, etc.
}