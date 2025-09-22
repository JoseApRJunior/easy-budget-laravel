<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Support\ServiceResult;

/**
 * Interface para serviço especializado em pagamentos de planos via MercadoPago.
 *
 * Define o contrato para operações específicas de processamento de pagamentos
 * de planos através da integração com MercadoPago, mantendo isolamento por
 * tenant e seguindo os padrões do projeto Easy Budget.
 *
 * @author IA - Kilo Code
 * @version 1.0.0
 */
interface PaymentMercadoPagoPlanServiceInterface
{
    /**
     * Cria uma preferência de pagamento específica para planos.
     *
     * @param array $planData Dados do plano para pagamento
     * @param int $tenantId ID do tenant (para isolamento)
     * @return ServiceResult
     */
    public function createPlanPaymentPreference( array $planData, int $tenantId ): ServiceResult;

    /**
     * Processa webhook de notificação do MercadoPago para planos.
     *
     * @param array $webhookData Dados do webhook do MercadoPago
     * @return ServiceResult
     */
    public function processPlanWebhook( array $webhookData ): ServiceResult;

    /**
     * Verifica status de um pagamento de plano.
     *
     * @param string $paymentId ID do pagamento no MercadoPago
     * @param int $tenantId ID do tenant
     * @return ServiceResult
     */
    public function checkPlanPaymentStatus( string $paymentId, int $tenantId ): ServiceResult;

    /**
     * Cancela um pagamento de plano.
     *
     * @param string $paymentId ID do pagamento no MercadoPago
     * @param int $tenantId ID do tenant
     * @return ServiceResult
     */
    public function cancelPlanPayment( string $paymentId, int $tenantId ): ServiceResult;

    /**
     * Reembolsa um pagamento de plano.
     *
     * @param string $paymentId ID do pagamento no MercadoPago
     * @param float|null $amount Valor a reembolsar (opcional - total se não informado)
     * @param int $tenantId ID do tenant
     * @return ServiceResult
     */
    public function refundPlanPayment( string $paymentId, ?float $amount = null, int $tenantId ): ServiceResult;

    /**
     * Lista pagamentos de planos com filtros avançados.
     *
     * @param int $tenantId ID do tenant
     * @param array $filters Filtros para consulta
     * @param array|null $orderBy Ordenação dos resultados
     * @param int|null $limit Limite de resultados
     * @param int|null $offset Offset dos resultados
     * @return ServiceResult
     */
    public function listPlanPayments( int $tenantId, array $filters = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): ServiceResult;
}
