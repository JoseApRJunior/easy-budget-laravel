<?php

/* The PlanSubscription class extends the Model class and provides methods for managing plan subscriptions, including
checking provider plans and retrieving active subscriptions by provider and tenant. */

namespace app\database\models;

use app\database\entities\PaymentMercadoPagoPlansEntity;
use app\database\Model;
use core\dbal\Entity;
use Exception;
use RuntimeException;

class PaymentMercadoPagoPlans extends Model
{
    /**
     * The name of the table associated with the model.
     *
     * @var string
     */
    protected string $table = 'payment_mercado_pago_plans';

    /**
     * Creates a new PaymentsMercadoPagoEntity instance from the provided data array.
     *
     * @param array $data The data to use for creating the entity.
     * @return Entity The created PaymentsMercadoPagoEntity instance.
     */
    protected static function createEntity(array $data): Entity
    {
        return PaymentMercadoPagoPlansEntity::create($data);
    }

    public function getPaymentId(string $payment_id, int $tenant_id, int $provider_id): PaymentMercadoPagoPlansEntity|Entity
    {
        try {
            return $this->findBy([
                'payment_id' => $payment_id,
                'tenant_id' => $tenant_id,
                'provider_id' => $provider_id,
            ]);
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar o pagamento do usuário, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

    public function getPaymentsByPlanSubscription(string $provider_id, int $tenant_id, int $plan_subscription_id): PaymentMercadoPagoPlansEntity|array|Entity
    {
        try {
            return $this->findBy([
                'provider_id' => $provider_id,
                'tenant_id' => $tenant_id,
                'plan_subscription_id' => $plan_subscription_id,
            ]);
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar o pagamento do usuário, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

    public function getLastPaymentByPlanSubscription(string $provider_id, int $tenant_id, int $plan_subscription_id): PaymentMercadoPagoPlansEntity|array|Entity
    {
        try {
            return $this->findBy([
                'provider_id' => $provider_id,
                'tenant_id' => $tenant_id,
                'plan_subscription_id' => $plan_subscription_id,
            ], [ 'id' => 'DESC' ], 1);
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar o pagamento do usuário, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

}
