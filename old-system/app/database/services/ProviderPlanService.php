<?php

namespace app\database\services;

use app\database\entities\PlanEntity;
use app\database\entities\PlanSubscriptionEntity;
use app\database\entities\PlansWithPlanSubscriptionEntity;
use app\database\models\PlanSubscription;
use app\enums\PlanSubscriptionsStatusEnum;
use core\dbal\EntityNotFound;
use core\library\Session;
use Doctrine\DBAL\Connection;
use Exception;
use RuntimeException;

class ProviderPlanService
{

    /**
     * The database table name for plan subscriptions.
     */
    protected string $table         = 'plan_subscriptions';
    private          $authenticated;

    /**
     * Constructs a new instance of the ProviderPlanService class.
     *
     * @param Connection $connection The database connection to use.
     * @param PlanSubscription $planSubscription The plan subscription model to use.
     * @param MerchantOrderMercadoPagoService $merchantOrderMercadoPagoService The MercadoPago merchant order service to use.
     * @param PaymentMercadoPagoService $paymentMercadoPagoService The MercadoPago payment service to use.
     */
    public function __construct(
        private readonly Connection $connection,
        private PlanSubscription $planSubscription,
        private MerchantOrderMercadoPagoService $merchantOrderMercadoPagoService,
        private PaymentMercadoPagoService $paymentMercadoPagoService,

    ) {
        if ( Session::has( 'auth' ) ) {
            $this->authenticated = Session::get( 'auth' );
        }
    }

    public function createPlanSubscription( PlanEntity $planSelected )
    {
        try {

            return $this->connection->transactional( function () use ($planSelected) {
                $result                  = [];
                $planSubscriptionCreated = [];
                $planSubscriptionUpdated = [];
                $planSubscriptionId      = null;

                // Verificar se o usuário já possui uma assinatura pending
                $currentSubscriptionPending = $this->planSubscription->getProviderPlan(
                    $this->authenticated->id,
                    $this->authenticated->tenant_id,
                    'pending',
                );

                // Verifica se existe uma assinatura
                if ( !$currentSubscriptionPending instanceof EntityNotFound ) {

                    /** @var PlanSubscriptionEntity $currentSubscriptionPending */
                    if ( $planSelected->id == $currentSubscriptionPending->plan_id ) {
                        // Retorna o id da assinatura pendente
                        return [ 
                            'status'  => 'success',
                            'message' => 'Você já possui uma assinatura pendente para este plano.',
                            'data'    => [ 
                                'currentSubscriptionPending' => $currentSubscriptionPending,
                                'planSubscriptionId'         => $currentSubscriptionPending->id,
                            ],
                        ];
                    }

                    // Verifica se o plano selecionado é diferente do plano atual
                    if ( $planSelected->id != $currentSubscriptionPending->plan_id ) {
                        // Atualiza o status da assinatura pendente para cancelado
                        $result = $this->updateStatusCanceled(
                            $this->authenticated->tenant_id,
                            $this->authenticated->id,
                            $currentSubscriptionPending->id,
                        );
                        // Verifica se a assinatura foi atualizada com sucesso
                        if ( $result[ 'status' ] === 'success' ) {
                            $planSubscriptionUpdated[ $currentSubscriptionPending->id ] = $result[ 'data' ][ 'updatedSubscription' ];
                        } else {
                            return [ 
                                'status'  => 'error',
                                'message' => 'Falha ao atualizar a assinatura pendente, tente novamente mais tarde ou entre em contato com suporte!',
                            ];
                        }
                    }
                }

                // Popular o PlanSubscriptionEntity com os dados do plano selecionado

                $properties                  = getConstructorProperties( PlanSubscriptionEntity::class);
                $properties[ 'tenant_id' ]   = $this->authenticated->tenant_id;
                $properties[ 'provider_id' ] = $this->authenticated->id;
                $data[ 'plan_id' ]           = $planSelected->id;
                $data[ 'status' ]            = $planSelected->slug === "free" ? 'active' : 'pending';
                $data[ 'price_paid' ]        = $planSelected->price;
                $data[ 'start_date' ]        = ( new \DateTime() )->format( 'Y-m-d H:i:s' );
                $data[ 'end_date' ]          = null;
                $data[ 'last_payment_date' ] = null;
                $data[ 'next_payment_date' ] = null;

                $planSubscriptionEntity = PlanSubscriptionEntity::create( removeUnnecessaryIndexes(
                    $properties,
                    [ 'id', 'created_at', 'updated_at' ],
                    $data,
                ) );

                // Criar PlanSubscription e retorna o id do planSubscription
                $result = $this->planSubscription->create( $planSubscriptionEntity );
                if ( $result[ 'status' ] === 'success' ) {
                    $planSubscriptionCreated = $result[ 'data' ];
                    $planSubscriptionId      = $result[ 'data' ][ 'id' ];
                } else {
                    return [ 
                        'status'  => 'error',
                        'message' => 'Falha ao criar a assinatura do plano, tente novamente mais tarde ou entre em contato com suporte!',
                    ];
                }

                return [ 
                    'status'  => 'success',
                    'message' => 'Assinatura do plano criada com sucesso.',
                    'data'    => [ 
                        'planSubscriptionCreated' => $planSubscriptionCreated,
                        'planSubscriptionUpdated' => $planSubscriptionUpdated,
                        'planSubscriptionId'      => $planSubscriptionId,
                    ],
                ];

            } );
        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao se inscrever no plano, tente novamente mais tarde ou entre em contato com suporte!", 0, $e );
        }
    }

    public function updatePlanSubscription( $payment )
    {
        try {
            return $this->connection->transactional( function () use ($payment) {
                $result                  = [ 'status' => 'error', 'message' => '' ];
                $planSubscriptionUpdated = [];
                // Buscar a assinatura atual
                $currentSubscription = $this->planSubscription->getPlanSubscriptionId(
                    $payment[ 'provider_id' ],
                    $payment[ 'tenant_id' ],
                    $payment[ 'plan_subscription_id' ],
                );
                // Verifica se a assinatura foi encontrada se não, retorna false
                if ( $currentSubscription instanceof EntityNotFound ) {
                    return [ 
                        'status'  => 'error',
                        'message' => 'Assinatura não encontrada.'
                    ];
                }
                // Buscar a última assinatura ativa
                $lastSubscription = $this->planSubscription->getProviderPlan(
                    $payment[ 'provider_id' ],
                    $payment[ 'tenant_id' ],
                    'active',
                );

                if ( $lastSubscription instanceof EntityNotFound ) {
                    return [ 
                        'status'  => 'error',
                        'message' => 'Assinatura não encontrada.'
                    ];
                }

                // Converter a entidade em um array
                $currentSubscription = $currentSubscription->toArray();

                // Verificar o status do pagamento do mercado pago se ativo atualiza o planSubscription
                if ( mapPaymentStatusToPlanSubscriptionsStatus( $payment[ 'status' ] ) == PlanSubscriptionsStatusEnum::active ) {

                    $data[ 'plan_id' ]           = $payment[ 'plan_id' ];
                    $data[ 'status' ]            = PlanSubscriptionsStatusEnum::active->value;
                    $data[ 'price_paid' ]        = $payment[ 'plan_price' ];
                    $data[ 'end_date' ]          = dateExpirate( '+35 days' );
                    $data[ 'payment_method' ]    = $payment[ 'payment_method' ];
                    $data[ 'payment_id' ]        = $payment[ 'payment_id' ];
                    $data[ 'last_payment_date' ] = ( new \DateTime() )->format( 'Y-m-d H:i:s' );
                    $data[ 'next_payment_date' ] = dateExpirate( '+1 month' );

                    $planSubscriptionEntity = PlanSubscriptionEntity::create( removeUnnecessaryIndexes(
                        $currentSubscription,
                        [ 'created_at', 'updated_at' ],
                        $data,
                    ) );

                    $result = $this->planSubscription->update( $planSubscriptionEntity );
                    if ( $result[ 'status' ] === 'success' ) {
                        $planSubscriptionUpdated[ $result[ 'data' ][ 'id' ] ] = $result[ 'data' ];
                    }
                }

                /// Verificar o status do pagamento do mercado pago se pendente atualiza o planSubscription
                if ( mapPaymentStatusToPlanSubscriptionsStatus( $payment[ 'status' ] ) == PlanSubscriptionsStatusEnum::pending ) {

                    $data[ 'plan_id' ]        = $payment[ 'plan_id' ];
                    $data[ 'price_paid' ]     = $payment[ 'plan_price' ];
                    $data[ 'payment_method' ] = $payment[ 'payment_method' ];
                    $data[ 'payment_id' ]     = $payment[ 'payment_id' ];

                    $planSubscriptionEntity = PlanSubscriptionEntity::create( removeUnnecessaryIndexes(
                        $currentSubscription,
                        [ 'created_at', 'updated_at' ],
                        $data,
                    ) );

                    $result = $this->planSubscription->update( $planSubscriptionEntity );
                    if ( $result[ 'status' ] === 'success' ) {
                        $planSubscriptionUpdated[ $result[ 'data' ][ 'id' ] ] = $result[ 'data' ];
                    }

                }

                // Verifica se a assinatura foi atualizada com sucesso e se há uma assinatura ativa anterior se  não retorna id da nova assinatura
                if (
                    $result[ 'status' ] === 'success'
                    && mapPaymentStatusToPlanSubscriptionsStatus( $payment[ 'status' ] ) == PlanSubscriptionsStatusEnum::active
                ) {
                    // Verifica se a assinatura atual foi cancelada e retorna o id da nova assinatura se não retorna false
                    /** @var PlansWithPlanSubscriptionEntity $lastSubscription */

                    // Atualiza o status de cancelado da assinatura do plano
                    $result = $this->updateStatusCanceled( $payment[ 'tenant_id' ], $payment[ 'provider_id' ], $lastSubscription->id );
                    // Verifica se a assinatura foi atualizada com sucesso
                    if ( $result[ 'status' ] === 'success' ) {
                        $planSubscriptionUpdated[ $lastSubscription->id ] = $result[ 'data' ][ 'updatedSubscription' ];
                    } else {
                        return [ 
                            'status'  => 'error',
                            'message' => 'Falha ao atualizar a assinatura pendente, tente novamente mais tarde ou entre em contato com suporte!',
                        ];
                    }

                }
                return [ 
                    'status'  => 'success',
                    'message' => 'Assinatura atualizada com sucesso.',
                    'data'    => [ 
                        'planSubscriptionUpdated' => $planSubscriptionUpdated,
                    ],
                ];

            } );
        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao atualizar a assinatura do plano, tente novamente mais tarde ou entre em contato com suporte!", 0, $e );
        }
    }

    public function updateStatusExpired( $tenant_id, $provider_id, $planSubscription_id )
    {
        try {
            // Iniciar uma transação
            return $this->connection->transactional( function () use ($tenant_id, $provider_id, $planSubscription_id) {
                $planSubscriptionUpdated = [];

                // Buscar a assinatura
                $currentSubscription = $this->planSubscription->getPlanSubscriptionId(
                    $provider_id,
                    $tenant_id,
                    $planSubscription_id,
                );
                // Verifica se a assinatura foi encontrada se não, retorna false
                if ( !$currentSubscription instanceof EntityNotFound ) {
                    return [ 
                        'status'  => 'error',
                        'message' => 'Assinatura não encontrada.'
                    ];

                }

                $properties = getConstructorProperties( PlanSubscriptionEntity::class);

                // Converter a entidade em um array
                $currentSubscriptionToArray = $currentSubscription->toArray();
                // Atualizar o status da assinatura para expirado
                $currentSubscriptionToArray[ 'status' ] = 'expired';
                // Atualizar a assinatura do plano para expirado e retorna true se não false

                $planSubscriptionEntity = PlanSubscriptionEntity::create( removeUnnecessaryIndexes(
                    $properties,
                    [ 'id', 'created_at', 'updated_at' ],
                    $currentSubscriptionToArray,
                ) );

                $result = $this->planSubscription->update( $planSubscriptionEntity );
                if ( $result[ 'status' ] === 'success' ) {
                    $planSubscriptionUpdated = $result[ 'data' ];
                }
                return [ 
                    'status'  => 'success',
                    'message' => 'Assinatura atualizada para expirado com sucesso.',
                    'data'    => [ 'planSubscriptionUpdated' => $planSubscriptionUpdated ],
                ];
            } );
        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao atualizar o status da assinatura do plano para expirado, tente novamente mais tarde ou entre em contato com suporte!", 0, $e );
        }
    }

    public function updateStatusCanceled( $tenant_id, $provider_id, $last_planSubscription_id )
    {
        try {
            return $this->connection->transactional( function () use ($tenant_id, $provider_id, $last_planSubscription_id) {
                $planSubscriptionUpdated = [];
                // Buscar a assinatura atual
                $currentSubscription = $this->planSubscription->getPlanSubscriptionId(
                    $provider_id,
                    $tenant_id,
                    $last_planSubscription_id,
                );
                // Verifica se a assinatura foi encontrada se não, retorna false
                if ( !$currentSubscription instanceof EntityNotFound ) {
                    return [ 
                        'status'  => 'error',
                        'message' => 'Assinatura não encontrada.'
                    ];
                }

                $properties = getConstructorProperties( PlanSubscriptionEntity::class);

                // Converter a entidade em um array
                $currentSubscriptionToArray = $currentSubscription->toArray();
                // Atualizar o status da assinatura para cancelado
                $currentSubscriptionToArray[ 'status' ] = 'canceled';
                // Atualizar a assinatura do plano para cancelado e retorna true se não false

                $planSubscriptionEntity = PlanSubscriptionEntity::create( removeUnnecessaryIndexes(
                    $properties,
                    [ 'id', 'created_at', 'updated_at' ],
                    $currentSubscriptionToArray,
                ) );

                $result = $this->planSubscription->update( $planSubscriptionEntity );
                if ( $result[ 'status' ] === 'success' ) {
                    $planSubscriptionUpdated = $result[ 'data' ];
                }
                return [ 
                    'status'  => 'success',
                    'message' => 'Assinatura cancelada com sucesso.',
                    'data'    => [ 'planSubscriptionUpdated' => $planSubscriptionUpdated ],
                ];

            } );

        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao cancelar a assinatura do plano, tente novamente mais tarde ou entre em contato com suporte!", 0, $e );
        }
    }

    /**
     * Handles a MercadoPago merchant order.
     *
     * @param array $merchant_order The merchant order data.
     * @return mixed The result of the `createMerchantOrder` method call.
     */
    public function handleMerchantOrderMercadoPago( $merchant_order )
    {
        return $this->merchantOrderMercadoPagoService->createMerchantOrder( $merchant_order );
    }

    /**
     * Handles a MercadoPago payment.
     *
     * @param array $payment The payment data.
     * @return mixed The result of the `createPayment` method call.
     */
    public function handlePaymentMercadoPago( $payment )
    {
        return $this->paymentMercadoPagoService->createPayment( $payment );
    }

}
