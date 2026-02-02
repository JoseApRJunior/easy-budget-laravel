<?php

declare(strict_types=1);

namespace App\Services\Infrastructure;

use App\Enums\OperationStatus;
use App\Models\Invoice;
use App\Models\ProviderCredential;
use App\Services\Infrastructure\Abstracts\BaseNoTenantService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\Log;

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

            return ServiceResult::success([
                'init_point' => $initPoint,
                'preference_id' => $data['id'] ?? null,
            ], 'Preferência de pagamento criada');

        } catch (Exception $e) {
            Log::error('create_mp_preference_error', ['invoice' => $invoiceCode, 'error' => $e->getMessage()]);

            return ServiceResult::error(OperationStatus::ERROR, 'Erro ao criar preferência de pagamento', null, $e);
        }
    }
}
