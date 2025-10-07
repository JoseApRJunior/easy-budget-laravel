<?php

namespace app\database\services;

use app\database\entitiesORM\PlanEntity;
use app\database\entitiesORM\PlanSubscriptionEntity;
use app\database\entitiesORM\PlansWithPlanSubscriptionEntity;
use app\database\models\PaymentMercadoPagoPlans;
use app\database\models\Plan;
use app\database\models\PlanSubscription;
use app\enums\PlanSubscriptionsStatusEnum;
use core\dbal\EntityNotFound;
use core\library\Session;
use Doctrine\DBAL\Connection;
use Exception;
use RuntimeException;

class PlanService
{
    /**
     * The database table name for plan subscriptions.
     */
    protected string $table = 'plan';
    protected string $tablePlanSubscriptions = 'plan_subscriptions';
    private mixed $authenticated;

    public function __construct(
        private readonly Connection $connection,
        private PlanSubscription $planSubscription,
        private PaymentMercadoPagoPlanService $paymentMercadoPagoPlanService,
        private PaymentMercadoPagoPlans $paymentMercadoPagoPlans,
        private Plan $plan,
    ) {
        if ( Session::has( 'auth' ) ) {
            $this->authenticated = Session::get( 'auth' );
        }
    }

    /**
     * Cria uma nova assinatura de plano.
     *
     * @param PlanEntity $planSelected Plano selecionado.
     * @return array<string, mixed> Resultado da operação.
     */
    public function createPlanSubscription( PlanEntity $planSelected ): array
    {
        try {

            return $this->connection->transactional( function () use ($planSelected) {
                $result                     = [];
                $planSubscriptionCreated    = [];
                $planSubscriptionUpdated    = [];
                $planSubscriptionId         = null;
                $currentSubscriptionPending = [];

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
                                'currentPlanSubscriptionPending' => $currentSubscriptionPending,
                                'planSubscriptionId'             => $currentSubscriptionPending->id,
                            ],
                        ];
                    }

                    // Verifica se o plano selecionado é diferente do plano atual
                    if ( $planSelected->id != $currentSubscriptionPending->plan_id ) {
                        // Atualiza o status da assinatura pendente para cancelado
                        $result = $this->updateStatusCancelled(
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

                $properties                         = getConstructorProperties( PlanSubscriptionEntity::class);
                $properties[ 'tenant_id' ]          = $this->authenticated->tenant_id;
                $properties[ 'provider_id' ]        = $this->authenticated->id;
                $properties[ 'plan_id' ]            = $planSelected->id;
                $properties[ 'status' ]             = $planSelected->slug === "free" ? 'active' : 'pending';
                $properties[ 'transaction_amount' ] = $planSelected->price;
                $properties[ 'start_date' ]         = ( new \DateTime() )->format( 'Y-m-d H:i:s' );
                $properties[ 'end_date' ]           = null;
                $properties[ 'last_payment_date' ]  = null;
                $properties[ 'next_payment_date' ]  = null;

                $planSubscriptionEntity = PlanSubscriptionEntity::create( removeUnnecessaryIndexes(
                    $properties,
                    [ 'id', 'created_at', 'updated_at' ],
                    $properties,
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
                        'planSubscriptionCreated'        => $planSubscriptionCreated,
                        'planSubscriptionUpdated'        => $planSubscriptionUpdated,
                        'planSubscriptionId'             => $planSubscriptionId,
                        'currentPlanSubscriptionPending' => $currentSubscriptionPending,
                    ],
                ];

            } );
        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao se inscrever no plano, tente novamente mais tarde ou entre em contato com suporte!", 0, $e );
        }
    }

    /**
     * Atualiza uma assinatura de plano com base nos dados de pagamento.
     *
     * @param array<string, mixed> $payment Dados do pagamento.
     * @return array<string, mixed> Resultado da operação.
     */
    public function updatePlanSubscription( array $payment ): array
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
                        'message' => 'Assinatura não encontrada.',
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
                        'message' => 'Assinatura não encontrada.',
                    ];
                }

                // Converter a entidade em um array
                $currentSubscription = $currentSubscription->toArray();

                // Verificar o status do pagamento do mercado pago se ativo atualiza o planSubscription
                if ( mapPaymentStatusToPlanSubscriptionsStatus( $payment[ 'status' ] ) == PlanSubscriptionsStatusEnum::active ) {
                    $currentSubscription[ 'status' ]             = PlanSubscriptionsStatusEnum::active->value;
                    $currentSubscription[ 'transaction_amount' ] = $payment[ 'plan_price' ];
                    $currentSubscription[ 'start_date' ]         = ( new \DateTime() )->format( 'Y-m-d H:i:s' );
                    $currentSubscription[ 'end_date' ]           = dateExpirate( '+35 days' );
                    $currentSubscription[ 'last_payment_date' ]  = ( new \DateTime() )->format( 'Y-m-d H:i:s' );
                    $currentSubscription[ 'next_payment_date' ]  = dateExpirate( '+1 month' );
                    $currentSubscription[ 'id' ]                 = $payment[ 'plan_subscription_id' ];
                    $data                                        = $payment;
                    unset( $data[ 'status' ] );
                    $entity = PlanSubscriptionEntity::create( removeUnnecessaryIndexes(
                        $currentSubscription,
                        [ 'created_at', 'updated_at' ],
                        $data,
                    ) );

                    $result = $this->planSubscription->update( $entity );
                    if ( $result[ 'status' ] === 'error' ) {
                        return [ 
                            'status'  => 'error',
                            'message' => 'Falha ao atualizar a assinatura do plano, tente novamente mais tarde ou entre em contato com suporte!',
                        ];
                    }
                    $planSubscriptionUpdated[ $result[ 'data' ][ 'id' ] ] = $result[ 'data' ];
                }

                /// Verificar o status do pagamento do mercado pago se pendente atualiza o planSubscription
                if ( mapPaymentStatusToPlanSubscriptionsStatus( $payment[ 'status' ] ) == PlanSubscriptionsStatusEnum::pending ) {
                    if (
                        $currentSubscription[ 'status' ] == PlanSubscriptionsStatusEnum::pending->value
                        && $currentSubscription[ 'payment_id' ] == $payment[ 'payment_id' ]
                        && $currentSubscription[ 'id' ] == $payment[ 'plan_subscription_id' ]
                        && $currentSubscription[ 'payment_method' ] == $payment[ 'payment_method' ]
                    ) {
                        return [ 
                            'status'                        => 'success',
                            'message'                       => 'Pagamento já existe com o mesmo status.',
                            'data'                          => $currentSubscription,
                            'planSubscriptionAlreadyExists' => true,
                        ];
                    }

                    $currentSubscription[ 'id' ]     = $payment[ 'plan_subscription_id' ];
                    $currentSubscription[ 'status' ] = PlanSubscriptionsStatusEnum::pending->value;
                    $data                            = $payment;
                    unset( $data[ 'status' ] );
                    $planSubscriptionEntity = PlanSubscriptionEntity::create( removeUnnecessaryIndexes(
                        $currentSubscription,
                        [ 'created_at', 'updated_at' ],
                        $data,
                    ) );

                    $result = $this->planSubscription->update( $planSubscriptionEntity );
                    if ( $result[ 'status' ] === 'error' ) {
                        return [ 
                            'status'  => 'error',
                            'message' => 'Falha ao atualizar a assinatura do plano, tente novamente mais tarde ou entre em contato com suporte!',
                        ];
                    }
                    $planSubscriptionUpdated[ $result[ 'data' ][ 'id' ] ] = $result[ 'data' ];

                }

                // Verifica se a assinatura foi atualizada com sucesso e se há uma assinatura ativa anterior se  não retorna id da nova assinatura
                if (
                    $result[ 'status' ] === 'success'
                    && mapPaymentStatusToPlanSubscriptionsStatus( $payment[ 'status' ] ) == PlanSubscriptionsStatusEnum::active
                ) {
                    // Verifica se a assinatura atual foi cancelada e retorna o id da nova assinatura se não retorna false
                    /** @var PlansWithPlanSubscriptionEntity $lastSubscription */

                    // Atualiza o status de cancelado da assinatura do plano
                    $result = $this->updateStatusCancelled( $payment[ 'tenant_id' ], $payment[ 'provider_id' ], $lastSubscription->id );
                    // Verifica se a assinatura foi atualizada com sucesso
                    if ( $result[ 'status' ] === 'error' ) {
                        return [ 
                            'status'  => 'error',
                            'message' => 'Falha ao atualizar a assinatura pendente, tente novamente mais tarde ou entre em contato com suporte!',
                        ];
                    }
                    $planSubscriptionUpdated[ $lastSubscription->id ] = $result[ 'data' ];

                }

                if ( $payment[ 'status' ] === 'approved' ) {
                    // Limpa o last updated session provider
                    Session::remove( 'checkPlan' );
                    Session::remove( 'last_updated_session_provider' );
                }

                return [ 
                    'status'  => 'success',
                    'message' => 'Assinatura atualizada com sucesso.',
                    'data'    => $planSubscriptionUpdated,
                ];

            } );
        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao atualizar a assinatura do plano, tente novamente mais tarde ou entre em contato com suporte!", 0, $e );
        }
    }

    /**
     * Atualiza o status de uma assinatura para expirado.
     *
     * @param mixed $tenant_id ID do tenant.
     * @param mixed $provider_id ID do provedor.
     * @param mixed $planSubscription_id ID da assinatura do plano.
     * @return array<string, mixed> Resultado da operação.
     */
    public function updateStatusExpired( mixed $tenant_id, mixed $provider_id, mixed $planSubscription_id ): array
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
                        'message' => 'Assinatura não encontrada.',
                    ];

                }

                // Converter a entidade em um array
                $currentSubscriptionToArray = $currentSubscription->toArray();
                // Atualizar o status da assinatura para expirado
                $currentSubscriptionToArray[ 'status' ] = 'expired';
                // Atualizar a assinatura do plano para expirado e retorna true se não false

                $planSubscriptionEntity = PlanSubscriptionEntity::create( removeUnnecessaryIndexes(
                    $currentSubscription,
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

    /**
     * Atualiza o status de uma assinatura para cancelado.
     *
     * @param mixed $tenant_id ID do tenant.
     * @param mixed $provider_id ID do provedor.
     * @param mixed $last_planSubscription_id ID da última assinatura do plano.
     * @return array<string, mixed> Resultado da operação.
     */
    public function updateStatusCancelled( mixed $tenant_id, mixed $provider_id, mixed $last_planSubscription_id ): array
    {
        try {
            return $this->connection->transactional( function () use ($tenant_id, $provider_id, $last_planSubscription_id) {
                // Buscar a assinatura atual
                $currentSubscription = $this->planSubscription->getPlanSubscriptionId(
                    $provider_id,
                    $tenant_id,
                    $last_planSubscription_id,
                );
                // Verifica se a assinatura foi encontrada se não, retorna false
                if ( $currentSubscription instanceof EntityNotFound ) {
                    return [ 
                        'status'  => 'error',
                        'message' => 'Assinatura não encontrada.',
                    ];
                }

                // Converter a entidade em um array
                $currentSubscriptionToArray = $currentSubscription->toArray();
                // Atualizar o status da assinatura para cancelado
                $currentSubscriptionToArray[ 'status' ] = 'cancelled';
                // Atualizar a assinatura do plano para cancelado e retorna true se não false
                $properties             = getConstructorProperties( PlanSubscriptionEntity::class);
                $planSubscriptionEntity = PlanSubscriptionEntity::create( removeUnnecessaryIndexes(
                    $properties,
                    [ 'created_at', 'updated_at' ],
                    $currentSubscriptionToArray,
                ) );

                $result = $this->planSubscription->update( $planSubscriptionEntity );
                if ( $result[ 'status' ] === 'error' ) {
                    return [ 
                        'status'  => 'error',
                        'message' => 'Falha ao atualizar a assinatura do plano, tente novamente mais tarde ou entre em contato com suporte!',
                    ];
                }

                return [ 
                    'status'  => 'success',
                    'message' => 'Assinatura cancelada com sucesso.',
                    'data'    => $result[ 'data' ],
                ];

            } );

        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao cancelar a assinatura do plano, tente novamente mais tarde ou entre em contato com suporte!", 0, $e );
        }
    }

    /**
     * Cria uma nova assinatura com base no slug do plano.
     *
     * @param string $planSlug Slug do plano selecionado.
     * @return array<string, mixed> Resultado da operação.
     */
    public function createSubscription( string $planSlug ): array
    {
        // Busca o plano selecionado pelo slug
        /** @var PlanEntity|EntityNotFound $planSelected */
        $planSelected = $this->plan->getActivePlanBySlug( $planSlug );

        // Verifica se o plano existe
        if ( $planSelected instanceof EntityNotFound ) {
            return [ 
                'status'  => 'error',
                'message' => "Plano {$planSlug} não encontrado ou não está ativo.",
            ];
        }

        // Lógica de bloqueio: não permitir criar novo pagamento se houver um "in_process".
        $pendingSubscription = $this->planSubscription->getProviderPlan(
            $this->authenticated->id,
            $this->authenticated->tenant_id,
            'pending',
        );

        if ( !$pendingSubscription instanceof EntityNotFound ) {
            // Encontrou uma assinatura pendente. Agora, verifique o status do último pagamento associado.
            /** @var PlanSubscriptionEntity $pendingSubscription */
            $lastPayment = $this->paymentMercadoPagoPlans->getPaymentsByPlanSubscription(
                $this->authenticated->id,
                $this->authenticated->tenant_id,
                $pendingSubscription->id,
            );

            // Se já existe um pagamento em andamento ou aprovado, não faz nada.
            if ( !$lastPayment instanceof EntityNotFound ) {
                $payments         = is_array( $lastPayment ) ? $lastPayment : [ $lastPayment ];
                $blockingStatuses = [ 'pending', 'authorized', 'in_process', 'in_mediation', 'approved' ];

                foreach ( $payments as $payment ) {
                    /** @var \app\database\entities\PaymentMercadoPagoPlansEntity $payment */
                    if ( in_array( $payment->status, $blockingStatuses ) ) {
                        return [ 
                            'status'                        => 'success',
                            'message'                       => "Um pagamento para atualização de assinatura já está em andamento. Nenhuma nova ação foi tomada.",
                            'data'                          => $payment->toArray(),
                            'planSubscriptionAlreadyExists' => true,
                        ];
                    }
                }
            }

        }

        // Pega o plano atual do usuário
        $currentPlan = Session::get( 'checkPlan' );

        // Verifica se o plano se existe sessão de plano
        if ( !$currentPlan ) {
            $currentPlan = EntityNotFound::create( [] );
        }

        // Verifica se o plano atual existe e se o status é ativo
        if ( !$currentPlan instanceof EntityNotFound && $currentPlan->status == 'active' ) {
            // Verifica se o plano é o  mesmo plano e se esta expirado
            if (
                ( $currentPlan->slug === $planSlug & $currentPlan->end_date > date( "d-m-Y" ) )
            ) {
                // Retorna uma resposta de erro
                return [ 
                    'status'  => 'error',
                    'message' => "Este já é seu plano atual, expira em " . date( 'd/m/Y', ( $currentPlan->end_date )?->getTimestamp() ) . "!",
                ];
            }
            // Verifica se o plano atual é superior ao plano selecionado
            if (
                (float) $currentPlan->transaction_amount > (float) $planSelected->price &
                $currentPlan->end_date > date( "d-m-Y" )
            ) {
                return [ 
                    'status'  => 'error',
                    'message' => "O seu plano atual, " . $currentPlan->name . " e superior, expira em " . date( 'd/m/Y', ( $currentPlan->end_date )?->getTimestamp() ) . "!",
                ];
                // Retorna uma resposta de erro
            }
        }

        // Cria uma nova assinatura do plano selecionado superior como pending ou cria uma nova assinatura do plano free como active
        $result = $this->createPlanSubscription( $planSelected );
        if ( $result[ 'status' ] === 'error' ) {
            return [ 
                'status'  => 'error',
                'message' => $result[ 'message' ],
            ];
        }
        $createOrUpdateOrPendingPlanSubscription = $result[ 'data' ];

        if ( $planSelected->slug === 'free' ) {
            // Limpa o last updated session provider
            Session::remove( 'checkPlan' );
            Session::remove( 'last_updated_session_provider' );

            // Retorna uma resposta de sucesso
            return [ 
                'status'  => 'success',
                'message' => "Plano atualizado com sucesso, seja bem-vindo ao seu novo {$planSelected->name}!",
                'data'    => [ 
                    'planSubscriptionId'                      => $result[ 'data' ][ 'planSubscriptionId' ],
                    'createOrUpdateOrPendingPlanSubscription' => $createOrUpdateOrPendingPlanSubscription,
                ],
            ];

        }

        // Cria a requisição para o Mercado Pago, e retorna a resposta para a pagina  success/failure/pending

        $result = $this->paymentMercadoPagoPlanService->createMercadoPagoPreference(
            $currentPlan,
            $planSelected,
            $result[ 'data' ][ 'planSubscriptionId' ],
        );

        // Redireciona para a página de pagamento
        return [ 
            'status'                                  => $result ? 'success' : 'error',
            'message'                                 => $result ? 'Preferençia de pagamento criada com sucesso' : 'Erro ao criar preferência de pagamento.',
            'data'                                    => $result[ 'data' ],
            'createOrUpdateOrPendingPlanSubscription' => $createOrUpdateOrPendingPlanSubscription,
            'payment_url'                             => $result[ 'payment_url' ],
        ];

    }

}
