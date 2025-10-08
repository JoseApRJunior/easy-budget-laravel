<?php

declare(strict_types=1);

namespace App\Contracts\Interfaces;

/**
 * Interface para serviço de pagamentos de planos MercadoPago
 */
interface PaymentMercadoPagoPlanServiceInterface
{
    /**
     * Processa pagamento de plano
     */
    public function processPlanPayment( array $data ): array;

    /**
     * Cancela assinatura
     */
    public function cancelSubscription( string $subscriptionId ): bool;
}
