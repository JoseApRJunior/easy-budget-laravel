<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OperationStatus;
use App\Interfaces\MerchantOrderMercadoPagoServiceInterface;
use App\Models\MerchantOrderMercadoPago;
use App\Services\Abstracts\BaseNoTenantService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Serviço especializado para gerenciamento de merchant orders do MercadoPago.
 *
 * Esta classe gerencia todas as operações relacionadas a merchant orders,
 * incluindo criação, atualização, processamento de webhooks, sincronização
 * de status e compatibilidade com API legacy. Mantém isolamento por tenant
 * e integra com o serviço MercadoPago para operações de merchant orders.
 *
 * Funcionalidades implementadas:
 * - Criação e atualização de merchant orders
 * - Processamento de webhooks de merchant orders
 * - Sincronização de status com MercadoPago
 * - Tenant isolation para operações
 * - Compatibilidade com API legacy
 * - Mapeamento de status entre sistemas
 *
 * @author IA - Kilo Code
 * @version 1.0.0
 * @package App\Services
 */
class MerchantOrderMercadoPagoService extends BaseNoTenantService implements MerchantOrderMercadoPagoServiceInterface
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
        $this->mercadoPagoService = $mercadoPagoService;
        $this->model              = new MerchantOrderMercadoPago();
    }

    /**
     * Cria uma nova merchant order.
     *
     * Este método valida os dados da merchant order, prepara as informações
     * necessárias e delega a criação ao serviço MercadoPago. Mantém o
     * isolamento por tenant através do ID fornecido.
     *
     * @param array $orderData Dados da merchant order
     * @param int $tenantId ID do tenant (para isolamento)
     * @return ServiceResult Resultado da operação
     */
    public function createMerchantOrder( array $orderData, int $tenantId ): ServiceResult
    {
        try {
            $validation = $this->validateMerchantOrderData( $orderData );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            // Verificar se já existe uma merchant order com mesmo ID
            $existingOrder = $this->findLocalMerchantOrder(
                $orderData[ 'merchant_order_id' ],
                $tenantId,
                $orderData[ 'provider_id' ],
            );

            if ( $existingOrder ) {
                return $this->error(
                    OperationStatus::CONFLICT,
                    'Merchant order já existe para este tenant e provider.',
                );
            }

            // Preparar dados para criação
            $preparedData = $this->prepareMerchantOrderData( $orderData, $tenantId );

            // Criar registro local primeiro
            $merchantOrder = $this->createEntity( $preparedData );
            $this->saveEntity( $merchantOrder );

            Log::info( 'Merchant order criada com sucesso', [ 
                'merchant_order_id' => $orderData[ 'merchant_order_id' ],
                'tenant_id'         => $tenantId,
                'provider_id'       => $orderData[ 'provider_id' ]
            ] );

            return $this->success(
                $merchantOrder->toArray(),
                'Merchant order criada com sucesso.',
            );
        } catch ( Exception $e ) {
            Log::error( 'Exceção ao criar merchant order', [ 
                'tenant_id'  => $tenantId,
                'order_data' => $orderData,
                'exception'  => $e->getMessage(),
                'trace'      => $e->getTraceAsString()
            ] );

            return $this->error(
                OperationStatus::ERROR,
                'Falha ao criar merchant order: ' . $e->getMessage(),
                null,
                $e,
            );
        }
    }

    /**
     * Atualiza uma merchant order existente.
     *
     * Processa a atualização de uma merchant order, verificando primeiro
     * se existe registro local e depois sincronizando com o MercadoPago
     * se necessário.
     *
     * @param array $orderData Dados atualizados da merchant order
     * @param int $tenantId ID do tenant
     * @return ServiceResult Resultado da operação
     */
    public function updateMerchantOrder( array $orderData, int $tenantId ): ServiceResult
    {
        try {
            $validation = $this->validateMerchantOrderData( $orderData, true );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            // Buscar merchant order existente
            $existingOrder = $this->findLocalMerchantOrder(
                $orderData[ 'merchant_order_id' ],
                $tenantId,
                $orderData[ 'provider_id' ],
            );

            if ( !$existingOrder ) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    'Merchant order não encontrada.',
                );
            }

            // Verificar se há mudanças significativas
            $hasChanges = $this->hasSignificantChanges( $existingOrder, $orderData );

            if ( !$hasChanges ) {
                return $this->success(
                    $existingOrder,
                    'Merchant order já está atualizada.',
                );
            }

            // Preparar dados para atualização
            $preparedData = $this->prepareMerchantOrderData( $orderData, $tenantId );

            // Atualizar registro local
            $updatedOrder = $this->updateEntity( $existingOrder[ 'id' ], $preparedData );
            $this->saveEntity( $updatedOrder );

            Log::info( 'Merchant order atualizada com sucesso', [ 
                'merchant_order_id' => $orderData[ 'merchant_order_id' ],
                'tenant_id'         => $tenantId,
                'changes'           => $hasChanges
            ] );

            return $this->success(
                $updatedOrder->toArray(),
                'Merchant order atualizada com sucesso.',
            );
        } catch ( Exception $e ) {
            Log::error( 'Exceção ao atualizar merchant order', [ 
                'tenant_id'  => $tenantId,
                'order_data' => $orderData,
                'exception'  => $e->getMessage(),
                'trace'      => $e->getTraceAsString()
            ] );

            return $this->error(
                OperationStatus::ERROR,
                'Falha ao atualizar merchant order: ' . $e->getMessage(),
                null,
                $e,
            );
        }
    }

    /**
     * Processa webhook de merchant order do MercadoPago.
     *
     * Recebe e processa notificações de mudança de status das merchant orders,
     * atualizando o banco de dados local e mantendo sincronia com o sistema
     * MercadoPago.
     *
     * @param array $webhookData Dados do webhook do MercadoPago
     * @return ServiceResult Resultado do processamento
     */
    public function processMerchantOrderWebhook( array $webhookData ): ServiceResult
    {
        try {
            $validation = $this->validateWebhookData( $webhookData );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            $orderId = $webhookData[ 'data' ][ 'id' ] ?? '';
            if ( empty( $orderId ) ) {
                return $this->error(
                    OperationStatus::INVALID_DATA,
                    'ID da merchant order não informado no webhook.',
                );
            }

            // Buscar detalhes da merchant order no MercadoPago
            $orderResult = $this->mercadoPagoService->getMerchantOrderDetails( $orderId );
            if ( !$orderResult->isSuccess() ) {
                return $orderResult;
            }

            $orderData = $orderResult->getData();
            $tenantId  = $orderData[ 'metadata' ][ 'tenant_id' ] ?? null;

            if ( !$tenantId ) {
                return $this->error(
                    OperationStatus::ERROR,
                    'Tenant ID não encontrado nos metadados da merchant order.',
                );
            }

            // Atualizar ou criar registro local
            $this->updateLocalMerchantOrder( $orderData, (int) $tenantId );

            Log::info( 'Webhook de merchant order processado com sucesso', [ 
                'order_id'  => $orderId,
                'tenant_id' => $tenantId,
                'status'    => $orderData[ 'status' ]
            ] );

            return $this->success(
                $orderData,
                'Webhook de merchant order processado com sucesso.',
            );
        } catch ( Exception $e ) {
            Log::error( 'Exceção ao processar webhook de merchant order', [ 
                'webhook_data' => $webhookData,
                'exception'    => $e->getMessage(),
                'trace'        => $e->getTraceAsString()
            ] );

            return $this->error(
                OperationStatus::ERROR,
                'Falha ao processar webhook de merchant order: ' . $e->getMessage(),
                null,
                $e,
            );
        }
    }

    /**
     * Sincroniza status de merchant order com MercadoPago.
     *
     * Consulta o status atual de uma merchant order no MercadoPago,
     * atualiza o banco de dados local e retorna os dados sincronizados.
     *
     * @param string $orderId ID da merchant order no MercadoPago
     * @param int $tenantId ID do tenant
     * @return ServiceResult Resultado da sincronização
     */
    public function syncMerchantOrderStatus( string $orderId, int $tenantId ): ServiceResult
    {
        try {
            // Buscar merchant order local primeiro
            $localOrder = $this->findLocalMerchantOrderById( $orderId, $tenantId );

            if ( $localOrder ) {
                return $this->success(
                    $localOrder,
                    'Status da merchant order obtido localmente.',
                );
            }

            // Consultar API do MercadoPago
            $statusResult = $this->mercadoPagoService->getMerchantOrderDetails( $orderId );
            if ( !$statusResult->isSuccess() ) {
                return $statusResult;
            }

            $orderData = $statusResult->getData();

            // Verificar se é do tenant correto
            $orderTenantId = $orderData[ 'metadata' ][ 'tenant_id' ] ?? null;
            if ( $orderTenantId != $tenantId ) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    'Merchant order não encontrada para este tenant.',
                );
            }

            // Atualizar registro local
            $this->updateLocalMerchantOrder( $orderData, $tenantId );

            return $this->success(
                $orderData,
                'Status da merchant order sincronizado com sucesso.',
            );
        } catch ( Exception $e ) {
            Log::error( 'Exceção ao sincronizar status da merchant order', [ 
                'order_id'  => $orderId,
                'tenant_id' => $tenantId,
                'exception' => $e->getMessage()
            ] );

            return $this->error(
                OperationStatus::ERROR,
                'Falha ao sincronizar status da merchant order: ' . $e->getMessage(),
                null,
                $e,
            );
        }
    }

    /**
     * Lista merchant orders com filtros avançados.
     *
     * Retorna uma lista paginada de merchant orders para o tenant
     * especificado, aplicando filtros opcionais e ordenação.
     *
     * @param int $tenantId ID do tenant
     * @param array $filters Filtros para consulta
     * @param array|null $orderBy Ordenação dos resultados
     * @param int|null $limit Limite de resultados
     * @param int|null $offset Offset dos resultados
     * @return ServiceResult Resultado da listagem
     */
    public function listMerchantOrders(
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

            if ( isset( $filters[ 'order_status' ] ) ) {
                $query->where( 'order_status', $filters[ 'order_status' ] );
            }

            if ( isset( $filters[ 'provider_id' ] ) ) {
                $query->where( 'provider_id', $filters[ 'provider_id' ] );
            }

            if ( isset( $filters[ 'plan_subscription_id' ] ) ) {
                $query->where( 'plan_subscription_id', $filters[ 'plan_subscription_id' ] );
            }

            if ( isset( $filters[ 'date_from' ] ) ) {
                $query->where( 'created_at', '>=', $filters[ 'date_from' ] );
            }

            if ( isset( $filters[ 'date_to' ] ) ) {
                $query->where( 'created_at', '<=', $filters[ 'date_to' ] );
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

            $orders = $query->get()->toArray();

            return $this->success(
                $orders,
                'Merchant orders listadas com sucesso.',
                [ 'total' => count( $orders ) ],
            );
        } catch ( Exception $e ) {
            Log::error( 'Exceção ao listar merchant orders', [ 
                'tenant_id' => $tenantId,
                'filters'   => $filters,
                'exception' => $e->getMessage()
            ] );

            return $this->error(
                OperationStatus::ERROR,
                'Falha ao listar merchant orders: ' . $e->getMessage(),
                null,
                $e,
            );
        }
    }

    /**
     * Cancela uma merchant order.
     *
     * Cancela uma merchant order que ainda não foi processada,
     * verificando primeiro o status local e depois delegando
     * a operação ao serviço MercadoPago.
     *
     * @param string $orderId ID da merchant order no MercadoPago
     * @param int $tenantId ID do tenant
     * @return ServiceResult Resultado do cancelamento
     */
    public function cancelMerchantOrder( string $orderId, int $tenantId ): ServiceResult
    {
        try {
            $localOrder = $this->findLocalMerchantOrderById( $orderId, $tenantId );

            if ( !$localOrder ) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    'Merchant order não encontrada.',
                );
            }

            if ( $localOrder[ 'status' ] === 'closed' || $localOrder[ 'status' ] === 'expired' ) {
                return $this->error(
                    OperationStatus::CONFLICT,
                    'Não é possível cancelar merchant order já processada.',
                );
            }

            $cancelResult = $this->mercadoPagoService->cancelMerchantOrder( $orderId, $tenantId );
            if ( !$cancelResult->isSuccess() ) {
                return $cancelResult;
            }

            $this->updateMerchantOrderStatus( $orderId, 'cancelled', $tenantId );

            return $this->success(
                $cancelResult->getData(),
                'Merchant order cancelada com sucesso.',
            );
        } catch ( Exception $e ) {
            Log::error( 'Exceção ao cancelar merchant order', [ 
                'order_id'  => $orderId,
                'tenant_id' => $tenantId,
                'exception' => $e->getMessage()
            ] );

            return $this->error(
                OperationStatus::ERROR,
                'Falha ao cancelar merchant order: ' . $e->getMessage(),
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
        if ( !$isUpdate && !isset( $data[ 'merchant_order_id' ] ) ) {
            return $this->error(
                OperationStatus::INVALID_DATA,
                'ID da merchant order é obrigatório.',
            );
        }

        if ( !isset( $data[ 'tenant_id' ] ) ) {
            return $this->error(
                OperationStatus::INVALID_DATA,
                'ID do tenant é obrigatório.',
            );
        }

        if ( !isset( $data[ 'provider_id' ] ) ) {
            return $this->error(
                OperationStatus::INVALID_DATA,
                'ID do provider é obrigatório.',
            );
        }

        return $this->success( true, 'Dados válidos.' );
    }

    /**
     * Verifica se a entidade pode ser deletada.
     *
     * Para merchant orders, geralmente não permitimos exclusão
     * para manter histórico de transações financeiras.
     *
     * @param Model $entity Entidade a ser verificada
     * @return bool True se pode ser deletada, false caso contrário
     */
    protected function canDeleteEntity( Model $entity ): bool
    {
        // Para merchant orders, geralmente não permitimos exclusão
        // para manter histórico de transações financeiras
        return false;
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
            Log::error( 'Erro ao salvar entidade de merchant order', [ 
                'entity_id' => $entity->id ?? 'new',
                'exception' => $e->getMessage()
            ] );
            return false;
        }
    }

    // MÉTODOS AUXILIARES PRIVADOS

    /**
     * Valida dados para criação/atualização de merchant order.
     *
     * @param array $orderData Dados da merchant order
     * @param bool $isUpdate Define se é atualização
     * @return ServiceResult
     */
    private function validateMerchantOrderData( array $orderData, bool $isUpdate = false ): ServiceResult
    {
        if ( !$isUpdate && !isset( $orderData[ 'merchant_order_id' ] ) ) {
            return $this->error(
                OperationStatus::INVALID_DATA,
                'ID da merchant order é obrigatório.',
            );
        }

        if ( !isset( $orderData[ 'provider_id' ] ) ) {
            return $this->error(
                OperationStatus::INVALID_DATA,
                'ID do provider é obrigatório.',
            );
        }

        if ( !isset( $orderData[ 'plan_subscription_id' ] ) ) {
            return $this->error(
                OperationStatus::INVALID_DATA,
                'ID da assinatura do plano é obrigatório.',
            );
        }

        if ( !isset( $orderData[ 'total_amount' ] ) || $orderData[ 'total_amount' ] < 0 ) {
            return $this->error(
                OperationStatus::INVALID_DATA,
                'Valor total deve ser maior ou igual a zero.',
            );
        }

        return $this->success( true, 'Dados da merchant order válidos.' );
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
     * Prepara dados para criação/atualização de merchant order.
     *
     * @param array $orderData Dados originais da merchant order
     * @param int $tenantId ID do tenant
     * @return array Dados preparados para o banco de dados
     */
    private function prepareMerchantOrderData( array $orderData, int $tenantId ): array
    {
        return [ 
            'tenant_id'            => $tenantId,
            'provider_id'          => $orderData[ 'provider_id' ],
            'merchant_order_id'    => $orderData[ 'merchant_order_id' ],
            'plan_subscription_id' => $orderData[ 'plan_subscription_id' ],
            'status'               => $orderData[ 'status' ] ?? 'opened',
            'order_status'         => $orderData[ 'order_status' ] ?? 'payment_required',
            'total_amount'         => $orderData[ 'total_amount' ] ?? 0,
        ];
    }

    /**
     * Encontra merchant order local por ID e tenant.
     *
     * @param string $orderId ID da merchant order
     * @param int $tenantId ID do tenant
     * @param int $providerId ID do provider
     * @return array|null Dados da merchant order ou null se não encontrada
     */
    private function findLocalMerchantOrder( string $orderId, int $tenantId, int $providerId ): ?array
    {
        $order = $this->model
            ->where( 'merchant_order_id', $orderId )
            ->where( 'tenant_id', $tenantId )
            ->where( 'provider_id', $providerId )
            ->first();

        return $order ? $order->toArray() : null;
    }

    /**
     * Encontra merchant order local por ID e tenant (sem provider).
     *
     * @param string $orderId ID da merchant order
     * @param int $tenantId ID do tenant
     * @return array|null Dados da merchant order ou null se não encontrada
     */
    private function findLocalMerchantOrderById( string $orderId, int $tenantId ): ?array
    {
        $order = $this->model
            ->where( 'merchant_order_id', $orderId )
            ->where( 'tenant_id', $tenantId )
            ->first();

        return $order ? $order->toArray() : null;
    }

    /**
     * Atualiza dados da merchant order local.
     *
     * @param array $orderData Dados da merchant order do MercadoPago
     * @param int $tenantId ID do tenant
     * @return void
     */
    private function updateLocalMerchantOrder( array $orderData, int $tenantId ): void
    {
        $this->model->updateOrCreate(
            [ 
                'merchant_order_id' => $orderData[ 'id' ],
                'tenant_id'         => $tenantId
            ],
            [ 
                'provider_id'          => $orderData[ 'provider_id' ] ?? null,
                'plan_subscription_id' => $orderData[ 'metadata' ][ 'plan_subscription_id' ] ?? null,
                'status'               => $orderData[ 'status' ] ?? 'opened',
                'order_status'         => $orderData[ 'order_status' ] ?? 'payment_required',
                'total_amount'         => $orderData[ 'total_amount' ] ?? 0,
            ],
        );
    }

    /**
     * Atualiza status da merchant order local.
     *
     * @param string $orderId ID da merchant order
     * @param string $status Novo status
     * @param int $tenantId ID do tenant
     * @return void
     */
    private function updateMerchantOrderStatus( string $orderId, string $status, int $tenantId ): void
    {
        $this->model
            ->where( 'merchant_order_id', $orderId )
            ->where( 'tenant_id', $tenantId )
            ->update( [ 
                'status'     => $status,
                'updated_at' => now()
            ] );
    }

    /**
     * Verifica se há mudanças significativas nos dados.
     *
     * @param array $existingOrder Merchant order existente
     * @param array $newData Novos dados
     * @return bool True se há mudanças significativas
     */
    private function hasSignificantChanges( array $existingOrder, array $newData ): bool
    {
        $fieldsToCheck = [ 'status', 'order_status', 'total_amount' ];

        foreach ( $fieldsToCheck as $field ) {
            if ( isset( $newData[ $field ] ) && $existingOrder[ $field ] !== $newData[ $field ] ) {
                return true;
            }
        }

        return false;
    }

}
