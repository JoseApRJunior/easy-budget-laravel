<?php

namespace App\Repositories;

use App\DTOs\Report\ReportDTO;
use App\Models\Report;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class ReportRepository extends AbstractTenantRepository
{
    protected function makeModel(): Model
    {
        return new Report;
    }

    public function getPaginated(
        array $filters = [],
        int $perPage = 15,
        array $with = [],
        ?array $orderBy = null,
    ): LengthAwarePaginator {
        $query = $this->model->newQuery();

        // Eager loading paramétrico - mescla com o padrão
        $defaultWith = ['tenant', 'user'];
        $effectiveWith = array_unique(array_merge($defaultWith, $with));
        $query->with($effectiveWith);

        // Aplicar filtros específicos do Report
        $this->applyAllReportFilters($query, $filters);

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

    /**
     * Aplica todos os filtros de relatório.
     */
    protected function applyAllReportFilters($query, array $filters): void
    {
        // Filtro de busca (search)
        $this->applySearchFilter($query, $filters, ['file_name', 'description', 'hash']);

        // Filtros de data (start_date, end_date)
        if (! empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        // Filtros básicos (type, status, format, user_id)
        // O applyFilters trata automaticamente filtros de igualdade e arrays
        $basicFilters = array_intersect_key($filters, array_flip(['type', 'status', 'format', 'user_id']));
        $this->applyFilters($query, $basicFilters);

        // Aplicar filtro de soft delete se necessário
        $this->applySoftDeleteFilter($query, $filters);
    }

    public function findByHash(string $hash, array $with = []): ?Report
    {
        $query = $this->model->where('hash', $hash);
        if (! empty($with)) {
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

    /**
     * Obtém estatísticas de relatórios.
     */
    public function getStats(): array
    {
        return [
            'total_reports' => $this->model->count(),
            'completed_today' => $this->model->where('status', 'completed')->whereDate('created_at', today())->count(),
            'by_type' => $this->model->selectRaw('type, count(*) as count')->groupBy('type')->pluck('count', 'type')->toArray(),
            'by_status' => $this->model->selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status')->toArray(),
        ];
    }

    public function createFromDTO(ReportDTO $dto): Model
    {
        return $this->create($dto->toArrayWithoutNulls());
    }

    public function updateFromDTO(int $id, ReportDTO $dto): ?Model
    {
        return $this->update($id, $dto->toArrayWithoutNulls());
    }

    public function getModel()
    {
        return $this->model;
    }
}
