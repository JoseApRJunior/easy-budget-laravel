<?php

declare(strict_types=1);

namespace App\Services\Infrastructure;

use App\Enums\OperationStatus;
use App\Services\Infrastructure\Abstracts\BaseNoTenantService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Serviço especializado para processamento de pagamentos de planos via MercadoPago.
 *
 * Esta classe gerencia todas as operações relacionadas a pagamentos de planos,
 * incluindo criação de preferências. Mantém isolamento por
 * tenant e integra com o serviço MercadoPago para operações financeiras.
 */
class PaymentMercadoPagoPlanService extends BaseNoTenantService
{
    public function __construct(
        private MercadoPagoService $mercadoPagoService
    ) {}

    public function createMercadoPagoPreference(int $planSubscriptionId): ServiceResult
    {
        try {
            $subscription = \App\Models\PlanSubscription::with(['plan'])
                ->find($planSubscriptionId);

            if (! $subscription || ! $subscription->plan) {
                return ServiceResult::error(OperationStatus::NOT_FOUND, 'Assinatura do plano não encontrada');
            }

            // Para pagamento de Planos, o dinheiro vai para a PLATAFORMA (Easy Budget).
            // Usamos o Token da Plataforma definido no .env/config
            $accessToken = config('services.mercadopago.access_token');

            if (empty($accessToken)) {
                return ServiceResult::error(OperationStatus::ERROR, 'Configuração de pagamento da plataforma ausente (Token MP).');
            }

            // Construir dados da preferência
            $preferenceData = [
                'items' => [
                    [
                        'title' => 'Assinatura do plano '.$subscription->plan->name,
                        'quantity' => 1,
                        'currency_id' => 'BRL',
                        'unit_price' => (float) $subscription->plan->price,
                    ],
                ],
                'external_reference' => 'plan:slug:'.$subscription->plan->slug.':plan_subscription_id:'.$subscription->id.':tenant:'.$subscription->tenant_id,
                'payer' => [
                    // Opcional: Adicionar dados do payer se disponíveis no user do tenant
                    'email' => auth()->user()->email ?? 'provider@example.com',
                ],
                'notification_url' => route('webhooks.mercadopago.plans'),
                'back_urls' => [
                    'success' => route('plans.status', $subscription->plan->slug),
                    'failure' => route('plans.status', $subscription->plan->slug),
                    'pending' => route('plans.status', $subscription->plan->slug),
                ],
                'auto_return' => 'approved',
            ];

            $result = $this->mercadoPagoService->createPreference($accessToken, $preferenceData);

            if (! $result->isSuccess()) {
                return $result;
            }

            $data = $result->getData();
            $initPoint = $data['init_point'] ?? null;

            if (! $initPoint) {
                return ServiceResult::error(OperationStatus::ERROR, 'Falha ao obter link de pagamento do Mercado Pago.');
            }

            return ServiceResult::success([
                'init_point' => $initPoint,
                'preference_id' => $data['id'] ?? null,
            ], 'Preferência de pagamento criada');

        } catch (Exception $e) {
            Log::error('create_mp_plan_preference_error', ['plan_subscription_id' => $planSubscriptionId, 'error' => $e->getMessage()]);

            return ServiceResult::error(OperationStatus::ERROR, 'Erro ao criar preferência de pagamento', null, $e);
        }
    }
}
