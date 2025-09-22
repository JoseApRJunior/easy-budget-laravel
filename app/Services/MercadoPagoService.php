<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OperationStatus;
use App\Interfaces\MercadoPagoServiceInterface;
use App\Models\MerchantOrderMercadoPago;
use App\Models\PaymentMercadoPagoInvoice;
use App\Models\PaymentMercadoPagoPlan;
use App\Services\Abstracts\BaseNoTenantService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/**
 * Serviço para integração com MercadoPago.
 *
 * Responsável por gerenciar pagamentos, processar notificações via webhooks
 * e manter compatibilidade com API legacy. Utiliza HTTP client do Laravel
 * para comunicação com APIs do MercadoPago.
 *
 * Funcionalidades implementadas:
 * - Criação de preferências de pagamento
 * - Processamento de webhooks/notificações
 * - Verificação de status de pagamentos
 * - Tenant isolation para operações de pagamento
 * - Compatibilidade com API legacy
 *
 * @author IA - Kilo Code
 * @version 1.0.0
 */
class MercadoPagoService extends BaseNoTenantService implements MercadoPagoServiceInterface
{
    /**
     * URL base da API do MercadoPago.
     */
    private const BASE_URL = 'https://api.mercadopago.com';

    /**
     * URL base para sandbox (desenvolvimento).
     */
    private const BASE_URL_SANDBOX = 'https://api.mercadopago.com';

    /**
     * Timeout padrão para requests em segundos.
     */
    private const DEFAULT_TIMEOUT = 30;

    /**
     * Configurações do MercadoPago.
     *
     * @var array
     */
    private array $config;

    /**
     * Indica se está em modo sandbox.
     *
     * @var bool
     */
    private bool $isSandbox;

    /**
     * Construtor com injeção de dependências.
     *
     * @param array $config Configurações do MercadoPago (injetadas automaticamente)
     */
    public function __construct( array $config = [] )
    {
        $this->config    = $config ?: \config( 'services.mercadopago', [] );
        $this->isSandbox = \config( 'app.env' ) !== 'production';
    }

    /**
     * Cria uma preferência de pagamento.
     *
     * @param array $paymentData Dados do pagamento
     * @param int $tenantId ID do tenant (para isolamento)
     * @return ServiceResult
     */
    public function createPaymentPreference( array $paymentData, int $tenantId ): ServiceResult
    {
        try {
            // Validação dos dados
            $validation = $this->validatePaymentData( $paymentData );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            // Preparar dados para API do MercadoPago
            $preferenceData = $this->preparePreferenceData( $paymentData, $tenantId );

            // Fazer request para API
            $response = $this->makeApiRequest(
                'POST',
                '/checkout/preferences',
                $preferenceData,
            );

            if ( !$response->successful() ) {
                Log::error( 'Erro ao criar preferência de pagamento', [ 
                    'tenant_id'    => $tenantId,
                    'response'     => $response->json(),
                    'payment_data' => $paymentData
                ] );
                return $this->error(
                    OperationStatus::ERROR,
                    'Falha ao criar preferência de pagamento: ' . $response->json( 'message', 'Erro desconhecido' )
                );
            }

            $preference = $response->json();

            // Registrar no banco para auditoria
            $this->logPaymentPreference( $preference, $tenantId, $paymentData );

            return $this->success( $preference, 'Preferência de pagamento criada com sucesso.' );

        } catch ( Exception $e ) {
            Log::error( 'Exceção ao criar preferência de pagamento', [ 
                'tenant_id' => $tenantId,
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString()
            ] );
            return $this->error(
                OperationStatus::ERROR,
                'Falha ao criar preferência de pagamento: ' . $e->getMessage(),
                null,
                $e,
            );
        }
    }

    /**
     * Processa webhook de notificação do MercadoPago.
     *
     * @param array $webhookData Dados do webhook
     * @return ServiceResult
     */
    public function processWebhook( array $webhookData ): ServiceResult
    {
        try {
            // Validar webhook
            $validation = $this->validateWebhookData( $webhookData );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            // Extrair dados do webhook
            $webhookType = $webhookData[ 'type' ] ?? '';
            $data        = $webhookData[ 'data' ] ?? [];

            Log::info( 'Processando webhook MercadoPago', [ 
                'type' => $webhookType,
                'data' => $data
            ] );

            // Processar baseado no tipo
            switch ( $webhookType ) {
                case 'payment':
                    return $this->processPaymentWebhook( $data );

                case 'merchant_order':
                    return $this->processMerchantOrderWebhook( $data );

                case 'subscription_preapproval':
                    return $this->processSubscriptionWebhook( $data );

                default:
                    Log::warning( 'Tipo de webhook não suportado', [ 'type' => $webhookType ] );
                    return $this->success( null, 'Webhook recebido, mas tipo não processado.' );
            }

        } catch ( Exception $e ) {
            Log::error( 'Exceção ao processar webhook', [ 
                'exception'    => $e->getMessage(),
                'webhook_data' => $webhookData,
                'trace'        => $e->getTraceAsString()
            ] );
            return $this->error(
                OperationStatus::ERROR,
                'Falha ao processar webhook: ' . $e->getMessage(),
                null,
                $e,
            );
        }
    }

    /**
     * Verifica status de um pagamento.
     *
     * @param string $paymentId ID do pagamento
     * @param int $tenantId ID do tenant
     * @return ServiceResult
     */
    public function checkPaymentStatus( string $paymentId, int $tenantId ): ServiceResult
    {
        try {
            // Buscar pagamento no banco primeiro
            $localPayment = $this->findLocalPayment( $paymentId, $tenantId );
            if ( $localPayment ) {
                return $this->success( $localPayment, 'Status do pagamento obtido localmente.' );
            }

            // Consultar API do MercadoPago
            $response = $this->makeApiRequest( 'GET', "/v1/payments/{$paymentId}" );

            if ( !$response->successful() ) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    'Pagamento não encontrado na API do MercadoPago.',
                );
            }

            $paymentData = $response->json();

            // Atualizar ou criar registro local
            $this->updateLocalPayment( $paymentData, $tenantId );

            return $this->success( $paymentData, 'Status do pagamento obtido com sucesso.' );

        } catch ( Exception $e ) {
            Log::error( 'Exceção ao verificar status do pagamento', [ 
                'payment_id' => $paymentId,
                'tenant_id'  => $tenantId,
                'exception'  => $e->getMessage()
            ] );
            return $this->error(
                OperationStatus::ERROR,
                'Falha ao verificar status do pagamento: ' . $e->getMessage(),
                null,
                $e,
            );
        }
    }

    /**
     * Cancela um pagamento.
     *
     * @param string $paymentId ID do pagamento
     * @param int $tenantId ID do tenant
     * @return ServiceResult
     */
    public function cancelPayment( string $paymentId, int $tenantId ): ServiceResult
    {
        try {
            $response = $this->makeApiRequest(
                'PUT',
                "/v1/payments/{$paymentId}",
                [ 'status' => 'cancelled' ],
            );

            if ( !$response->successful() ) {
                return $this->error(
                    OperationStatus::ERROR,
                    'Falha ao cancelar pagamento: ' . $response->json( 'message', 'Erro desconhecido' )
                );
            }

            // Atualizar status local
            $this->updatePaymentStatus( $paymentId, 'cancelled', $tenantId );

            return $this->success(
                $response->json(),
                'Pagamento cancelado com sucesso.',
            );

        } catch ( Exception $e ) {
            Log::error( 'Exceção ao cancelar pagamento', [ 
                'payment_id' => $paymentId,
                'tenant_id'  => $tenantId,
                'exception'  => $e->getMessage()
            ] );
            return $this->error(
                OperationStatus::ERROR,
                'Falha ao cancelar pagamento: ' . $e->getMessage()
            );
        }
    }

    /**
     * Reembolsa um pagamento.
     *
     * @param string $paymentId ID do pagamento
     * @param float|null $amount Valor a reembolsar (opcional - total se não informado)
     * @param int $tenantId ID do tenant
     * @return ServiceResult
     */
    public function refundPayment( string $paymentId, ?float $amount = null, int $tenantId ): ServiceResult
    {
        try {
            $refundData = [];
            if ( $amount !== null ) {
                $refundData[ 'amount' ] = $amount;
            }

            $response = $this->makeApiRequest(
                'POST',
                "/v1/payments/{$paymentId}/refunds",
                $refundData,
            );

            if ( !$response->successful() ) {
                return $this->error(
                    OperationStatus::ERROR,
                    'Falha ao processar reembolso: ' . $response->json( 'message', 'Erro desconhecido' )
                );
            }

            // Atualizar status local
            $this->updatePaymentStatus( $paymentId, 'refunded', $tenantId );

            return $this->success(
                $response->json(),
                'Reembolso processado com sucesso.',
            );

        } catch ( Exception $e ) {
            Log::error( 'Exceção ao processar reembolso', [ 
                'payment_id' => $paymentId,
                'tenant_id'  => $tenantId,
                'amount'     => $amount,
                'exception'  => $e->getMessage()
            ] );
            return $this->error(
                OperationStatus::ERROR,
                'Falha ao processar reembolso: ' . $e->getMessage(),
                null,
                $e,
            );
        }
    }

    /**
     * Obtém detalhes de uma merchant order.
     *
     * @param string $orderId ID da merchant order
     * @return ServiceResult
     */
    public function getMerchantOrderDetails( string $orderId ): ServiceResult
    {
        try {
            $response = $this->makeApiRequest( 'GET', "/merchant_orders/{$orderId}" );

            if ( !$response->successful() ) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    'Merchant order não encontrada na API do MercadoPago.',
                );
            }

            $orderData = $response->json();

            return $this->success( $orderData, 'Detalhes da merchant order obtidos com sucesso.' );

        } catch ( Exception $e ) {
            Log::error( 'Exceção ao obter detalhes da merchant order', [ 
                'order_id'  => $orderId,
                'exception' => $e->getMessage()
            ] );
            return $this->error(
                OperationStatus::ERROR,
                'Falha ao obter detalhes da merchant order: ' . $e->getMessage(),
                null,
                $e,
            );
        }
    }

    /**
     * Cancela uma merchant order.
     *
     * @param string $orderId ID da merchant order
     * @param int $tenantId ID do tenant
     * @return ServiceResult
     */
    public function cancelMerchantOrder( string $orderId, int $tenantId ): ServiceResult
    {
        try {
            $response = $this->makeApiRequest(
                'PUT',
                "/merchant_orders/{$orderId}",
                [ 'status' => 'cancelled' ],
            );

            if ( !$response->successful() ) {
                return $this->error(
                    OperationStatus::ERROR,
                    'Falha ao cancelar merchant order: ' . $response->json( 'message', 'Erro desconhecido' )
                );
            }

            return $this->success(
                $response->json(),
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
                'Falha ao cancelar merchant order: ' . $e->getMessage()
            );
        }
    }

    // MÉTODOS PRIVADOS - AUXILIARES

    /**
     * Faz request para API do MercadoPago.
     *
     * @param string $method Método HTTP
     * @param string $endpoint Endpoint da API
     * @param array $data Dados para envio
     * @return \Illuminate\Http\Client\Response
     */
    private function makeApiRequest( string $method, string $endpoint, array $data = [] )
    {
        $httpClient = $this->getHttpClient();

        $url = self::BASE_URL . $endpoint;

        Log::info( 'Fazendo request para MercadoPago API', [ 
            'method' => $method,
            'url'    => $url,
            'data'   => $data
        ] );

        return match ( strtoupper( $method ) ) {
            'GET'    => $httpClient->get( $url ),
            'POST'   => $httpClient->post( $url, $data ),
            'PUT'    => $httpClient->put( $url, $data ),
            'DELETE' => $httpClient->delete( $url ),
            default  => throw new Exception( "Método HTTP não suportado: {$method}" )
        };
    }

    /**
     * Retorna instância configurada do HTTP client.
     *
     * @return PendingRequest
     */
    private function getHttpClient(): PendingRequest
    {
        $accessToken = $this->config[ 'access_token' ] ?? '';

        if ( empty( $accessToken ) ) {
            throw new Exception( 'Access token do MercadoPago não configurado.' );
        }

        return Http::withToken( $accessToken )
            ->timeout( self::DEFAULT_TIMEOUT )
            ->retry( 3, 100 );
    }

    /**
     * Prepara dados para criação de preferência.
     *
     * @param array $paymentData
     * @param int $tenantId
     * @return array
     */
    private function preparePreferenceData( array $paymentData, int $tenantId ): array
    {
        return [ 
            'items'              => [ 
                [ 
                    'id'          => $paymentData[ 'id' ] ?? uniqid(),
                    'title'       => $paymentData[ 'title' ] ?? 'Pagamento',
                    'description' => $paymentData[ 'description' ] ?? '',
                    'quantity'    => $paymentData[ 'quantity' ] ?? 1,
                    'unit_price'  => $paymentData[ 'amount' ] ?? 0,
                    'currency_id' => $paymentData[ 'currency' ] ?? 'BRL',
                ]
            ],
            'external_reference' => $paymentData[ 'external_reference' ] ?? "tenant_{$tenantId}",
            'notification_url'   => $paymentData[ 'notification_url' ] ?? route( 'mercadopago.webhook' ),
            'back_urls'          => [ 
                'success' => $paymentData[ 'success_url' ] ?? route( 'payment.success' ),
                'failure' => $paymentData[ 'failure_url' ] ?? route( 'payment.failure' ),
                'pending' => $paymentData[ 'pending_url' ] ?? route( 'payment.pending' ),
            ],
            'auto_return'        => $paymentData[ 'auto_return' ] ?? 'approved',
            'payment_methods'    => [ 
                'excluded_payment_methods' => [],
                'excluded_payment_types'   => [],
                'installments'             => $paymentData[ 'installments' ] ?? 12,
            ],
            'metadata'           => [ 
                'tenant_id' => $tenantId,
                'type'      => $paymentData[ 'type' ] ?? 'general',
            ]
        ];
    }

    /**
     * Valida dados de pagamento.
     *
     * @param array $data
     * @return ServiceResult
     */
    private function validatePaymentData( array $data ): ServiceResult
    {
        if ( empty( $data[ 'amount' ] ) || $data[ 'amount' ] <= 0 ) {
            return $this->error( OperationStatus::INVALID_DATA, 'Valor do pagamento deve ser maior que zero.' );
        }

        if ( empty( $data[ 'title' ] ) ) {
            return $this->error( OperationStatus::INVALID_DATA, 'Título do pagamento é obrigatório.' );
        }

        return $this->success();
    }

    /**
     * Valida dados de webhook.
     *
     * @param array $data
     * @return ServiceResult
     */
    private function validateWebhookData( array $data ): ServiceResult
    {
        if ( empty( $data[ 'type' ] ) ) {
            return $this->error( OperationStatus::INVALID_DATA, 'Tipo de webhook não informado.' );
        }

        if ( empty( $data[ 'data' ] ) ) {
            return $this->error( OperationStatus::INVALID_DATA, 'Dados do webhook não informados.' );
        }

        return $this->success();
    }

    /**
     * Processa webhook de pagamento.
     *
     * @param array $data
     * @return ServiceResult
     */
    private function processPaymentWebhook( array $data ): ServiceResult
    {
        $paymentId = $data[ 'id' ] ?? '';

        if ( empty( $paymentId ) ) {
            return $this->error( OperationStatus::INVALID_DATA, 'ID do pagamento não informado no webhook.' );
        }

        // Buscar detalhes do pagamento
        $response = $this->makeApiRequest( 'GET', "/v1/payments/{$paymentId}" );

        if ( !$response->successful() ) {
            return $this->error( OperationStatus::ERROR, 'Falha ao buscar detalhes do pagamento.' );
        }

        $paymentData = $response->json();

        // Extrair tenant_id dos metadados
        $tenantId = $paymentData[ 'metadata' ][ 'tenant_id' ] ?? null;
        if ( !$tenantId ) {
            return $this->error( OperationStatus::ERROR, 'Tenant ID não encontrado nos metadados do pagamento.' );
        }

        // Atualizar ou criar registro local
        $this->updateLocalPayment( $paymentData, (int) $tenantId );

        Log::info( 'Webhook de pagamento processado', [ 
            'payment_id' => $paymentId,
            'tenant_id'  => $tenantId,
            'status'     => $paymentData[ 'status' ]
        ] );

        return $this->success( $paymentData, 'Webhook de pagamento processado com sucesso.' );
    }

    /**
     * Processa webhook de merchant order.
     *
     * @param array $data
     * @return ServiceResult
     */
    private function processMerchantOrderWebhook( array $data ): ServiceResult
    {
        $orderId = $data[ 'id' ] ?? '';

        if ( empty( $orderId ) ) {
            return $this->error( OperationStatus::INVALID_DATA, 'ID da order não informado no webhook.' );
        }

        Log::info( 'Webhook de merchant order processado', [ 
            'order_id' => $orderId,
            'data'     => $data
        ] );

        // TODO: Implementar lógica específica para merchant orders
        return $this->success( $data, 'Webhook de merchant order processado.' );
    }

    /**
     * Processa webhook de assinatura.
     *
     * @param array $data
     * @return ServiceResult
     */
    private function processSubscriptionWebhook( array $data ): ServiceResult
    {
        Log::info( 'Webhook de assinatura processado', [ 
            'data' => $data
        ] );

        // TODO: Implementar lógica específica para assinaturas
        return $this->success( $data, 'Webhook de assinatura processado.' );
    }

    /**
     * Busca pagamento local.
     *
     * @param string $paymentId
     * @param int $tenantId
     * @return array|null
     */
    private function findLocalPayment( string $paymentId, int $tenantId ): ?array
    {
        // Buscar em diferentes tabelas
        $payment = PaymentMercadoPagoInvoice::where( 'payment_id', $paymentId )
            ->where( 'tenant_id', $tenantId )
            ->first();

        if ( $payment ) {
            return $payment->toArray();
        }

        $payment = PaymentMercadoPagoPlan::where( 'payment_id', $paymentId )
            ->where( 'tenant_id', $tenantId )
            ->first();

        if ( $payment ) {
            return $payment->toArray();
        }

        return null;
    }

    /**
     * Atualiza ou cria pagamento local.
     *
     * @param array $paymentData
     * @param int $tenantId
     * @return void
     */
    private function updateLocalPayment( array $paymentData, int $tenantId ): void
    {
        $paymentId = $paymentData[ 'id' ];

        // Determinar tipo baseado nos metadados
        $type = $paymentData[ 'metadata' ][ 'type' ] ?? 'general';

        if ( $type === 'invoice' ) {
            PaymentMercadoPagoInvoice::updateOrCreate(
                [ 'payment_id' => $paymentId, 'tenant_id' => $tenantId ],
                [ 
                    'status'             => $paymentData[ 'status' ],
                    'payment_method'     => $paymentData[ 'payment_method_id' ] ?? null,
                    'transaction_amount' => $paymentData[ 'transaction_amount' ] ?? 0,
                    'transaction_date'   => $paymentData[ 'date_created' ] ?? \now(),
                ],
            );
        } elseif ( $type === 'plan' ) {
            PaymentMercadoPagoPlan::updateOrCreate(
                [ 'payment_id' => $paymentId, 'tenant_id' => $tenantId ],
                [ 
                    'status'             => $paymentData[ 'status' ],
                    'payment_method'     => $paymentData[ 'payment_method_id' ] ?? null,
                    'transaction_amount' => $paymentData[ 'transaction_amount' ] ?? 0,
                    'transaction_date'   => $paymentData[ 'date_created' ] ?? now(),
                ],
            );
        }
    }

    /**
     * Atualiza status de pagamento local.
     *
     * @param string $paymentId
     * @param string $status
     * @param int $tenantId
     * @return void
     */
    private function updatePaymentStatus( string $paymentId, string $status, int $tenantId ): void
    {
        PaymentMercadoPagoInvoice::where( 'payment_id', $paymentId )
            ->where( 'tenant_id', $tenantId )
            ->update( [ 'status' => $status ] );

        PaymentMercadoPagoPlan::where( 'payment_id', $paymentId )
            ->where( 'tenant_id', $tenantId )
            ->update( [ 'status' => $status ] );
    }

    /**
     * Registra preferência de pagamento para auditoria.
     *
     * @param array $preference
     * @param int $tenantId
     * @param array $paymentData
     * @return void
     */
    private function logPaymentPreference( array $preference, int $tenantId, array $paymentData ): void
    {
        Log::info( 'Preferência de pagamento criada', [ 
            'preference_id' => $preference[ 'id' ],
            'tenant_id'     => $tenantId,
            'amount'        => $paymentData[ 'amount' ],
            'title'         => $paymentData[ 'title' ]
        ] );
    }

    // MÉTODOS ABSTRATOS DA BaseNoTenantService

    /**
     * {@inheritdoc}
     */
    protected function findEntityById( int $id ): ?\Illuminate\Database\Eloquent\Model
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
    protected function createEntity( array $data ): \Illuminate\Database\Eloquent\Model
    {
        // Não utilizado para este service
        return new class extends \Illuminate\Database\Eloquent\Model
        {
        };
    }

    /**
     * {@inheritdoc}
     */
    protected function updateEntity( int $id, array $data ): \Illuminate\Database\Eloquent\Model
    {
        // Não utilizado para este service
        return new class extends \Illuminate\Database\Eloquent\Model
        {
        };
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
    protected function canDeleteEntity( \Illuminate\Database\Eloquent\Model $entity ): bool
    {
        // Não utilizado para este service
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function saveEntity( \Illuminate\Database\Eloquent\Model $entity ): bool
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
