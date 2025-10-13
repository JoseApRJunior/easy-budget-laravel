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
     * Cria endereços para cliente dentro do tenant atual (suporta múltiplos).
     *
     * @param array<array<string, mixed>> $addressesData Dados dos endereços
     * @param int $customerId ID do cliente
     * @return array<Address> Endereços criados
     */
    public function createForCustomer( array $addressesData, int $customerId ): array
    {
        $addresses = [];
        foreach ( $addressesData as $addressData ) {
            $address     = $this->create( [
                'customer_id' => $customerId,
                'street'      => $addressData[ 'street' ] ?? null,
                'number'      => $addressData[ 'number' ] ?? null,
                'city'        => $addressData[ 'city' ] ?? null,
                'state'       => $addressData[ 'state' ] ?? null,
                'zip_code'    => $addressData[ 'zip_code' ] ?? null,
                'is_main'     => $addressData[ 'is_main' ] ?? false,
                // Outros campos de endereço
            ] );
            $addresses[] = $address;
        }
        return $addresses;
    }

    /**
     * Remove endereços por ID do cliente dentro do tenant atual.
     *
     * @param int $customerId ID do cliente
     * @return int Número de endereços removidos
     */
    public function deleteByCustomerId( int $customerId ): int
    {
        return $this->model->where( 'customer_id', $customerId )->delete();
    }

    /**
     * Lista endereços por cliente dentro do tenant atual.
     *
     * @param int $customerId ID do cliente
     * @return \Illuminate\Database\Eloquent\Collection<int, Address> Endereços do cliente
     */
    public function listByCustomerId( int $customerId ): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->where( 'customer_id', $customerId )->get();
    }

    /**
     * Cria endereços para fornecedor dentro do tenant atual (suporta múltiplos).
     *
     * @param array<array<string, mixed>> $addressesData Dados dos endereços
     * @param int $providerId ID do fornecedor
     * @return array<Address> Endereços criados
     */
    public function createForProvider( array $addressesData, int $providerId ): array
    {
        $addresses = [];
        foreach ( $addressesData as $addressData ) {
            $address     = $this->create( [
                'provider_id' => $providerId,
                'street'      => $addressData[ 'street' ] ?? null,
                'number'      => $addressData[ 'number' ] ?? null,
                'city'        => $addressData[ 'city' ] ?? null,
                'state'       => $addressData[ 'state' ] ?? null,
                'zip_code'    => $addressData[ 'zip_code' ] ?? null,
                'is_main'     => $addressData[ 'is_main' ] ?? false,
                // Outros campos de endereço
            ] );
            $addresses[] = $address;
        }
        return $addresses;
    }

    /**
     * Atualiza endereços para fornecedor dentro do tenant atual.
     *
     * @param array<array<string, mixed>> $addressesData Dados dos novos endereços
     * @param int $providerId ID do fornecedor
     * @return array<Address> Novos endereços criados
     */
    public function updateForProvider( array $addressesData, int $providerId ): array
    {
        // Deletar endereços existentes
        $this->deleteByProviderId( $providerId );

        // Criar novos endereços
        return $this->createForProvider( $addressesData, $providerId );
    }

    /**
     * Remove endereços por ID do fornecedor dentro do tenant atual.
     *
     * @param int $providerId ID do fornecedor
     * @return int Número de endereços removidos
     */
    public function deleteByProviderId( int $providerId ): int
    {
        return $this->model->where( 'provider_id', $providerId )->delete();
    }

    /**
     * Lista endereços por fornecedor dentro do tenant atual.
     *
     * @param int $providerId ID do fornecedor
     * @return \Illuminate\Database\Eloquent\Collection<int, Address> Endereços do fornecedor
     */
    public function listByProviderId( int $providerId ): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->where( 'provider_id', $providerId )->get();
    }

}
