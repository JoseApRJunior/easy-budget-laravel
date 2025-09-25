<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\SlugAwareRepositoryInterface;
use App\Interfaces\RepositoryNoTenantInterface;
use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class RoleRepository extends AbstractNoTenantRepository implements RepositoryNoTenantInterface, SlugAwareRepositoryInterface
{
    protected string $modelClass = Role::class;

    public function __construct()
    {
        parent::__construct();
    }

    public function findById( int $id ): ?Role
    {
        return parent::findById( $id );
    }

    public function findAll( array $criteria = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array
    {
        return parent::findAll( $criteria, $orderBy, $limit, $offset );
    }
    public function paginate( int $page = 1, int $perPage = 15, array $criteria = [], ?array $orderBy = null ): array
    {
        return parent::paginate( $page, $perPage, $criteria, $orderBy );
    }

    public function count( array $criteria = [] ): int
    {
        return parent::countBy( $criteria );
    }

    public function existsByName( string $name ): bool
    {
        return parent::existsBy( [ 'name' => $name ] );
    }

    public function listActive( ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array
    {
        return parent::findBy( [ 'status' => 'active' ], $orderBy, $limit, $offset );
    }

    public function getBySlug( string $slug ): ?Role
    {
        return parent::findBySlug( $slug );
    }

    public function existsBySlug( string $slug, ?int $tenantId = null, ?int $excludeId = null ): bool
    {
        $criteria = [ 'slug' => $slug ];
        if ( $excludeId ) {
            // Para exclusão de ID, precisamos usar uma query customizada
            $query = $this->newQuery()->where( 'slug', $slug );
            $query->where( 'id', '!=', $excludeId );
            return $query->exists();
        }
        // Ignora $tenantId pois é repositório sem tenant
        return parent::existsBy( $criteria );
    }

    public function existsById( int $id ): bool
    {
        return parent::existsBy( [ 'id' => $id ] );
    }

    public function deleteManyByIds( array $id ): int
    {
        return parent::deleteManyByIds( $id );
    }

    public function updateMany( array $id, array $data ): int
    {
        return parent::updateMany( [ 'id' => $id ], $data );
    }

}
