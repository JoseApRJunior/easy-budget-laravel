<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Support\ServiceResult;

/**
 * Interface para serviço de gerenciamento de merchant orders do MercadoPago.
 *
 * Define contrato para operações de merchant orders, incluindo criação,
 * atualização, processamento de webhooks, sincronização de status e
 * compatibilidade com API legacy. Esta interface segue os padrões do
 * projeto Easy Budget para consistência.
 *
 * @author IA - Kilo Code
 * @version 1.0.0
 */
interface MerchantOrderMercadoPagoServiceInterface
{
    /**
     * Cria uma nova merchant order.
     *
     * @param array $orderData Dados da merchant order
     * @param int $tenantId ID do tenant (para isolamento)
     * @return ServiceResult
     */
    public function createMerchantOrder( array $orderData, int $tenantId ): ServiceResult;

    /**
     * Atualiza uma merchant order existente.
     *
     * @param array $orderData Dados atualizados da merchant order
     * @param int $tenantId ID do tenant
     * @return ServiceResult
     */
    public function updateMerchantOrder( array $orderData, int $tenantId ): ServiceResult;

    /**
     * Processa webhook de merchant order do MercadoPago.
     *
     * @param array $webhookData Dados do webhook do MercadoPago
     * @return ServiceResult
     */
    public function processMerchantOrderWebhook( array $webhookData ): ServiceResult;

    /**
     * Sincroniza status de merchant order com MercadoPago.
     *
     * @param string $orderId ID da merchant order no MercadoPago
     * @param int $tenantId ID do tenant
     * @return ServiceResult
     */
    public function syncMerchantOrderStatus( string $orderId, int $tenantId ): ServiceResult;

    /**
     * Lista merchant orders com filtros avançados.
     *
     * @param int $tenantId ID do tenant
     * @param array $filters Filtros para consulta
     * @param array|null $orderBy Ordenação dos resultados
     * @param int|null $limit Limite de resultados
     * @param int|null $offset Offset dos resultados
     * @return ServiceResult
     */
    public function listMerchantOrders(
        int $tenantId,
        array $filters = [],
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): ServiceResult;

    /**
     * Cancela uma merchant order.
     *
     * @param string $orderId ID da merchant order no MercadoPago
     * @param int $tenantId ID do tenant
     * @return ServiceResult
     */
    public function cancelMerchantOrder( string $orderId, int $tenantId ): ServiceResult;
}
