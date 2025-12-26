<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BusinessData;
use App\DTOs\Common\BusinessDataDTO;
use App\Repositories\Abstracts\AbstractTenantRepository;
use App\Repositories\Traits\RepositoryFiltersTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Repository para BusinessData - Dados empresariais
 *
 * Tabela reutilizável para dados específicos de empresas
 * Pode ser usada tanto para customers quanto para providers
 */
class BusinessDataRepository extends AbstractTenantRepository
{
    use RepositoryFiltersTrait;

    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new BusinessData();
    }

    /**
     * Cria um novo registro de dados empresariais a partir de um DTO.
     */
    public function createFromDTO(BusinessDataDTO $dto): BusinessData
    {
        return $this->model->newQuery()->create($dto->toArrayWithoutNulls());
    }

    /**
     * Atualiza um registro de dados empresariais a partir de um DTO.
     */
    public function updateFromDTO(int $id, BusinessDataDTO $dto): bool
    {
        $businessData = $this->find($id);
        if (!$businessData) {
            return false;
        }

        return $businessData->update($dto->toArrayWithoutNulls());
    }

    public function findByCustomerId(int $customerId): ?BusinessData
    {
        return $this->model->newQuery()
            ->where('customer_id', $customerId)
            ->first();
    }

    public function findByProviderId(int $providerId): ?BusinessData
    {
        return $this->model->newQuery()
            ->where('provider_id', $providerId)
            ->first();
    }
}
