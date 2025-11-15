<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Schedule;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ScheduleRepository extends AbstractTenantRepository
{
    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new Schedule();
    }

    /**
     * Get the latest schedule for a specific service.
     *
     * @param int $serviceId
     * @return Schedule|null
     */
    public function findLatestByServiceId( int $serviceId ): ?Schedule
    {
        return $this->model
            ->where( 'service_id', $serviceId )
            ->where( 'tenant_id', $this->getCurrentTenantId() )
            ->latest()
            ->first();
    }

    /**
     * Get schedules by service ID with pagination.
     *
     * @param int $serviceId
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getByServiceIdPaginated( int $serviceId, int $perPage = 15 )
    {
        return $this->model
            ->where( 'service_id', $serviceId )
            ->where( 'tenant_id', $this->getCurrentTenantId() )
            ->latest()
            ->paginate( $perPage );
    }

    /**
     * Get upcoming schedules for a tenant.
     *
     * @param int $limit
     * @return Collection
     */
    public function getUpcomingSchedules( int $limit = 10 ): Collection
    {
        return $this->model
            ->where( 'tenant_id', $this->getCurrentTenantId() )
            ->where( 'start_date_time', '>', now() )
            ->with( [ 'service', 'service.customer' ] )
            ->orderBy( 'start_date_time', 'asc' )
            ->limit( $limit )
            ->get();
    }

    /**
     * Get schedules for a specific date range.
     *
     * @param string $startDate
     * @param string $endDate
     * @return Collection
     */
    public function getByDateRange( string $startDate, string $endDate ): Collection
    {
        return $this->model
            ->where( 'tenant_id', $this->getCurrentTenantId() )
            ->whereBetween( 'start_date_time', [ $startDate, $endDate ] )
            ->with( [ 'service', 'service.customer' ] )
            ->orderBy( 'start_date_time', 'asc' )
            ->get();
    }

    /**
     * Check for scheduling conflicts.
     *
     * @param int $serviceId
     * @param string $startDateTime
     * @param string $endDateTime
     * @param int|null $excludeId
     * @return bool
     */
    public function hasConflict( int $serviceId, string $startDateTime, string $endDateTime, ?int $excludeId = null ): bool
    {
        $query = $this->model
            ->where( 'service_id', $serviceId )
            ->where( 'tenant_id', $this->getCurrentTenantId() )
            ->where( function ( Builder $query ) use ( $startDateTime, $endDateTime ) {
                $query->where( function ( Builder $q ) use ( $startDateTime, $endDateTime ) {
                    $q->where( 'start_date_time', '<', $endDateTime )
                        ->where( 'end_date_time', '>', $startDateTime );
                } );
            } );

        if ( $excludeId ) {
            $query->where( 'id', '!=', $excludeId );
        }

        return $query->exists();
    }

    /**
     * Create a new schedule.
     *
     * @param array $data
     * @return Schedule
     */
    public function create( array $data ): Schedule
    {
        $data[ 'tenant_id' ] = $this->getCurrentTenantId();
        return $this->model->create( $data );
    }

    /**
     * Delete a schedule.
     *
     * @param int $id
     * @return bool
     */
    public function delete( int $id ): bool
    {
        return $this->model
            ->where( 'id', $id )
            ->where( 'tenant_id', $this->getCurrentTenantId() )
            ->delete();
    }

}
