<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Models\Invoice;
use App\Support\ServiceResult;

/**
 * Interface para serviço especializado em pagamentos de faturas via MercadoPago.
 *
 * Define o contrato para operações específicas de processamento de pagamentos
 * de faturas através da integração com MercadoPago, mantendo isolamento por
 * tenant e seguindo os padrões do projeto Easy Budget.
 *
 * @author IA - Kilo Code
 * @version 1.0.0
 */
interface PaymentMercadoPagoInvoiceServiceInterface
{
    /**
     * Cria preferência de pagamento para uma fatura específica.
     *
     * @param Invoice $invoice Fatura a ser paga
     * @param int $tenantId ID do tenant proprietário da fatura
     * @param array $additionalData Dados adicionais para o pagamento
     * @return ServiceResult
     */
    public function createPaymentPreference( Invoice $invoice, int $tenantId, array $additionalData = [] ): ServiceResult;

    /**
     * Processa webhook específico para pagamentos de faturas.
     *
     * @param array $webhookData Dados do webhook do MercadoPago
     * @return ServiceResult
     */
    public function processInvoicePaymentWebhook( array $webhookData ): ServiceResult;

    /**
     * Verifica status de pagamento de uma fatura específica.
     *
     * @param string $paymentId ID do pagamento no MercadoPago
     * @param int $tenantId ID do tenant
     * @return ServiceResult
     */
    public function checkInvoicePaymentStatus( string $paymentId, int $tenantId ): ServiceResult;

    /**
     * Cancela pagamento de uma fatura.
     *
     * @param string $paymentId ID do pagamento
     * @param int $tenantId ID do tenant
     * @return ServiceResult
     */
    public function cancelInvoicePayment( string $paymentId, int $tenantId ): ServiceResult;

    /**
     * Processa reembolso de pagamento de fatura.
     *
     * @param string $paymentId ID do pagamento
     * @param float|null $amount Valor a reembolsar (null para total)
     * @param int $tenantId ID do tenant
     * @return ServiceResult
     */
    public function refundInvoicePayment( string $paymentId, ?float $amount = null, int $tenantId ): ServiceResult;

    /**
     * Lista pagamentos de faturas por tenant.
     *
     * @param int $tenantId ID do tenant
     * @param array $filters Filtros opcionais
     * @return ServiceResult
     */
    public function listInvoicePayments( int $tenantId, array $filters = [] ): ServiceResult;
}
