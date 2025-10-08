<?php

namespace app\database\services;

use app\database\entities\PlanSubscriptionEntity;
use app\database\entities\ProviderEntity;
use app\database\entities\TenantEntity;
use app\database\entities\UserConfirmationTokenEntity;
use app\database\entities\UserEntity;
use app\database\entities\UserRolesEntity;
use app\database\models\Plan;
use app\database\models\PlanSubscription;
use app\database\models\Provider;
use app\database\models\Tenant;
use app\database\models\User;
use core\library\Session;
use Doctrine\DBAL\Connection;
use RuntimeException;

class ProviderUpdatePlanService
{
    /**
     * Summary of table
     * @var string
     */

    protected string $table = 'plan_subscriptions';

    public function __construct(
        private readonly Connection $connection,
        private Tenant $tenant,
        private User $user,
        private Provider $provider,
        private Plan $plan,
        private PlanSubscription $planSubscription,
    ) {}

    public function updatePlanSubscription( $transactionDetails )
    {
        //TODO : CRIAR SERVIÇO PARA ATUALIZAR ASSINATURA NO BANCO DE DADOS
        return $this->connection->transactional( function () use ($transactionDetails) {
            $currentSubscription = $this->planSubscription->getActiveByProviderAndPlan(
                Session::get( 'provider' )->id,
                Session::get( 'provider' )->tenant_id,
            );

            $currentSubscription = is_object( $currentSubscription ) ? $currentSubscription->toArray() : $currentSubscription;
            // Marcar a assinatura atual como inativa
            if ( $currentSubscription ) {
                $currentSubscription[ 'status' ]   = 'canceled';
                $currentSubscription[ 'end_date' ] = ( new \DateTime() )->format( 'Y-m-d H:i:s' );
                $this->planSubscription->update( PlanSubscriptionEntity::create( $currentSubscription ) );
            }

            // Criar uma nova assinatura
            $newSubscription = [ 
                'provider_id'       => Session::get( 'provider' )->id,
                'tenant_id'         => Session::get( 'provider' )->tenant_id,
                'plan_id'           => $transactionDetails[ 'plan_id' ],
                'status'            => 'active',
                'price_paid'        => $transactionDetails[ 'plan_price' ],
                'start_date'        => ( new \DateTime() )->format( 'Y-m-d H:i:s' ),
                'end_date'          => dateExpirate( '+35 days' ),
                'payment_method'    => $transactionDetails[ 'payment_method' ],
                'last_payment_date' => ( new \DateTime() )->format( 'Y-m-d H:i:s' ),
                'next_payment_date' => dateExpirate( '+1 month' ),
                'payment_id'        => $transactionDetails[ 'payment_id' ]
            ];

            $newSubscriptionEntity = PlanSubscriptionEntity::create( $newSubscription );
            $this->planSubscription->create( $newSubscriptionEntity );
        } );
    }

    private function updateUserSubscriptionFree( $transactionDetails )
    {
        $currentSubscription = $this->planSubscription->getActiveByProviderAndPlan(
            Session::get( 'provider' )->id,
            Session::get( 'provider' )->tenant_id,
        )->toArray();

        // Marcar a assinatura atual como inativa
        if ( $currentSubscription ) {
            $currentSubscription[ 'status' ]   = 'canceled';
            $currentSubscription[ 'end_date' ] = ( new \DateTime() )->format( 'Y-m-d H:i:s' );
            $this->planSubscription->update( PlanSubscriptionEntity::create( $currentSubscription ) );
        }

        // Criar uma nova assinatura gratuita
        $newSubscription = [ 
            'provider_id'       => Session::get( 'provider' )->id,
            'tenant_id'         => Session::get( 'provider' )->tenant_id,
            'plan_id'           => $transactionDetails[ 'plan_id' ],
            'status'            => 'active',
            'price_paid'        => 0.00,
            'start_date'        => ( new \DateTime() )->format( 'Y-m-d H:i:s' ),
            'end_date'          => null,
            'payment_method'    => 'free',
            'last_payment_date' => ( new \DateTime() )->format( 'Y-m-d H:i:s' ),
            'next_payment_date' => null
        ];

        $newSubscriptionEntity = PlanSubscriptionEntity::create( $newSubscription );
        $this->planSubscription->create( $newSubscriptionEntity );
    }

}
