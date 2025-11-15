<?php

declare(strict_types=1);

namespace App\Services\Infrastructure;

use App\Enums\OperationStatus;
use App\Interfaces\PaymentMercadoPagoInvoiceServiceInterface;
use App\Models\Invoice;
use App\Models\ProviderCredential;
use App\Models\PaymentMercadoPagoInvoice;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Services\Infrastructure\Abstracts\BaseNoTenantService;
use App\Services\Infrastructure\EncryptionService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MercadoPago\Client\Preference\PreferenceClient;

/**
 * Serviço especializado para processamento de pagamentos de faturas via MercadoPago.
 *
 * Este service é responsável por gerenciar todo o ciclo de vida dos pagamentos
 * de faturas através da integração com MercadoPago, incluindo:
 * - Criação de preferências de pagamento específicas para faturas
 * - Processamento de webhooks relacionados a pagamentos de faturas
 * - Verificação de status de pagamentos de faturas
 * - Cancelamento e reembolso de pagamentos de faturas
 * - Manutenção do isolamento por tenant
 *
 * Utiliza o MercadoPagoService para comunicação com APIs do MercadoPago
 * e mantém compatibilidade com a arquitetura de multi-tenancy do sistema.
 *
 * @author IA - Kilo Code
 * @version 1.0.0
 */
class PaymentMercadoPagoInvoiceService extends AbstractBaseService
{
    public function createMercadoPagoPreference(string $invoiceCode): ServiceResult
    {
        try {
            $invoice = Invoice::where('code', $invoiceCode)
                ->with(['customer','service'])
                ->first();
            if (!$invoice) {
                return $this->error(OperationStatus::NOT_FOUND, 'Fatura não encontrada');
            }

            $credential = ProviderCredential::where('tenant_id', $invoice->tenant_id)
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

            $client = new PreferenceClient($accessToken);
            $request = [
                'items' => [
                    [
                        'title' => 'Fatura ' . $invoice->code,
                        'quantity' => 1,
                        'unit_price' => (float)$invoice->total,
                    ],
                ],
                'external_reference' => 'invoice:' . $invoice->code . ':tenant:' . $invoice->tenant_id,
                'payer' => [
                    'email' => $invoice->customer?->contact?->email_personal ?? $invoice->customer?->contact?->email_business ?? null,
                ],
                'notification_url' => route('webhooks.mercadopago.invoices'),
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
            Log::error('create_mp_preference_error', ['invoice' => $invoiceCode, 'error' => $e->getMessage()]);
            return $this->error(OperationStatus::ERROR, 'Erro ao criar preferência de pagamento', null, $e);
        }
    }
}
