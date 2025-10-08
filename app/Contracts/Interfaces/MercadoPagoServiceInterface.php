<?php

declare(strict_types=1);

namespace App\Contracts\Interfaces;

/**
 * Interface para serviço MercadoPago
 */
interface MercadoPagoServiceInterface
{
    /**
     * Processa pagamento
     */
    public function processPayment( array $data ): array;

    /**
     * Verifica status do pagamento
     */
    public function checkPaymentStatus( string $paymentId ): array;
}
