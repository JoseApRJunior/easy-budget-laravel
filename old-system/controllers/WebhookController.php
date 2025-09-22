<?php

namespace app\controllers;

use app\database\entitiesORM\ProviderEntity;
use app\database\models\Invoice;
use app\database\models\InvoiceStatuses;
use app\database\models\ProviderCredential;
use app\database\repositories\CustomerRepository;
use app\database\repositories\ProviderRepository;
use app\database\servicesORM\ActivityService;
use app\database\servicesORM\EncryptionService;
use app\database\servicesORM\InvoiceService;
use app\database\servicesORM\NotificationService;
use app\database\servicesORM\PaymentMercadoPagoInvoiceService;
use app\database\servicesORM\PaymentMercadoPagoPlanService;
use app\database\servicesORM\PlanService;
use core\dbal\EntityNotFound;
use core\library\Response;
use core\orm\EntityManagerFactory;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use http\Request;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;

class WebhookController extends AbstractController
{
    /** @var ProviderRepository */
    private $providerRepository;

    public function __construct(
        Request $request,
        private CustomerRepository $customerRepository,
        protected PaymentMercadoPagoInvoiceService $paymentMercadoPagoInvoiceService,
        protected PaymentMercadoPagoPlanService $paymentMercadoPagoPlanService,
        protected NotificationService $notificationService,
        protected PlanService $planService,
        protected InvoiceService $invoiceService,
    ) {
        parent::__construct( $request );
        /** @var ProviderRepository $repository */
        $repository               = $this->entityManager->getRepository( ProviderEntity::class);
        $this->providerRepository = $repository;
    }

    /**
     * Handles incoming webhook notifications from Mercado Pago.
     *
     * @return Response
     */
    public function handleMercadoPagoInvoice(): Response
    {
        $body = $this->request->all();

        $headers = getallheaders();

        $xRequestId = $headers[ 'X-Request-Id' ] ?? $headers[ 'x-request-id' ] ?? null;

        if ( $xRequestId === null ) {
            return new Response( 'Não autorizado', 401 );
        }

        $payment_id = $body[ 'data' ][ 'id' ] ?? null;
        $topic      = $body[ 'topic' ] ?? ( $body[ 'type' ] ?? null );

        if ( $topic !== 'payment' || !$payment_id ) {
            return new Response( [ 'status' => "Ok, não é uma notificação de pagamento. Tópico: {$topic}" ], 200 );
        }

        try {
            // Validação de autenticidade
            if ( !validateMercadoPagoAuthenticity( $body ) ) {
                // Retorna uma resposta de erro
                return new Response( 'Não autorizado', 401 );
            }

            // 1. Obtém os detalhes do pagamento da API do Mercado Pago
            $payment = $this->getResponsePaymentInvoice( $payment_id );

            // 2. Cria ou atualiza o registro de pagamento no banco de dados local
            $result = $this->paymentMercadoPagoInvoiceService->createOrUpdatePayment( $payment );

            if ( $result[ 'status' ] !== 'success' ) {
                logger()->error( "Erro ao processar o webhook para o pagamento {$payment_id}: " . $result[ 'message' ] );

                return new Response( 'Erro ao processar pagamento', 500 );
            }
            $payment_mercado_pago_invoice = $result[ 'data' ];

            $invoicePaymentAlreadyExists = $result[ 'invoicePaymentAlreadyExists' ] ?? false;

            $result = $this->invoiceService->updateInvoice( $payment );

            if ( $result[ 'status' ] !== 'success' ) {
                logger()->error( "Erro ao atualizar a fatura via webhook para o pagamento {$payment_id}: " . $result[ 'message' ] );

                return new Response( 'Erro ao atualizar a fatura', 500 );

            }
            $invoiceData          = $result[ 'data' ];
            $invoiceAlreadyExists = $result[ 'invoiceAlreadyExists' ] ?? false;

            // 4. Envia notificação por e-mail para o usuário
            // Só envia se a fatura não foi processada anteriormente (evita e-mails duplicados)
            if ( !$invoiceAlreadyExists && !$invoicePaymentAlreadyExists ) {
                $authenticated = $this->providerRepository->findProviderFullByUserId( $payment[ 'user_id' ], $payment[ 'tenant_id' ] );

                if ( $authenticated === null ) {
                    logger()->error( "Erro ao enviar notificação de atualização de status da fatura para o usuário {$payment[ 'user_id' ]}: Usuário não autenticado" );

                    return new Response( 'Erro ao enviar notificação', 500 );
                }
                $customer = $this->customerRepository->findFullById( $payment[ 'customer_id' ], $payment[ 'tenant_id' ] );
                if ( $customer === null ) {
                    logger()->error( "Erro ao enviar notificação de atualização de status da fatura para o usuário {$payment[ 'user_id' ]}: Cliente não encontrado" );

                    return new Response( 'Erro ao enviar notificação', 500 );
                }

                $response = $this->notificationService->sendInvoiceStatusUpdate( $customer, $authenticated, $invoiceData, $payment );
                if ( $response[ 'status' ] !== 'success' ) {
                    logger()->error( "Erro ao enviar notificação de atualização de status da fatura para o usuário {$payment[ 'user_id' ]}: " . $response[ 'message' ] );

                    return new Response( 'Erro ao enviar notificação', 500 );
                }
            }

            // 5. Registra as atividades (logs)

            if ( !$invoicePaymentAlreadyExists ) {
                $this->activityLogger(
                    $payment[ 'tenant_id' ],
                    $payment[ 'user_id' ],
                    'payment_mercado_pago_invoice_created',
                    'payment_mercado_pago_invoices',
                    (int) $payment_mercado_pago_invoice[ 'id' ],
                    "Pagamento #{$payment_mercado_pago_invoice[ 'payment_id' ]} registrado via webhook do Mercado Pago com status {$payment[ 'status' ]}",
                    $result[ 'data' ],
                );
            }
            if ( !$invoiceAlreadyExists ) {
                $this->activityLogger(
                    $payment[ 'tenant_id' ],
                    $payment[ 'user_id' ],
                    'invoice_updated',
                    'invoice',
                    $payment[ 'invoice_id' ],
                    "Pagamento da fatura #{$payment[ 'code' ]} atualizada para {$payment[ 'status' ]}  via webhook.",
                    $result[ 'data' ],
                );
            }

            // 6. Retorna uma resposta de sucesso para o Mercado Pago
            return new Response( "Webhook processado com sucesso, {$result[ 'message' ]}", 200 );

        } catch ( MPApiException $e ) {
            logger()->error( "Webhook MP API Error: " . $e->getMessage(), $e->getApiResponse()->getContent() );

            return new Response( [ 'error' => 'Mercado Pago API error' ], 500 );
        } catch ( \Throwable $e ) {
            logger()->error( "Webhook Unhandled Error: " . $e->getMessage(), [ 'trace' => $e->getTraceAsString() ] );

            return new Response( [ 'error' => 'Internal server error' ], 500 );
        }
    }

    public function handleMercadoPagoPlan(): Response
    {
        $body = $this->request->all();

        $headers = getallheaders();

        $xRequestId = $headers[ 'X-Request-Id' ] ?? $headers[ 'x-request-id' ] ?? null;

        if ( $xRequestId === null ) {
            return new Response( 'Não autorizado', 401 );
        }

        $payment_id = $body[ 'data' ][ 'id' ] ?? null;
        $topic      = $body[ 'topic' ] ?? ( $body[ 'type' ] ?? null );

        if ( $topic !== 'payment' || !$payment_id ) {
            return new Response( [ 'status' => "Ok, não é uma notificação de pagamento. Tópico: {$topic}" ], 200 );
        }

        try {
            // Validação de autenticidade
            if ( !validateMercadoPagoAuthenticity( $body ) ) {
                // Retorna uma resposta de erro
                return new Response( 'Não autorizado', 401 );
            }

            // 1. Obtém os detalhes do pagamento da API do Mercado Pago
            $payment = $this->getResponsePaymentPlan( $payment_id );

            // 2. Cria ou atualiza o registro de pagamento no banco de dados local
            $result = $this->paymentMercadoPagoPlanService->createOrUpdateFromWebhook( $payment );

            if ( $result[ 'status' ] !== 'success' ) {
                logger()->error( "Erro ao processar o webhook para o pagamento {$payment_id}: " . $result[ 'message' ] );

                return new Response( 'Erro ao processar pagamento', 500 );
            }
            $payment_mercado_pago_plan = $result[ 'data' ];

            $planPaymentAlreadyExists = $result[ 'planPaymentAlreadyExists' ] ?? false;

            // 3. Atualiza o status da assinatura do plano com base no status do pagamento
            $result = $this->planService->updatePlanSubscription( $payment );

            if ( $result[ 'status' ] !== 'success' ) {
                logger()->error( "Erro ao atualizar a assinatura via webhook para o pagamento {$payment_id}: " . $result[ 'message' ] );

                return new Response( 'Erro ao atualizar assinatura', 500 );

            }

            $planSubscriptionAlreadyExists = $result[ 'planSubscriptionAlreadyExists' ] ?? false;

            // 4. Envia notificação por e-mail para o usuário
            // Só envia se a assinatura não foi processada anteriormente (evita e-mails duplicados)
            if ( !$planSubscriptionAlreadyExists && !$planPaymentAlreadyExists ) {
                $authenticated = $this->providerRepository->findProviderFullByUserId( $payment[ 'user_id' ], $payment[ 'tenant_id' ] );
                $response      = $this->notificationService->sendPlanSubscriptionStatusUpdate( $authenticated, $payment );
                if ( $response[ 'status' ] !== 'success' ) {
                    logger()->error( "Erro ao enviar notificação de atualização de status da assinatura para o usuário {$payment[ 'user_id' ]}: " . $response[ 'message' ] );

                    return new Response( 'Erro ao enviar notificação', 500 );
                }
            }

            // 5. Registra as atividades (logs)

            if ( !$planPaymentAlreadyExists ) {
                $this->activityLogger(
                    $payment[ 'tenant_id' ],
                    $payment[ 'user_id' ],
                    'payment_mercado_pago_plans_created',
                    'payment_mercado_pago_plans',
                    (int) $payment_mercado_pago_plan[ 'id' ],
                    "Pagamento #{$payment_mercado_pago_plan[ 'payment_id' ]} registrado via webhook do Mercado Pago com status {$payment[ 'status' ]}",
                    $result[ 'data' ],
                );
            }
            if ( !$planSubscriptionAlreadyExists ) {
                $this->activityLogger(
                    $payment[ 'tenant_id' ],
                    $payment[ 'user_id' ],
                    'plan_subscription_updated',
                    'plan_subscription',
                    $payment[ 'plan_subscription_id' ],
                    "Assinatura do plano {$payment[ 'plan_name' ]} atualizada para {$payment[ 'status' ]}  via webhook.",
                    $result[ 'data' ],
                );
            }

            // 6. Retorna uma resposta de sucesso para o Mercado Pago
            return new Response( "Webhook processado com sucesso, {$result[ 'message' ]}", 200 );

        } catch ( MPApiException $e ) {
            logger()->error( "Webhook MP API Error: " . $e->getMessage(), $e->getApiResponse()->getContent() );

            return new Response( [ 'error' => 'Mercado Pago API error' ], 500 );
        } catch ( \Throwable $e ) {
            logger()->error( "Webhook Unhandled Error: " . $e->getMessage(), [ 'trace' => $e->getTraceAsString() ] );

            return new Response( [ 'error' => 'Internal server error' ], 500 );
        }
    }

    private function getPaymentInfo( string $payment_id ): mixed
    {
        $this->authenticate();
        $client = new PaymentClient();

        return $client->get( (int) $payment_id );
    }

    protected function authenticate(): void
    {
        $mpAccessToken = env( 'MERCADO_PAGO_ACCESS_TOKEN' );
        if ( !$mpAccessToken ) {
            throw new Exception( "Token de acesso do Mercado Pago não configurado." );
        }

        if ( env( 'APP_ENV' ) === 'development' ) {
            MercadoPagoConfig::setRuntimeEnviroment( MercadoPagoConfig::LOCAL );
        } else {
            MercadoPagoConfig::setRuntimeEnviroment( MercadoPagoConfig::SERVER );
        }
        MercadoPagoConfig::setAccessToken( $mpAccessToken );
    }

    /**
     * Obtém detalhes do pagamento do plano do Mercado Pago.
     *
     * @param string $payment_id ID do pagamento
     * @return array<string, mixed> Detalhes da transação
     */
    private function getResponsePaymentPlan( string $payment_id ): array
    {
        $get = $this->getPaymentInfo( $payment_id );

        $externalReference     = html_entity_decode( $get->external_reference ?? '' );
        $externalReferenceData = json_decode( $externalReference, true );

        $transactionDetails = [ 
            'payment_id'                => $get->id,
            'status'                    => $get->status,
            'payment_method'            => $get->payment_method_id,
            'user_id'                   => $externalReferenceData[ 'user_id' ] ?? null,
            'provider_id'               => $externalReferenceData[ 'provider_id' ] ?? null,
            'tenant_id'                 => $externalReferenceData[ 'tenant_id' ] ?? null,
            'plan_id'                   => $externalReferenceData[ 'plan_id' ] ?? null,
            'plan_name'                 => $externalReferenceData[ 'plan_name' ] ?? null,
            'plan_slug'                 => $externalReferenceData[ 'plan_slug' ] ?? null,
            'plan_price'                => $externalReferenceData[ 'plan_price' ] ?? null,
            'plan_subscription_id'      => $externalReferenceData[ 'plan_subscription_id' ] ?? null,
            'last_plan_subscription_id' => $externalReferenceData[ 'last_plan_subscription_id' ] ?? null,
            'transaction_amount'        => $get->transaction_amount,
            'transaction_date'          => $get->date_last_updated ? convertToDateTime( $get->date_last_updated ) : new \DateTime(),

        ];

        return $transactionDetails;
    }

    /**
     * Obtém detalhes do pagamento da fatura do Mercado Pago.
     *
     * @param string $payment_id ID do pagamento
     * @return array<string, mixed> Detalhes da transação
     */
    private function getResponsePaymentInvoice( string $payment_id ): array
    {
        $get                   = $this->getPaymentInfo( $payment_id );
        $externalReference     = html_entity_decode( $get->external_reference ?? '' );
        $externalReferenceData = json_decode( $externalReference, true );

        $transactionDetails = [ 
            'payment_id'         => $get->id,
            'status'             => $get->status,
            'payment_method'     => $get->payment_method_id,
            'user_id'            => $externalReferenceData[ 'user_id' ] ?? null,
            'invoice_id'         => $externalReferenceData[ 'invoice_id' ],
            'tenant_id'          => $externalReferenceData[ 'tenant_id' ],
            'customer_id'        => $externalReferenceData[ 'customer_id' ],
            'service_id'         => $externalReferenceData[ 'service_id' ],
            'public_hash'        => $externalReferenceData[ 'public_hash' ],
            'code'               => $externalReferenceData[ 'invoice_code' ],
            'transaction_amount' => $get->transaction_amount,
            'transaction_date'   => $get->date_last_updated ? convertToDateTime( $get->date_last_updated ) : new \DateTime(),

        ];

        return $transactionDetails;
    }

    public function activityLogger( int $tenant_id, int $user_id, string $action_type, string $entity_type, int $entity_id, string $description, array $metadata = [] ): void
    {
        $this->activityService->logActivity( $tenant_id, $user_id, $action_type, $entity_type, $entity_id, $description, $metadata );
    }

}