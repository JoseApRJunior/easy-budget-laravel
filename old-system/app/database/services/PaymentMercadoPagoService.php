<?php

namespace app\database\services;

use app\database\entities\MerchantOrderMercadoPagoEntity;
use app\database\entities\PaymentMercadoPagoEntity;
use app\database\models\MerchantOrderMercadoPago;
use app\database\models\PaymentMercadoPago;
use core\dbal\EntityNotFound;
use Doctrine\DBAL\Connection;

class PaymentMercadoPagoService
{
    /**
     * Summary of table
     * @var string
     */

    protected string $table = 'payments_mercado_pago';

    public function __construct(
        private readonly Connection $connection,
        private PaymentMercadoPago $paymentMercadoPago,
    ) {}

    public function createPayment( array $paymentMercadoPago )
    {
        return $this->connection->transactional( function () use ($paymentMercadoPago) {
            $payment = $this->paymentMercadoPago->getPaymentId(
                $paymentMercadoPago[ 'payment_id' ],
                $paymentMercadoPago[ 'tenant_id' ],
                $paymentMercadoPago[ 'provider_id' ],
            );

            // Buscar a assinatura atual
            $lastPayment = $this->paymentMercadoPago->getPaymentByPlanSubscription(
                $paymentMercadoPago[ 'provider_id' ],
                $paymentMercadoPago[ 'tenant_id' ],
                $paymentMercadoPago[ 'plan_subscription_id' ],
            );

            if ( $payment instanceof EntityNotFound ) {
                // Popular o MerchantOrderEntiy com os dados que vem mercado pago
                $paymentEntity = PaymentMercadoPagoEntity::create( [ 
                    'payment_id'           => $paymentMercadoPago[ 'payment_id' ],
                    'tenant_id'            => $paymentMercadoPago[ 'tenant_id' ],
                    'provider_id'          => $paymentMercadoPago[ 'provider_id' ],
                    'plan_subscription_id' => $paymentMercadoPago[ 'plan_subscription_id' ],
                    'status'               => mapPaymentStatusMercadoPago( $paymentMercadoPago[ 'status' ] )->value,
                    'payment_method'       => $paymentMercadoPago[ 'payment_method' ],
                    'transaction_amount'   => $paymentMercadoPago[ 'transaction_amount' ]
                ] );

                // Criar MerchantOrder e retorna o id do merchant order criado
                $paymentId = $this->paymentMercadoPago->create( $paymentEntity );

                if ( !$paymentId ) {
                    return false;
                }

                if ( !$lastPayment instanceof EntityNotFound ) {
                    if ( $this->updateStatusCanceled( $lastPayment ) ) {
                        return $paymentId;
                    } else {
                        return false;
                    }
                }
                return $paymentId;
            } else {
                /** @var PaymentMercadoPagoEntity $payment */
                return $this->updatePayment( $payment );
            }
        } );
    }

    public function updatePayment( PaymentMercadoPagoEntity $paymentMercadoPagoEntity )
    {
        return $this->connection->transactional( function () use ($paymentMercadoPagoEntity) {
            $currentPayment = $this->paymentMercadoPago->getPaymentId(
                $paymentMercadoPagoEntity->payment_id,
                $paymentMercadoPagoEntity->tenant_id,
                $paymentMercadoPagoEntity->provider_id,
            );

            if ( $currentPayment instanceof EntityNotFound ) {
                return false;
            }

            $currentPayment = $currentPayment->toArray();

            if (
                $currentPayment[ 'status' ] === $paymentMercadoPagoEntity->status &&
                $currentPayment[ 'payment_method' ] === $paymentMercadoPagoEntity->payment_method &&
                $currentPayment[ 'transaction_amount' ] === $paymentMercadoPagoEntity->transaction_amount
            ) {
                return $currentPayment[ 'id' ];
            }

            $currentPayment[ 'status' ]             = mapPaymentStatusMercadoPago( $paymentMercadoPagoEntity->status )->value;
            $currentPayment[ 'payment_method' ]     = $paymentMercadoPagoEntity->payment_method;
            $currentPayment[ 'transaction_amount' ] = $paymentMercadoPagoEntity->transaction_amount;
            return $this->paymentMercadoPago->update( PaymentMercadoPagoEntity::create( $currentPayment ) )
                ? $currentPayment[ 'id' ]
                : false;

        } );
    }

    public function updateStatusCanceled( $lastPayment )
    {
        // Iniciar uma transação
        return $this->connection->transactional( function () use ($lastPayment) {
            // Converter $lastPayment para array, caso seja um objeto com método toArray
            if ( is_object( $lastPayment ) && method_exists( $lastPayment, 'toArray' ) ) {
                $lastPayment = $lastPayment->toArray();
            } elseif ( is_array( $lastPayment ) && !empty( $lastPayment ) && is_object( reset( $lastPayment ) ) && method_exists( reset( $lastPayment ), 'toArray' ) ) {
                $lastPayment = array_map( function ($payment) {
                    return method_exists( $payment, 'toArray' ) ? $payment->toArray() : (array) $payment;
                }, $lastPayment );
            }

            // Se $lastPayment for um array de pagamentos, percorre cada um e atualiza via update
            if ( isset( $lastPayment[ 0 ] ) ) {
                // Cada elemento é um pagamento
                foreach ( $lastPayment as $payment ) {
                    if ( isset( $payment[ 'status' ] ) && $payment[ 'status' ] !== 'approved' ) {
                        $payment[ 'status' ] = 'cancelled';
                    }
                    $this->paymentMercadoPago->update( PaymentMercadoPagoEntity::create( $payment ) );
                }
                return true;
            } else {
                // $lastPayment é um único pagamento
                if ( isset( $lastPayment[ 'status' ] ) && $lastPayment[ 'status' ] !== 'approved' ) {
                    $lastPayment[ 'status' ] = 'cancelled';
                }
                return $this->paymentMercadoPago->update( PaymentMercadoPagoEntity::create( $lastPayment ) );
            }
        } );
    }

}
