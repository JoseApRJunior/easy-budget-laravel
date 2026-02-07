<?php

declare(strict_types=1);

namespace App\Services\Infrastructure;

/**
 * Serviço especializado para gerenciamento de merchant orders do MercadoPago.
 *
 * Esta classe gerencia todas as operações relacionadas a merchant orders,
 * incluindo criação, atualização, processamento de webhooks, sincronização
 * de status e compatibilidade com API legacy. Mantém isolamento por tenant
 * e integra com o serviço MercadoPago para operações de merchant orders.
 *
 * Funcionalidades implementadas:
 * - Criação e atualização de merchant orders
 * - Processamento de webhooks de merchant orders
 * - Sincronização de status com MercadoPago
 * - Tenant isolation para operações
 * - Compatibilidade com API legacy
 * - Mapeamento de status entre sistemas
 *
 * @author IA - Kilo Code
 *
 * @version 1.0.0
 */
class MerchantOrderMercadoPagoService extends AbstractBaseService {}
