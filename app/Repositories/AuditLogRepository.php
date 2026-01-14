<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\AuditLog\AuditLogDTO;
use App\Models\AuditLog;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Repositório para gerenciamento de logs de auditoria.
 *
 * Esta classe implementa operações CRUD específicas para o modelo AuditLog,
 * fornecendo acesso controlado e consistente aos dados de auditoria do sistema.
 */
class AuditLogRepository extends AbstractTenantRepository
{
    /**
     * Modelo gerenciado por este repositório.
     */
    protected Model $model;

    /**
     * {@inheritdoc}
     */
    protected function makeModel(): Model
    {
        return new AuditLog;
    }

    /**
     * Cria um novo log de auditoria a partir de um DTO.
     */
    public function createFromDTO(AuditLogDTO $dto): Model
    {
        return $this->create($dto->toArrayWithoutNulls());
    }

    /**
     * Busca logs de auditoria com filtros.
     */
    public function getFiltered(array $filters = [], int $perPage = 50): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $this->model->with(['user', 'tenant']);

        $this->applyDateRangeFilter($query, $filters, 'created_at', 'start_date', 'end_date');

        return $this->applyFilters($query, $filters)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Obtém estatísticas de auditoria.
     */
    public function getStats(int $days = 30): array
    {
        $startDate = now()->subDays($days);
        $baseQuery = $this->model->where('created_at', '>=', $startDate);

        return [
            'total_logs' => (clone $baseQuery)->count(),
            'logs_by_severity' => (clone $baseQuery)->selectRaw('severity, COUNT(*) as count')
                ->groupBy('severity')
                ->pluck('count', 'severity')
                ->toArray(),
            'logs_by_action' => (clone $baseQuery)->selectRaw('action, COUNT(*) as count')
                ->groupBy('action')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'action')
                ->toArray(),
        ];
    }

    /**
     * Busca atividades recentes por usuário.
     */
    public function getRecentActivities(int $userId, int $limit = 10): Collection
    {
        return $this->model
            ->where('user_id', $userId)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Busca logs de auditoria por usuário.
     */
    public function findByUserId(int $userId): Collection
    {
        return $this->model->where('user_id', $userId)->get();
    }

    /**
     * Busca logs de auditoria por ação.
     */
    public function findByAction(string $action): Collection
    {
        return $this->model->where('action', $action)->get();
    }

    /**
     * Busca logs de auditoria por severidade.
     */
    public function findBySeverity(string $severity): Collection
    {
        return $this->model->where('severity', $severity)->get();
    }

    /**
     * Busca logs de auditoria por categoria.
     */
    public function findByCategory(string $category): Collection
    {
        return $this->model->where('category', $category)->get();
    }

    /**
     * Busca logs de auditoria por tipo de modelo.
     */
    public function findByModelType(string $modelType): Collection
    {
        return $this->model->where('model_type', $modelType)->get();
    }

    /**
     * Busca logs de auditoria por ID do modelo.
     */
    public function findByModelId(int $modelId): Collection
    {
        return $this->model->where('model_id', $modelId)->get();
    }

    /**
     * Conta logs de auditoria por tenant.
     */
    public function countByTenantId(int $tenantId): int
    {
        return $this->model->where('tenant_id', $tenantId)->count();
    }

    /**
     * Conta logs de auditoria por severidade.
     */
    public function countBySeverity(string $severity): int
    {
        return $this->model->where('severity', $severity)->count();
    }

    /**
     * Conta logs de auditoria por categoria.
     */
    public function countByCategory(string $category): int
    {
        return $this->model->where('category', $category)->count();
    }
}
