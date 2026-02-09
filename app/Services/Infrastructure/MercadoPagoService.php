<?php

declare(strict_types=1);

namespace App\Services\Infrastructure;

use App\Enums\OperationStatus;
use App\Services\Core\Abstracts\BaseNoTenantService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Serviço para integração com MercadoPago.
 *
 * Responsável por gerenciar pagamentos, processar notificações via webhooks
 * e criar preferências de pagamento.
 */
class MercadoPagoService extends BaseNoTenantService
{
    private string $baseUrl = 'https://api.mercadopago.com';

    /**
     * Cria uma preferência de pagamento no Mercado Pago.
     *
     * @param  string  $accessToken  Token de acesso do Vendedor (Provider)
     * @param  array  $preferenceData  Dados da preferência (items, payer, back_urls, etc.)
     */
    public function createPreference(string $accessToken, array $preferenceData): ServiceResult
    {
        try {
            $response = Http::withToken($accessToken)
                ->post("{$this->baseUrl}/checkout/preferences", $preferenceData);

            if (! $response->successful()) {
                Log::error('Erro ao criar preferência MP', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'data' => $preferenceData,
                ]);

                return ServiceResult::error(
                    OperationStatus::ERROR,
                    'Erro ao criar preferência de pagamento: '.($response->json()['message'] ?? 'Erro desconhecido')
                );
            }

            return ServiceResult::success($response->json(), 'Preferência criada com sucesso.');
        } catch (Exception $e) {
            Log::error('Exceção ao criar preferência MP', ['error' => $e->getMessage()]);

            return ServiceResult::error(OperationStatus::ERROR, 'Erro interno ao comunicar com Mercado Pago.');
        }
    }

    /**
     * Busca informações de um pagamento pelo ID.
     */
    public function getPayment(string $accessToken, string $paymentId): ServiceResult
    {
        try {
            $response = Http::withToken($accessToken)
                ->get("{$this->baseUrl}/v1/payments/{$paymentId}");

            if (! $response->successful()) {
                return ServiceResult::error(
                    OperationStatus::ERROR,
                    'Erro ao buscar pagamento: '.($response->json()['message'] ?? 'Erro desconhecido')
                );
            }

            return ServiceResult::success($response->json());
        } catch (Exception $e) {
            return ServiceResult::error(OperationStatus::ERROR, 'Erro interno ao buscar pagamento: '.$e->getMessage());
        }
    }

    /**
     * Busca informações de uma Merchant Order.
     */
    public function getMerchantOrder(string $accessToken, string $merchantOrderId): ServiceResult
    {
        try {
            $response = Http::withToken($accessToken)
                ->get("{$this->baseUrl}/merchant_orders/{$merchantOrderId}");

            if (! $response->successful()) {
                return ServiceResult::error(
                    OperationStatus::ERROR,
                    'Erro ao buscar merchant order: '.($response->json()['message'] ?? 'Erro desconhecido')
                );
            }

            return ServiceResult::success($response->json());
        } catch (Exception $e) {
            return ServiceResult::error(OperationStatus::ERROR, 'Erro interno ao buscar merchant order: '.$e->getMessage());
        }
    }

    /**
     * Processa webhook de notificação.
     *
     * @param  array  $data  Dados do webhook
     * @param  string  $accessToken  Token de acesso do Vendedor
     */
    public function processWebhook(array $data, string $accessToken): ServiceResult
    {
        // Implementação básica para identificar o tipo de notificação
        $topic = $data['topic'] ?? $data['type'] ?? null;
        $id = $data['data']['id'] ?? $data['id'] ?? null;

        if (! $topic || ! $id) {
            return ServiceResult::error(OperationStatus::INVALID_DATA, 'Dados de webhook inválidos.');
        }

        if ($topic === 'payment') {
            return $this->getPayment($accessToken, (string) $id);
        } elseif ($topic === 'merchant_order') {
            return $this->getMerchantOrder($accessToken, (string) $id);
        }

        return ServiceResult::success(null, 'Tópico ignorado: '.$topic);
    }
}
