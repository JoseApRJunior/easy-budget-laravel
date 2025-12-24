<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\OperationStatus;
use App\Enums\ServiceStatus;
use App\Models\Service;
use App\Repositories\ServiceRepository;
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

    public function __construct(
        ServiceRepository $serviceRepository,
        ScheduleService $scheduleService
    ) {
        parent::__construct($serviceRepository);
        $this->scheduleService = $scheduleService;
    }

    /**
     * Obtém estatísticas para o dashboard de serviços.
     */
    public function getDashboardStats(): ServiceResult
    {
        return $this->safeExecute(function () {
            $tenantId = $this->ensureTenantId();

            $total     = Service::where('tenant_id', $tenantId)->count();
            $approved  = Service::where('tenant_id', $tenantId)->where('status', ServiceStatus::APPROVED->value)->count();
            $pending   = Service::where('tenant_id', $tenantId)->where('status', ServiceStatus::PENDING->value)->count();
            $cancelled = Service::where('tenant_id', $tenantId)->where('status', ServiceStatus::CANCELLED->value)->count();
            $rejected  = Service::where('tenant_id', $tenantId)->where('status', ServiceStatus::REJECTED->value)->count();
            $completed = Service::where('tenant_id', $tenantId)->where('status', ServiceStatus::COMPLETED->value)->count();

            $recent = Service::where('tenant_id', $tenantId)
                ->latest('created_at')
                ->limit(10)
                ->with(['budget.customer.commonData', 'category'])
                ->get();

            $stats = [
                'total_services'    => $total,
                'approved_services' => $approved,
                'pending_services'  => $pending,
                'rejected_services' => $rejected,
                'status_breakdown'  => compact('pending', 'approved', 'rejected', 'cancelled', 'completed'),
                'recent_services'   => $recent,
            ];

            return ServiceResult::success($stats);
        }, 'Erro ao obter estatísticas do dashboard.');
    }

    /**
     * Busca serviço por código.
     */
    public function findByCode(string $code, array $with = []): ServiceResult
    {
        return $this->safeExecute(function () use ($code, $with) {
            $tenantId = $this->ensureTenantId();

            $query = $this->repository->newQuery()
                ->where('code', $code)
                ->where('tenant_id', $tenantId);

            if (!empty($with)) {
                $query->with($with);
            }

            $service = $query->first();

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
            $tenantId = $this->ensureTenantId($dto->tenant_id);

            return DB::transaction(function () use ($dto, $tenantId) {
                $serviceData = $dto->toArray();
                $serviceData['tenant_id'] = $tenantId;

                // Gera código se não houver
                if (empty($serviceData['code'])) {
                    $serviceData['code'] = 'SRV-' . strtoupper(bin2hex(random_bytes(4)));
                }

                $service = $this->repository->create($serviceData);

                if (!empty($dto->items)) {
                    foreach ($dto->items as $itemDto) {
                        /** @var ServiceItemDTO $itemDto */
                        $itemData = $itemDto->toArray();
                        $itemData['tenant_id'] = $tenantId;
                        $service->items()->create($itemData);
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
            $tenantId = $this->ensureTenantId($dto->tenant_id);

            return DB::transaction(function () use ($id, $dto, $tenantId) {
                $service = is_numeric($id)
                    ? $this->repository->find((int) $id)
                    : $this->repository->newQuery()->where('code', $id)->where('tenant_id', $tenantId)->first();

                if (!$service) {
                    return ServiceResult::error('Serviço não encontrado.');
                }

                $serviceData = $dto->toArray();
                $this->repository->update($service, $serviceData);

                if (isset($dto->items)) {
                    $service->items()->delete();
                    foreach ($dto->items as $itemDto) {
                        /** @var ServiceItemDTO $itemDto */
                        $itemData = $itemDto->toArray();
                        $itemData['tenant_id'] = $tenantId;
                        $service->items()->create($itemData);
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
            $tenantId = $this->ensureTenantId();
            $service = $this->repository->newQuery()
                ->where('code', $code)
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$service) {
                return ServiceResult::error('Serviço não encontrado.');
            }

            $this->repository->update($service, ['status' => $status]);

            return ServiceResult::success($service->fresh(), 'Status do serviço atualizado com sucesso.');
        });
    }

    /**
     * Exclui um serviço por código.
     */
    public function deleteByCode(string $code): ServiceResult
    {
        return $this->safeExecute(function () use ($code) {
            $tenantId = $this->ensureTenantId();
            $service = $this->repository->newQuery()
                ->where('code', $code)
                ->where('tenant_id', $tenantId)
                ->first();

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

            $this->repository->update($service, ['status' => $status]);

            return ServiceResult::success($service->fresh(), 'Status do serviço atualizado com sucesso.');
        });
    }
}
