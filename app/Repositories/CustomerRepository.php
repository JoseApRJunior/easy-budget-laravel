<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerRepository extends AbstractRepository
{
    protected string $modelClass = Customer::class;

    /**
     * Lista clientes ativos por tenant.
     */
    public function listActiveByTenantId( int $tenantId, ?array $orderBy = null, ?int $limit = null ): array
    {
        $query = $this->applyTenantFilter( $this->model::query(), $tenantId )
            ->where( 'status', 'active' );
        if ( $orderBy ) {
            foreach ( $orderBy as $column => $direction ) {
                $query->orderBy( $column, $direction );
            }
        }
        if ( $limit ) {
            $query->limit( $limit );
        }
        return $query->get()->all();
    }

    /**
     * Conta clientes por tenant.
     */
    public function countByTenantId( int $tenantId, array $filters = [] ): int
    {
        $query = $this->applyTenantFilter( $this->model::query(), $tenantId );
        if ( !empty( $filters ) ) {
            $query->where( $filters );
        }
        return $query->count();
    }

    /**
     * Verifica existência por critérios e tenant.
     */
    public function existsByTenantId( array $criteria, int $tenantId ): bool
    {
        $query = $this->applyTenantFilter( $this->model::query(), $tenantId );
        foreach ( $criteria as $key => $value ) {
            $query->where( $key, $value );
        }
        return $query->exists();
    }

    /**
     * Deleta múltiplos por IDs e tenant.
     */
    public function deleteManyByIdsAndTenantId( array $id, int $tenantId ): int
    {
        return $this->applyTenantFilter( $this->model::query(), $tenantId )
            ->whereIn( 'id', $id )
            ->delete();
    }

    /**
     * Atualiza múltiplos por critérios e tenant.
     */
    public function updateManyByTenantId( array $criteria, array $updates, int $tenantId ): int
    {
        $query = $this->applyTenantFilter( $this->model::query(), $tenantId );
        foreach ( $criteria as $key => $value ) {
            $query->where( $key, $value );
        }
        return $query->update( $updates );
    }

    /**
     * Encontra por critérios e tenant.
     */
    public function findByAndTenantId( array $criteria, int $tenantId, ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array
    {
        $query = $this->applyTenantFilter( $this->model::query(), $tenantId );
        foreach ( $criteria as $key => $value ) {
            $query->where( $key, $value );
        }
        if ( $orderBy ) {
            foreach ( $orderBy as $column => $direction ) {
                $query->orderBy( $column, $direction );
            }
        }
        if ( $limit ) {
            $query->limit( $limit );
        }
        if ( $offset ) {
            $query->offset( $offset );
        }
        return $query->get()->all();
    }

    /**
     * Paginação por tenant.
     */
    public function paginateByTenantId( int $tenantId, int $page = 1, int $perPage = 15, array $criteria = [], ?array $orderBy = null ): array
    {
        $query = $this->applyTenantFilter( $this->model::query(), $tenantId );
        if ( !empty( $criteria ) ) {
            $query->where( $criteria );
        }
        if ( $orderBy ) {
            foreach ( $orderBy as $column => $direction ) {
                $query->orderBy( $column, $direction );
            }
        }
        $paginator = $query->paginate( $perPage, [ '*' ], 'page', $page );
        return $paginator->toArray();
    }

    /**
     * Alias para listagem por tenant (compatibilidade com service).
     */
    public function listByTenantId( int $tenantId, array $filters = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array
    {
        return $this->findAllByTenantId( $tenantId, $filters, $orderBy, $limit, $offset );
    }

}
