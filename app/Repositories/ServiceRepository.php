<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\RepositoryInterface;
use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ServiceRepository extends AbstractRepository implements RepositoryInterface
{
    protected string $modelClass = Service::class;

    /**
     * Lista serviços por status e tenant.
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
     * Lista serviços por provider e tenant.
     */
    public function listByProviderIdAndTenantId( int $providerId, int $tenantId, ?array $orderBy = null ): array
    {
        $query = $this->applyTenantFilter( $this->model::query(), $tenantId )
            ->where( 'provider_id', $providerId );
        if ( $orderBy ) {
            foreach ( $orderBy as $column => $direction ) {
                $query->orderBy( $column, $direction );
            }
        }
        return $query->get()->all();
    }

    /**
     * Conta serviços por status e tenant.
     */
    public function countByStatusByTenantId( int $tenantId, string $status ): int
    {
        return $this->applyTenantFilter( $this->model::query(), $tenantId )
            ->where( 'status', $status )
            ->count();
    }

}
