<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\DTOs\Service\ServiceDTO;
use App\DTOs\Service\ServiceItemDTO;
use App\Enums\ScheduleStatus;
use App\Enums\ServiceStatus;
use App\Repositories\ServiceItemRepository;
use App\Repositories\ServiceRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\DB;

class ServiceService extends AbstractBaseService
{
    protected ScheduleService $scheduleService;

    protected ServiceItemRepository $itemRepository;

    public function __construct(
        ServiceRepository $serviceRepository,
        ScheduleService $scheduleService,
        ServiceItemRepository $itemRepository,
    ) {
        parent::__construct($serviceRepository);
        $this->scheduleService = $scheduleService;
        $this->itemRepository = $itemRepository;
    }

    /**
     * Obtém estatísticas para o dashboard de serviços.
     */
    public function getDashboardStats(): ServiceResult
    {
        return $this->safeExecute(function () {
            $stats = $this->repository->countByStatus();
            $total = array_sum($stats);

            $recent = $this->repository->getFiltered([], ['created_at' => 'desc'], 10);

            // Calcular o valor total de todos os serviços
            $totalValue = $this->repository->sumTotal();

            // Formatar a distribuição por status com cores e labels
            $statusBreakdown = [];
            foreach ($stats as $statusValue => $count) {
                $statusEnum = ServiceStatus::tryFrom($statusValue);
                if ($statusEnum) {
                    $statusBreakdown[$statusValue] = [
                        'count' => (int) $count,
                        'color' => $statusEnum->getColor(),
                        'label' => $statusEnum->label(),
                    ];
                }
            }

            $dashboardData = [
                'total_services' => $total,
                'status_breakdown' => $statusBreakdown,
                'recent_services' => $recent,
                'completed_services' => $stats[ServiceStatus::COMPLETED->value] ?? 0,
                'in_progress_services' => $stats[ServiceStatus::IN_PROGRESS->value] ?? 0,
                'pending_services' => $stats[ServiceStatus::PENDING->value] ?? 0,
                'cancelled_services' => $stats[ServiceStatus::CANCELLED->value] ?? 0,
                'total_service_value' => $totalValue,
                'approved_services' => $stats[ServiceStatus::APPROVED->value] ?? 0,
                'rejected_services' => $stats[ServiceStatus::REJECTED->value] ?? 0,
            ];

            return ServiceResult::success($dashboardData);
        }, 'Erro ao obter estatísticas do dashboard.');
    }

    /**
     * Busca serviço por código.
     */
    public function findByCode(string $code, array $with = []): ServiceResult
    {
        return $this->safeExecute(function () use ($code, $with) {
            $service = $this->repository->findByCode($code, $with);

            if (! $service) {
                return ServiceResult::error("Serviço com código {$code} não encontrado.");
            }

            return ServiceResult::success($service);
        });
    }

    /**
     * Gera o próximo código disponível para um novo serviço.
     */
    public function generateNextCode(): ServiceResult
    {
        return $this->safeExecute(function () {
            $code = $this->repository->generateUniqueCode();
            return ServiceResult::success($code);
        }, 'Erro ao gerar código de serviço.');
    }

    /**
     * Cria um novo serviço usando DTO.
     */
    public function create(array|ServiceDTO $data): ServiceResult
    {
        return $this->safeExecute(function () use ($data) {
            $dto = $data instanceof ServiceDTO ? $data : ServiceDTO::fromRequest($data);

            return DB::transaction(function () use ($dto) {
                // Prepara dados - o código pode vir no DTO ou ser gerado
                $serviceData = $dto->toDatabaseArray();
                if (empty($serviceData['code'])) {
                    $serviceData['code'] = $this->repository->generateUniqueCode();
                }

                // Garante que o status seja draft se não for informado
                if (empty($serviceData['status'])) {
                    $serviceData['status'] = ServiceStatus::DRAFT->value;
                }

                $service = $this->repository->create($serviceData);

                if (! empty($dto->items)) {
                    foreach ($dto->items as $itemDto) {
                        /** @var ServiceItemDTO $itemDto */
                        $this->itemRepository->createFromDTO($itemDto, $service->id);
                    }
                }

                $this->updateServiceTotal($service->id);

                return ServiceResult::success($service->fresh(), 'Serviço criado com sucesso.');
            });
        });
    }

    /**
     * Atualiza um serviço existente usando DTO.
     */
    public function update(int|string $id, array|ServiceDTO $data): ServiceResult
    {
        return $this->safeExecute(function () use ($id, $data) {
            $dto = $data instanceof ServiceDTO ? $data : ServiceDTO::fromRequest($data);

            return DB::transaction(function () use ($id, $dto) {
                $service = is_numeric($id)
                    ? $this->repository->find((int) $id)
                    : $this->repository->findByCode((string) $id);

                if (! $service) {
                    return ServiceResult::error('Serviço não encontrado.');
                }

                $this->repository->updateFromDTO($service->id, $dto);

                if (isset($dto->items)) {
                    $service->serviceItems()->delete();
                    foreach ($dto->items as $itemDto) {
                        /** @var ServiceItemDTO $itemDto */
                        $service->serviceItems()->create($itemDto->toDatabaseArray());
                    }
                }

                $this->updateServiceTotal($service->id);

                return ServiceResult::success($service->fresh(), 'Serviço atualizado com sucesso.');
            });
        });
    }

    /**
     * Atualiza o valor total do serviço com base na soma dos seus itens.
     */
    private function updateServiceTotal(int $serviceId): void
    {
        $service = $this->repository->find($serviceId);
        if ($service) {
            $total = $service->serviceItems()->sum('total');
            $this->repository->update($service->id, ['total' => $total]);
        }
    }

    /**
     * Busca serviços com filtros avançados.
     */
    public function getFilteredServices(array $filters = [], ?array $orderBy = null, ?int $limit = null): ServiceResult
    {
        return $this->safeExecute(function () use ($filters, $orderBy, $limit) {
            $services = $this->repository->getFiltered($filters, $orderBy, $limit);

            return ServiceResult::success($services);
        }, 'Erro ao obter serviços filtrados.');
    }

    /**
     * Altera o status de um serviço por código.
     */
    public function changeStatusByCode(string $code, string $status): ServiceResult
    {
        return $this->safeExecute(function () use ($code, $status) {
            $service = $this->repository->findByCode($code);

            if (! $service) {
                return ServiceResult::error('Serviço não encontrado.');
            }

            return $this->changeStatus($service->id, $status);
        });
    }

    /**
     * Exclui um serviço por código.
     */
    public function deleteByCode(string $code): ServiceResult
    {
        return $this->safeExecute(function () use ($code) {
            $service = $this->repository->findByCode($code);

            if (! $service) {
                return ServiceResult::error('Serviço não encontrado.');
            }

            return $this->repository->delete($service->id)
                ? ServiceResult::success(null, 'Serviço excluído com sucesso.')
                : ServiceResult::error('Falha ao excluir serviço.');
        });
    }

    /**
     * Altera o status de um serviço.
     */
    public function changeStatus(int $serviceId, string $status): ServiceResult
    {
        return $this->safeExecute(function () use ($serviceId, $status) {
            $service = $this->repository->find($serviceId);

            if (! $service) {
                return ServiceResult::error('Serviço não encontrado.');
            }

            $newStatusEnum = ServiceStatus::tryFrom($status);
            if (! $newStatusEnum) {
                return ServiceResult::error('Status de serviço inválido: '.$status);
            }

            // Validação de transição de estado
            $allowedTransitions = ServiceStatus::getAllowedTransitions($service->status->value);
            if (! in_array($status, $allowedTransitions) && $service->status !== $newStatusEnum) {
                return ServiceResult::error("Transição de status não permitida: {$service->status->value} -> {$status}");
            }

            // Validação de integridade: Não pode iniciar execução sem itens
            if ($newStatusEnum === ServiceStatus::IN_PROGRESS) {
                if ($service->serviceItems()->count() === 0) {
                    return ServiceResult::error('Não é possível iniciar o serviço sem itens adicionados.');
                }
            }

            $this->repository->update($service->id, ['status' => $status]);

            // Sincronizar com agendamentos se necessário
            $this->syncScheduleStatus($service, $newStatusEnum);

            return ServiceResult::success($service->fresh(), 'Status do serviço atualizado com sucesso.');
        });
    }

    /**
     * Sincroniza o status do serviço com seus agendamentos.
     */
    private function syncScheduleStatus($service, ServiceStatus $newStatus): void
    {
        // Se o serviço foi aprovado pelo cliente ou o agendamento foi confirmado
        if (in_array($newStatus, [ServiceStatus::APPROVED, ServiceStatus::SCHEDULED])) {
            $service->schedules()
                ->where('status', ScheduleStatus::PENDING->value)
                ->update([
                    'status' => ScheduleStatus::CONFIRMED->value,
                    'confirmed_at' => now(),
                ]);
        }

        // Se o serviço foi rejeitado ou cancelado, cancelamos os agendamentos pendentes ou confirmados
        if (in_array($newStatus, [ServiceStatus::REJECTED, ServiceStatus::CANCELLED])) {
            $service->schedules()
                ->whereIn('status', [ScheduleStatus::PENDING->value, ScheduleStatus::CONFIRMED->value])
                ->update([
                    'status' => ScheduleStatus::CANCELLED->value,
                    'cancelled_at' => now(),
                    'cancellation_reason' => 'Serviço ' . $newStatus->label() . ' pelo cliente.',
                ]);
        }
    }
}
