<?php

namespace app\database\services;

use app\database\entitiesORM\MerchantOrderMercadoPagoEntity;
use app\database\models\MerchantOrderMercadoPago;
use core\dbal\EntityNotFound;
use Doctrine\DBAL\Connection;

class MerchantOrderMercadoPagoService
{
    /**
     * Summary of table
     * @var string
     */

    protected string $table = 'merchant_orders_mercado_pago';

    public function __construct(
        private readonly Connection $connection,
        private MerchantOrderMercadoPago $merchantOrderMercadoPago,
    ) {}

    /**
     * Cria uma nova ordem de comerciante do Mercado Pago.
     *
     * @param array<string, mixed> $merchantOrderMercadoPago Dados da ordem
     * @return mixed ID da ordem criada ou false em caso de erro
     */
    public function createMerchantOrder( array $merchantOrderMercadoPago ): mixed
    {
        return $this->connection->transactional( function () use ($merchantOrderMercadoPago) {
            $merchantOrder = $this->merchantOrderMercadoPago->getMerchantOrderId(
                $merchantOrderMercadoPago[ 'merchant_order_id' ],
                $merchantOrderMercadoPago[ 'tenant_id' ],
                $merchantOrderMercadoPago[ 'provider_id' ],
            );

            // Buscar a assinatura atual
            $lastMerchantOrder = $this->merchantOrderMercadoPago->getMerchantOrderStatus(
                $merchantOrderMercadoPago[ 'provider_id' ],
                $merchantOrderMercadoPago[ 'tenant_id' ],
                $merchantOrderMercadoPago[ 'plan_subscription_id' ],
            );

            if ( $merchantOrder instanceof EntityNotFound ) {
                // Popular o MerchantOrderEntiy com os dados que vem mercado pago
                $merchantOrderEntity = MerchantOrderMercadoPagoEntity::create( [ 
                    'tenant_id'            => $merchantOrderMercadoPago[ 'tenant_id' ],
                    'provider_id'          => $merchantOrderMercadoPago[ 'provider_id' ],
                    'merchant_order_id'    => $merchantOrderMercadoPago[ 'merchant_order_id' ],
                    'plan_subscription_id' => $merchantOrderMercadoPago[ 'plan_subscription_id' ],
                    'status'               => mapMerchantOrderStatusMercadoPago( $merchantOrderMercadoPago[ 'status' ] )->value,
                    'order_status'         => mapMerchantOrderOrderStatusMercadoPago( $merchantOrderMercadoPago[ 'order_status' ] )->value,
                    'total_amount'         => $merchantOrderMercadoPago[ 'paid_amount' ],
                ] );

                // Criar MerchantOrder e retorna o id do merchant order criado
                $result = $this->merchantOrderMercadoPago->create( $merchantOrderEntity );

                if ( $result[ 'status' ] === 'error' ) {
                    return false;
                }
                $merchantOrderId = $result[ 'data' ][ 'id' ];
                if ( !$lastMerchantOrder instanceof EntityNotFound ) {
                    if ( $this->updateStatusCancelled( $lastMerchantOrder ) ) {
                        return $merchantOrderId;
                    } else {
                        return false;
                    }
                }

                return $merchantOrderId;
            } else {
                /** @var MerchantOrderMercadoPagoEntity $merchantOrder */
                return $this->updateMerchantOrder( $merchantOrder );
            }
        } );
    }

    /**
     * Atualiza uma ordem de comerciante existente.
     *
     * @param MerchantOrderMercadoPagoEntity $merchantOrderMercadoPagoEntity Entidade da ordem
     * @return mixed ID da ordem atualizada ou false em caso de erro
     */
    public function updateMerchantOrder( MerchantOrderMercadoPagoEntity $merchantOrderMercadoPagoEntity ): mixed
    {

        return $this->connection->transactional( function () use ($merchantOrderMercadoPagoEntity) {
            $currentMerchantOrder = $this->merchantOrderMercadoPago->getMerchantOrderId(
                $merchantOrderMercadoPagoEntity->merchant_order_id,
                $merchantOrderMercadoPagoEntity->tenant_id,
                $merchantOrderMercadoPagoEntity->provider_id,
            );

            if ( $currentMerchantOrder instanceof EntityNotFound ) {
                return false;
            }

            $currentMerchantOrder = $currentMerchantOrder->toArray();

            if ( $currentMerchantOrder[ 'status' ] === $merchantOrderMercadoPagoEntity->status && $currentMerchantOrder[ 'order_status' ] === $merchantOrderMercadoPagoEntity->order_status && $currentMerchantOrder[ 'total_amount' ] === $merchantOrderMercadoPagoEntity->total_amount ) {
                return $currentMerchantOrder[ 'id' ];
            }

            $currentMerchantOrder[ 'status' ]       = mapMerchantOrderStatusMercadoPago( $merchantOrderMercadoPagoEntity->status )->value;
            $currentMerchantOrder[ 'order_status' ] = mapMerchantOrderOrderStatusMercadoPago( $merchantOrderMercadoPagoEntity->order_status )->value;
            $currentMerchantOrder[ 'total_amount' ] = $merchantOrderMercadoPagoEntity->total_amount;

            $result = $this->merchantOrderMercadoPago->update( MerchantOrderMercadoPagoEntity::create( $currentMerchantOrder ) );

            if ( $result[ 'status' ] === 'error' ) {
                return false;
            }

            return $result[ 'data' ][ 'id' ];

        } );
    }

    /**
     * Atualiza o status para cancelado.
     *
     * @param mixed $lastMerchantOrder Última ordem de comerciante
     * @return mixed Resultado da operação
     */
    public function updateStatusCancelled( mixed $lastMerchantOrder ): mixed
    {
        // Iniciar uma transação
        return $this->connection->transactional( function () use ($lastMerchantOrder) {
            // Verifica se a assinatura foi encontrada se não, retorna false
            if ( !$lastMerchantOrder instanceof EntityNotFound ) {
                // Converter a entidade em um array
                $lastMerchantOrder = $lastMerchantOrder->toArray();
                // Atualizar o status da assinatura para cancelado
                $lastMerchantOrder[ 'status' ]       = 'cancelled';
                $lastMerchantOrder[ 'order_status' ] = 'expired';

                // Atualizar a assinatura do plano para cancelado e retorna true se não false
                return $this->merchantOrderMercadoPago->update( MerchantOrderMercadoPagoEntity::create( $lastMerchantOrder ) );
            }

            return false;
        } );
    }

}
