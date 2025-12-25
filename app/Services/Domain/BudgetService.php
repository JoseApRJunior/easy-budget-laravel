<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\BudgetStatus;
use App\Enums\OperationStatus;
use App\Events\BudgetStatusChanged;
use App\Models\Budget;
use App\Models\User;
use App\Repositories\BudgetRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use App\DTOs\Budget\BudgetDTO;
use App\DTOs\Budget\BudgetItemDTO;

class BudgetService extends AbstractBaseService
{
    public function __construct(BudgetRepository $budgetRepository)
    {
        parent::__construct($budgetRepository);
    }

    /**
     * Retorna lista paginada de orçamentos para um provider específico.
     */
    public function getBudgetsForProvider(int $userId, array $filters = []): ServiceResult
    {
        return $this->safeExecute(function () use ($filters) {
            $tenantId = $this->ensureTenantId();
            $perPage = (int) ($filters['per_page'] ?? 10);
            unset($filters['per_page']);

            $budgets = $this->repository->getPaginatedBudgets(
                tenantId: $tenantId,
                filters: $filters,
                perPage: $perPage,
            );

            return ServiceResult::success($budgets);
        }, 'Erro ao obter orçamentos.');
    }

    /**
     * Busca um orçamento por código.
     */
    public function findByCode(string $code): ServiceResult
    {
        return $this->safeExecute(function () use ($code) {
            $tenantId = $this->ensureTenantId();
            $budget = $this->repository->findByCode($code, $tenantId);

            if (!$budget) {
                return ServiceResult::error('Orçamento não encontrado.');
            }

            return ServiceResult::success($budget);
        }, 'Erro ao buscar orçamento.');
    }

    /**
     * Obtém estatísticas para o dashboard de orçamentos.
     */
    public function getDashboardStats(): ServiceResult
    {
        return $this->safeExecute(function () {
            $tenantId = $this->ensureTenantId();
            $stats = $this->repository->getDashboardStats($tenantId);
            return ServiceResult::success($stats);
        }, 'Erro ao obter estatísticas do dashboard.');
    }

    /**
     * Cria um novo orçamento usando DTO.
     */
    public function create(BudgetDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($dto) {
            $tenantId = $this->ensureTenantId($dto->tenant_id);

            return DB::transaction(function () use ($dto, $tenantId) {
                $code = $dto->code ?? $this->generateUniqueBudgetCode($tenantId);

                $budgetData = $dto->toArray();
                $budgetData['code'] = $code;
                $budgetData['tenant_id'] = $tenantId;

                $budget = $this->repository->create($budgetData);

                if (!empty($dto->items)) {
                    foreach ($dto->items as $itemDto) {
                        /** @var BudgetItemDTO $itemDto */
                        $itemData = $itemDto->toArray();
                        $itemData['tenant_id'] = $tenantId;
                        $budget->items()->create($itemData);
                    }
                }

                return ServiceResult::success($budget, 'Orçamento criado com sucesso.');
            });
        });
    }

    /**
     * Atualiza um orçamento existente usando DTO.
     */
    public function update($id, BudgetDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($id, $dto) {
            $tenantId = $this->ensureTenantId($dto->tenant_id);

            return DB::transaction(function () use ($id, $dto, $tenantId) {
                $budget = is_numeric($id)
                    ? $this->repository->find((int) $id)
                    : $this->repository->findByCode((string) $id, $tenantId);

                if (!$budget) {
                    return ServiceResult::error('Orçamento não encontrado.');
                }

                $budgetData = $dto->toArray();
                $this->repository->update($budget, $budgetData);

                if (isset($dto->items)) {
                    $budget->items()->delete();
                    foreach ($dto->items as $itemDto) {
                        /** @var BudgetItemDTO $itemDto */
                        $itemData = $itemDto->toArray();
                        $itemData['tenant_id'] = $tenantId;
                        $budget->items()->create($itemData);
                    }
                }

                return ServiceResult::success($budget->fresh(), 'Orçamento atualizado com sucesso.');
            });
        });
    }

    /**
     * Altera o status de um orçamento por código.
     */
    public function changeStatusByCode(string $code, string $status, string $comment = ''): ServiceResult
    {
        return $this->safeExecute(function () use ($code, $status, $comment) {
            $tenantId = $this->ensureTenantId();

            $budget = $this->repository->findByCode($code, $tenantId);

            if (!$budget) {
                return ServiceResult::error('Orçamento não encontrado.');
            }

            return $this->changeStatus($budget, $status, $comment);
        });
    }

    /**
     * Altera o status de um orçamento.
     */
    public function changeStatus(Budget $budget, string $status, string $comment = ''): ServiceResult
    {
        return $this->safeExecute(function () use ($budget, $status, $comment) {
            if (!$this->isValidBudgetStatus($status)) {
                return ServiceResult::error('Status de orçamento inválido: ' . $status);
            }

            return DB::transaction(function () use ($budget, $status, $comment) {
                $oldStatus = $budget->status->value;

                $updated = $this->repository->update($budget, [
                    'status'            => $status,
                    'status_comment'    => $comment,
                    'status_updated_at' => now(),
                    'status_updated_by' => $this->authUser()?->id
                ]);

                if (!$updated) {
                    throw new \Exception('Falha ao alterar status do orçamento.');
                }

                $updatedBudget = $budget->fresh();

                // Disparar evento para notificação
                event(new BudgetStatusChanged($updatedBudget, $oldStatus, $status, $comment));

                // Atualizar serviços em cascata se necessário
                $this->updateRelatedServices($updatedBudget, $status);

                return ServiceResult::success($updatedBudget, 'Status do orçamento alterado com sucesso.');
            });
        });
    }

    /**
     * Exclui um orçamento por código.
     */
    public function deleteByCode(string $code): ServiceResult
    {
        return $this->safeExecute(function () use ($code) {
            $tenantId = $this->ensureTenantId();
            $budget = $this->repository->findByCode($code, $tenantId);

            if (!$budget) {
                return ServiceResult::error('Orçamento não encontrado.');
            }

            return $this->repository->delete($budget->id)
                ? ServiceResult::success(null, 'Orçamento excluído com sucesso.')
                : ServiceResult::error('Falha ao excluir orçamento.');
        });
    }

    /**
     * Gera um código único para o orçamento.
     */
    private function generateUniqueBudgetCode(int $tenantId): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "BUD-{$year}{$month}-";

        $lastBudget = $this->repository->getLastBudgetByMonth($year, $month, $tenantId);

        if (!$lastBudget) {
            return "{$prefix}0001";
        }

        $lastCode = $lastBudget->code;
        $lastNumber = (int) substr($lastCode, -4);
        $newNumber = str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);

        return "{$prefix}{$newNumber}";
    }

    /**
     * Valida se o status é válido.
     */
    private function isValidBudgetStatus(string $status): bool
    {
        return BudgetStatus::tryFrom($status) !== null;
    }

    /**
     * Atualiza serviços relacionados baseado no novo status do orçamento.
     */
    private function updateRelatedServices(Budget $budget, string $newStatus): void
    {
        $serviceStatus = match (strtolower($newStatus)) {
            'approved'              => 'in-progress',
            'rejected', 'cancelled' => 'cancelled',
            default                 => null
        };

        if ($serviceStatus && $budget->services()->exists()) {
            $budget->services()->update(['status' => $serviceStatus]);
        }
    }
}
