<?php

declare(strict_types=1);

namespace App\Contracts\Interfaces;

/**
 * Interface para serviço de ordens MercadoPago
 */
interface MerchantOrderMercadoPagoServiceInterface
{
    /**
     * Cria ordem de pagamento
     */
    public function createOrder( array $data ): array;

    /**
     * Atualiza status da ordem
     */
    public function updateOrderStatus( string $orderId, string $status ): bool;
}
