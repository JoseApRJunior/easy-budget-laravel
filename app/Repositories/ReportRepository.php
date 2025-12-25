<?php

namespace App\Repositories;

use App\Models\Report;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class ReportRepository extends AbstractTenantRepository
{
    protected function makeModel(): Model
    {
        return new Report();
    }

    public function getPaginated(
        array $filters = [],
        int $perPage = 15,
        array $with = [],
        ?array $orderBy = null,
    ): LengthAwarePaginator {
        $query = $this->model->newQuery();

        // Eager loading paramétrico - mescla com o padrão
        $defaultWith   = ['tenant', 'user'];
        $effectiveWith = array_unique(array_merge($defaultWith, $with));
        $query->with($effectiveWith);

        // Aplicar filtros específicos do Report
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('file_name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('description', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('hash', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['format'])) {
            $query->where('format', $filters['format']);
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Aplicar filtros avançados do trait
        $this->applyFilters($query, $filters);

        // Aplicar filtro de soft delete se necessário
        $this->applySoftDeleteFilter($query, $filters);

        // Aplicar ordenação
        if ($orderBy) {
            $this->applyOrderBy($query, $orderBy);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Per page dinâmico
        $effectivePerPage = $this->getEffectivePerPage($filters, $perPage);

        return $query->paginate($effectivePerPage);
    }

    public function findByHash(string $hash, array $with = []): ?Report
    {
        $query = $this->model->where('hash', $hash);
        if (!empty($with)) {
            $query->with($with);
        }
        return $query->first();
    }

    public function countByType(string $type): int
    {
        return $this->model->where('type', $type)->count();
    }

    public function getRecentReports(int $limit = 10): Collection
    {
        return $this->model->with(['user'])
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function createFromDTO(\App\DTOs\Report\ReportDTO $dto): Model
    {
        return $this->create($dto->toArrayWithoutNulls());
    }

    public function updateFromDTO(int $id, \App\DTOs\Report\ReportDTO $dto): ?Model
    {
        return $this->update($id, $dto->toArrayWithoutNulls());
    }

    public function getModel()
    {
        return $this->model;
    }
}
