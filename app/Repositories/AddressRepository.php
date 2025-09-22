<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\RepositoryInterface;
use App\Models\Address;
use Illuminate\Database\Eloquent\Model;

class AddressRepository extends AbstractRepository implements RepositoryInterface
{
    protected string $modelClass = Address::class;

    /**
     * Cria endereços para cliente (suporta múltiplos).
     */
    public function createForCustomer( array $addressesData, int $tenantId, int $customerId ): array
    {
        $addresses = [];
        foreach ( $addressesData as $addressData ) {
            $address = new Address();
            $address->fill( [ 
                'tenant_id'   => $tenantId,
                'customer_id' => $customerId,
                'street'      => $addressData[ 'street' ] ?? null,
                'number'      => $addressData[ 'number' ] ?? null,
                'city'        => $addressData[ 'city' ] ?? null,
                'state'       => $addressData[ 'state' ] ?? null,
                'zip_code'    => $addressData[ 'zip_code' ] ?? null,
                'is_main'     => $addressData[ 'is_main' ] ?? false,
                // Outros campos de endereço
            ] );
            $address->save();
            $addresses[] = $address;
        }
        return $addresses;
    }

    /**
     * Deleta por ID do cliente.
     */
    public function deleteByCustomerId( int $customerId, int $tenantId ): bool
    {
        return $this->model::where( 'customer_id', $customerId )
            ->where( 'tenant_id', $tenantId )
            ->delete() > 0;
    }

    /**
     * Lista endereços por cliente e tenant.
     */
    public function listByCustomerIdAndTenantId( int $customerId, int $tenantId ): array
    {
        return $this->model::where( 'customer_id', $customerId )
            ->where( 'tenant_id', $tenantId )
            ->get()
            ->all();
    }

    /**
     * Cria endereços para fornecedor (suporta múltiplos).
     */
    public function createForProvider( array $addressesData, int $tenantId, int $providerId ): array
    {
        $addresses = [];
        foreach ( $addressesData as $addressData ) {
            $address = new Address();
            $address->fill( [ 
                'tenant_id'   => $tenantId,
                'provider_id' => $providerId,
                'street'      => $addressData[ 'street' ] ?? null,
                'number'      => $addressData[ 'number' ] ?? null,
                'city'        => $addressData[ 'city' ] ?? null,
                'state'       => $addressData[ 'state' ] ?? null,
                'zip_code'    => $addressData[ 'zip_code' ] ?? null,
                'is_main'     => $addressData[ 'is_main' ] ?? false,
                // Outros campos de endereço
            ] );
            $address->save();
            $addresses[] = $address;
        }
        return $addresses;
    }

    /**
     * Atualiza endereços para fornecedor: deleta existentes e cria novos.
     */
    public function updateForProvider( array $addressesData, int $tenantId, int $providerId ): array
    {
        // Deletar endereços existentes
        $this->deleteByProviderId( $providerId, $tenantId );

        // Criar novos endereços
        return $this->createForProvider( $addressesData, $tenantId, $providerId );
    }

    /**
     * Deleta por ID do fornecedor.
     */
    public function deleteByProviderId( int $providerId, int $tenantId ): bool
    {
        return $this->model::where( 'provider_id', $providerId )
            ->where( 'tenant_id', $tenantId )
            ->delete() > 0;
    }

    /**
     * Lista endereços por fornecedor e tenant.
     */
    public function listByProviderIdAndTenantId( int $providerId, int $tenantId ): array
    {
        return $this->model::where( 'provider_id', $providerId )
            ->where( 'tenant_id', $tenantId )
            ->get()
            ->all();
    }

}
