<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\InvoiceStatus;
use App\Enums\OperationStatus;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service para gerenciamento de pagamentos.
 */
class PaymentService extends AbstractBaseService
{
    /**
     * Processa um novo pagamento.
     */
    public function processPayment(array $data): ServiceResult
    {
        try {
            return DB::transaction(function () use ($data) {
                // Buscar fatura
                $invoice = Invoice::find($data['invoice_id']);
                if (!$invoice) {
                    return $this->error(
                        OperationStatus::NOT_FOUND,
                        'Fatura não encontrada'
                    );
                }

                // Verificar se fatura pode receber pagamento
                if (!$invoice->status->isPending()) {
                    return $this->error(
                        OperationStatus::VALIDATION_ERROR,
                        'Fatura não está disponível para pagamento'
                    );
                }

                // Criar registro de pagamento
                $payment = Payment::create([
                    'tenant_id' => $invoice->tenant_id,
                    'invoice_id' => $invoice->id,
                    'customer_id' => $invoice->customer_id,
                    'status' => PaymentStatus::PENDING,
                    'method' => $data['method'],
                    'amount' => $data['amount'],
                    'notes' => $data['notes'] ?? null,
                ]);

                // Processar pagamento baseado no método
                $result = match ($data['method']) {
                    Payment::METHOD_PIX => $this->processPixPayment($payment, $data),
                    Payment::METHOD_BOLETO => $this->processBoletoPayment($payment, $data),
                    Payment::METHOD_CREDIT_CARD => $this->processCreditCardPayment($payment, $data),
                    Payment::METHOD_CASH => $this->processCashPayment($payment, $data),
                    default => $this->error(
                        OperationStatus::VALIDATION_ERROR,
                        'Método de pagamento não suportado'
                    )
                };

                if (!$result->isSuccess()) {
                    return $result;
                }

                return $this->success($payment->fresh(), 'Pagamento processado com sucesso');
            });
        } catch (Exception $e) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao processar pagamento',
                null,
                $e
            );
        }
    }

    /**
     * Confirma um pagamento e atualiza a fatura.
     */
    public function confirmPayment(int $paymentId, array $data = []): ServiceResult
    {
        try {
            return DB::transaction(function () use ($paymentId, $data) {
                $payment = Payment::find($paymentId);
                if (!$payment) {
                    return $this->error(
                        OperationStatus::NOT_FOUND,
                        'Pagamento não encontrado'
                    );
                }

                // Atualizar status do pagamento
                $payment->update([
                    'status' => PaymentStatus::COMPLETED,
                    'confirmed_at' => now(),
                    'gateway_transaction_id' => $data['transaction_id'] ?? null,
                    'gateway_response' => $data['gateway_response'] ?? null,
                ]);

                // Atualizar status da fatura
                $invoice = $payment->invoice;
                $invoice->update([
                    'status' => InvoiceStatus::PAID,
                    'transaction_amount' => $payment->amount,
                    'transaction_date' => now(),
                    'payment_method' => $payment->method,
                    'payment_id' => $payment->id,
                ]);

                Log::info('Pagamento confirmado', [
                    'payment_id' => $payment->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $payment->amount,
                ]);

                return $this->success($payment->fresh(), 'Pagamento confirmado com sucesso');
            });
        } catch (Exception $e) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao confirmar pagamento',
                null,
                $e
            );
        }
    }

    /**
     * Processa pagamento via PIX.
     */
    private function processPixPayment(Payment $payment, array $data): ServiceResult
    {
        try {
            // Aqui seria integrado com o gateway de pagamento (Mercado Pago, etc.)
            // Por enquanto, simular processamento
            
            $payment->update([
                'status' => PaymentStatus::PROCESSING,
                'processed_at' => now(),
                'gateway_response' => [
                    'method' => 'pix',
                    'qr_code' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
                    'pix_key' => config('payment.pix_key', 'pix@empresa.com'),
                ],
            ]);

            return $this->success($payment, 'PIX gerado com sucesso');
        } catch (Exception $e) {
            $payment->update(['status' => PaymentStatus::FAILED]);
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao processar PIX',
                null,
                $e
            );
        }
    }

    /**
     * Processa pagamento via boleto.
     */
    private function processBoletoPayment(Payment $payment, array $data): ServiceResult
    {
        try {
            // Integração com gateway para gerar boleto
            $payment->update([
                'status' => PaymentStatus::PROCESSING,
                'processed_at' => now(),
                'gateway_response' => [
                    'method' => 'boleto',
                    'barcode' => '12345678901234567890123456789012345678901234',
                    'due_date' => $payment->invoice->due_date->format('Y-m-d'),
                ],
            ]);

            return $this->success($payment, 'Boleto gerado com sucesso');
        } catch (Exception $e) {
            $payment->update(['status' => PaymentStatus::FAILED]);
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao gerar boleto',
                null,
                $e
            );
        }
    }

    /**
     * Processa pagamento via cartão de crédito.
     */
    private function processCreditCardPayment(Payment $payment, array $data): ServiceResult
    {
        try {
            // Integração com gateway para processar cartão
            $payment->update([
                'status' => PaymentStatus::PROCESSING,
                'processed_at' => now(),
                'gateway_response' => [
                    'method' => 'credit_card',
                    'card_last_digits' => $data['card_last_digits'] ?? '****',
                    'installments' => $data['installments'] ?? 1,
                ],
            ]);

            // Simular aprovação imediata para cartão
            return $this->confirmPayment($payment->id, [
                'transaction_id' => 'CC_' . uniqid(),
                'gateway_response' => ['status' => 'approved'],
            ]);
        } catch (Exception $e) {
            $payment->update(['status' => PaymentStatus::FAILED]);
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao processar cartão',
                null,
                $e
            );
        }
    }

    /**
     * Processa pagamento em dinheiro.
     */
    private function processCashPayment(Payment $payment, array $data): ServiceResult
    {
        try {
            // Pagamento em dinheiro é confirmado imediatamente
            return $this->confirmPayment($payment->id, [
                'transaction_id' => 'CASH_' . uniqid(),
                'gateway_response' => ['method' => 'cash'],
            ]);
        } catch (Exception $e) {
            $payment->update(['status' => PaymentStatus::FAILED]);
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao processar pagamento em dinheiro',
                null,
                $e
            );
        }
    }

    /**
     * Processa webhook de confirmação de pagamento.
     */
    public function processWebhook(array $webhookData): ServiceResult
    {
        try {
            // Validar assinatura do webhook (implementar conforme gateway)
            
            $transactionId = $webhookData['transaction_id'] ?? null;
            if (!$transactionId) {
                return $this->error(
                    OperationStatus::VALIDATION_ERROR,
                    'Transaction ID não fornecido'
                );
            }

            // Buscar pagamento pelo transaction_id
            $payment = Payment::where('gateway_transaction_id', $transactionId)->first();
            if (!$payment) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    'Pagamento não encontrado'
                );
            }

            // Processar status do webhook
            $status = $webhookData['status'] ?? 'unknown';
            
            return match ($status) {
                'approved', 'paid' => $this->confirmPayment($payment->id, $webhookData),
                'rejected', 'cancelled' => $this->failPayment($payment->id, $webhookData),
                default => $this->success($payment, 'Webhook processado - status não alterado')
            };
        } catch (Exception $e) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao processar webhook',
                null,
                $e
            );
        }
    }

    /**
     * Marca pagamento como falhou.
     */
    private function failPayment(int $paymentId, array $data = []): ServiceResult
    {
        try {
            $payment = Payment::find($paymentId);
            if (!$payment) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    'Pagamento não encontrado'
                );
            }

            $payment->update([
                'status' => PaymentStatus::FAILED,
                'gateway_response' => $data,
            ]);

            return $this->success($payment, 'Pagamento marcado como falhou');
        } catch (Exception $e) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao marcar pagamento como falhou',
                null,
                $e
            );
        }
    }

    /**
     * Lista pagamentos com filtros.
     */
    public function getFilteredPayments(array $filters = []): ServiceResult
    {
        try {
            $query = Payment::query()->with(['invoice', 'customer']);

            // Aplicar filtros
            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (!empty($filters['method'])) {
                $query->where('method', $filters['method']);
            }

            if (!empty($filters['customer_id'])) {
                $query->where('customer_id', $filters['customer_id']);
            }

            if (!empty($filters['date_from'])) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            }

            if (!empty($filters['date_to'])) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            }

            $payments = $query->orderBy('created_at', 'desc')->paginate(15);

            return $this->success($payments, 'Pagamentos filtrados');
        } catch (Exception $e) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao filtrar pagamentos',
                null,
                $e
            );
        }
    }

    /**
     * Retorna estatísticas de pagamentos.
     */
    public function getPaymentStats(int $tenantId): array
    {
        try {
            $total = Payment::where('tenant_id', $tenantId)->count();
            $completed = Payment::where('tenant_id', $tenantId)
                ->where('status', PaymentStatus::COMPLETED)->count();
            $pending = Payment::where('tenant_id', $tenantId)
                ->where('status', PaymentStatus::PENDING)->count();
            $failed = Payment::where('tenant_id', $tenantId)
                ->where('status', PaymentStatus::FAILED)->count();

            $totalAmount = Payment::where('tenant_id', $tenantId)
                ->where('status', PaymentStatus::COMPLETED)
                ->sum('amount');

            return [
                'total_payments' => $total,
                'completed_payments' => $completed,
                'pending_payments' => $pending,
                'failed_payments' => $failed,
                'total_amount' => $totalAmount,
                'success_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
            ];
        } catch (Exception $e) {
            Log::error('Erro ao obter estatísticas de pagamentos', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId,
            ]);

            return [
                'total_payments' => 0,
                'completed_payments' => 0,
                'pending_payments' => 0,
                'failed_payments' => 0,
                'total_amount' => 0,
                'success_rate' => 0,
            ];
        }
    }
}