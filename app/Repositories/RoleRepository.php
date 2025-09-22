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
    public function __construct()
    {
        parent::__construct( new Role() );
    }

    public function findById( int $tenantId ): ?Role
    {
        return $this->model->find( $id );
    }

    public function findAll( array $criteria = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array
    {
        $query = $this->model->newQuery();
        $query->where( $criteria );
        if ( $orderBy ) {
            $query->orderBy( $orderBy[ 'column' ] ?? 'id', $orderBy[ 'direction' ] ?? 'asc' );
        }
        if ( $limit ) {
            $query->limit( $limit );
        }
        if ( $offset ) {
            $query->offset( $offset );
        }
        return $query->get()->toArray();
    }

    public function paginate( int $page = 1, int $perPage = 15, array $criteria = [], ?array $orderBy = null ): LengthAwarePaginator
    {
        $query = $this->model->newQuery();
        $query->where( $criteria );
        if ( $orderBy ) {
            $query->orderBy( $orderBy[ 'column' ] ?? 'id', $orderBy[ 'direction' ] ?? 'asc' );
        }
        return $query->paginate( $perPage, [ '*' ], 'page', $page );
    }

    public function count( array $criteria = [] ): int
    {
        return $this->model->where( $criteria )->count();
    }

    public function existsByName( string $name ): bool
    {
        return $this->model->where( 'name', $name )->exists();
    }

    public function listActive( ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array
    {
        $query = $this->model->newQuery()->where( 'status', 'active' );
        if ( $orderBy ) {
            $query->orderBy( $orderBy[ 'column' ] ?? 'name', $orderBy[ 'direction' ] ?? 'asc' );
        }
        if ( $limit ) {
            $query->limit( $limit );
        }
        if ( $offset ) {
            $query->offset( $offset );
        }
        return $query->get()->toArray();
    }

    public function getBySlug( string $slug ): ?Role
    {
        return $this->model->where( 'slug', $slug )->first();
    }

    public function existsBySlug( string $slug, ?int $tenantId = null, ?int $excludeId = null ): bool
    {
        $query = $this->model->newQuery()->where( 'slug', $slug );
        if ( $excludeId ) {
            $query->where( 'id', '!=', $excludeId );
        }
        // Ignora $tenantId pois é repositório sem tenant
        return $query->exists();
    }

    public function existsById( int $tenantId ): bool
    {
        return $this->model->where( 'id', $id )->exists();
    }

    public function deleteManyByIds( array $id ): int
    {
        return $this->model->whereIn( 'id', $id )->delete();
    }

    public function updateMany( array $id, array $data ): int
    {
        return $this->model->whereIn( 'id', $id )->update( $data );
    }

}
