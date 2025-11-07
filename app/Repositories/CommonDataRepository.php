<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\RepositoryInterface;
use App\Models\CommonData;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Model;

class CommonDataRepository extends AbstractTenantRepository
{
    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new CommonData();
    }

    /**
     * Cria dados comuns para cliente.
     */
    public function createForCustomer( array $data, int $tenantId, int $customerId ): Model
    {
        return $this->create( [
            'tenant_id'           => $tenantId,
            'customer_id'         => $customerId,
            'type'                => $data[ 'type' ] ?? CommonData::TYPE_INDIVIDUAL,
            'first_name'          => $data[ 'first_name' ] ?? null,
            'last_name'           => $data[ 'last_name' ] ?? null,
            'cpf'                 => $data[ 'cpf' ] ?? null,
            'birth_date'          => $data[ 'birth_date' ] ?? null,
            'company_name'        => $data[ 'company_name' ] ?? null,
            'cnpj'                => $data[ 'cnpj' ] ?? null,
            'description'         => $data[ 'description' ] ?? null,
            'area_of_activity_id' => $data[ 'area_of_activity_id' ] ?? null,
            'profession_id'       => $data[ 'profession_id' ] ?? null,
        ] );
    }

    /**
     * Deleta por ID do cliente.
     */
    public function deleteByCustomerId( int $customerId, int $tenantId ): bool
    {
        return $this->model->where( 'customer_id', $customerId )
            ->where( 'tenant_id', $tenantId )
            ->delete() > 0;
    }

    /**
     * Cria dados comuns para provider.
     */
    public function createForProvider( array $data, int $tenantId, int $providerId ): Model
    {
        return $this->create( [
            'tenant_id'           => $tenantId,
            'provider_id'         => $providerId,
            'type'                => $data[ 'type' ] ?? CommonData::TYPE_INDIVIDUAL,
            'first_name'          => $data[ 'first_name' ] ?? null,
            'last_name'           => $data[ 'last_name' ] ?? null,
            'cpf'                 => $data[ 'cpf' ] ?? null,
            'birth_date'          => $data[ 'birth_date' ] ?? null,
            'company_name'        => $data[ 'company_name' ] ?? null,
            'cnpj'                => $data[ 'cnpj' ] ?? null,
            'description'         => $data[ 'description' ] ?? null,
            'area_of_activity_id' => $data[ 'area_of_activity_id' ] ?? null,
            'profession_id'       => $data[ 'profession_id' ] ?? null,
        ] );
    }

    /**
     * Atualiza dados comuns para provider.
     */
    public function updateForProvider( array $data, int $tenantId, int $providerId ): bool
    {
        $commonData = $this->model->where( 'provider_id', $providerId )
            ->where( 'tenant_id', $tenantId )
            ->first();
        
        if ( !$commonData ) {
            return false;
        }
        
        return $commonData->update( $data );
    }

    /**
     * Deleta por ID do provider.
     */
    public function deleteByProviderId( int $providerId, int $tenantId ): bool
    {
        return $this->model->where( 'provider_id', $providerId )
            ->where( 'tenant_id', $tenantId )
            ->delete() > 0;
    }

}
