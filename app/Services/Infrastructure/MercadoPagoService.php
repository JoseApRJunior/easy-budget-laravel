<?php

declare(strict_types=1);

namespace App\Services\Infrastructure;

use App\Enums\OperationStatus;
use App\Interfaces\MercadoPagoServiceInterface;
use App\Models\MerchantOrderMercadoPago;
use App\Models\PaymentMercadoPagoInvoice;
use App\Models\PaymentMercadoPagoPlan;
use App\Services\Infrastructure\Abstracts\BaseNoTenantService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/**
 * Serviço para integração com MercadoPago.
 *
 * Responsável por gerenciar pagamentos, processar notificações via webhooks
 * e manter compatibilidade com API legacy. Utiliza HTTP client do Laravel
 * para comunicação com APIs do MercadoPago.
 *
 * Funcionalidades implementadas:
 * - Criação de preferências de pagamento
 * - Processamento de webhooks/notificações
 * - Verificação de status de pagamentos
 * - Tenant isolation para operações de pagamento
 * - Compatibilidade com API legacy
 *
 * @author IA - Kilo Code
 * @version 1.0.0
 */
class MercadoPagoService extends AbstractBaseService
{

}
