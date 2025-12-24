<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\InvoiceStatus;
use App\Enums\OperationStatus;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Repositories\PaymentRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use App\DTOs\Payment\PaymentDTO;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service para gerenciamento de pagamentos.
 */
class PaymentService extends AbstractBaseService
{
    public function __construct(PaymentRepository $paymentRepository)
    {
        parent::__construct($paymentRepository);
    }

    /**
     * Lista pagamentos filtrados.
     */
    public function getFilteredPayments(array $filters = []): ServiceResult
    {
        return $this->safeExecute(function () use ($filters) {
            $tenantId = $this->ensureTenantId();
            $payments = $this->repository->getFilteredPayments($tenantId, $filters);
            return ServiceResult::success($payments);
        });
    }

    /**
     * Processa um novo pagamento usando DTO.
     */
    public function processPayment(PaymentDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($dto) {
            $tenantId = $this->ensureTenantId($dto->tenant_id);

            return DB::transaction(function () use ($dto, $tenantId) {
                // Buscar fatura
                $invoice = Invoice::find($dto->invoice_id);
                if (!$invoice) {
                    return ServiceResult::error('Fatura não encontrada');
                }

                // Verificar se fatura pode receber pagamento
                if (!$invoice->status->isPending()) {
                    return ServiceResult::error('Fatura não está disponível para pagamento');
                }

                // Dados do pagamento a partir do DTO
                $paymentData = $dto->toArray();
                $paymentData['tenant_id'] = $tenantId;
                $paymentData['status'] = PaymentStatus::PENDING;

                // Criar registro de pagamento via repositório
                $payment = $this->repository->create($paymentData);

                // Processar pagamento baseado no método
                $result = match ($dto->method) {
                    Payment::METHOD_PIX => $this->processPixPayment($payment, $paymentData),
                    Payment::METHOD_BOLETO => $this->processBoletoPayment($payment, $paymentData),
                    Payment::METHOD_CREDIT_CARD => $this->processCreditCardPayment($payment, $paymentData),
                    Payment::METHOD_CASH => $this->processCashPayment($payment, $paymentData),
                    default => ServiceResult::error('Método de pagamento não suportado')
                };

                if ($result->isError()) {
                    return $result;
                }

                return ServiceResult::success($payment->fresh(), 'Pagamento processado com sucesso');
            });
        });
    }

    /**
     * Confirma um pagamento e atualiza a fatura.
     */
    public function confirmPayment(int $paymentId, array $data = []): ServiceResult
    {
        return $this->safeExecute(function () use ($paymentId, $data) {
            return DB::transaction(function () use ($paymentId, $data) {
                $payment = $this->repository->find($paymentId);
                if (!$payment) {
                    return ServiceResult::error('Pagamento não encontrado');
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
                    'amount'     => $payment->amount,
                ]);

                return ServiceResult::success($payment->fresh(), 'Pagamento confirmado com sucesso');
            });
        });
    }

    /**
     * Processa pagamento via PIX.
     */
    private function processPixPayment(Payment $payment, array $data): ServiceResult
    {
        return $this->safeExecute(function () use ($payment) {
            $payment->update([
                'status'           => PaymentStatus::PROCESSING,
                'processed_at'     => now(),
                'gateway_response' => [
                    'method'  => 'pix',
                    'qr_code' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
                    'pix_key' => config('payment.pix_key', 'pix@empresa.com'),
                ],
            ]);

            return ServiceResult::success($payment, 'PIX gerado com sucesso');
        }, 'Erro ao processar PIX');
    }

    /**
     * Processa pagamento via boleto.
     */
    private function processBoletoPayment(Payment $payment, array $data): ServiceResult
    {
        return $this->safeExecute(function () use ($payment) {
            $payment->update([
                'status'           => PaymentStatus::PROCESSING,
                'processed_at'     => now(),
                'gateway_response' => [
                    'method'   => 'boleto',
                    'barcode'  => '12345678901234567890123456789012345678901234',
                    'due_date' => $payment->invoice->due_date->format('Y-m-d'),
                ],
            ]);

            return ServiceResult::success($payment, 'Boleto gerado com sucesso');
        }, 'Erro ao gerar boleto');
    }

    /**
     * Processa pagamento via cartão de crédito.
     */
    private function processCreditCardPayment(Payment $payment, array $data): ServiceResult
    {
        return $this->safeExecute(function () use ($payment, $data) {
            $payment->update([
                'status'           => PaymentStatus::PROCESSING,
                'processed_at'     => now(),
                'gateway_response' => [
                    'method'           => 'credit_card',
                    'card_last_digits' => $data['card_last_digits'] ?? '****',
                    'installments'     => $data['installments'] ?? 1,
                ],
            ]);

            // Simular aprovação imediata para cartão
            return $this->confirmPayment($payment->id, [
                'transaction_id'   => 'CC_' . uniqid(),
                'gateway_response' => ['status' => 'approved'],
            ]);
        }, 'Erro ao processar cartão');
    }

    /**
     * Processa pagamento em dinheiro.
     */
    private function processCashPayment(Payment $payment, array $data): ServiceResult
    {
        return $this->safeExecute(function () use ($payment) {
            // Pagamento em dinheiro é confirmado imediatamente
            return $this->confirmPayment($payment->id, [
                'transaction_id'   => 'CASH_' . uniqid(),
                'gateway_response' => ['method' => 'cash'],
            ]);
        }, 'Erro ao processar pagamento em dinheiro');
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
    public function getPaymentStats(): ServiceResult
    {
        return $this->safeExecute(function () {
            $tenantId = $this->ensureTenantId();

            $total     = Payment::where('tenant_id', $tenantId)->count();
            $completed = Payment::where('tenant_id', $tenantId)->where('status', PaymentStatus::COMPLETED)->count();
            $pending   = Payment::where('tenant_id', $tenantId)->where('status', PaymentStatus::PENDING)->count();
            $failed    = Payment::where('tenant_id', $tenantId)->where('status', PaymentStatus::FAILED)->count();

            $totalAmount = Payment::where('tenant_id', $tenantId)
                ->where('status', PaymentStatus::COMPLETED)
                ->sum('amount');

            $stats = [
                'total_payments'     => $total,
                'completed_payments' => $completed,
                'pending_payments'   => $pending,
                'failed_payments'    => $failed,
                'total_amount'       => $totalAmount,
                'success_rate'       => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
            ];

            return ServiceResult::success($stats);
        }, 'Erro ao obter estatísticas de pagamentos.');
    }
}
