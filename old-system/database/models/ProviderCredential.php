<?php

namespace app\database\models;

use app\database\entitiesORM\ProviderCredentialEntity;
use app\database\Model;
use core\dbal\Entity;

class ProviderCredential extends Model
{
    protected string $table = 'provider_credentials';

    protected static function createEntity( array $data ): Entity
    {
        return ProviderCredentialEntity::create( $data );
    }

    /**
     * Encontra as credenciais do provedor de pagamento Mercado Pago para um inquilino específico.
     *
     * @param int $tenantId O ID do inquilino.
     * @return ProviderCredentialEntity|Entity As credenciais do provedor de pagamento ou uma entidade vazia se não encontrado.
     */
    public function findByProvider( int $tenantId ): ProviderCredentialEntity|Entity
    {
        return $this->findBy( [ 
            'tenant_id'       => $tenantId,
            'payment_gateway' => 'mercadopago',
        ] );
    }

}
