<?php

/* The PlanSubscription class extends the Model class and provides methods for managing plan subscriptions, including
checking provider plans and retrieving active subscriptions by provider and tenant. */

namespace app\database\models;

use app\database\entitiesORM\PaymentMercadoPagoPlansEntity;
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
     * Cria uma nova instância de PaymentMercadoPagoPlansEntity a partir dos dados fornecidos.
     *
     * @param array<string, mixed> $data Os dados para criar a entidade.
     * @return Entity A instância criada de PaymentMercadoPagoPlansEntity.
     */
    protected static function createEntity( array $data ): Entity
    {
        return PaymentMercadoPagoPlansEntity::create( $data );
    }

    public function getPaymentId( string $payment_id, int $tenant_id, int $provider_id ): PaymentMercadoPagoPlansEntity|Entity
    {
        try {
            return $this->findBy( [ 
                'payment_id'  => $payment_id,
                'tenant_id'   => $tenant_id,
                'provider_id' => $provider_id,
            ] );
        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao buscar o pagamento do usuário, tente mais tarde ou entre em contato com suporte.", 0, $e );
        }
    }

    /**
     * Busca pagamentos por assinatura de plano.
     *
     * @param string $provider_id ID do provedor
     * @param int $tenant_id ID do tenant
     * @param int $plan_subscription_id ID da assinatura do plano
     * @return PaymentMercadoPagoPlansEntity|array<int, Entity>|Entity Pagamentos encontrados
     */
    public function getPaymentsByPlanSubscription( string $provider_id, int $tenant_id, int $plan_subscription_id ): PaymentMercadoPagoPlansEntity|array|Entity
    {
        try {
            return $this->findBy( [ 
                'provider_id'          => $provider_id,
                'tenant_id'            => $tenant_id,
                'plan_subscription_id' => $plan_subscription_id,
            ] );
        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao buscar o pagamento do usuário, tente mais tarde ou entre em contato com suporte.", 0, $e );
        }
    }

    public function getLastPaymentByPlanSubscription( int $provider_id, int $tenant_id, int $plan_subscription_id ): PaymentMercadoPagoPlansEntity|Entity
    {
        try {
            return $this->findBy( [ 
                'provider_id'          => $provider_id,
                'tenant_id'            => $tenant_id,
                'plan_subscription_id' => $plan_subscription_id,
            ], [ 'id' => 'DESC' ], 1 );
        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao buscar o pagamento do usuário, tente mais tarde ou entre em contato com suporte.", 0, $e );
        }
    }

}
