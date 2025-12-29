<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\DTOs\Budget\BudgetDTO;
use App\DTOs\Budget\BudgetItemDTO;
use App\Enums\BudgetStatus;
use App\Enums\ServiceStatus;
use App\Events\BudgetStatusChanged;
use App\Models\Budget;
use App\Repositories\BudgetItemRepository;
use App\Repositories\BudgetRepository;
use App\Repositories\ServiceRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\DB;

class BudgetService extends AbstractBaseService
{
    public function __construct(
        BudgetRepository $budgetRepository,
        private readonly ServiceRepository $serviceRepository,
        private readonly \App\Repositories\ServiceItemRepository $itemRepository,
        private readonly BudgetCodeGeneratorService $codeGeneratorService,
    ) {
        parent::__construct($budgetRepository);
    }

    /**
     * Retorna lista paginada de orçamentos.
     */
    public function getBudgetsForProvider(array $filters = []): ServiceResult
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

            if (! $budget) {
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
    public function create(array|BudgetDTO $data): ServiceResult
    {
        return $this->safeExecute(function () use ($data) {
            $dto = $data instanceof BudgetDTO ? $data : BudgetDTO::fromRequest($data);

            return DB::transaction(function () use ($dto) {
                // Gera o código usando o serviço especializado
                $codeResult = $this->codeGeneratorService->generateUniqueCode();
                if ($codeResult->isError()) {
                    throw new \Exception($codeResult->getMessage());
                }

                $code = $dto->code ?? $codeResult->getData();

                // Cria um novo DTO com o código gerado
                $finalDto = new BudgetDTO(
                    customer_id: $dto->customer_id,
                    status: $dto->status,
                    code: $code,
                    due_date: $dto->due_date,
                    discount: $dto->discount,
                    total: $dto->total,
                    description: $dto->description,
                    payment_terms: $dto->payment_terms,
                    items: $dto->items,
                );

                // Cria o orçamento usando o repositório
                $budget = $this->repository->createFromDTO($finalDto);

                // No novo modelo hierárquico, criamos Serviços vinculados
                if (! empty($dto->services)) {
                    foreach ($dto->services as $serviceDto) {
                        /** @var \App\DTOs\Service\ServiceDTO $serviceDto */

                        // Garante o vínculo com o orçamento criado
                        $finalServiceDto = new \App\DTOs\Service\ServiceDTO(
                            budget_id: $budget->id,
                            category_id: $serviceDto->category_id,
                            status: $serviceDto->status,
                            code: $serviceDto->code,
                            description: $serviceDto->description,
                            discount: $serviceDto->discount,
                            total: $serviceDto->total,
                            due_date: $serviceDto->due_date,
                            reason: $serviceDto->reason,
                            items: $serviceDto->items,
                            tenant_id: $budget->tenant_id
                        );

                        // Cria o serviço usando o repository correspondente
                        $service = $this->serviceRepository->createFromDTO($finalServiceDto);

                        // Cria os itens do serviço usando seu repositório especializado
                        if (! empty($serviceDto->items)) {
                            foreach ($serviceDto->items as $itemDto) {
                                /** @var \App\DTOs\Service\ServiceItemDTO $itemDto */
                                // O Repositório de itens de serviço deve ser o ServiceItemRepository
                                if ($this->serviceRepository instanceof \App\Repositories\ServiceRepository) {
                                    // Assumindo que o ServiceRepository tem acesso ou injeta o ServiceItemRepository
                                    // Mas aqui usamos o repositório injetado no construtor
                                    $this->itemRepository->createFromDTO($itemDto, $service->id);
                                }
                            }
                        }
                    }
                }

                return ServiceResult::success($budget, 'Orçamento criado com sucesso.');
            });
        });
    }

    /**
     * Atualiza um orçamento existente usando DTO.
     */
    public function update(int|string $id, array|BudgetDTO $data): ServiceResult
    {
        return $this->safeExecute(function () use ($id, $data) {
            $dto = $data instanceof BudgetDTO ? $data : BudgetDTO::fromRequest($data);

            return DB::transaction(function () use ($id, $dto) {
                $budget = is_numeric($id)
                    ? $this->repository->find((int) $id)
                    : $this->repository->findByCode((string) $id);

                if (! $budget) {
                    return ServiceResult::error('Orçamento não encontrado.');
                }

                // Atualiza o orçamento principal usando DTO
                $this->repository->updateFromDTO($budget->id, $dto);

                // Atualiza os serviços: estratégia de sincronização completa
                if (isset($dto->services)) {
                    // Remove serviços e itens órfãos vinculados a este orçamento
                    $oldServices = $budget->services;
                    foreach ($oldServices as $oldService) {
                        $oldService->serviceItems()->delete();
                        $oldService->delete();
                    }

                    // Recria a hierarquia completa
                    foreach ($dto->services as $serviceDto) {
                        /** @var \App\DTOs\Service\ServiceDTO $serviceDto */
                        $finalServiceDto = new \App\DTOs\Service\ServiceDTO(
                            budget_id: $budget->id,
                            category_id: $serviceDto->category_id,
                            status: $serviceDto->status,
                            code: $serviceDto->code,
                            description: $serviceDto->description,
                            discount: $serviceDto->discount,
                            total: $serviceDto->total,
                            due_date: $serviceDto->due_date,
                            reason: $serviceDto->reason,
                            items: $serviceDto->items,
                            tenant_id: $budget->tenant_id
                        );

                        $service = $this->serviceRepository->createFromDTO($finalServiceDto);

                        if (! empty($serviceDto->items)) {
                            foreach ($serviceDto->items as $itemDto) {
                                /** @var \App\DTOs\Service\ServiceItemDTO $itemDto */
                                $this->itemRepository->createFromDTO($itemDto, $service->id);
                            }
                        }
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

            if (! $budget) {
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
            $newStatusEnum = BudgetStatus::tryFrom($status);
            if (! $newStatusEnum) {
                return ServiceResult::error('Status de orçamento inválido: '.$status);
            }

            // Validação de transição de estado
            if (! $budget->status->canTransitionTo($newStatusEnum)) {
                return ServiceResult::error("Transição de status não permitida: {$budget->status->value} -> {$status}");
            }

            // Validação de integridade para conclusão
            if ($newStatusEnum === BudgetStatus::COMPLETED) {
                $hasPendingServices = $budget->services()->whereNotIn('status', ServiceStatus::getFinalStatuses())->exists();
                if ($hasPendingServices) {
                    return ServiceResult::error('Não é possível concluir o orçamento pois existem serviços pendentes de execução.');
                }
            }

            return DB::transaction(function () use ($budget, $status, $comment) {
                $oldStatus = $budget->status->value;

                $updated = $this->repository->update($budget->id, [
                    'status' => $status,
                    'status_comment' => $comment,
                    'status_updated_at' => now(),
                    'status_updated_by' => $this->authUser()?->id,
                ]);

                if (! $updated) {
                    throw new \Exception('Falha ao alterar status do orçamento.');
                }

                $updatedBudget = $budget->fresh();

                // Disparar evento para notificação
                event(new BudgetStatusChanged($updatedBudget, $oldStatus, $status, $comment));

                // NOTA: A atualização de serviços em cascata agora é gerenciada pelo BudgetStatusObserver
                // para garantir consistência em todas as formas de atualização do modelo.

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

            if (! $budget) {
                return ServiceResult::error('Orçamento não encontrado.');
            }

            // Remove serviços e seus itens antes de excluir o orçamento
            foreach ($budget->services as $service) {
                $service->serviceItems()->delete();
                $service->delete();
            }

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
}
