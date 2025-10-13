<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Invoice;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Repositório para gerenciamento de faturas.
 *
 * Estende AbstractTenantRepository para operações tenant-aware
 * com isolamento automático de dados por empresa.
 */
class InvoiceRepository extends AbstractTenantRepository
{
    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new Invoice();
    }

    public function findByIdAndTenantId( int $id, int $tenantId ): ?Invoice
    {
        $query = $this->model->newQuery();
        $query = $this->applyTenantFilter( $query, $tenantId );
        $query->where( 'id', $id );
        return $query->first();
    }

    public function findAllByTenantId( int $tenantId, array $criteria = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array
    {
        $query = $this->model->newQuery();
        $query = $this->applyTenantFilter( $query, $tenantId );
        foreach ( $criteria as $column => $value ) {
            $query->where( $column, $value );
        }
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

    public function paginateByTenantId( int $tenantId, int $page = 1, int $perPage = 15, array $criteria = [], ?array $orderBy = null ): array
    {
        $query = $this->model->newQuery();
        $query = $this->applyTenantFilter( $query, $tenantId );
        foreach ( $criteria as $column => $value ) {
            $query->where( $column, $value );
        }
        if ( $orderBy ) {
            $query->orderBy( $orderBy[ 'column' ] ?? 'id', $orderBy[ 'direction' ] ?? 'asc' );
        }
        $paginator = $query->paginate( $perPage, [ '*' ], 'page', $page );
        return $paginator->toArray();
    }

    public function countByTenantId( int $tenantId, array $criteria = [] ): int
    {
        $query = $this->model->newQuery();
        $query = $this->applyTenantFilter( $query, $tenantId );
        foreach ( $criteria as $column => $value ) {
            $query->where( $column, $value );
        }
        return $query->count();
    }

    public function listByStatusAndTenantId( string $status, int $tenantId, ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array
    {
        $query = $this->model->newQuery();
        $query = $this->applyTenantFilter( $query, $tenantId );
        $query->where( 'status', $status );
        if ( $orderBy ) {
            $query->orderBy( $orderBy[ 'column' ] ?? 'created_at', $orderBy[ 'direction' ] ?? 'desc' );
        }
        if ( $limit ) {
            $query->limit( $limit );
        }
        if ( $offset ) {
            $query->offset( $offset );
        }
        return $query->get()->toArray();
    }

    public function countByStatusByTenantId( string $status, int $tenantId ): int
    {
        $query = $this->model->newQuery();
        $query = $this->applyTenantFilter( $query, $tenantId );
        $query->where( 'status', $status );
        return $query->count();
    }

    public function findByBudgetIdAndTenantId( int $budgetId, int $tenantId ): ?Invoice
    {
        $query = $this->model->newQuery();
        $query = $this->applyTenantFilter( $query, $tenantId );
        $query->where( 'budget_id', $budgetId );
        return $query->first();
    }

    public function listByBudgetIdAndTenantId( int $budgetId, int $tenantId ): array
    {
        $query = $this->model->newQuery();
        $query = $this->applyTenantFilter( $query, $tenantId );
        $query->where( 'budget_id', $budgetId );
        return $query->get()->toArray();
    }

    public function existsByIdAndTenantId( int $id, int $tenantId ): bool
    {
        $query = $this->model->newQuery();
        $query = $this->applyTenantFilter( $query, $tenantId );
        $query->where( 'id', $id );
        return $query->exists();
    }

    public function deleteManyByIdsAndTenantId( array $id, int $tenantId ): int
    {
        $query = $this->model->newQuery();
        $query = $this->applyTenantFilter( $query, $tenantId );
        $query->whereIn( 'id', $id );
        return $query->delete();
    }

    public function updateManyByTenantId( array $id, array $data, int $tenantId ): int
    {
        $query = $this->model->newQuery();
        $query = $this->applyTenantFilter( $query, $tenantId );
        $query->whereIn( 'id', $id );
        return $query->update( $data );
    }

    protected function applyTenantFilter( Builder $query, int $tenantId ): Builder
    {
        $query->where( 'tenant_id', $tenantId );
        return $query;
    }

}
