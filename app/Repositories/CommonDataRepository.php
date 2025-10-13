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
        $commonData = new CommonData();
        $commonData->fill( [
            'tenant_id'   => $tenantId,
            'customer_id' => $customerId,
            'cpf_cnpj'    => $data[ 'cpf_cnpj' ] ?? null,
            'rg'          => $data[ 'rg' ] ?? null,
            // Outros campos de dados comuns como birth_date, etc.
        ] );
        $commonData->save();
        return $commonData;
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
        $commonData = new CommonData();
        $commonData->fill( [
            'tenant_id'   => $tenantId,
            'provider_id' => $providerId,
            'cpf_cnpj'    => $data[ 'cpf_cnpj' ] ?? null,
            'rg'          => $data[ 'rg' ] ?? null,
            // Outros campos de dados comuns
        ] );
        $commonData->save();
        return $commonData;
    }

    /**
     * Atualiza dados comuns para provider.
     */
    public function updateForProvider( array $data, int $tenantId, int $providerId ): bool
    {
        $commonData = $this->model->where( 'provider_id', $providerId )->first();
        if ( !$commonData ) {
            return false;
        }
        $commonData->fill( $data );
        $commonData->save();
        return true;
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
