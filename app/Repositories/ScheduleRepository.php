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
     */
    public function findLatestByServiceId(int $serviceId): ?Schedule
    {
        return $this->model
            ->where('service_id', $serviceId)
            ->where('tenant_id', $this->getCurrentTenantId())
            ->latest()
            ->first();
    }

    /**
     * Get schedules by service ID with pagination.
     */
    public function getByServiceIdPaginated(int $serviceId, int $perPage = 15)
    {
        return $this->model
            ->where('service_id', $serviceId)
            ->where('tenant_id', $this->getCurrentTenantId())
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get upcoming schedules for a tenant.
     */
    public function getUpcomingSchedules(int $limit = 10): Collection
    {
        return $this->model
            ->where('tenant_id', $this->getCurrentTenantId())
            ->where('start_date_time', '>', now())
            ->with(['service', 'service.customer'])
            ->orderBy('start_date_time', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get schedules for a specific date range.
     */
    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return $this->model
            ->where('tenant_id', $this->getCurrentTenantId())
            ->whereBetween('start_date_time', [$startDate, $endDate])
            ->with(['service', 'service.customer'])
            ->orderBy('start_date_time', 'asc')
            ->get();
    }

    /**
     * Get schedules by date range with relations.
     */
    public function getByDateRangeWithRelations(string $startDate, string $endDate, array $filters = []): Collection
    {
        $query = $this->model
            ->where('tenant_id', $this->getCurrentTenantId())
            ->whereBetween('start_date_time', [$startDate, $endDate])
            ->with(['service', 'service.customer', 'confirmationToken']);

        if (isset($filters['service_id'])) {
            $query->where('service_id', $filters['service_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['location'])) {
            $query->where('location', 'like', '%' . $filters['location'] . '%');
        }

        return $query->orderBy('start_date_time', 'asc')->get();
    }

    /**
     * Verifica conflitos de horário.
     */
    public function hasConflict(string $startTime, string $endTime, ?int $serviceId = null, ?int $excludeScheduleId = null): bool
    {
        $query = $this->model
            ->where('tenant_id', $this->getCurrentTenantId())
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where('start_date_time', '<', $endTime)
                    ->where('end_date_time', '>', $startTime);
            });

        if ($serviceId) {
            $query->where('service_id', $serviceId);
        }

        if ($excludeScheduleId) {
            $query->where('id', '!=', $excludeScheduleId);
        }

        return $query->exists();
    }

    /**
     * Busca um agendamento por ID e Tenant.
     */
    public function findByIdAndTenant(int $id, int $tenantId): ?Schedule
    {
        return $this->model
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    /**
     * Busca eventos de hoje por tenant.
     */
    public function getTodayEvents(int $tenantId, int $limit = 5): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->whereDate('start_date_time', now()->toDateString())
            ->with(['service', 'service.customer'])
            ->orderBy('start_date_time', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get count by status.
     */
    public function getCountByStatus(int $tenantId, string $status): int
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('status', $status)
            ->count();
    }

    /**
     * Get count of upcoming schedules.
     */
    public function getUpcomingCount(int $tenantId): int
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('status', '!=', 'cancelled')
            ->where('start_date_time', '>=', now())
            ->count();
    }

    /**
     * Get recent upcoming schedules with relations.
     */
    public function getRecentUpcoming(int $tenantId, int $limit = 10): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('status', '!=', 'cancelled')
            ->where('start_date_time', '>=', now()->subDays(1))
            ->orderBy('start_date_time')
            ->limit($limit)
            ->with(['service.customer', 'service'])
            ->get();
    }

    /**
     * Get total count for tenant.
     */
    public function getTotalCount(int $tenantId): int
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->count();
    }

    /**
     * Get count for past schedules (completed).
     */
    public function getPastCount(int $tenantId): int
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('end_date_time', '<', now())
            ->count();
    }

    /**
     * Get count for future schedules (pending).
     */
    public function getFutureCount(int $tenantId): int
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('start_date_time', '>', now())
            ->count();
    }
}
