<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OperationStatus;
use App\Interfaces\PaymentMercadoPagoPlanServiceInterface;
use App\Models\PaymentMercadoPagoPlan;
use App\Services\Abstracts\BaseNoTenantService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

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
class PaymentMercadoPagoPlanService extends BaseNoTenantService implements PaymentMercadoPagoPlanServiceInterface
{
    /**
     * @var MercadoPagoService Serviço de integração com MercadoPago
     */
    private MercadoPagoService $mercadoPagoService;

    /**
     * @var Model Instância do modelo para operações de banco de dados
     */
    protected Model $model;

    /**
     * Construtor do serviço.
     *
     * @param MercadoPagoService $mercadoPagoService Serviço de integração com MercadoPago
     */
    public function __construct( MercadoPagoService $mercadoPagoService )
    {
        parent::__construct();
        $this->mercadoPagoService = $mercadoPagoService;
    }

    /**
     * Retorna a classe do modelo PaymentMercadoPagoPlan.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function getModelClass(): \Illuminate\Database\Eloquent\Model
    {
        return new PaymentMercadoPagoPlan();
    }

    /**
     * Cria uma preferência de pagamento específica para planos.
     *
     * Este método valida os dados do plano, prepara as informações necessárias
     * para o pagamento e delega a criação da preferência ao serviço MercadoPago.
     * Mantém o isolamento por tenant através do ID fornecido.
     *
     * @param array $planData Dados do plano para pagamento
     * @param int $tenantId ID do tenant (para isolamento)
     * @return ServiceResult Resultado da operação
     */
    public function createPlanPaymentPreference( array $planData, int $tenantId ): ServiceResult
    {
        try {
            $validation = $this->validatePlanPaymentData( $planData );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            $paymentData = $this->preparePlanPaymentData( $planData, $tenantId );

            return $this->mercadoPagoService->createPaymentPreference( $paymentData, $tenantId );
        } catch ( Exception $e ) {
            Log::error( 'Exceção ao criar preferência de pagamento para plano', [
                'tenant_id' => $tenantId,
                'plan_data' => $planData,
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString()
            ] );

            return $this->error(
                OperationStatus::ERROR,
                'Falha ao criar preferência de pagamento para plano: ' . $e->getMessage(),
                null,
                $e,
            );
        }
    }

    /**
     * Processa webhook de notificação do MercadoPago para planos.
     *
     * Recebe e processa notificações de mudança de status dos pagamentos
     * de planos, atualizando o banco de dados local e mantendo sincronia
     * com o sistema MercadoPago.
     *
     * @param array $webhookData Dados do webhook do MercadoPago
     * @return ServiceResult Resultado do processamento
     */
    public function processPlanWebhook( array $webhookData ): ServiceResult
    {
        try {
            $validation = $this->validateWebhookData( $webhookData );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            $paymentId = $webhookData[ 'data' ][ 'id' ] ?? '';
            if ( empty( $paymentId ) ) {
                return $this->error(
                    OperationStatus::INVALID_DATA,
                    'ID do pagamento não informado no webhook.',
                );
            }

            $paymentResult = $this->mercadoPagoService->checkPaymentStatus( $paymentId, 0 );
            if ( !$paymentResult->isSuccess() ) {
                return $paymentResult;
            }

            $paymentData = $paymentResult->getData();
            $tenantId    = $paymentData[ 'metadata' ][ 'tenant_id' ] ?? null;

            if ( !$tenantId ) {
                return $this->error(
                    OperationStatus::ERROR,
                    'Tenant ID não encontrado nos metadados do pagamento.',
                );
            }

            $this->updateLocalPlanPayment( $paymentData, (int) $tenantId );

            Log::info( 'Webhook de plano processado com sucesso', [
                'payment_id' => $paymentId,
                'tenant_id'  => $tenantId,
                'status'     => $paymentData[ 'status' ]
            ] );

            return $this->success( $paymentData, 'Webhook de plano processado com sucesso.' );
        } catch ( Exception $e ) {
            Log::error( 'Exceção ao processar webhook de plano', [
                'webhook_data' => $webhookData,
                'exception'    => $e->getMessage(),
                'trace'        => $e->getTraceAsString()
            ] );

            return $this->error(
                OperationStatus::ERROR,
                'Falha ao processar webhook de plano: ' . $e->getMessage(),
                null,
                $e,
            );
        }
    }

    /**
     * Verifica status de um pagamento de plano.
     *
     * Consulta o status atual de um pagamento de plano, primeiro verificando
     * se existe registro local e, caso contrário, consulta o MercadoPago
     * e atualiza o banco de dados local.
     *
     * @param string $paymentId ID do pagamento no MercadoPago
     * @param int $tenantId ID do tenant
     * @return ServiceResult Resultado da verificação
     */
    public function checkPlanPaymentStatus( string $paymentId, int $tenantId ): ServiceResult
    {
        try {
            $localPayment = $this->findLocalPlanPayment( $paymentId, $tenantId );

            if ( $localPayment ) {
                return $this->success(
                    $localPayment,
                    'Status do pagamento de plano obtido localmente.',
                );
            }

            $statusResult = $this->mercadoPagoService->checkPaymentStatus( $paymentId, $tenantId );
            if ( !$statusResult->isSuccess() ) {
                return $statusResult;
            }

            $paymentData = $statusResult->getData();
            $this->updateLocalPlanPayment( $paymentData, $tenantId );

            return $this->success(
                $paymentData,
                'Status do pagamento de plano obtido com sucesso.',
            );
        } catch ( Exception $e ) {
            Log::error( 'Exceção ao verificar status do pagamento de plano', [
                'payment_id' => $paymentId,
                'tenant_id'  => $tenantId,
                'exception'  => $e->getMessage()
            ] );

            return $this->error(
                OperationStatus::ERROR,
                'Falha ao verificar status do pagamento de plano: ' . $e->getMessage(),
                null,
                $e,
            );
        }
    }

    /**
     * Cancela um pagamento de plano.
     *
     * Cancela um pagamento de plano que ainda não foi aprovado,
     * verificando primeiro o status local e depois delegando
     * a operação ao serviço MercadoPago.
     *
     * @param string $paymentId ID do pagamento no MercadoPago
     * @param int $tenantId ID do tenant
     * @return ServiceResult Resultado do cancelamento
     */
    public function cancelPlanPayment( string $paymentId, int $tenantId ): ServiceResult
    {
        try {
            $localPayment = $this->findLocalPlanPayment( $paymentId, $tenantId );

            if ( !$localPayment ) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    'Pagamento de plano não encontrado.',
                );
            }

            if ( $localPayment[ 'status' ] === 'approved' ) {
                return $this->error(
                    OperationStatus::CONFLICT,
                    'Não é possível cancelar pagamento já aprovado.',
                );
            }

            $cancelResult = $this->mercadoPagoService->cancelPayment( $paymentId, $tenantId );
            if ( !$cancelResult->isSuccess() ) {
                return $cancelResult;
            }

            $this->updatePlanPaymentStatus( $paymentId, 'cancelled', $tenantId );

            return $this->success(
                $cancelResult->getData(),
                'Pagamento de plano cancelado com sucesso.',
            );
        } catch ( Exception $e ) {
            Log::error( 'Exceção ao cancelar pagamento de plano', [
                'payment_id' => $paymentId,
                'tenant_id'  => $tenantId,
                'exception'  => $e->getMessage()
            ] );

            return $this->error(
                OperationStatus::ERROR,
                'Falha ao cancelar pagamento de plano: ' . $e->getMessage(),
                null,
                $e,
            );
        }
    }

    /**
     * Reembolsa um pagamento de plano.
     *
     * Processa o reembolso de um pagamento de plano já aprovado,
     * delegando a operação ao serviço MercadoPago e atualizando
     * o status local após o processamento.
     *
     * @param string $paymentId ID do pagamento no MercadoPago
     * @param float|null $amount Valor a reembolsar (opcional - total se não informado)
     * @param int $tenantId ID do tenant
     * @return ServiceResult Resultado do reembolso
     */
    public function refundPlanPayment( string $paymentId, ?float $amount = null, int $tenantId ): ServiceResult
    {
        try {
            $localPayment = $this->findLocalPlanPayment( $paymentId, $tenantId );

            if ( !$localPayment ) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    'Pagamento de plano não encontrado.',
                );
            }

            if ( $localPayment[ 'status' ] !== 'approved' ) {
                return $this->error(
                    OperationStatus::CONFLICT,
                    'Apenas pagamentos aprovados podem ser reembolsados.',
                );
            }

            $refundResult = $this->mercadoPagoService->refundPayment( $paymentId, $amount, $tenantId );
            if ( !$refundResult->isSuccess() ) {
                return $refundResult;
            }

            $this->updatePlanPaymentStatus( $paymentId, 'refunded', $tenantId );

            return $this->success(
                $refundResult->getData(),
                'Reembolso do pagamento de plano processado com sucesso.',
            );
        } catch ( Exception $e ) {
            Log::error( 'Exceção ao processar reembolso de pagamento de plano', [
                'payment_id' => $paymentId,
                'tenant_id'  => $tenantId,
                'amount'     => $amount,
                'exception'  => $e->getMessage()
            ] );

            return $this->error(
                OperationStatus::ERROR,
                'Falha ao processar reembolso de pagamento de plano: ' . $e->getMessage(),
                null,
                $e,
            );
        }
    }

    /**
     * Lista pagamentos de planos com filtros avançados.
     *
     * Retorna uma lista paginada de pagamentos de planos para o tenant
     * especificado, aplicando filtros opcionais e ordenação.
     *
     * @param int $tenantId ID do tenant
     * @param array $filters Filtros para consulta
     * @param array|null $orderBy Ordenação dos resultados
     * @param int|null $limit Limite de resultados
     * @param int|null $offset Offset dos resultados
     * @return ServiceResult Resultado da listagem
     */
    public function listPlanPayments(
        int $tenantId,
        array $filters = [],
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): ServiceResult {
        try {
            $query = $this->model->where( 'tenant_id', $tenantId );

            // Aplicar filtros
            if ( isset( $filters[ 'status' ] ) ) {
                $query->where( 'status', $filters[ 'status' ] );
            }

            if ( isset( $filters[ 'payment_method' ] ) ) {
                $query->where( 'payment_method', $filters[ 'payment_method' ] );
            }

            if ( isset( $filters[ 'plan_subscription_id' ] ) ) {
                $query->where( 'plan_subscription_id', $filters[ 'plan_subscription_id' ] );
            }

            if ( isset( $filters[ 'date_from' ] ) ) {
                $query->where( 'transaction_date', '>=', $filters[ 'date_from' ] );
            }

            if ( isset( $filters[ 'date_to' ] ) ) {
                $query->where( 'transaction_date', '<=', $filters[ 'date_to' ] );
            }

            // Aplicar ordenação
            if ( $orderBy ) {
                foreach ( $orderBy as $column => $direction ) {
                    $query->orderBy( $column, $direction );
                }
            } else {
                $query->orderBy( 'created_at', 'desc' );
            }

            // Aplicar paginação
            if ( $limit ) {
                $query->limit( $limit );
            }

            if ( $offset ) {
                $query->offset( $offset );
            }

            $payments = $query->get()->toArray();

            return $this->success(
                $payments,
                'Pagamentos de planos listados com sucesso.',
                [ 'total' => count( $payments ) ],
            );
        } catch ( Exception $e ) {
            Log::error( 'Exceção ao listar pagamentos de planos', [
                'tenant_id' => $tenantId,
                'filters'   => $filters,
                'exception' => $e->getMessage()
            ] );

            return $this->error(
                OperationStatus::ERROR,
                'Falha ao listar pagamentos de planos: ' . $e->getMessage(),
                null,
                $e,
            );
        }
    }

    // MÉTODOS ABSTRATOS DA BASE CLASS

    /**
     * Encontra entidade por ID (sem tenant).
     *
     * @param int $id ID da entidade
     * @return Model|null
     */
    protected function findEntityById( int $id ): ?Model
    {
        return $this->model->find( $id );
    }

    /**
     * Lista entidades com filtros (sem tenant).
     *
     * @param array|null $orderBy Ordenação dos resultados
     * @param int|null $limit Limite de resultados
     * @return array
     */
    protected function listEntities( ?array $orderBy = null, ?int $limit = null ): array
    {
        $query = $this->model->query();

        // Aplicar ordenação
        if ( $orderBy ) {
            foreach ( $orderBy as $column => $direction ) {
                $query->orderBy( $column, $direction );
            }
        } else {
            $query->orderBy( 'created_at', 'desc' );
        }

        // Aplicar limite
        if ( $limit ) {
            $query->limit( $limit );
        }

        return $query->get()->toArray();
    }

    /**
     * Cria nova entidade.
     *
     * @param array $data Dados para criação
     * @return Model
     */
    protected function createEntity( array $data ): Model
    {
        return $this->model->newInstance( $data );
    }

    /**
     * Atualiza entidade existente.
     *
     * @param int $id ID da entidade
     * @param array $data Dados para atualização
     * @return Model
     */
    protected function updateEntity( int $id, array $data ): Model
    {
        $entity = $this->findEntityById( $id );
        if ( !$entity ) {
            throw new Exception( "Entidade com ID {$id} não encontrada." );
        }

        $entity->fill( $data );
        return $entity;
    }

    /**
     * Deleta entidade.
     *
     * @param int $id ID da entidade
     * @return bool
     */
    protected function deleteEntity( int $id ): bool
    {
        $entity = $this->findEntityById( $id );
        return $entity ? $entity->delete() : false;
    }

    /**
     * Validação específica para entidades globais (sem tenant).
     *
     * @param array $data Dados a validar
     * @param bool $isUpdate Define se é atualização
     * @return ServiceResult
     */
    protected function validateForGlobal( array $data, bool $isUpdate = false ): ServiceResult
    {
        // Validação básica para campos obrigatórios
        if ( !$isUpdate && !isset( $data[ 'payment_id' ] ) ) {
            return $this->error(
                OperationStatus::INVALID_DATA,
                'ID do pagamento é obrigatório.',
            );
        }

        if ( !isset( $data[ 'tenant_id' ] ) ) {
            return $this->error(
                OperationStatus::INVALID_DATA,
                'ID do tenant é obrigatório.',
            );
        }

        return $this->success( true, 'Dados válidos.' );
    }

    /**
     * Verifica se a entidade pode ser deletada.
     *
     * Para pagamentos de planos, geralmente não permitimos exclusão
     * para manter histórico de transações financeiras.
     *
     * @param Model $entity Entidade a ser verificada
     * @return bool True se pode ser deletada, false caso contrário
     */
    protected function canDeleteEntity( Model $entity ): bool
    {
        // Para pagamentos de planos, geralmente não permitimos exclusão
        // para manter histórico de transações financeiras
        return false;
    }

    /**
     * Validação para tenant (não aplicável para serviços NoTenant).
     *
     * Este método é obrigatório por herança mas não realiza validação específica
     * de tenant, pois esta é uma classe NoTenant.
     *
     * @param array $data Dados a validar
     * @param int $tenant_id ID do tenant
     * @param bool $is_update Se é uma operação de atualização
     * @return ServiceResult Resultado da validação
     */
    protected function validateForTenant( array $data, int $tenant_id, bool $is_update = false ): ServiceResult
    {
        // Para serviços NoTenant, não há validação específica de tenant
        // Retorna sucesso pois a validação é feita pelo método validateForGlobal
        return $this->success();
    }

    /**
     * Salva a entidade no banco de dados.
     *
     * @param Model $entity Entidade a ser salva
     * @return bool True se salvou com sucesso, false caso contrário
     */
    protected function saveEntity( Model $entity ): bool
    {
        try {
            return $entity->save();
        } catch ( Exception $e ) {
            Log::error( 'Erro ao salvar entidade de pagamento de plano', [
                'entity_id' => $entity->id ?? 'new',
                'exception' => $e->getMessage()
            ] );
            return false;
        }
    }

    // MÉTODOS AUXILIARES PRIVADOS

    /**
     * Valida dados para criação de pagamento de plano.
     *
     * @param array $planData Dados do plano
     * @return ServiceResult
     */
    private function validatePlanPaymentData( array $planData ): ServiceResult
    {
        if ( !isset( $planData[ 'plan_subscription_id' ] ) ) {
            return $this->error(
                OperationStatus::INVALID_DATA,
                'ID da assinatura do plano é obrigatório.',
            );
        }

        if ( !isset( $planData[ 'transaction_amount' ] ) || $planData[ 'transaction_amount' ] <= 0 ) {
            return $this->error(
                OperationStatus::INVALID_DATA,
                'Valor da transação deve ser maior que zero.',
            );
        }

        if ( !isset( $planData[ 'payment_method' ] ) ) {
            return $this->error(
                OperationStatus::INVALID_DATA,
                'Método de pagamento é obrigatório.',
            );
        }

        return $this->success( true, 'Dados do plano válidos.' );
    }

    /**
     * Valida dados do webhook.
     *
     * @param array $webhookData Dados do webhook
     * @return ServiceResult
     */
    private function validateWebhookData( array $webhookData ): ServiceResult
    {
        if ( !isset( $webhookData[ 'type' ] ) || !isset( $webhookData[ 'data' ] ) ) {
            return $this->error(
                OperationStatus::INVALID_DATA,
                'Dados do webhook inválidos ou incompletos.',
            );
        }

        return $this->success( true, 'Dados do webhook válidos.' );
    }

    /**
     * Prepara dados para criação de pagamento de plano.
     *
     * @param array $planData Dados originais do plano
     * @param int $tenantId ID do tenant
     * @return array Dados preparados para o MercadoPago
     */
    private function preparePlanPaymentData( array $planData, int $tenantId ): array
    {
        return [
            'transaction_amount'   => $planData[ 'transaction_amount' ],
            'payment_method'       => $planData[ 'payment_method' ],
            'plan_subscription_id' => $planData[ 'plan_subscription_id' ],
            'tenant_id'            => $tenantId,
            'description'          => $planData[ 'description' ] ?? 'Pagamento de plano - Easy Budget',
            'metadata'             => [
                'tenant_id'            => $tenantId,
                'plan_subscription_id' => $planData[ 'plan_subscription_id' ],
                'type'                 => 'plan_payment'
            ]
        ];
    }

    /**
     * Encontra pagamento de plano local por ID e tenant.
     *
     * @param string $paymentId ID do pagamento
     * @param int $tenantId ID do tenant
     * @return array|null Dados do pagamento ou null se não encontrado
     */
    private function findLocalPlanPayment( string $paymentId, int $tenantId ): ?array
    {
        $payment = $this->model
            ->where( 'payment_id', $paymentId )
            ->where( 'tenant_id', $tenantId )
            ->first();

        return $payment ? $payment->toArray() : null;
    }

    /**
     * Atualiza dados do pagamento de plano local.
     *
     * @param array $paymentData Dados do pagamento do MercadoPago
     * @param int $tenantId ID do tenant
     * @return void
     */
    private function updateLocalPlanPayment( array $paymentData, int $tenantId ): void
    {
        $this->model->updateOrCreate(
            [
                'payment_id' => $paymentData[ 'id' ],
                'tenant_id'  => $tenantId
            ],
            [
                'provider_id'          => $paymentData[ 'provider_id' ] ?? null,
                'plan_subscription_id' => $paymentData[ 'metadata' ][ 'plan_subscription_id' ] ?? null,
                'status'               => $paymentData[ 'status' ],
                'payment_method'       => $paymentData[ 'payment_method' ] ?? null,
                'transaction_amount'   => $paymentData[ 'transaction_amount' ] ?? 0,
                'transaction_date'     => $paymentData[ 'transaction_date' ] ?? \now(),
            ],
        );
    }

    /**
     * Atualiza status do pagamento de plano local.
     *
     * @param string $paymentId ID do pagamento
     * @param string $status Novo status
     * @param int $tenantId ID do tenant
     * @return void
     */
    private function updatePlanPaymentStatus( string $paymentId, string $status, int $tenantId ): void
    {
        $this->model
            ->where( 'payment_id', $paymentId )
            ->where( 'tenant_id', $tenantId )
            ->update( [
                'status'     => $status,
                'updated_at' => \now()
            ] );
    }

}
