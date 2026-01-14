<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\DTOs\Payment\PaymentConfirmDTO;
use App\DTOs\Payment\PaymentDTO;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Repositories\InvoiceRepository;
use App\Repositories\PaymentRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service para gerenciamento de pagamentos.
 */
class PaymentService extends AbstractBaseService
{
    protected InvoiceRepository $invoiceRepository;

    public function __construct(
        PaymentRepository $paymentRepository,
        InvoiceRepository $invoiceRepository
    ) {
        parent::__construct($paymentRepository);
        $this->invoiceRepository = $invoiceRepository;
    }

    /**
     * Lista pagamentos filtrados.
     */
    public function getFilteredPayments(array $filters = []): ServiceResult
    {
        return $this->safeExecute(function () use ($filters) {
            $perPage = $filters['per_page'] ?? 15;
            $payments = $this->repository->getFilteredPaginated($filters, (int) $perPage);

            return ServiceResult::success($payments, 'Pagamentos filtrados com sucesso');
        });
    }

    /**
     * Processa um novo pagamento usando DTO.
     */
    public function processPayment(PaymentDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($dto) {
            return DB::transaction(function () use ($dto) {
                // Buscar fatura usando o repositório
                $invoice = $this->invoiceRepository->find($dto->invoice_id);
                if (! $invoice) {
                    return ServiceResult::error('Fatura não encontrada');
                }

                // Verificar se fatura pode receber pagamento
                if (! $invoice->status->isPending()) {
                    return ServiceResult::error('Fatura não está disponível para pagamento');
                }

                // Dados do pagamento a partir do DTO
                $payment = $this->repository->createFromDTO($dto);

                // Processar pagamento baseado no método
                $result = match ($dto->method) {
                    Payment::METHOD_PIX => $this->processPixPayment($payment),
                    Payment::METHOD_BOLETO => $this->processBoletoPayment($payment),
                    Payment::METHOD_CREDIT_CARD => $this->processCreditCardPayment($payment, $dto->toArray()),
                    Payment::METHOD_CASH => $this->processCashPayment($payment),
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
    public function confirmPayment(PaymentConfirmDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($dto) {
            return DB::transaction(function () use ($dto) {
                $payment = $this->repository->find($dto->payment_id);
                if (! $payment) {
                    return ServiceResult::error('Pagamento não encontrado');
                }

                // Atualizar status do pagamento
                $payment->update([
                    'status' => PaymentStatus::COMPLETED,
                    'confirmed_at' => now(),
                    'gateway_transaction_id' => $dto->transaction_id,
                    'gateway_response' => $dto->gateway_response,
                    'notes' => $dto->notes ?? $payment->notes,
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

                return ServiceResult::success($payment->fresh(), 'Pagamento confirmado com sucesso');
            });
        });
    }

    /**
     * Processa pagamento via PIX.
     */
    private function processPixPayment(Payment $payment): ServiceResult
    {
        return $this->safeExecute(function () use ($payment) {
            $payment->update([
                'status' => PaymentStatus::PROCESSING,
                'processed_at' => now(),
                'gateway_response' => [
                    'method' => 'pix',
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
    private function processBoletoPayment(Payment $payment): ServiceResult
    {
        return $this->safeExecute(function () use ($payment) {
            $payment->update([
                'status' => PaymentStatus::PROCESSING,
                'processed_at' => now(),
                'gateway_response' => [
                    'method' => 'boleto',
                    'barcode' => '12345678901234567890123456789012345678901234',
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
                'status' => PaymentStatus::PROCESSING,
                'processed_at' => now(),
                'gateway_response' => [
                    'method' => 'credit_card',
                    'card_last_digits' => $data['card_last_digits'] ?? '****',
                    'installments' => $data['installments'] ?? 1,
                ],
            ]);

            // Simular aprovação imediata para cartão
            $confirmDTO = new PaymentConfirmDTO(
                payment_id: $payment->id,
                transaction_id: 'CC_'.uniqid(),
                gateway_response: ['status' => 'approved']
            );

            return $this->confirmPayment($confirmDTO);
        }, 'Erro ao processar cartão');
    }

    /**
     * Processa pagamento em dinheiro.
     */
    private function processCashPayment(Payment $payment): ServiceResult
    {
        return $this->safeExecute(function () use ($payment) {
            // Pagamento em dinheiro é confirmado imediatamente
            $confirmDTO = new PaymentConfirmDTO(
                payment_id: $payment->id,
                transaction_id: 'CASH_'.uniqid(),
                gateway_response: ['method' => 'cash']
            );

            return $this->confirmPayment($confirmDTO);
        }, 'Erro ao processar pagamento em dinheiro');
    }

    /**
     * Processa webhook de confirmação de pagamento.
     */
    public function processWebhook(array $webhookData): ServiceResult
    {
        return $this->safeExecute(function () use ($webhookData) {
            $transactionId = $webhookData['transaction_id'] ?? null;
            if (! $transactionId) {
                return ServiceResult::error('Transaction ID não fornecido');
            }

            // Buscar pagamento pelo transaction_id usando o repositório
            $payment = $this->repository->findOneBy(['gateway_transaction_id' => $transactionId]);
            if (! $payment) {
                return ServiceResult::error('Pagamento não encontrado');
            }

            // Processar status do webhook
            $status = $webhookData['status'] ?? 'unknown';

            $confirmDTO = PaymentConfirmDTO::fromRequest(array_merge($webhookData, ['payment_id' => $payment->id]));

            return match ($status) {
                'approved', 'paid' => $this->confirmPayment($confirmDTO),
                'rejected', 'cancelled' => $this->failPayment($payment->id, $webhookData),
                default => ServiceResult::success($payment, 'Webhook processado - status não alterado')
            };
        });
    }

    /**
     * Marca pagamento como falhou.
     */
    public function failPayment(int $paymentId, array $data = []): ServiceResult
    {
        return $this->safeExecute(function () use ($paymentId, $data) {
            $payment = $this->repository->find($paymentId);
            if (! $payment) {
                return ServiceResult::error('Pagamento não encontrado');
            }

            $payment->update([
                'status' => PaymentStatus::FAILED,
                'gateway_response' => $data,
            ]);

            return ServiceResult::success($payment, 'Pagamento marcado como falhou');
        });
    }

    /**
     * Retorna estatísticas de pagamentos.
     */
    public function getPaymentStats(): ServiceResult
    {
        return $this->safeExecute(function () {
            $stats = $this->repository->getStats();

            return ServiceResult::success($stats);
        }, 'Erro ao obter estatísticas de pagamentos.');
    }
}
