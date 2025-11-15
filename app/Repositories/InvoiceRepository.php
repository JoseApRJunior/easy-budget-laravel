<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Invoice;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
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

    public function getFiltered( array $filters = [], ?array $orderBy = null, ?int $limit = null ): Collection
    {
        $query = $this->model->newQuery();

        // Aplicar filtros
        if ( !empty( $filters[ 'status' ] ) ) {
            $query->where( 'status', $filters[ 'status' ] );
        }

        if ( !empty( $filters[ 'customer_id' ] ) ) {
            $query->where( 'customer_id', $filters[ 'customer_id' ] );
        }

        if ( !empty( $filters[ 'date_from' ] ) ) {
            $query->whereDate( 'due_date', '>=', $filters[ 'date_from' ] );
        }

        if ( !empty( $filters[ 'date_to' ] ) ) {
            $query->whereDate( 'due_date', '<=', $filters[ 'date_to' ] );
        }

        if ( !empty( $filters[ 'search' ] ) ) {
            $query->where( function ( $q ) use ( $filters ) {
                $q->where( 'code', 'like', '%' . $filters[ 'search' ] . '%' )
                    ->orWhereHas( 'customer', function ( $sq ) use ( $filters ) {
                        $sq->where( 'name', 'like', '%' . $filters[ 'search' ] . '%' );
                    } )
                    ->orWhereHas( 'service', function ( $sq ) use ( $filters ) {
                        $sq->where( 'description', 'like', '%' . $filters[ 'search' ] . '%' );
                    } );
            } );
        }

        // Eager loading padrão
        $query->with( [ 'customer', 'service.budget' ] );

        // Ordenação
        if ( $orderBy ) {
            foreach ( $orderBy as $field => $direction ) {
                $query->orderBy( $field, $direction );
            }
        } else {
            $query->orderBy( 'due_date', 'desc' );
        }

        // Limite
        if ( $limit ) {
            $query->limit( $limit );
        }

        return $query->get();
    }

    public function findByCode( string $code, array $with = [] ): ?Model
    {
        $query = $this->model->where( 'code', $code );

        if ( !empty( $with ) ) {
            $query->with( $with );
        }

        return $query->first();
    }

    public function countByStatus(): array
    {
        return $this->model
            ->selectRaw( 'status, COUNT(*) as count' )
            ->groupBy( 'status' )
            ->pluck( 'count', 'status' )
            ->toArray();
    }

    public function countOverdue(): int
    {
        return $this->model->where( 'due_date', '<', now() )
            ->where( 'status', '!=', 'paid' ) // Assumindo que 'paid' é um status final
            ->count();
    }

    public function getTotalRevenue(): float
    {
        return $this->model->where( 'status', 'paid' )->sum( 'total' );
    }

    public function sumTotalByBudgetId( int $budgetId, ?array $statusFilter = null ): float
    {
        $query = $this->model->newQuery()
            ->whereHas('service', function ($q) use ($budgetId) {
                $q->where('budget_id', $budgetId);
            });

        if ($statusFilter && count($statusFilter) > 0) {
            $query->whereIn('status', $statusFilter);
        }

        return (float) ($query->sum('total') ?? 0);
    }

}
