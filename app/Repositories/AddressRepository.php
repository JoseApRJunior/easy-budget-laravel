<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\Common\AddressDTO;
use App\Models\Address;
use App\Repositories\Abstracts\AbstractTenantRepository;
use App\Repositories\Traits\RepositoryFiltersTrait;
use Illuminate\Database\Eloquent\Model;

class AddressRepository extends AbstractTenantRepository
{
    use RepositoryFiltersTrait;

    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new Address;
    }

    /**
     * Cria um novo endereço a partir de um DTO.
     */
    public function createFromDTO(AddressDTO $dto): Address
    {
        return $this->model->newQuery()->create($dto->toArray());
    }

    /**
     * Atualiza um endereço a partir de um DTO.
     */
    public function updateFromDTO(int $id, AddressDTO $dto): bool
    {
        $address = $this->find($id);
        if (! $address) {
            return false;
        }

        return $address->update(array_filter($dto->toArray(), fn ($value) => $value !== null));
    }

    /**
     * Cria endereço para cliente (1:1).
     *
     * @param  array<string, mixed>  $addressData  Dados do endereço
     * @param  int  $customerId  ID do cliente
     * @return Address Endereço criado
     */
    public function createForCustomer(array $addressData, int $customerId): Model
    {
        $addressData['customer_id'] = $customerId;

        return $this->createFromDTO(AddressDTO::fromRequest($addressData));
    }

    /**
     * Remove endereço por ID do cliente.
     */
    public function deleteByCustomerId(int $customerId): bool
    {
        return $this->model->newQuery()->where('customer_id', $customerId)->delete() > 0;
    }

    /**
     * Busca endereço por cliente (1:1).
     */
    public function findByCustomerId(int $customerId): ?Address
    {
        return $this->model->newQuery()->where('customer_id', $customerId)->first();
    }

    /**
     * Cria endereço para provider (1:1).
     */
    public function createForProvider(array $addressData, int $providerId): Model
    {
        $addressData['provider_id'] = $providerId;

        return $this->createFromDTO(AddressDTO::fromRequest($addressData));
    }

    /**
     * Atualiza endereço para provider.
     */
    public function updateForProvider(array $addressData, int $providerId): bool
    {
        $address = $this->model->newQuery()->where('provider_id', $providerId)->first();

        if (! $address) {
            return false;
        }

        $addressData['provider_id'] = $providerId;

        return $this->updateFromDTO($address->id, AddressDTO::fromRequest($addressData));
    }

    /**
     * Remove endereço por ID do provider.
     *
     * @param  int  $providerId  ID do provider
     */
    public function deleteByProviderId(int $providerId): bool
    {
        return $this->model->newQuery()->where('provider_id', $providerId)->delete() > 0;
    }

    /**
     * Busca endereço por provider (1:1).
     *
     * @param  int  $providerId  ID do provider
     */
    public function findByProviderId(int $providerId): ?Address
    {
        return $this->model->newQuery()->where('provider_id', $providerId)->first();
    }
}
