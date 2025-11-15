<?php

namespace App\Repositories;

use App\Models\Report;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ReportRepository extends AbstractTenantRepository
{
    protected function makeModel(): Model
    {
        return new Report();
    }

    public function getPaginated( array $filters = [], int $perPage = 15 ): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with( [ 'tenant', 'user' ] );

        if ( !empty( $filters[ 'search' ] ) ) {
            $query->where( function ( $q ) use ( $filters ) {
                $q->where( 'file_name', 'like', '%' . $filters[ 'search' ] . '%' )
                    ->orWhere( 'description', 'like', '%' . $filters[ 'search' ] . '%' )
                    ->orWhere( 'hash', 'like', '%' . $filters[ 'search' ] . '%' );
            } );
        }

        if ( !empty( $filters[ 'type' ] ) ) {
            $query->where( 'type', $filters[ 'type' ] );
        }

        if ( !empty( $filters[ 'status' ] ) ) {
            $query->where( 'status', $filters[ 'status' ] );
        }

        if ( !empty( $filters[ 'format' ] ) ) {
            $query->where( 'format', $filters[ 'format' ] );
        }

        if ( !empty( $filters[ 'start_date' ] ) ) {
            $query->whereDate( 'created_at', '>=', $filters[ 'start_date' ] );
        }

        if ( !empty( $filters[ 'end_date' ] ) ) {
            $query->whereDate( 'created_at', '<=', $filters[ 'end_date' ] );
        }

        if ( !empty( $filters[ 'user_id' ] ) ) {
            $query->where( 'user_id', $filters[ 'user_id' ] );
        }

        return $query->orderBy( 'created_at', 'desc' )->paginate( $perPage );
    }

    public function findByHash( string $hash, array $with = [] ): ?Report
    {
        $query = $this->model->where( 'hash', $hash );
        if ( !empty( $with ) ) {
            $query->with( $with );
        }
        return $query->first();
    }

    public function countByType( string $type ): int
    {
        return $this->model->where( 'type', $type )->count();
    }

    public function getRecentReports( int $limit = 10 ): Collection
    {
        return $this->model->with( [ 'user' ] )
            ->where( 'status', 'completed' )
            ->orderBy( 'generated_at', 'desc' )
            ->limit( $limit )
            ->get();
    }

    public function getModel()
    {
        return $this->model;
    }

}
