<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\OperationStatus;
use App\Enums\ServiceStatus;
use App\Models\Service;
use App\Repositories\ServiceRepository;
use App\Repositories\ServiceItemRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Services\Domain\ScheduleService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\DB;
use App\DTOs\Service\ServiceDTO;
use App\DTOs\Service\ServiceItemDTO;

class ServiceService extends AbstractBaseService
{
    protected ScheduleService $scheduleService;
    protected ServiceItemRepository $itemRepository;

    public function __construct(
        ServiceRepository $serviceRepository,
        ScheduleService $scheduleService,
        ServiceItemRepository $itemRepository
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

            $dashboardData = [
                'total_services'    => $total,
                'status_breakdown'  => $stats,
                'recent_services'   => $recent,
                'approved_services' => $stats[ServiceStatus::APPROVED->value] ?? 0,
                'pending_services'  => $stats[ServiceStatus::PENDING->value] ?? 0,
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

            if (!$service) {
                return ServiceResult::error("Serviço com código {$code} não encontrado.");
            }

            return ServiceResult::success($service);
        });
    }

    /**
     * Cria um novo serviço usando DTO.
     */
    public function create(ServiceDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($dto) {
            return DB::transaction(function () use ($dto) {
                // Prepara dados - o código pode vir no DTO ou ser gerado
                $serviceData = $dto->toArray();
                if (empty($serviceData['code'])) {
                    $serviceData['code'] = 'SRV-' . strtoupper(bin2hex(random_bytes(4)));
                }

                $service = $this->repository->createFromDTO($dto);

                // Atualiza o código se foi gerado aqui
                if (empty($dto->code)) {
                    $service->update(['code' => $serviceData['code']]);
                }

                if (!empty($dto->items)) {
                    foreach ($dto->items as $itemDto) {
                        /** @var ServiceItemDTO $itemDto */
                        $this->itemRepository->createFromDTO($itemDto, $service->id);
                    }
                }

                return ServiceResult::success($service, 'Serviço criado com sucesso.');
            });
        });
    }

    /**
     * Atualiza um serviço existente usando DTO.
     */
    public function update($id, ServiceDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($id, $dto) {
            return DB::transaction(function () use ($id, $dto) {
                $service = is_numeric($id)
                    ? $this->repository->find((int) $id)
                    : $this->repository->findByCode((string) $id);

                if (!$service) {
                    return ServiceResult::error('Serviço não encontrado.');
                }

                $this->repository->updateFromDTO($service->id, $dto);

                if (isset($dto->items)) {
                    $service->items()->delete();
                    foreach ($dto->items as $itemDto) {
                        /** @var ServiceItemDTO $itemDto */
                        $service->items()->create($itemDto->toArray());
                    }
                }

                return ServiceResult::success($service->fresh(), 'Serviço atualizado com sucesso.');
            });
        });
    }

    /**
     * Altera o status de um serviço por código.
     */
    public function changeStatusByCode(string $code, string $status): ServiceResult
    {
        return $this->safeExecute(function () use ($code, $status) {
            $service = $this->repository->findByCode($code);

            if (!$service) {
                return ServiceResult::error('Serviço não encontrado.');
            }

            $this->repository->update($service->id, ['status' => $status]);

            return ServiceResult::success($service->fresh(), 'Status do serviço atualizado com sucesso.');
        });
    }

    /**
     * Exclui um serviço por código.
     */
    public function deleteByCode(string $code): ServiceResult
    {
        return $this->safeExecute(function () use ($code) {
            $service = $this->repository->findByCode($code);

            if (!$service) {
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

            if (!$service) {
                return ServiceResult::error('Serviço não encontrado.');
            }

            $this->repository->update($service->id, ['status' => $status]);

            return ServiceResult::success($service->fresh(), 'Status do serviço atualizado com sucesso.');
        });
    }
}
