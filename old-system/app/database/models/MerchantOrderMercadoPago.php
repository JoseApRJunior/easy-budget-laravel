<?php

/* The PlanSubscription class extends the Model class and provides methods for managing plan subscriptions, including
checking provider plans and retrieving active subscriptions by provider and tenant. */

namespace app\database\models;

use app\database\entities\MerchantOrderMercadoPagoEntity;
use app\database\Model;
use core\dbal\Entity;
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
     * Creates a new MerchantOrderEntity instance from the provided data array.
     *
     * @param array $data The data to use for creating the entity.
     * @return Entity The created MerchantOrderEntity instance.
     */
    protected static function createEntity(array $data): Entity
    {
        return MerchantOrderMercadoPagoEntity::create($data);
    }

    public function getMerchantOrderId(string $merchant_order_id, int $tenant_id, int $provider_id): MerchantOrderMercadoPagoEntity|Entity
    {
        try {
            return $this->findBy([
                'merchant_order_id' => $merchant_order_id,
                'tenant_id' => $tenant_id,
                'provider_id' => $provider_id,
            ]);
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar a ordem de pagamento do usuário, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

    public function getMerchantOrderStatus(string $provider_id, int $tenant_id, string $plan_subscription_id): MerchantOrderMercadoPagoEntity|Entity
    {
        try {
            return $this->findBy([
                'provider_id' => $provider_id,
                'tenant_id' => $tenant_id,
                'plan_subscription_id' => $plan_subscription_id,
                'status' => 'opened',
            ]);
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar o pagamento do usuário, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

}
