<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\RepositoryInterface;
use App\Models\Address;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Model;

class AddressRepository extends AbstractTenantRepository
{
    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new Address();
    }

    /**
     * Cria endereço para cliente (1:1).
     *
     * @param array<string, mixed> $addressData Dados do endereço
     * @param int $customerId ID do cliente
     * @return Address Endereço criado
     */
    public function createForCustomer( array $addressData, int $customerId ): Model
    {
        return $this->create( [
            'customer_id'    => $customerId,
            'address'        => $addressData[ 'address' ] ?? null,
            'address_number' => $addressData[ 'address_number' ] ?? null,
            'neighborhood'   => $addressData[ 'neighborhood' ] ?? null,
            'city'           => $addressData[ 'city' ] ?? null,
            'state'          => $addressData[ 'state' ] ?? null,
            'cep'            => $addressData[ 'cep' ] ?? null,
        ] );
    }

    /**
     * Remove endereço por ID do cliente.
     *
     * @param int $customerId ID do cliente
     * @return bool
     */
    public function deleteByCustomerId( int $customerId ): bool
    {
        return $this->model->where( 'customer_id', $customerId )->delete() > 0;
    }

    /**
     * Busca endereço por cliente (1:1).
     *
     * @param int $customerId ID do cliente
     * @return Address|null
     */
    public function findByCustomerId( int $customerId ): ?Address
    {
        return $this->model->where( 'customer_id', $customerId )->first();
    }

    /**
     * Cria endereço para provider (1:1).
     *
     * @param array<string, mixed> $addressData Dados do endereço
     * @param int $providerId ID do provider
     * @return Address Endereço criado
     */
    public function createForProvider( array $addressData, int $providerId ): Model
    {
        return $this->create( [
            'provider_id'    => $providerId,
            'address'        => $addressData[ 'address' ] ?? null,
            'address_number' => $addressData[ 'address_number' ] ?? null,
            'neighborhood'   => $addressData[ 'neighborhood' ] ?? null,
            'city'           => $addressData[ 'city' ] ?? null,
            'state'          => $addressData[ 'state' ] ?? null,
            'cep'            => $addressData[ 'cep' ] ?? null,
        ] );
    }

    /**
     * Atualiza endereço para provider.
     *
     * @param array<string, mixed> $addressData Dados do endereço
     * @param int $providerId ID do provider
     * @return bool
     */
    public function updateForProvider( array $addressData, int $providerId ): bool
    {
        $address = $this->model->where( 'provider_id', $providerId )->first();
        
        if ( !$address ) {
            return false;
        }
        
        return $address->update( $addressData );
    }

    /**
     * Remove endereço por ID do provider.
     *
     * @param int $providerId ID do provider
     * @return bool
     */
    public function deleteByProviderId( int $providerId ): bool
    {
        return $this->model->where( 'provider_id', $providerId )->delete() > 0;
    }

    /**
     * Busca endereço por provider (1:1).
     *
     * @param int $providerId ID do provider
     * @return Address|null
     */
    public function findByProviderId( int $providerId ): ?Address
    {
        return $this->model->where( 'provider_id', $providerId )->first();
    }

}
