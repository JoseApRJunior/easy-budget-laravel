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

use App\Repositories\BudgetItemRepository;
use App\Repositories\ServiceRepository;
use App\Services\Domain\BudgetCodeGeneratorService;

class BudgetService extends AbstractBaseService
{
    public function __construct(
        BudgetRepository $budgetRepository,
        private readonly BudgetItemRepository $budgetItemRepository,
        private readonly ServiceRepository $serviceRepository,
        private readonly BudgetCodeGeneratorService $codeGeneratorService
    ) {
        parent::__construct($budgetRepository);
    }

    /**
     * Retorna lista paginada de orçamentos para um provider específico.
     */
    public function getBudgetsForProvider(int $userId, array $filters = []): ServiceResult
    {
        return $this->safeExecute(function () use ($filters) {
            $perPage = (int) ($filters['per_page'] ?? 10);
            unset($filters['per_page']);

            $budgets = $this->repository->getPaginatedBudgets(
                filters: $filters,
                perPage: $perPage,
            );

            return ServiceResult::success($budgets);
        }, 'Erro ao obter orçamentos.');
    }

    /**
     * Busca um orçamento por código com relações opcionais.
     */
    public function findByCode(string $code, array $with = []): ServiceResult
    {
        return $this->safeExecute(function () use ($code, $with) {
            $budget = $this->repository->findByCode($code, $with);

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
            $stats = $this->repository->getDashboardStats();
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
                // Gera o código usando o serviço especializado
                $codeResult = $this->codeGeneratorService->generateUniqueCode($tenantId);
                if ($codeResult->isError()) {
                    throw new \Exception($codeResult->getMessage());
                }

                $code = $dto->code ?? $codeResult->getData();

                // Prepara dados para criação
                $budgetData = array_merge($dto->toArray(), [
                    'code'      => $code,
                    'tenant_id' => $tenantId,
                ]);

                // Cria o orçamento usando o repositório
                $budget = $this->repository->create($budgetData);

                // Cria os itens usando o repositório de itens
                if (!empty($dto->items)) {
                    foreach ($dto->items as $itemDto) {
                        /** @var BudgetItemDTO $itemDto */
                        $this->budgetItemRepository->createFromDTO($itemDto, $budget->id);
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
            return DB::transaction(function () use ($id, $dto) {
                $budget = is_numeric($id)
                    ? $this->repository->find((int) $id)
                    : $this->repository->findByCode((string) $id);

                if (!$budget) {
                    return ServiceResult::error('Orçamento não encontrado.');
                }

                // Atualiza o orçamento principal
                $this->repository->update($budget->id, $dto->toArray());

                // Atualiza os itens: remove antigos e insere novos (estratégia simples)
                if (isset($dto->items)) {
                    $this->budgetItemRepository->deleteByBudgetId($budget->id);
                    foreach ($dto->items as $itemDto) {
                        /** @var BudgetItemDTO $itemDto */
                        $this->budgetItemRepository->createFromDTO($itemDto, $budget->id);
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
            $budget = $this->repository->findByCode($code);

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

                // Atualizar serviços em cascata se necessário usando o repositório
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
            $budget = $this->repository->findByCode($code);

            if (!$budget) {
                return ServiceResult::error('Orçamento não encontrado.');
            }

            // Remove itens antes de excluir o orçamento (ou depende de cascade delete no DB)
            $this->budgetItemRepository->deleteByBudgetId($budget->id);

            return $this->repository->delete($budget->id)
                ? ServiceResult::success(null, 'Orçamento excluído com sucesso.')
                : ServiceResult::error('Falha ao excluir orçamento.');
        });
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

        if ($serviceStatus) {
            $this->serviceRepository->updateStatusByBudgetId($budget->id, $serviceStatus);
        }
    }
}
