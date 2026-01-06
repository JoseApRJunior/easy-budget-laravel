<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\DTOs\Budget\BudgetDTO;
use App\Enums\BudgetStatus;
use App\Enums\ServiceStatus;
use App\Events\BudgetStatusChanged;
use App\Models\Budget;
use App\Repositories\BudgetRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\DB;

class BudgetService extends AbstractBaseService
{
    public function __construct(
        BudgetRepository $budgetRepository,
        private readonly ServiceService $serviceService,
        private readonly BudgetCodeGeneratorService $codeGeneratorService,
        private readonly \App\Actions\Budget\SendBudgetToCustomerAction $sendAction,
        private readonly \App\Actions\Budget\ReserveBudgetProductsAction $reserveAction,
    ) {
        parent::__construct($budgetRepository);
    }

    /**
     * Envia o orçamento para o cliente.
     */
    public function sendToCustomer(string $code, ?string $message = null): ServiceResult
    {
        return $this->safeExecute(function () use ($code, $message) {
            $budget = $this->repository->findByCode($code);

            if (! $budget) {
                return ServiceResult::error('Orçamento não encontrado.');
            }

            return $this->sendAction->execute($budget, $message);
        });
    }

    /**
     * Retorna lista paginada de orçamentos.
     */
    public function getBudgetsForProvider(array $filters = []): ServiceResult
    {
        $perPage = (int) ($filters['per_page'] ?? 15);

        return $this->paginate(
            filters: $filters,
            perPage: $perPage,
            with: ['customer.commonData']
        );
    }

    /**
     * Retorna lista de orçamentos filtrados (usado em relatórios).
     */
    public function getFilteredBudgets(array $filters = []): ServiceResult
    {
        return $this->safeExecute(function () use ($filters) {
            $budgets = $this->repository->getFilteredBudgets($filters);

            return ServiceResult::success($budgets);
        }, 'Erro ao obter orçamentos filtrados.');
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
                    services: $dto->services,
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

                        // Cria o serviço usando o ServiceService
                        $serviceResult = $this->serviceService->create($finalServiceDto);

                        if ($serviceResult->isError()) {
                            throw new \Exception($serviceResult->getMessage());
                        }
                    }
                }

                // Atualiza o total do orçamento baseado nos serviços criados
                $this->updateBudgetTotal($budget->id);

                return ServiceResult::success($budget->fresh(), 'Orçamento criado com sucesso.');
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
                    foreach ($budget->services as $oldService) {
                        $this->serviceService->deleteByCode($oldService->code);
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

                        $serviceResult = $this->serviceService->create($finalServiceDto);
                        if ($serviceResult->isError()) {
                            throw new \Exception($serviceResult->getMessage());
                        }
                    }
                }

                // Atualiza o total do orçamento baseado nos serviços atualizados
                $this->updateBudgetTotal($budget->id);

                return ServiceResult::success($budget->fresh(), 'Orçamento atualizado com sucesso.');
            });
        });
    }

    /**
     * Atualiza o valor total do orçamento com base na soma dos seus serviços.
     */
    private function updateBudgetTotal(int $budgetId): void
    {
        $budget = $this->repository->find($budgetId);
        if ($budget) {
            $total = $budget->services()->sum('total');
            $this->repository->update($budget->id, ['total' => $total]);
        }
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
