<?php

namespace app\Controllers;

use app\controllers\AbstractController;
use app\database\entities\PlanEntity;
use app\database\models\Plan;
use app\database\services\ActivityService;
use app\database\services\ProviderPlanService;
use app\request\PaymentPlanRequest;
use core\dbal\EntityNotFound;
use core\library\Response;
use core\library\Session;
use core\library\Twig;
use Exception;
use http\Redirect;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;

class PaymentController extends AbstractController
{

    /**
     * Constructs a new instance of the PaymentController class.
     *
     * @param Twig $twig The Twig templating engine instance.
     * @param Plan $plan The plan model instance.
     * @param ProviderPlanService $providerPlanService The provider plan service instance.
     */
    public function __construct(
        private Twig $twig,
        private Plan $plan,
        private ProviderPlanService $providerPlanService,
        private ActivityService $activityService,

    ) {
        parent::__construct();
    }

    /**
     * Authenticates the application with the Mercado Pago API by setting the access token and runtime environment.
     *
     * This method is responsible for configuring the Mercado Pago SDK with the necessary credentials and environment settings.
     * It first retrieves the Mercado Pago access token from the environment variables, and throws an exception if it is not configured.
     * Then, it sets the runtime environment based on the current application environment (development or production).
     * Finally, it sets the access token for the Mercado Pago SDK.
     *
     * @throws \Exception If the Mercado Pago access token is not configured.
     * @return void
     */
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

    public function createSubscription( $planSlug ): Redirect|Response
    {
        // Busca o plano selecionado pelo slug
        /** @var PlanEntity|EntityNotFound $planSelected */
        $planSelected = $this->plan->getActivePlanBySlug( $planSlug );

        // Verifica se o plano existe
        if ( $planSelected instanceof EntityNotFound ) {
            return Redirect::redirect( '/plans' )->withMessage( "error", "Plano inexistente!" );
        }

        // Pega o plano atual do usuário
        $currentPlan = Session::get( 'checkPlan' );

        // Verifica se o plano se existe sessão de plano
        if ( !$currentPlan ) {
            return Redirect::redirect( '/login' )->withMessage( "error", "Erro interno, relogue-se e tente novamente!" );
        }

        // Verifica se o plano atual existe e se o status é ativo
        if ( !$currentPlan instanceof EntityNotFound && $currentPlan->status == 'active' ) {
            // Verifica se o plano é o  mesmo plano e se esta expirado
            if (
                ( $currentPlan->slug === $planSlug & $currentPlan->end_date > date( "d-m-Y" ) )
            ) {
                // Retorna uma resposta de erro
                return Redirect::redirect( '/provider' )->withMessage( "error", "Este já e seu plano atual, expira em " . date( 'd/m/Y', ( $currentPlan->end_date )?->getTimestamp() ) . "!" );
            }
            // Verifica se o plano atual é superior ao plano selecionado
            if (
                (float) $currentPlan->price_paid > (float) $planSelected->price &
                $currentPlan->end_date > date( "d-m-Y" )
            ) {
                // Retorna uma resposta de erro
                return Redirect::redirect( '/plans' )->withMessage( "error", "O seu plano atual, " . $currentPlan->name . " e superior, expira em " . date( 'd/m/Y', ( $currentPlan->end_date )?->getTimestamp() ) . "!" );
            }
        }

        // Cria uma nova assinatura do plano selecionado superior como pending ou cria uma nova assinatura do plano free como active
        $response = $this->providerPlanService->createPlanSubscription( $planSelected );
        if ( $response[ 'status' ] === 'error' ) {
            // Retorna uma resposta de erro
            return Redirect::redirect( '/plans' )->withMessage( "error", $response[ 'message' ] );
        }

        if ( isset( $response[ 'data' ][ 'planSubscriptionCreated' ] ) ) {
            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'plan_subscription_create',
                'plan_subscription',
                $response[ 'data' ][ 'planSubscriptionId' ],
                " Assinatura do plano {$planSelected->name} criada com sucesso!",
                $response[ 'data' ],
            );
        }

        if ( $planSelected->slug === 'free' ) {

            // Limpa o last updated session provider
            Session::remove( 'checkPlan' );
            Session::remove( 'last_updated_session_provider' );

            // Retorna uma resposta de sucesso
            return Redirect::redirect( '/provider' )->withMessage( "success", "Plano atualizado com sucesso, seja bem-vindo ao seu novo {$planSelected->name}!" );
        }

        // Cria a requisição para o Mercado Pago, e retorna a resposta para a pagina  success/failure/pending
        try {
            // Autentica a aplicação com o Mercado Pago
            $this->authenticate();
            // Cria uma nova instancia de preferência de pagamento
            $client = new PreferenceClient;
            // Cria a preferência de pagamento
            $preference = $client->create(
                $this->buildPreferenceData(
                    $planSelected,
                    $planSlug,
                    $response[ 'data' ][ 'planSubscriptionId' ],
                ),
            );
            // Redireciona para a página de pagamento
            return new Response( $this->twig->env->render( 'pages/payment/redirect.twig', [ 
                'payment_url' => $preference->init_point
            ] ) );

        } catch ( MPApiException $e ) {
            // Retorna uma resposta de erro
            return $this->handlePaymentError( $e->getMessage() );
        } catch ( Exception $e ) {
            // Retorna uma resposta de erro
            return $this->handlePaymentError( "Ocorreu um erro inesperado. Por favor, tente novamente mais tarde." );
        }
    }

    /**
     * Handles a payment error and returns a Response object.
     *
     * @param string $message The error message to be displayed.
     * @return Response The response object containing the error message.
     */
    private function handlePaymentError( $message ): Response
    {
        return new Response( $this->twig->env->render( 'pages/payment/error.twig', [ 
            'message'       => 'Ocorreu um erro ao processar o pagamento. Por favor, tente novamente mais tarde.',
            'error_details' => $message
        ] ) );
    }

    /**
     * Handles the payment process.
     *
     * @return Response The response object containing the result of the payment process.
     */
    public function payment(): Response
    {
        // Valida a requisição de pagamento
        $validated = PaymentPlanRequest::validate( $this->request );
        // Se a validação falhar, redireciona para a rota de erro
        if ( !$validated ) {
            return Redirect::redirect( '/payment/error' )->withMessage( 'message', 'Dados inválidos' );
        }
        // Obtém os dados da requisição
        $data = $this->request->all();
        try {
            return $this->createSubscription( $data[ 'planSlug' ] );
        } catch ( \Throwable $th ) {
            // Loga o erro detalhado
            getDetailedErrorInfo( $th );
            // Redireciona para a rota de erro
            return Redirect::redirect( '/payment' )->withMessage( 'error', 'Falha no pagamento, tente novamente mais tarde ou entre em contato com suporte!' );
        }

    }

    /**
     * Renders the payment error view.
     *
     * This method is responsible for rendering the 'payment/error.twig' view, which is used to display an error message
     * to the user when there is an issue with the payment process.
     *
     * @return Response The response object containing the rendered error view.
     */
    public function error(): Response
    {
        // Renderiza a view de erro de pagamento
        return new Response( $this->twig->env->render( 'pages/payment/error.twig' ) );
    }

    /**
     * Handles the webhook request.
     *
     * This method is responsible for handling the webhook request from the payment gateway. It sanitizes the request
     * data, validates it, and then processes the payment accordingly.
     *
     * @return Response The response object containing the rendered view or redirect response.
     */
    public function handleWebhook(): Response
    {
        try {
            // Obtem os dados da requisição ja sanitizados
            $data = $this->request->all();

            // pega os headers da requisição
            $headers = getallheaders();
            // Validação do X-Request-ID
            $xRequestId = $headers[ 'X-Request-Id' ] ?? $headers[ 'x-request-id' ] ?? null;

            // Validação do X-Request-ID
            if ( $xRequestId === null ) {
                logger()->error( 'X-Request-Id não encontrado' );
                return new Response( 'Não autorizado', 401 );
            }

            // Merchant Order via topic
            if ( $data[ 'type' ] == 'merchant_order' || $data[ 'topic' ] == 'merchant_order' ) {
                // Pega o ID do Merchant Order
                $merchant_order_id = $data[ 'id' ] ?? $data[ 'data_id' ];
                // Pega o Merchant Order
                $merchant_order = $this->getResponseMerchantOrder( $merchant_order_id );
                // Lida com Merchant Order se for falso retorna erro
                if ( !$this->providerPlanService->handleMerchantOrderMercadoPago( $merchant_order ) ) {
                    // Log de erro
                    logger()->error( 'Falha ao processar Merchant Order', [ 'id' => $merchant_order_id ] );
                    // Retorna uma resposta de erro
                    return new Response( 'Erro ao processar webhook', 500 );

                }
                // Retorna uma resposta de sucesso
                return new Response( 'Webhook processado com sucesso', 200 );
            }

            // Payment via type
            if ( $data[ 'type' ] == 'payment' || $data[ 'topic' ] == 'payment' ) {

                // Validação de autenticidade
                if ( !validateMercadoPagoAuthenticity( $data ) ) {
                    // Log de erro
                    logger()->error( 'Webhook não autorizado' );
                    // Retorna uma resposta de erro
                    return new Response( 'Não autorizado', 401 );
                }
                // Pega o ID do payment
                $payment_data_id = $data[ 'data_id' ] ?? $data[ 'id' ];
                // Pega o payment
                $payment = $this->getResponsePayment( $payment_data_id );
                // Lida com payment se for falso retorna erro
                if ( !$this->providerPlanService->handlePaymentMercadoPago( $payment ) ) {
                    // Log de erro
                    logger()->error( 'Falha ao processar pagamento', [ 'data_id' => $payment_data_id ] );
                    // Retorna uma resposta de erro
                    return new Response( 'Erro ao processar pagamento', 500 );
                }

                // Lida com atualização de plano de assinatura se for falso retorna erro
                if ( !$this->providerPlanService->updatePlanSubscription( $payment ) ) {

                    // Log de erro
                    logger()->error( 'Falha ao atualizar assinatura', [ 'data_id' => $payment_data_id ] );

                    // Retorna uma resposta de erro
                    return new Response( 'Erro ao atualizar assinatura', 500 );
                }

                // Retorna uma resposta de sucesso
                return new Response( 'Webhook processado com sucesso', 200 );
            }
            // Log de erro
            logger()->error( 'Tipo de webhook não reconhecido' );
            // Retorna uma resposta de erro
            return new Response( 'Tipo de webhook não reconhecido', 400 );

        } catch ( Exception $e ) {
            // Log de erro
            logger()->error( 'Erro no processamento', [ 
                'error'        => $e->getMessage(),
                'trace'        => $e->getTraceAsString(),
                'x_request_id' => $xRequestId ?? 'não encontrado'
            ] );
            // Retorna uma resposta de erro
            return new Response( 'Erro interno do servidor', 500 );
        }
    }

    /**
     * Handles the success payment response and renders the payment/success.twig template.
     *
     * This method is responsible for processing the successful payment response from the payment gateway.
     * It extracts the necessary payment information from the request data, such as the payment ID, plan name, and plan price.
     * If the required data is present, it renders the payment/success.twig template with the relevant information.
     * If any errors occur during the process, it redirects the user to the plans page with an error message.
     *
     * @return Response The rendered payment/success.twig template or a redirect response in case of errors.
     */
    public function success(): Response
    {
        // Obtem os dados da requisição ja sanitizados
        // $data = $this->request->all();
        // todo atualizar a lógica das outras functions retorno
        $externalReference = $this->request->get( 'external_reference' );

        $payment_id = $this->request->get( 'payment_id' );
        // Obtem o plan name
        $plan_name = $externalReference[ 'plan_name' ];
        // Obtem o plan price
        $plan_price = $externalReference[ 'plan_price' ];
        try {
            // Verifica se os dados estão presentes se não retorna erro
            if ( $payment_id == null || $plan_name == null || $plan_price == null ) {
                return $this->error();
            }

            // Limpa o last updated session provider
            Session::remove( 'checkPlan' );
            Session::remove( 'last_updated_session_provider' );

            // Renderiza a view de sucesso
            return new Response( $this->twig->env->render( 'pages/payment/success.twig', [ 
                'payment_id' => $payment_id,
                'plan_name'  => $plan_name,
                'plan_price' => $plan_price
            ] ) );

        } catch ( \Throwable $th ) {
            // Log de erro
            getDetailedErrorInfo( $th );
            // Retorna uma resposta de erro
            return Redirect::redirect( '/plans' )->withMessage( 'error', 'Falha na atualização do plano entre em contato com suporte!' );
        }

    }

    public function pending(): Response
    {
        $externalReference = $this->request->get( 'external_reference' );
        $payment_id        = $this->request->get( 'payment_id' );
        $plan_name         = $externalReference[ 'plan_name' ];
        $plan_price        = $externalReference[ 'plan_price' ];
        try {

            if ( $payment_id == null || $plan_name == null || $plan_price == null ) {
                return $this->error();
            }
            return new Response( $this->twig->env->render( 'pages/payment/pending.twig', [ 
                'payment_id' => $payment_id,
                'plan_name'  => $plan_name,
                'plan_price' => $plan_price
            ] ) );

        } catch ( \Throwable $th ) {
            getDetailedErrorInfo( $th );
            return Redirect::redirect( '/plans' )->withMessage( 'error', 'Falha na atualização do plano entre em contato com suporte!' );
        }
    }

    public function failure(): Response
    {
        $externalReference = $this->request->get( 'external_reference' );
        $payment_id        = $this->request->get( 'payment_id' );
        $plan_name         = $externalReference[ 'plan_name' ];
        $plan_price        = $externalReference[ 'plan_price' ];
        try {

            if ( $payment_id == null || $plan_name == null || $plan_price == null ) {
                return $this->error();
            }
            return new Response( $this->twig->env->render( 'pages/payment/failure.twig', [ 
                'payment_id' => $payment_id,
                'plan_name'  => $plan_name,
                'plan_price' => $plan_price
            ] ) );

        } catch ( \Throwable $th ) {
            getDetailedErrorInfo( $th );
            return Redirect::redirect( '/plans' )->withMessage( 'error', 'Falha na atualização do plano entre em contato com suporte!' );
        }
    }

    private function getPaymentInfo( $paymentId )
    {
        $this->authenticate();
        $client = new \MercadoPago\Client\Payment\PaymentClient();
        return $client->get( $paymentId );
    }

    private function getMerchantOrder( $merchantOrderId )
    {
        $this->authenticate();
        $client  = new \MercadoPago\Client\MerchantOrder\MerchantOrderClient();
        $payment = $client->get( $merchantOrderId );
        if ( $payment == null ) {
            return false;
        }
        return $payment;

    }

    private function getResponsePayment( $paymentId ): array
    {
        $get = $this->getPaymentInfo( $paymentId );

        $externalReference     = html_entity_decode( $get->external_reference ?? '' );
        $externalReferenceData = json_decode( $externalReference, true );

        $transactionDetails = [ 
            'payment_id'                => $get->id === 'null' ? null : $get->id,
            'status'                    => $get->status === 'null' ? null : $get->status,
            'payment_method'            => $get->payment_method_id === 'null' ? null : $get->payment_method_id,
            'user_id'                   => $externalReferenceData[ 'user_id' ] ?? null,
            'provider_id'               => $externalReferenceData[ 'provider_id' ] ?? null,
            'tenant_id'                 => $externalReferenceData[ 'tenant_id' ] ?? null,
            'plan_id'                   => $externalReferenceData[ 'plan_id' ] ?? null,
            'plan_name'                 => $externalReferenceData[ 'plan_name' ] ?? null,
            'plan_slug'                 => $externalReferenceData[ 'plan_slug' ] ?? null,
            'plan_price'                => $externalReferenceData[ 'plan_price' ] ?? null,
            'plan_subscription_id'      => $externalReferenceData[ 'plan_subscription_id' ] ?? null,
            'last_plan_subscription_id' => $externalReferenceData[ 'last_plan_subscription_id' ] ?? null,
            'transaction_amount'        => $get->transaction_amount === 'null' ? null : $get->transaction_amount,
            'transaction_date'          => new \DateTime(),
        ];
        return $transactionDetails;
    }

    private function getResponseMerchantOrder( $merchantOrderId ): array
    {
        $get = $this->getMerchantOrder( $merchantOrderId );

        $externalReference     = html_entity_decode( $get->external_reference ?? '' );
        $externalReferenceData = json_decode( $externalReference, true );

        $transactionDetails = [ 
            'merchant'                  => $get,
            'merchant_order_id'         => $get->id === 'null' ? null : $get->id,
            'status'                    => $get->status === 'null' ? null : $get->status,
            'order_status'              => $get->order_status === 'null' ? null : $get->order_status,
            'paid_amount'               => $get->paid_amount === 'null' ? null : $get->paid_amount,
            'user_id'                   => $externalReferenceData[ 'user_id' ] ?? null,
            'provider_id'               => $externalReferenceData[ 'provider_id' ] ?? null,
            'tenant_id'                 => $externalReferenceData[ 'tenant_id' ] ?? null,
            'plan_id'                   => $externalReferenceData[ 'plan_id' ] ?? null,
            'plan_name'                 => $externalReferenceData[ 'plan_name' ] ?? null,
            'plan_slug'                 => $externalReferenceData[ 'plan_slug' ] ?? null,
            'plan_price'                => $externalReferenceData[ 'plan_price' ] ?? null,
            'plan_subscription_id'      => $externalReferenceData[ 'plan_subscription_id' ] ?? null,
            'last_plan_subscription_id' => $externalReferenceData[ 'last_plan_subscription_id' ] ?? null,
            'transaction_date'          => convertDateLocale( $get->date_created, 'America/Sao_Paulo' )
        ];

        return $transactionDetails;
    }

    private function buildPreferenceData( PlanEntity $planSelected, string $planSlug, int $plan_subscription_id ): array
    {
        $checkPlan = Session::get( 'checkPlan' );

        // External reference como string JSON (recomendado MP)
        $externalReference = json_encode( [ 
            'plan_id'                   => $planSelected->id,
            'plan_name'                 => $planSelected->name,
            'plan_slug'                 => $planSlug,
            'plan_price'                => $planSelected->price,
            'user_id'                   => $this->authenticated->user_id,
            'provider_id'               => $this->authenticated->id,
            'tenant_id'                 => $this->authenticated->tenant_id,
            'plan_subscription_id'      => $plan_subscription_id,
            'last_plan_subscription_id' => $checkPlan->id,
        ] );

        // URL absoluta para webhook
        $webhookUrl = buildUrl( '/payment/webhook', true );

        return [ 
            'items'               => [ 
                [ 
                    'title'       => sprintf( 'Assinatura do Plano %s', ucfirst( $planSlug ) ),
                    'quantity'    => 1,
                    'currency_id' => 'BRL',
                    'unit_price'  => (float) $planSelected->price,
                    'description' => 'Assinatura recorrente mensal'
                ]
            ],
            'payer'               => [ 
                'first_name' => $this->authenticated->first_name,
                'last_name'  => $this->authenticated->last_name,
                'email'      => $this->authenticated->email,
            ],
            'payment_methods'     => [ 
                "excluded_payment_methods" => [],
                "installments"             => 12,
                "default_installments"     => 1
            ],
            'external_reference'  => $externalReference,
            'back_urls'           => [ 
                'success' => buildUrl( '/payment/success', true ),
                'failure' => buildUrl( '/payment/failure', true ),
                'pending' => buildUrl( '/payment/pending', true )
            ],
            'auto_return'         => 'approved',
            'notification_url'    => $webhookUrl,
            'notification_topics' => [ 'payment', 'merchant_order' ]
        ];
    }

    /**
     * @inheritDoc
     */
    public function activityLogger( int $tenant_id, int $user_id, string $action_type, string $entity_type, int $entity_id, string $description, array $metadata = [] )
    {
        $this->activityService->logActivity( $tenant_id, $user_id, $action_type, $entity_type, $entity_id, $description, $metadata );
    }

}
