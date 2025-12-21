<?php
declare(strict_types=1);

namespace App\Services\Core\Abstracts;

use App\Enums\OperationStatus;
use App\Models\User;
use App\Repositories\Contracts\BaseRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Services\Core\Contracts\CrudServiceInterface;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Classe base abstrata para todos os serviços (Nível Sênior).
 * Centraliza lógica de CRUD, tratamento de erros e delegação para repositórios.
 */
abstract class AbstractBaseService implements CrudServiceInterface
{
    protected BaseRepositoryInterface $repository;

    public function __construct(BaseRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Helper para execução segura de operações com tratamento de erro padronizado.
     */
    protected function safeExecute(callable $callback, string $errorMessage = 'Erro ao processar operação.'): ServiceResult
    {
        try {
            $data = $callback();
            return $data instanceof ServiceResult ? $data : $this->success($data);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error($errorMessage, ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->error(OperationStatus::CONFLICT, 'Erro de integridade de dados ou conflito no banco.', null, $e);
        } catch (Exception $e) {
            Log::error($errorMessage, ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->error(OperationStatus::ERROR, $errorMessage, null, $e);
        }
    }

    // --- Read Operations ---

    public function findById(int $id, array $with = []): ServiceResult
    {
        return $this->safeExecute(function() use ($id) {
            $entity = $this->repository->find($id);
            return $entity ?: $this->error(OperationStatus::NOT_FOUND, "Recurso com ID {$id} não encontrado.");
        });
    }

    public function list(array $filters = []): ServiceResult
    {
        return $this->safeExecute(function() use ($filters) {
            $criteria = $this->extractCriteriaFromFilters($filters);
            $orderBy = $this->extractOrderByFromFilters($filters);

            if ($this->repository instanceof TenantRepositoryInterface) {
                return $this->repository->getAllByTenant($criteria, $orderBy, $filters['limit'] ?? null, $filters['offset'] ?? null);
            }

            return $this->repository->getAll(); // Fallback básico
        });
    }

    public function count(array $filters = []): ServiceResult
    {
        return $this->safeExecute(function() use ($filters) {
            if ($this->repository instanceof TenantRepositoryInterface) {
                return $this->repository->countByTenant($this->extractCriteriaFromFilters($filters));
            }
            return $this->repository->getAll()->count();
        });
    }

    // --- Write Operations ---

    public function create(array $data): ServiceResult
    {
        return $this->safeExecute(fn() => $this->repository->create($data), 'Erro ao criar recurso.');
    }

    public function update(int $id, array $data): ServiceResult
    {
        return $this->safeExecute(function() use ($id, $data) {
            $entity = $this->repository->update($id, $data);
            return $entity ?: $this->error(OperationStatus::NOT_FOUND, 'Recurso não encontrado para atualização.');
        }, 'Erro ao atualizar recurso.');
    }

    public function delete(int $id): ServiceResult
    {
        return $this->safeExecute(function() use ($id) {
            return $this->repository->delete($id)
                ? $this->success(null, 'Recurso excluído.')
                : $this->error(OperationStatus::NOT_FOUND, 'Recurso não encontrado.');
        }, 'Erro ao excluir recurso.');
    }

    // --- Batch Operations ---

    public function deleteMany(array $ids): ServiceResult
    {
        return $this->safeExecute(function() use ($ids) {
            if ($this->repository instanceof TenantRepositoryInterface) {
                $count = $this->repository->deleteManyByTenant($ids);
                return ['deleted_count' => $count];
            }
            // Fallback loop se necessário, mas idealmente todos os repos usam a interface tenant
            return ['deleted_count' => collect($ids)->filter(fn($id) => $this->repository->delete($id))->count()];
        });
    }

    public function updateMany(array $ids, array $data): ServiceResult
    {
        return $this->safeExecute(function() use ($ids, $data) {
            $count = collect($ids)->filter(fn($id) => $this->repository->update($id, $data))->count();
            return ['updated_count' => $count];
        });
    }

    // --- Extra CRUD operations ---

    public function findMany(array $ids, array $with = []): ServiceResult
    {
        return $this->safeExecute(function() use ($ids) {
            if ($this->repository instanceof TenantRepositoryInterface) {
                return $this->repository->findManyByTenant($ids);
            }
            return $this->repository->getAll()->whereIn('id', $ids);
        });
    }

    public function exists(array $criteria): ServiceResult
    {
        return $this->safeExecute(function() use ($criteria) {
            if ($this->repository instanceof TenantRepositoryInterface) {
                return $this->repository->countByTenant($criteria) > 0;
            }
            return false; // Implementar fallback se necessário
        });
    }

    public function restore(int $id): ServiceResult
    {
        return $this->safeExecute(function() use ($id) {
            if ($this->repository instanceof TenantRepositoryInterface) {
                return $this->repository->restoreManyByTenant([$id]) > 0
                    ? $this->success(null, 'Recurso restaurado.')
                    : $this->error(OperationStatus::NOT_FOUND, 'Recurso não encontrado ou não deletado.');
            }
            return $this->error(OperationStatus::ERROR, 'Repositório não suporta restauração.');
        });
    }

    // --- Helpers ---

    protected function success(mixed $data = null, string $message = ''): ServiceResult
    {
        return ServiceResult::success($data, $message);
    }

    protected function error(OperationStatus|string $status, string $message = '', mixed $data = null, ?Exception $exception = null): ServiceResult
    {
        $finalStatus = is_string($status) ? OperationStatus::ERROR : $status;
        $finalMessage = is_string($status) ? $status : $message;
        return ServiceResult::error($finalStatus, $finalMessage, $data, $exception);
    }

    protected function extractCriteriaFromFilters(array $filters): array
    {
        return collect($filters)->except(['per_page', 'page', 'order_by', 'order_direction', 'limit', 'offset', 'with'])->toArray();
    }

    protected function extractOrderByFromFilters(array $filters): ?array
    {
        return (isset($filters['order_by']) && isset($filters['order_direction']))
            ? [$filters['order_by'] => $filters['order_direction']]
            : null;
    }

    protected function authUser(): ?User { return Auth::user(); }
    protected function tenantId(): ?int { return $this->authUser()?->tenant_id; }

    // Métodos vazios para evitar erro em instâncias que não os sobrescrevem
    public function findOneBy(array $criteria, array $with = []): ServiceResult { return $this->error('Não implementado'); }
    public function deleteByCriteria(array $criteria): ServiceResult { return $this->error('Não implementado'); }
    public function duplicate(int $id, array $overrides = []): ServiceResult { return $this->error('Não implementado'); }
    public function getStats(array $filters = []): ServiceResult { return $this->error('Não implementado'); }
    protected function getSupportedFilters(): array { return []; }
}
