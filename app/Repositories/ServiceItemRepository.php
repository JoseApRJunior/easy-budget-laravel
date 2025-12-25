<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ServiceItem;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Model;

class ServiceItemRepository extends AbstractTenantRepository
{
    protected function makeModel(): Model
    {
        return new ServiceItem();
    }

    /**
     * Busca um item de serviço por ID e ID do serviço.
     */
    public function findByIdAndServiceId(int $id, int $serviceId): ?ServiceItem
    {
        return $this->model->where('id', $id)
            ->where('service_id', $serviceId)
            ->first();
    }
}
