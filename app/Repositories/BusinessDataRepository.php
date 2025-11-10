<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BusinessData;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Repository para BusinessData - Dados empresariais
 *
 * Tabela reutilizável para dados específicos de empresas
 * Pode ser usada tanto para customers quanto para providers
 */
class BusinessDataRepository
{
    public function create( array $data ): BusinessData
    {
        return BusinessData::create( $data );
    }

    public function update( Model $model, array $data ): bool
    {
        return $model->update( $data );
    }

    public function findByCustomerId( int $customerId, int $tenantId ): ?BusinessData
    {
        return BusinessData::where( 'customer_id', $customerId )
            ->where( 'tenant_id', $tenantId )
            ->first();
    }

    public function delete( Model $model ): bool
    {
        return $model->delete();
    }

    public function findByProviderId( int $providerId, int $tenantId ): ?BusinessData
    {
        return BusinessData::where( 'provider_id', $providerId )
            ->where( 'tenant_id', $tenantId )
            ->first();
    }

}
