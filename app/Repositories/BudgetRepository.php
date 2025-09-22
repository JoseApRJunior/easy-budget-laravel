<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\RepositoryInterface;
use App\Models\Budget;
use Illuminate\Database\Eloquent\Model;

class BudgetRepository extends AbstractRepository
{
    protected string $modelClass = Budget::class;

    /**
     * Lista budgets por status e tenant.
     */
    public function listByStatusAndTenantId( int $tenantId, array $statuses, ?array $orderBy = null, ?int $limit = null ): array
    {
        $query = $this->applyTenantFilter( $this->model::query(), $tenantId )
            ->whereIn( 'status', $statuses );
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
     * Conta budgets por status e tenant.
     */
    public function countByStatusByTenantId( int $tenantId, string $status ): int
    {
        return $this->applyTenantFilter( $this->model::query(), $tenantId )
            ->where( 'status', $status )
            ->count();
    }

    /**
     * Lista budgets por cliente e tenant.
     */
    public function listByCustomerIdAndTenantId( int $customerId, int $tenantId, ?array $orderBy = null ): array
    {
        $query = $this->applyTenantFilter( $this->model::query(), $tenantId )
            ->where( 'customer_id', $customerId );
        if ( $orderBy ) {
            foreach ( $orderBy as $column => $direction ) {
                $query->orderBy( $column, $direction );
            }
        }
        return $query->get()->all();
    }

    /**
     * Alias para listagem por tenant (compatibilidade com service).
     */
    public function listByTenantId( int $tenantId, array $filters = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array
    {
        return $this->findAllByTenantId( $tenantId, $filters, $orderBy, $limit, $offset );
    }

}
