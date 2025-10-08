<?php

declare(strict_types=1);

namespace App\Contracts\Interfaces;

/**
 * Interface para serviço de pagamentos de faturas MercadoPago
 */
interface PaymentMercadoPagoInvoiceServiceInterface
{
    /**
     * Processa pagamento de fatura
     */
    public function processInvoicePayment( array $data ): array;

    /**
     * Verifica status do pagamento da fatura
     */
    public function checkInvoicePaymentStatus( string $paymentId ): array;
}
