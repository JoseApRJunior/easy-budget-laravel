<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\Common\CommonDataDTO;
use App\Models\CommonData;
use App\Repositories\Abstracts\AbstractTenantRepository;
use App\Repositories\Traits\RepositoryFiltersTrait;
use Illuminate\Database\Eloquent\Model;

class CommonDataRepository extends AbstractTenantRepository
{
    use RepositoryFiltersTrait;

    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new CommonData;
    }

    /**
     * Cria dados comuns a partir de um DTO.
     */
    public function createFromDTO(CommonDataDTO $dto): CommonData
    {
        return $this->model->newQuery()->create($dto->toArray());
    }

    /**
     * Atualiza dados comuns a partir de um DTO.
     */
    public function updateFromDTO(int $id, CommonDataDTO $dto): ?Model
    {
        return $this->update($id, $dto->toArrayWithoutNulls());
    }

    /**
     * Cria dados comuns para cliente.
     */
    public function createForCustomer(array $data, int $customerId): Model
    {
        $data['customer_id'] = $customerId;

        return $this->createFromDTO(CommonDataDTO::fromRequest($data));
    }

    /**
     * Deleta por ID do cliente.
     */
    public function deleteByCustomerId(int $customerId): bool
    {
        return $this->model->newQuery()->where('customer_id', $customerId)->delete() > 0;
    }

    /**
     * Cria dados comuns para provider.
     */
    public function createForProvider(array $data, int $providerId): Model
    {
        $data['provider_id'] = $providerId;

        return $this->createFromDTO(CommonDataDTO::fromRequest($data));
    }

    /**
     * Atualiza dados comuns para provider.
     */
    public function updateForProvider(array $data, int $providerId): bool
    {
        $commonData = $this->model->newQuery()
            ->where('provider_id', $providerId)
            ->first();

        if (! $commonData) {
            return false;
        }

        $data['provider_id'] = $providerId;

        return $this->updateFromDTO($commonData->id, CommonDataDTO::fromRequest($data));
    }

    /**
     * Deleta por ID do provider.
     */
    public function deleteByProviderId(int $providerId): bool
    {
        return $this->model->newQuery()
            ->where('provider_id', $providerId)
            ->delete() > 0;
    }
}
