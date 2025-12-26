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
        return new Schedule;
    }

    /**
     * Get the latest schedule for a specific service.
     */
    public function findLatestByServiceId(int $serviceId): ?Schedule
    {
        return $this->model
            ->where('service_id', $serviceId)
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
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get upcoming schedules for a tenant.
     */
    public function getUpcomingSchedules(int $limit = 10): Collection
    {
        return $this->model
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
            ->whereBetween('start_date_time', [$startDate, $endDate])
            ->with(['service', 'service.customer', 'confirmationToken']);

        $this->applyAllScheduleFilters($query, $filters);

        return $query->orderBy('start_date_time', 'asc')->get();
    }

    /**
     * Verifica conflitos de horário.
     */
    public function hasConflict(string $startTime, string $endTime, ?int $serviceId = null, ?int $excludeScheduleId = null): bool
    {
        $query = $this->model
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
     * Busca eventos de hoje.
     */
    public function getTodayEvents(int $limit = 5): Collection
    {
        return $this->model
            ->whereDate('start_date_time', now()->toDateString())
            ->with(['service', 'service.customer'])
            ->orderBy('start_date_time', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtém estatísticas de agendamentos.
     */
    public function getStats(): array
    {
        return [
            'total' => $this->model->count(),
            'upcoming' => $this->model->where('status', '!=', 'cancelled')->where('start_date_time', '>=', now())->count(),
            'today' => $this->model->whereDate('start_date_time', now()->toDateString())->count(),
            'by_status' => $this->model->selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status')->toArray(),
        ];
    }

    /**
     * Get recent upcoming schedules with relations.
     */
    public function getRecentUpcoming(int $limit = 10): Collection
    {
        return $this->model
            ->where('status', '!=', 'cancelled')
            ->where('start_date_time', '>=', now()->subDays(1))
            ->orderBy('start_date_time')
            ->limit($limit)
            ->with(['service.customer', 'service'])
            ->get();
    }

    /**
     * Aplica todos os filtros de agendamento.
     */
    protected function applyAllScheduleFilters(Builder $query, array $filters): void
    {
        // Filtro de busca
        if (! empty($filters['location'])) {
            $query->where('location', 'like', '%'.$filters['location'].'%');
        }

        // Filtros básicos
        $basicFilters = array_intersect_key($filters, array_flip(['service_id', 'status']));
        $this->applyFilters($query, $basicFilters);

        // Filtro de soft delete
        $this->applySoftDeleteFilter($query, $filters);
    }

    public function createFromDTO(\App\DTOs\Schedule\ScheduleDTO $dto): Model
    {
        return $this->create($dto->toArrayWithoutNulls());
    }

    public function updateFromDTO(int $id, \App\DTOs\Schedule\ScheduleUpdateDTO $dto): ?Model
    {
        return $this->update($id, $dto->toArrayWithoutNulls());
    }
}
