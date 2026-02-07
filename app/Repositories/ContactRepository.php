<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\Common\ContactDTO;
use App\Models\Contact;
use App\Repositories\Abstracts\AbstractTenantRepository;
use App\Repositories\Traits\RepositoryFiltersTrait;
use Illuminate\Database\Eloquent\Model;

class ContactRepository extends AbstractTenantRepository
{
    use RepositoryFiltersTrait;

    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new Contact;
    }

    /**
     * Cria um novo contato a partir de um DTO.
     */
    public function createFromDTO(ContactDTO $dto): Contact
    {
        return $this->model->newQuery()->create($dto->toArray());
    }

    /**
     * Atualiza um contato a partir de um DTO.
     */
    public function updateFromDTO(int $id, ContactDTO $dto): ?Model
    {
        return $this->update($id, $dto->toArrayWithoutNulls());
    }

    /**
     * Cria contato para cliente dentro do tenant atual.
     *
     * @param  array<string, mixed>  $data  Dados do contato
     * @param  int  $customerId  ID do cliente
     * @return Contact Contato criado
     */
    public function createForCustomer(array $data, int $customerId): Contact
    {
        $data['customer_id'] = $customerId;

        return $this->createFromDTO(ContactDTO::fromRequest($data));
    }

    /**
     * Remove contato por ID do cliente (1:1).
     */
    public function deleteByCustomerId(int $customerId): bool
    {
        return $this->model->newQuery()->where('customer_id', $customerId)->delete() > 0;
    }

    /**
     * Busca contato por cliente (1:1).
     */
    public function findByCustomerId(int $customerId): ?Contact
    {
        return $this->model->newQuery()->where('customer_id', $customerId)->first();
    }

    /**
     * Cria contato para provider dentro do tenant atual.
     */
    public function createForProvider(array $data, int $providerId): Contact
    {
        $data['provider_id'] = $providerId;

        return $this->createFromDTO(ContactDTO::fromRequest($data));
    }

    /**
     * Atualiza contato para provider.
     */
    public function updateForProvider(array $data, int $providerId): bool
    {
        $contact = $this->model->newQuery()->where('provider_id', $providerId)->first();

        if (! $contact) {
            return false;
        }

        $data['provider_id'] = $providerId;

        return $this->updateFromDTO($contact->id, ContactDTO::fromRequest($data));
    }

    /**
     * Remove contato por ID do provider (1:1).
     */
    public function deleteByProviderId(int $providerId): bool
    {
        return $this->model->newQuery()->where('provider_id', $providerId)->delete() > 0;
    }

    /**
     * Busca contato por provider (1:1).
     *
     * @param  int  $providerId  ID do provider
     */
    public function findByProviderId(int $providerId): ?Contact
    {
        return $this->model->newQuery()->where('provider_id', $providerId)->first();
    }
}
