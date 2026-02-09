<?php

declare(strict_types=1);

namespace App\Services\Infrastructure;

use App\Enums\OperationStatus;
use App\Models\Invoice;
use App\Models\ProviderCredential;
use App\Services\Core\Abstracts\BaseNoTenantService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Serviço especializado para processamento de pagamentos de faturas via MercadoPago.
 */
class PaymentMercadoPagoInvoiceService extends BaseNoTenantService
{
    public function __construct(
        private MercadoPagoService $mercadoPagoService,
        private EncryptionService $encryptionService
    ) {}

    public function createMercadoPagoPreference(string $invoiceCode): ServiceResult
    {
        try {
            $invoice = Invoice::where('code', $invoiceCode)
                ->with(['customer.contact', 'service'])
                ->first();

            if (! $invoice) {
                return ServiceResult::error(OperationStatus::NOT_FOUND, 'Fatura não encontrada');
            }

            // Tentar recuperar do cache para evitar múltiplas preferências para a mesma fatura
            $cacheKey = 'mp_preference_'.$invoice->code;
            if (Cache::has($cacheKey)) {
                return ServiceResult::success(Cache::get($cacheKey), 'Preferência de pagamento recuperada do cache');
            }

            $credential = ProviderCredential::where('tenant_id', $invoice->tenant_id)
                ->where('payment_gateway', 'mercadopago')
                ->first();

            if (! $credential) {
                return ServiceResult::error(OperationStatus::NOT_FOUND, 'Credenciais do provider não configuradas');
            }

            $tokenResult = $this->encryptionService->decryptStringLaravel($credential->access_token_encrypted);
            if (! $tokenResult->isSuccess()) {
                return ServiceResult::error(OperationStatus::ERROR, 'Falha ao descriptografar access token');
            }
            $accessToken = (string) ($tokenResult->getData()['decrypted'] ?? '');

            if (empty($accessToken)) {
                return ServiceResult::error(OperationStatus::ERROR, 'Access token vazio');
            }

            $publicUrl = $invoice->getPublicUrl() ?? route('services.public.invoices.public.show', ['hash' => 'unknown']);

            // Construir dados da preferência
            $preferenceData = [
                'items' => [
                    [
                        'title' => 'Fatura '.$invoice->code,
                        'quantity' => 1,
                        'currency_id' => 'BRL',
                        'unit_price' => (float) $invoice->total,
                    ],
                ],
                'external_reference' => 'invoice:'.$invoice->code.':tenant:'.$invoice->tenant_id,
                'payer' => [
                    'email' => $invoice->customer?->contact?->email_personal
                            ?? $invoice->customer?->contact?->email_business
                            ?? 'customer@example.com', // MP requer email
                ],
                'notification_url' => route('webhooks.mercadopago.invoices'),
                'back_urls' => [
                    'success' => $publicUrl,
                    'failure' => $publicUrl,
                    'pending' => $publicUrl,
                ],
                'auto_return' => 'approved',
                'binary_mode' => true, // Pagamento ou é aprovado ou rejeitado, sem meio termo
                'expires' => true,
                'date_of_expiration' => now()->addHours(24)->format('Y-m-d\TH:i:s.vP'),
                'payment_methods' => [
                    'installments' => 1, // Limita a 1 parcela para evitar múltiplas tentativas de parcelamento
                ],
            ];

            $result = $this->mercadoPagoService->createPreference($accessToken, $preferenceData);

            if (! $result->isSuccess()) {
                return $result;
            }

            $data = $result->getData();
            $initPoint = $data['init_point'] ?? null; // Link de pagamento (Checkout Pro)

            if (! $initPoint) {
                return ServiceResult::error(OperationStatus::ERROR, 'Link de pagamento não retornado pelo MP');
            }

            $resultData = [
                'init_point' => $initPoint,
                'preference_id' => $data['id'] ?? null,
            ];

            // Salvar no cache por 24 horas para reuso
            Cache::put($cacheKey, $resultData, now()->addHours(24));

            return ServiceResult::success($resultData, 'Preferência de pagamento criada');

        } catch (Exception $e) {
            Log::error('create_mp_preference_error', ['invoice' => $invoiceCode, 'error' => $e->getMessage()]);

            return ServiceResult::error(OperationStatus::ERROR, 'Erro ao criar preferência de pagamento', null, $e);
        }
    }
}
