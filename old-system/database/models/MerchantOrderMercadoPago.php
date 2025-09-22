<?php

/* The PlanSubscription class extends the Model class and provides methods for managing plan subscriptions, including
checking provider plans and retrieving active subscriptions by provider and tenant. */

namespace app\database\models;

use app\database\entitiesORM\MerchantOrderMercadoPagoEntity;
use app\database\Model;
use core\dbal\Entity;
use core\dbal\EntityNotFound;
use Exception;
use RuntimeException;

class MerchantOrderMercadoPago extends Model
{
    /**
     * The name of the table associated with the model.
     *
     * @var string
     */
    protected string $table = 'merchant_orders_mercado_pago';

    /**
     * Cria uma nova instância de MerchantOrderMercadoPagoEntity a partir dos dados fornecidos.
     *
     * @param array<string, mixed> $data Os dados para criar a entidade.
     * @return Entity A instância criada de MerchantOrderMercadoPagoEntity.
     */
    protected static function createEntity( array $data ): Entity
    {
        return MerchantOrderMercadoPagoEntity::create( $data );
    }

    /**
     * @param string $merchant_order_id
     * @param int $tenant_id
     * @param int $provider_id
     * @return MerchantOrderMercadoPagoEntity|EntityNotFound
     */
    public function getMerchantOrderId( string $merchant_order_id, int $tenant_id, int $provider_id ): MerchantOrderMercadoPagoEntity|EntityNotFound
    {
        try {
            $result = $this->findOneBy( [ 
                'merchant_order_id' => $merchant_order_id,
                'tenant_id'         => $tenant_id,
                'provider_id'       => $provider_id,
            ] );

            if ( !$result instanceof MerchantOrderMercadoPagoEntity ) {
                throw new RuntimeException( "Falha ao buscar a ordem de pagamento do usuário, tente mais tarde ou entre em contato com suporte." );
            }

            return $result;

        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao buscar a ordem de pagamento do usuário, tente mais tarde ou entre em contato com suporte.", 0, $e );
        }
    }

    /**
     * @param string $provider_id
     * @param int $tenant_id
     * @param string $plan_subscription_id
     * @return MerchantOrderMercadoPagoEntity|EntityNotFound
     */
    public function getMerchantOrderStatus( string $provider_id, int $tenant_id, string $plan_subscription_id ): MerchantOrderMercadoPagoEntity|EntityNotFound
    {
        try {
            $result = $this->findOneBy( [ 
                'provider_id'          => $provider_id,
                'tenant_id'            => $tenant_id,
                'plan_subscription_id' => $plan_subscription_id,
                'status'               => 'opened',
            ] );

            if ( !$result instanceof MerchantOrderMercadoPagoEntity ) {
                throw new RuntimeException( "Falha ao buscar a ordem de pagamento do usuário, tente mais tarde ou entre em contato com suporte." );
            }

            return $result;

        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao buscar o pagamento do usuário, tente mais tarde ou entre em contato com suporte.", 0, $e );
        }
    }

}