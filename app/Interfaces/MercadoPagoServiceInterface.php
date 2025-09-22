<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Support\ServiceResult;

/**
 * Interface para serviço de integração com MercadoPago.
 *
 * Define contrato para operações de pagamento, processamento de webhooks
 * e gerenciamento de transações com o MercadoPago. Esta interface segue
 * os padrões do projeto Easy Budget para consistência.
 *
 * @author IA - Kilo Code
 * @version 1.0.0
 */
interface MercadoPagoServiceInterface
{
    /**
     * Cria uma preferência de pagamento.
     *
     * @param array $paymentData Dados do pagamento
     * @param int $tenantId ID do tenant (para isolamento)
     * @return ServiceResult
     */
    public function createPaymentPreference( array $paymentData, int $tenantId ): ServiceResult;

    /**
     * Processa webhook de notificação do MercadoPago.
     *
     * @param array $webhookData Dados do webhook
     * @return ServiceResult
     */
    public function processWebhook( array $webhookData ): ServiceResult;

    /**
     * Verifica status de um pagamento.
     *
     * @param string $paymentId ID do pagamento
     * @param int $tenantId ID do tenant
     * @return ServiceResult
     */
    public function checkPaymentStatus( string $paymentId, int $tenantId ): ServiceResult;

    /**
     * Cancela um pagamento.
     *
     * @param string $paymentId ID do pagamento
     * @param int $tenantId ID do tenant
     * @return ServiceResult
     */
    public function cancelPayment( string $paymentId, int $tenantId ): ServiceResult;

    /**
     * Reembolsa um pagamento.
     *
     * @param string $paymentId ID do pagamento
     * @param float|null $amount Valor a reembolsar (opcional - total se não informado)
     * @param int $tenantId ID do tenant
     * @return ServiceResult
     */
    public function refundPayment( string $paymentId, ?float $amount = null, int $tenantId ): ServiceResult;
}
