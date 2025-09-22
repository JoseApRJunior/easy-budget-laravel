<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OperationStatus;
use App\Interfaces\PaymentMercadoPagoInvoiceServiceInterface;
use App\Models\Invoice;
use App\Models\PaymentMercadoPagoInvoice;
use App\Services\Abstracts\BaseNoTenantService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
class PaymentMercadoPagoInvoiceService extends BaseNoTenantService implements PaymentMercadoPagoInvoiceServiceInterface
{
    /**
     * Serviço de integração com MercadoPago.
     */
    private MercadoPagoService $mercadoPagoService;

    /**
     * Construtor com injeção de dependências.
     *
     * @param MercadoPagoService $mercadoPagoService Serviço de integração com MercadoPago
     */
    public function __construct( MercadoPagoService $mercadoPagoService )
    {
        $this->mercadoPagoService = $mercadoPagoService;
    }

    /**
     * Cria preferência de pagamento para uma fatura específica.
     *
     * Este método prepara os dados da fatura para criação de uma preferência
     * de pagamento no MercadoPago, incluindo informações específicas do tenant
     * e dados da fatura para rastreamento.
     *
     * @param Invoice $invoice Fatura a ser paga
     * @param int $tenantId ID do tenant proprietário da fatura
     * @param array $additionalData Dados adicionais para o pagamento (URLs de callback, etc.)
     * @return ServiceResult
     */
    public function createPaymentPreference( Invoice $invoice, int $tenantId, array $additionalData = [] ): ServiceResult
    {
        try {
            // Validar fatura
            $validation = $this->validateInvoiceForPayment( $invoice, $tenantId );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            // Preparar dados específicos para fatura
            $paymentData = $this->prepareInvoicePaymentData( $invoice, $tenantId, $additionalData );

            // Criar preferência via MercadoPagoService
            return $this->mercadoPagoService->createPaymentPreference( $paymentData, $tenantId );

        } catch ( Exception $e ) {
            Log::error( 'Exceção ao criar preferência de pagamento para fatura', [
                'invoice_id' => $invoice->id,
                'tenant_id'  => $tenantId,
                'exception'  => $e->getMessage(),
                'trace'      => $e->getTraceAsString()
            ] );

            return $this->error(
                OperationStatus::ERROR,
                'Falha ao criar preferência de pagamento para fatura: ' . $e->getMessage(),
                null,
                $e,
            );
        }
    }

    /**
     * Processa webhook específico para pagamentos de faturas.
     *
     * Este método processa notificações do MercadoPago relacionadas a pagamentos
     * de faturas, atualizando o status da fatura e do pagamento local conforme
     * necessário, mantendo o isolamento por tenant.
     *
     * @param array $webhookData Dados do webhook do MercadoPago
     * @return ServiceResult
     */
    public function processInvoicePaymentWebhook( array $webhookData ): ServiceResult
    {
        try {
            // Processar webhook geral primeiro
            $webhookResult = $this->mercadoPagoService->processWebhook( $webhookData );
            if ( !$webhookResult->isSuccess() ) {
                return $webhookResult;
            }

            // Verificar se é um pagamento de fatura
            if ( !$this->isInvoicePaymentWebhook( $webhookData ) ) {
                return $this->success( null, 'Webhook não relacionado a pagamento de fatura.' );
            }

            // Extrair dados do pagamento
            $paymentId = $webhookData[ 'data' ][ 'id' ] ?? '';
            if ( empty( $paymentId ) ) {
                return $this->error(
                    OperationStatus::INVALID_DATA,
                    'ID do pagamento não informado no webhook de fatura.',
                );
            }

            // Buscar dados do pagamento local
            $localPayment = $this->findLocalInvoicePayment( $paymentId );
            if ( !$localPayment ) {
                Log::warning( 'Pagamento de fatura não encontrado localmente', [
                    'payment_id' => $paymentId
                ] );
                return $this->success( null, 'Pagamento de fatura processado, mas não encontrado localmente.' );
            }

            // Atualizar status da fatura se necessário
            $this->updateInvoiceStatusFromPayment( $localPayment, $webhookData );

            Log::info( 'Webhook de pagamento de fatura processado', [
                'payment_id' => $paymentId,
                'invoice_id' => $localPayment->invoice_id,
                'tenant_id'  => $localPayment->tenant_id,
                'status'     => $webhookData[ 'data' ][ 'status' ] ?? 'unknown'
            ] );

            return $this->success(
                $localPayment,
                'Webhook de pagamento de fatura processado com sucesso.',
            );

        } catch ( Exception $e ) {
            Log::error( 'Exceção ao processar webhook de pagamento de fatura', [
                'webhook_data' => $webhookData,
                'exception'    => $e->getMessage(),
                'trace'        => $e->getTraceAsString()
            ] );

            return $this->error(
                OperationStatus::ERROR,
                'Falha ao processar webhook de pagamento de fatura: ' . $e->getMessage(),
                null,
                $e,
            );
        }
    }

    /**
     * Verifica status de pagamento de uma fatura específica.
     *
     * @param string $paymentId ID do pagamento no MercadoPago
     * @param int $tenantId ID do tenant
     * @return ServiceResult
     */
    public function checkInvoicePaymentStatus( string $paymentId, int $tenantId ): ServiceResult
    {
        try {
            // Verificar se existe pagamento local
            $localPayment = $this->findLocalInvoicePayment( $paymentId, $tenantId );
            if ( $localPayment ) {
                return $this->success( $localPayment, 'Status do pagamento de fatura obtido localmente.' );
            }

            // Consultar status via MercadoPagoService
            $statusResult = $this->mercadoPagoService->checkPaymentStatus( $paymentId, $tenantId );
            if ( !$statusResult->isSuccess() ) {
                return $statusResult;
            }

            $paymentData = $statusResult->getData();

            // Verificar se é um pagamento de fatura
            if ( !$this->isInvoicePayment( $paymentData ) ) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    'Pagamento não é relacionado a uma fatura.',
                );
            }

            // Criar registro local se necessário
            $this->createLocalInvoicePayment($paymentData, $tenantId);

            return $this->success($paymentData, 'Status do pagamento de fatura obtido com sucesso.');
        } catch (Exception $e) {
            Log::error( 'Exceção ao verificar status de pagamento de fatura', [
                'payment_id' => $paymentId,
                'tenant_id'  => $tenantId,
                'exception'  => $e->getMessage()
            ] );

            return $this->error(
                OperationStatus::ERROR,
                'Falha ao verificar status de pagamento de fatura: ' . $e->getMessage(),
                null,
                $e,
            );
        }
    }

    /**
     * Cancela pagamento de uma fatura.
     *
     * @param string $paymentId ID do pagamento
     * @param int $tenantId ID do tenant
     * @return ServiceResult
     */
    public function cancelInvoicePayment( string $paymentId, int $tenantId ): ServiceResult
    {
        try {
            // Verificar se é um pagamento de fatura
            $localPayment = $this->findLocalInvoicePayment( $paymentId, $tenantId );
            if ( !$localPayment ) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    'Pagamento de fatura não encontrado.',
                );
            }

            // Cancelar via MercadoPagoService
            $cancelResult = $this->mercadoPagoService->cancelPayment( $paymentId, $tenantId );
            if ( !$cancelResult->isSuccess() ) {
                return $cancelResult;
            }

            // Atualizar status local
            $this->updateLocalInvoicePaymentStatus( $paymentId, 'cancelled', $tenantId );

            // Atualizar status da fatura
            $this->updateInvoiceStatus( $localPayment->invoice_id, 'canceled', $tenantId );

            return $this->success(
                $cancelResult->getData(),
                'Pagamento de fatura cancelado com sucesso.',
            );

        } catch ( Exception $e ) {
            Log::error( 'Exceção ao cancelar pagamento de fatura', [
                'payment_id' => $paymentId,
                'tenant_id'  => $tenantId,
                'exception'  => $e->getMessage()
            ] );

            return $this->error(
                OperationStatus::ERROR,
                'Falha ao cancelar pagamento de fatura: ' . $e->getMessage(),
                null,
                $e,
            );
        }
    }

    /**
     * Processa reembolso de pagamento de fatura.
     *
     * @param string $paymentId ID do pagamento
     * @param float|null $amount Valor a reembolsar (null para total)
     * @param int $tenantId ID do tenant
     * @return ServiceResult
     */
    public function refundInvoicePayment( string $paymentId, ?float $amount = null, int $tenantId ): ServiceResult
    {
        try {
            // Verificar se é um pagamento de fatura
            $localPayment = $this->findLocalInvoicePayment( $paymentId, $tenantId );
            if ( !$localPayment ) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    'Pagamento de fatura não encontrado.',
                );
            }

            // Processar reembolso via MercadoPagoService
            $refundResult = $this->mercadoPagoService->refundPayment( $paymentId, $amount, $tenantId );
            if ( !$refundResult->isSuccess() ) {
                return $refundResult;
            }

            // Atualizar status local
            $this->updateLocalInvoicePaymentStatus( $paymentId, 'refunded', $tenantId );

            // Atualizar status da fatura
            $this->updateInvoiceStatus( $localPayment->invoice_id, 'pending', $tenantId );

            return $this->success(
                $refundResult->getData(),
                'Reembolso de pagamento de fatura processado com sucesso.',
            );

        } catch ( Exception $e ) {
            Log::error( 'Exceção ao processar reembolso de pagamento de fatura', [
                'payment_id' => $paymentId,
                'tenant_id'  => $tenantId,
                'amount'     => $amount,
                'exception'  => $e->getMessage()
            ] );

            return $this->error(
                OperationStatus::ERROR,
                'Falha ao processar reembolso de pagamento de fatura: ' . $e->getMessage(),
                null,
                $e,
            );
        }
    }

    /**
     * Lista pagamentos de faturas por tenant.
     *
     * @param int $tenantId ID do tenant
     * @param array $filters Filtros opcionais
     * @return ServiceResult
     */
    public function listInvoicePayments( int $tenantId, array $filters = [] ): ServiceResult
    {
        try {
            $query = PaymentMercadoPagoInvoice::where( 'tenant_id', $tenantId );

            // Aplicar filtros
            if ( isset( $filters[ 'status' ] ) ) {
                $query->where( 'status', $filters[ 'status' ] );
            }

            if ( isset( $filters[ 'invoice_id' ] ) ) {
                $query->where( 'invoice_id', $filters[ 'invoice_id' ] );
            }

            if ( isset( $filters[ 'date_from' ] ) ) {
                $query->where( 'transaction_date', '>=', $filters[ 'date_from' ] );
            }

            if ( isset( $filters[ 'date_to' ] ) ) {
                $query->where( 'transaction_date', '<=', $filters[ 'date_to' ] );
            }

            $payments = $query->orderBy( 'created_at', 'desc' )->get();

            return $this->success(
                $payments,
                'Pagamentos de faturas listados com sucesso.',
            );

        } catch ( Exception $e ) {
            Log::error( 'Exceção ao listar pagamentos de faturas', [
                'tenant_id' => $tenantId,
                'filters'   => $filters,
                'exception' => $e->getMessage()
            ] );

            return $this->error(
                OperationStatus::ERROR,
                'Falha ao listar pagamentos de faturas: ' . $e->getMessage(),
                null,
                $e,
            );
        }
    }

    // MÉTODOS PRIVADOS - AUXILIARES

    /**
     * Valida se a fatura pode ser paga.
     *
     * @param Invoice $invoice
     * @param int $tenantId
     * @return ServiceResult
     */
    private function validateInvoiceForPayment( Invoice $invoice, int $tenantId ): ServiceResult
    {
        if ( $invoice->tenant_id !== $tenantId ) {
            return $this->error(
                OperationStatus::FORBIDDEN,
                'Fatura não pertence ao tenant especificado.',
            );
        }

        if ( $invoice->status !== 'pending' ) {
            return $this->error(
                OperationStatus::CONFLICT,
                'Apenas faturas pendentes podem ser pagas.',
            );
        }

        if ( $invoice->amount <= 0 ) {
            return $this->error(
                OperationStatus::INVALID_DATA,
                'Valor da fatura deve ser maior que zero.',
            );
        }

        return $this->success();
    }

    /**
     * Prepara dados de pagamento específicos para fatura.
     *
     * @param Invoice $invoice
     * @param int $tenantId
     * @param array $additionalData
     * @return array
     */
    private function prepareInvoicePaymentData( Invoice $invoice, int $tenantId, array $additionalData ): array
    {
        return [
            'id'                 => "invoice_{$invoice->id}",
            'title'              => "Pagamento de Fatura #{$invoice->id}",
            'description'        => "Pagamento referente à fatura #{$invoice->id}",
            'amount'             => $invoice->amount,
            'currency'           => 'BRL',
            'type'               => 'invoice',
            'external_reference' => "invoice_{$invoice->id}_tenant_{$tenantId}",
            'notification_url'   => $additionalData[ 'notification_url' ] ?? null,
            'success_url'        => $additionalData[ 'success_url' ] ?? null,
            'failure_url'        => $additionalData[ 'failure_url' ] ?? null,
            'pending_url'        => $additionalData[ 'pending_url' ] ?? null,
            'auto_return'        => $additionalData[ 'auto_return' ] ?? 'approved',
            'installments'       => $additionalData[ 'installments' ] ?? 1,
        ];
    }

    /**
     * Verifica se o webhook é relacionado a pagamento de fatura.
     *
     * @param array $webhookData
     * @return bool
     */
    private function isInvoicePaymentWebhook( array $webhookData ): bool
    {
        $paymentId = $webhookData[ 'data' ][ 'id' ] ?? '';

        if ( empty( $paymentId ) ) {
            return false;
        }

        // Buscar dados do pagamento para verificar metadados
        try {
            $statusResult = $this->mercadoPagoService->checkPaymentStatus( $paymentId, 0 );
            if ( !$statusResult->isSuccess() ) {
                return false;
            }

            $paymentData = $statusResult->getData();
            return ( $paymentData[ 'metadata' ][ 'type' ] ?? '' ) === 'invoice';

        } catch ( Exception $e ) {
            Log::warning( 'Erro ao verificar tipo de pagamento no webhook', [
                'payment_id' => $paymentId,
                'exception'  => $e->getMessage()
            ] );
            return false;
        }
    }

    /**
     * Verifica se o pagamento é relacionado a uma fatura.
     *
     * @param array $paymentData
     * @return bool
     */
    private function isInvoicePayment( array $paymentData ): bool
    {
        return ( $paymentData[ 'metadata' ][ 'type' ] ?? '' ) === 'invoice';
    }

    /**
     * Busca pagamento de fatura local.
     *
     * @param string $paymentId
     * @param int|null $tenantId
     * @return PaymentMercadoPagoInvoice|null
     */
    private function findLocalInvoicePayment( string $paymentId, ?int $tenantId = null ): ?PaymentMercadoPagoInvoice
    {
        $query = PaymentMercadoPagoInvoice::where( 'payment_id', $paymentId );

        if ( $tenantId ) {
            $query->where( 'tenant_id', $tenantId );
        }

        return $query->first();
    }

    /**
     * Cria registro local de pagamento de fatura.
     *
     * @param array $paymentData
     * @param int $tenantId
     * @return void
     */
    private function createLocalInvoicePayment( array $paymentData, int $tenantId ): void
    {
        $paymentId = $paymentData[ 'id' ];
        $invoiceId = $this->extractInvoiceIdFromPayment( $paymentData );

        PaymentMercadoPagoInvoice::updateOrCreate(
            [ 'payment_id' => $paymentId, 'tenant_id' => $tenantId ],
            [
                'invoice_id'         => $invoiceId,
                'status'             => $paymentData[ 'status' ],
                'payment_method'     => $paymentData[ 'payment_method_id' ] ?? null,
                'transaction_amount' => $paymentData[ 'transaction_amount' ] ?? 0,
                'transaction_date'   => $paymentData[ 'date_created' ] ?? \now(),
            ],
        );
    }

    /**
     * Atualiza status de pagamento de fatura local.
     *
     * @param string $paymentId
     * @param string $status
     * @param int $tenantId
     * @return void
     */
    private function updateLocalInvoicePaymentStatus( string $paymentId, string $status, int $tenantId ): void
    {
        PaymentMercadoPagoInvoice::where( 'payment_id', $paymentId )
            ->where( 'tenant_id', $tenantId )
            ->update( [ 'status' => $status ] );
    }

    /**
     * Atualiza status da fatura baseado no pagamento.
     *
     * @param PaymentMercadoPagoInvoice $payment
     * @param array $webhookData
     * @return void
     */
    private function updateInvoiceStatusFromPayment( PaymentMercadoPagoInvoice $payment, array $webhookData ): void
    {
        $newStatus = match ( $webhookData[ 'data' ][ 'status' ] ?? '' ) {
            'approved'  => 'paid',
            'cancelled' => 'canceled',
            'rejected'  => 'canceled',
            default     => null
        };

        if ( $newStatus && $payment->invoice ) {
            $payment->invoice->update( [ 'status' => $newStatus ] );
        }
    }

    /**
     * Atualiza status de uma fatura específica.
     *
     * @param int $invoiceId
     * @param string $status
     * @param int $tenantId
     * @return void
     */
    private function updateInvoiceStatus( int $invoiceId, string $status, int $tenantId ): void
    {
        Invoice::where( 'id', $invoiceId )
            ->where( 'tenant_id', $tenantId )
            ->update( [ 'status' => $status ] );
    }

    /**
     * Extrai ID da fatura dos dados do pagamento.
     *
     * @param array $paymentData
     * @return int|null
     */
    private function extractInvoiceIdFromPayment( array $paymentData ): ?int
    {
        $externalReference = $paymentData[ 'external_reference' ] ?? '';

        if ( preg_match( '/invoice_(\d+)_tenant_(\d+)/', $externalReference, $matches ) ) {
            return (int) $matches[ 1 ];
        }

        return null;
    }

    // MÉTODOS ABSTRATOS DA BaseNoTenantService

    /**
     * {@inheritdoc}
     */
    protected function findEntityById( int $id ): ?Model
    {
        // Não utilizado para este service
        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function listEntities( ?array $orderBy = null, ?int $limit = null ): array
    {
        // Não utilizado para este service
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function createEntity( array $data ): Model
    {
        // Não utilizado para este service
        return new class extends Model
        {};
    }

    /**
     * {@inheritdoc}
     */
    protected function updateEntity( int $id, array $data ): Model
    {
        // Não utilizado para este service
        return new class extends Model
        {};
    }

    /**
     * {@inheritdoc}
     */
    protected function deleteEntity( int $id ): bool
    {
        // Não utilizado para este service
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function canDeleteEntity( Model $entity ): bool
    {
        // Não utilizado para este service
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function saveEntity( Model $entity ): bool
    {
        // Não utilizado para este service
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function validateForGlobal( array $data, bool $isUpdate = false ): ServiceResult
    {
        // Validação básica - pode ser sobrescrita se necessário
        return $this->success();
    }

}
