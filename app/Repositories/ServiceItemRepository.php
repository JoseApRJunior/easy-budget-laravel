<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\Service\ServiceItemDTO;
use App\Models\ServiceItem;
use App\Repositories\Abstracts\AbstractTenantRepository;
use App\Repositories\Traits\RepositoryFiltersTrait;
use Illuminate\Database\Eloquent\Model;

class ServiceItemRepository extends AbstractTenantRepository
{
    use RepositoryFiltersTrait;

    protected function makeModel(): Model
    {
        return new ServiceItem;
    }

    /**
     * Busca um item de serviço por ID e ID do serviço.
     */
    public function findByIdAndServiceId(int $id, int $serviceId): ?ServiceItem
    {
        return $this->model->newQuery()->where('id', $id)
            ->where('service_id', $serviceId)
            ->first();
    }

    /**
     * Cria um item de serviço a partir de um DTO.
     */
    public function createFromDTO(ServiceItemDTO $dto, int $serviceId): ServiceItem
    {
        return $this->model->newQuery()->create(array_merge(
            $dto->toArrayWithoutNulls(),
            ['service_id' => $serviceId]
        ));
    }

    /**
     * Atualiza um item de serviço a partir de um DTO.
     */
    public function updateFromDTO(int $id, ServiceItemDTO $dto): bool
    {
        $item = $this->find($id);
        if (! $item) {
            return false;
        }

        return $item->update($dto->toArrayWithoutNulls());
    }
}
