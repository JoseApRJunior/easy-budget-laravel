<?php

declare(strict_types=1);

namespace App\Services\Infrastructure;

use App\Enums\OperationStatus;
use App\Interfaces\PaymentMercadoPagoPlanServiceInterface;
use App\Models\PaymentMercadoPagoPlan;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\Log;

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
    public function createMercadoPagoPreference(int $planSubscriptionId): ServiceResult
    {
        try {
            $subscription = \App\Models\PlanSubscription::with(['plan'])
                ->find($planSubscriptionId);
            if (!$subscription || !$subscription->plan) {
                return $this->error(OperationStatus::NOT_FOUND, 'Assinatura do plano não encontrada');
            }

            $credential = \App\Models\ProviderCredential::where('tenant_id', $subscription->tenant_id)
                ->where('payment_gateway', 'mercadopago')
                ->first();
            if (!$credential) {
                return $this->error(OperationStatus::NOT_FOUND, 'Credenciais do provider não configuradas');
            }

            $encryption = app(EncryptionService::class);
            $tokenResult = $encryption->decryptStringLaravel($credential->access_token_encrypted);
            if (!$tokenResult->isSuccess()) {
                return $this->error(OperationStatus::ERROR, 'Falha ao descriptografar access token');
            }
            $accessToken = (string)($tokenResult->getData()['decrypted'] ?? '');
            if (empty($accessToken)) {
                return $this->error(OperationStatus::ERROR, 'Access token vazio');
            }

            $client = new \MercadoPago\Client\Preference\PreferenceClient($accessToken);
            $request = [
                'items' => [
                    [
                        'title' => 'Assinatura do plano ' . $subscription->plan->name,
                        'quantity' => 1,
                        'unit_price' => (float)$subscription->plan->price,
                    ],
                ],
                'external_reference' => 'plan:slug:' . $subscription->plan->slug . ':plan_subscription_id:' . $subscription->id . ':tenant:' . $subscription->tenant_id,
                'notification_url' => route('webhooks.mercadopago.plans'),
            ];

            $preference = $client->create($request);
            $initPoint = method_exists($preference, 'getInitPoint') ? $preference->getInitPoint() : ($preference->init_point ?? null);

            if (!$initPoint) {
                return $this->error(OperationStatus::ERROR, 'Falha ao criar preferência de pagamento');
            }

            return $this->success([
                'init_point' => $initPoint,
                'preference_id' => $preference->id ?? null,
            ], 'Preferência de pagamento criada');
        } catch ( Exception $e ) {
            Log::error('create_mp_plan_preference_error', ['plan_subscription_id' => $planSubscriptionId, 'error' => $e->getMessage()]);
            return $this->error(OperationStatus::ERROR, 'Erro ao criar preferência de pagamento', null, $e);
        }
    }
}
