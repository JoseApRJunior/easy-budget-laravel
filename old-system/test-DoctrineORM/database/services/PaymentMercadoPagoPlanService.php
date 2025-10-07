<?php

namespace app\database\services;

use app\database\entitiesORM\PaymentMercadoPagoPlansEntity;
use app\database\models\PaymentMercadoPagoPlans;
use app\database\models\Plan;
use core\dbal\EntityNotFound;
use core\library\Session;
use Doctrine\DBAL\Connection;
use Exception;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Payment\PaymentRefundClient;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Resources\Payment;
use MercadoPago\Resources\PaymentRefund;
use RuntimeException;

class PaymentMercadoPagoPlanService
{
    /**
     * Summary of table
     * @var string
     */

    protected string $table = 'payments_mercado_pago_plans';
    private mixed $authenticated;

    public function __construct(
        private readonly Connection $connection,
        private PaymentMercadoPagoPlans $paymentMercadoPagoPlans,
    ) {
        if ( Session::has( 'auth' ) ) {
            $this->authenticated = Session::get( 'auth' );
        }
    }

    /**
     * Cria ou atualiza um registro de pagamento a partir de uma notificação de webhook.
     *
     * Este método centraliza a lógica para:
     * 1. Validar se já existe um pagamento ativo ('pending' ou 'approved') para a assinatura.
     * 2. Criar um novo registro de pagamento se ele não existir.
     * 3. Atualizar um registro existente se o status do pagamento mudou.
     *
     * @param array<string, mixed> $webhookPaymentData Dados do pagamento recebidos do webhook.
     * @return array<string, mixed> Resultado da operação.
     */
    public function createOrUpdateFromWebhook( array $webhookPaymentData ): array
    {
        try {

            return $this->connection->transactional( function () use ($webhookPaymentData) {
                // 1. Validação de Negócio: Verifica se já existe um pagamento ativo para esta assinatura.
                $lastPayment = $this->paymentMercadoPagoPlans->getPaymentsByPlanSubscription(
                    $webhookPaymentData[ 'provider_id' ],
                    $webhookPaymentData[ 'tenant_id' ],
                    $webhookPaymentData[ 'plan_subscription_id' ],
                );

                // Se já existe um pagamento em andamento ou aprovado, não faz nada.
                if ( !$lastPayment instanceof EntityNotFound ) {
                    $payments         = is_array( $lastPayment ) ? $lastPayment : [ $lastPayment ];
                    $blockingStatuses = [ 'pending', 'authorized', 'in_process', 'in_mediation', 'approved' ];

                    foreach ( $payments as $payment ) {
                        /** @var PaymentMercadoPagoPlansEntity $payment */
                        // Bloqueia apenas se o pagamento ativo for DIFERENTE do que estamos processando agora.
                        if ( in_array( $payment->status, $blockingStatuses ) && $payment->payment_id !== $webhookPaymentData[ 'payment_id' ] ) {
                            return [ 
                                'status'  => 'success',
                                'message' => "Um pagamento para atualização de assinatura já está em andamento. Nenhuma nova ação foi tomada.",
                                'data'    => $payment->toArray(),
                            ];
                        }
                    }
                }
                // 2. Busca o registro de pagamento específico do webhook.
                $existingPayment = $this->paymentMercadoPagoPlans->getPaymentId(
                    $webhookPaymentData[ 'payment_id' ],
                    $webhookPaymentData[ 'tenant_id' ],
                    $webhookPaymentData[ 'provider_id' ],
                );
                // 3. Mapeia o status da string para o Enum.
                $webhookPaymentData[ 'status' ] = mapPaymentStatusMercadoPago( $webhookPaymentData[ 'status' ] )->value;

                // 4. Decide se deve criar ou atualizar.
                if ( $existingPayment instanceof EntityNotFound ) {
                    // CRIAR
                    $properties = getConstructorProperties( PaymentMercadoPagoPlansEntity::class);
                    $entity     = PaymentMercadoPagoPlansEntity::create( \removeUnnecessaryIndexes(
                        $properties,
                        [ 'id', 'created_at', 'updated_at' ],
                        $webhookPaymentData,
                    ) );

                    return $this->paymentMercadoPagoPlans->create( $entity );
                }

                // ATUALIZAR
                /** @var PaymentMercadoPagoPlansEntity $existingPayment */
                if ( $existingPayment->status === $webhookPaymentData[ 'status' ] ) {
                    return [ 
                        'status'                   => 'success',
                        'message'                  => 'Pagamento já existe com o mesmo status. Nenhuma alteração necessária.',
                        'data'                     => $existingPayment->toArray(),
                        'planPaymentAlreadyExists' => true,
                    ];
                } else { // Prepara os dados para a atualização.
                    $updateData = $existingPayment->toArray();
                }
                $updateData[ 'status' ]             = $webhookPaymentData[ 'status' ];
                $updateData[ 'transaction_amount' ] = $webhookPaymentData[ 'transaction_amount' ];
                $updateData[ 'payment_method' ]     = $webhookPaymentData[ 'payment_method' ];
                $updateData[ 'transaction_date' ]   = $webhookPaymentData[ 'transaction_date' ];
                $entityToUpdate                     = PaymentMercadoPagoPlansEntity::create( $updateData );

                return $this->paymentMercadoPagoPlans->update( $entityToUpdate );
            } );
        } catch ( Exception $e ) {
            getDetailedErrorInfo( $e );
            logger()->error( "Erro ao criar ou atualizar o pagamento: " . $e->getMessage(), [ 'exception' => $e ] );

            return [ 
                'status'  => 'error',
                'message' => 'Erro inesperado ao processar o pagamento.',
            ];

        }
    }

    /**
     * Busca os detalhes de um pagamento diretamente na API do Mercado Pago.
     *
     * @param int $paymentId O ID do pagamento no Mercado Pago.
     * @return Payment|null O objeto de pagamento do SDK ou null em caso de erro.
     */
    public function getPaymentFromMercadoPagoAPI( int $paymentId ): ?Payment
    {
        try {
            $this->authenticate();
            $client = new PaymentClient();

            return $client->get( $paymentId );
        } catch ( MPApiException $e ) {
            logger()->error( "Erro da API do Mercado Pago ao buscar o pagamento {$paymentId}: " . $e->getApiResponse()->getContent()[ 'message' ], [ 
                'response' => $e->getApiResponse()->getContent(),
            ] );

            return null;
        } catch ( \Throwable $e ) {
            getDetailedErrorInfo( $e );
            logger()->error( "Erro inesperado ao buscar o pagamento {$paymentId} no Mercado Pago." );

            return null;
        }
    }

    /**
     * Cancela um pagamento que está 'in_process' diretamente na API do Mercado Pago.
     *
     * @param int $paymentId O ID do pagamento no Mercado Pago.
     * @return bool True se o cancelamento foi bem-sucedido, false caso contrário.
     */
    public function cancelPaymentOnMercadoPago( int $paymentId ): bool
    {
        try {
            $this->authenticate();
            $client = new PaymentClient();

            // O SDK do MP usa o método update para alterar o status para 'cancelled'
            $payment = $client->cancel( $paymentId );

            // Verifica se a resposta da API confirma o cancelamento
            if ( $payment instanceof Payment && $payment->status === 'cancelled' ) {
                logger()->info( "Pagamento {$paymentId} cancelado com sucesso no Mercado Pago." );

                return true;
            }

            logger()->warning( "A API do Mercado Pago não confirmou o cancelamento para o pagamento {$paymentId}.", [ 'response' => (array) $payment ] );

            return false;

        } catch ( MPApiException $e ) {
            logger()->error( "Erro da API do Mercado Pago ao tentar cancelar o pagamento {$paymentId}: " . $e->getApiResponse()->getContent()[ 'message' ], [ 
                'response' => $e->getApiResponse()->getContent(),
            ] );

            return false;
        } catch ( \Throwable $e ) {
            getDetailedErrorInfo( $e );
            logger()->error( "Erro inesperado ao cancelar o pagamento {$paymentId} no Mercado Pago." );

            return false;
        }
    }

    /**
     * Reembolsa (estorna) um pagamento aprovado diretamente na API do Mercado Pago.
     *
     * @param int $paymentId O ID do pagamento no Mercado Pago.
     * @return bool True se o reembolso foi processado com sucesso, false caso contrário.
     */
    public function refundPaymentOnMercadoPago( int $paymentId ): bool
    {
        try {
            $this->authenticate();
            $client = new PaymentRefundClient();

            // O SDK do MP v3 usa o método create() para iniciar um reembolso total.
            $refund = $client->refundTotal( $paymentId );

            // A API de estorno retorna 201 (Created) em sucesso e um objeto de reembolso.
            if ( $refund instanceof PaymentRefund && $refund->id ) {
                logger()->info( "Reembolso para o pagamento {$paymentId} processado com sucesso no Mercado Pago. Refund ID: {$refund->id}" );

                return true;
            }

            logger()->warning( "A API do Mercado Pago não confirmou o reembolso para o pagamento {$paymentId}.", [ 'response' => (array) $refund ] );

            return false;

        } catch ( MPApiException $e ) {
            $responseContent = $e->getApiResponse()->getContent();
            logger()->error( "Erro da API do Mercado Pago ao tentar reembolsar o pagamento {$paymentId}: " . ( $responseContent[ 'message' ] ?? 'Erro desconhecido' ), [ 
                'response' => $responseContent,
            ] );

            return false;
        } catch ( \Throwable $e ) {
            getDetailedErrorInfo( $e );
            logger()->error( "Erro inesperado ao reembolsar o pagamento {$paymentId} no Mercado Pago." );

            return false;
        }
    }

    /**
     * Atualiza o status de um registro de pagamento local.
     *
     * @param string $paymentId O ID do pagamento no Mercado Pago.
     * @param string $newStatus O novo status para o pagamento (ex: 'cancelled').
     * @param int $tenantId O ID do tenant.
     * @return array<string, mixed> O resultado da operação de atualização.
     */
    public function updatePaymentStatus( string $paymentId, string $newStatus, int $tenantId ): array
    {
        try {
            $payment = $this->paymentMercadoPagoPlans->findBy( [ 'payment_id' => $paymentId, 'tenant_id' => $tenantId ] );

            if ( $payment instanceof EntityNotFound ) {
                return [ 'status' => 'error', 'message' => 'Pagamento local não encontrado.' ];
            }

            /** @var PaymentMercadoPagoPlansEntity $payment */
            if ( $payment->status === $newStatus ) {
                return [ 'status' => 'success', 'message' => 'O pagamento já está com o status desejado.' ];
            }

            $paymentData             = $payment->toArray();
            $paymentData[ 'status' ] = $newStatus;

            $entity = PaymentMercadoPagoPlansEntity::create( $paymentData );

            return $this->paymentMercadoPagoPlans->update( $entity );

        } catch ( \Throwable $e ) {
            getDetailedErrorInfo( $e );
            logger()->error( "Erro ao atualizar status do pagamento local {$paymentId} para {$newStatus}.", [ 'exception' => $e ] );

            return [ 'status' => 'error', 'message' => 'Erro inesperado ao atualizar o status do pagamento.' ];
        }
    }

    /**
     * Cria uma preferência de pagamento no Mercado Pago.
     *
     * @param mixed $lastPlan Dados do plano anterior.
     * @param mixed $newPlan Dados do novo plano.
     * @param mixed $plan_subscription_id ID da assinatura do plano.
     * @return array<string, mixed> Resultado da operação.
     */
    public function createMercadoPagoPreference( mixed $lastPlan, mixed $newPlan, mixed $plan_subscription_id ): array
    {

        // Cria a requisição para o Mercado Pago, e retorna a resposta para a pagina  success/failure/pending
        try {
            // Autentica a aplicação com o Mercado Pago

            $this->authenticate();
            // Cria uma nova instancia de preferência de pagamento
            $client = new PreferenceClient();
            // Cria a preferência de pagamento
            $preference = $client->create(
                $this->buildPreferenceData(
                    (object) $newPlan,
                    $lastPlan->slug,
                    $plan_subscription_id,
                ),
            );

            // Redireciona para a página de pagamento
            return [ 
                'status'      => 'success',
                'message'     => 'Preferençia de pagamento criada com sucesso',
                'data'        => $preference,
                'payment_url' => $preference->init_point,
            ];

        } catch ( MPApiException $e ) {
            $errorContent = json_encode( $e->getApiResponse()->getContent() );
            logger()->error( "Erro ao criar pagamento MP para o plano {$newPlan->name}: " . $errorContent );

            throw new RuntimeException( "Erro ao processar o pagamento: " . $e->getApiResponse()->getContent()[ 'message' ] );
        }
    }

    /**
     * Constrói os dados da preferência de pagamento.
     *
     * @param object $planSelected Plano selecionado.
     * @param string $planSlug Slug do plano.
     * @param int $plan_subscription_id ID da assinatura do plano.
     * @return array<string, mixed> Dados da preferência de pagamento.
     */
    private function buildPreferenceData( object $planSelected, string $planSlug, int $plan_subscription_id ): array
    {
        $checkPlan = Session::get( 'checkPlan' );

        // External reference como string JSON (recomendado MP)
        $externalReference = json_encode( [ 
            'plan_id'                   => $planSelected->id,
            'plan_name'                 => $planSelected->name,
            'plan_slug'                 => $planSelected->slug,
            'plan_price'                => $planSelected->price,
            'user_id'                   => $this->authenticated->user_id,
            'provider_id'               => $this->authenticated->id,
            'tenant_id'                 => $this->authenticated->tenant_id,
            'plan_subscription_id'      => $plan_subscription_id,
            'last_plan_subscription_id' => $checkPlan->id,
        ] );

        // URL absoluta para webhook
        $webhookUrl = buildUrl( '/webhooks/mercadopago/plans', true );

        return [ 
            'items'              => [ 
                [ 
                    'title'       => sprintf( 'Assinatura do %s', ucfirst( $planSelected->name ) ),
                    'quantity'    => 1,
                    'currency_id' => 'BRL',
                    'unit_price'  => (float) $planSelected->price,
                    'description' => 'Assinatura recorrente mensal',
                ],
            ],
            'payer'              => [ 
                'first_name' => $this->authenticated->first_name,
                'last_name'  => $this->authenticated->last_name,
                'email'      => $this->authenticated->email,
            ],
            'payment_methods'    => [ 
                "excluded_payment_methods" => [],
                "installments"             => 12,
                "default_installments"     => 1,
            ],
            'external_reference' => $externalReference,
            'back_urls'          => [ 
                'success' => buildUrl( '/plans/payment-status', true ),
                'failure' => buildUrl( '/plans/payment-status', true ),
                'pending' => buildUrl( '/plans/payment-status', true ),
            ],
            'auto_return'        => 'approved',
            'notification_url'   => $webhookUrl,
        ];
    }

    protected function authenticate(): void
    {
        $mpAccessToken = env( 'MERCADO_PAGO_ACCESS_TOKEN' );
        if ( !$mpAccessToken ) {
            throw new Exception( "Token de acesso do Mercado Pago não configurado." );
        }

        $isDevelopment = env( 'APP_ENV' ) === 'development';

        if ( $isDevelopment ) {
            MercadoPagoConfig::setRuntimeEnviroment( MercadoPagoConfig::LOCAL );
        } else {
            MercadoPagoConfig::setRuntimeEnviroment( MercadoPagoConfig::SERVER );
        }
        MercadoPagoConfig::setAccessToken( $mpAccessToken );
    }

}
