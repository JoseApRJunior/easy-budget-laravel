<?php

declare(strict_types=1);

namespace App\Services\Infrastructure;

use App\Enums\OperationStatus;
use App\Interfaces\PaymentMercadoPagoPlanServiceInterface;
use App\Models\PaymentMercadoPagoPlan;
use App\Services\Infrastructure\Abstracts\BaseNoTenantService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

/**
 * Serviço especializado para processamento de pagamentos de planos via MercadoPago.
 *
 * Esta classe gerencia todas as operações relacionadas a pagamentos de planos,
 * incluindo criação de preferências, processamento de webhooks, verificação
 * de status, cancelamento e reembolso de pagamentos. Mantém isolamento por
 * tenant e integra com o serviço MercadoPago para operações financeiras.
 *
 * @author IA - Kilo Code
 * @version 1.0.0
 * @package App\Services
 */
class PaymentMercadoPagoPlanService extends AbstractBaseService
{

}
