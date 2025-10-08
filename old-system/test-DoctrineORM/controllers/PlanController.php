<?php

namespace app\Controllers;

use app\controllers\AbstractController;
use app\database\entities\PlansWithPlanSubscriptionEntity;
use app\database\entitiesORM\PaymentMercadoPagoPlansEntity;
use app\database\models\PaymentMercadoPagoPlans;
use app\database\models\Plan;
use app\database\models\PlanSubscription;
use app\database\servicesORM\ActivityService;
use app\database\servicesORM\PaymentMercadoPagoPlanService;
use app\database\servicesORM\PlanService;
use app\request\PaymentPlanRequest;
use core\dbal\EntityNotFound;
use core\library\Response;
use core\library\Session;
use core\library\Twig;
use http\Redirect;
use http\Request;

class PlanController extends AbstractController
{
    public function __construct(
        protected Twig $twig,
        private Plan $plan,
        protected PlanService $planService,
        private PlanSubscription $planSubscription,
        private PaymentMercadoPagoPlans $paymentMercadoPagoPlans,
        protected PaymentMercadoPagoPlanService $paymentMercadoPagoPlanService,
        protected ActivityService $activityService,

        Request $request,
    ) {
        parent::__construct( $request );
    }

    public function index(): Response
    {
        $plans       = $this->plan->findActivePlans();
        $pendingPlan = $this->planSubscription->getProviderPlan(
            $this->authenticated->id,
            $this->authenticated->tenant_id,
            'pending',
        );
        Session::set( "checkPlanPending", $pendingPlan );

        return new Response( $this->twig->env->render( 'pages/plan/index.twig', [ 'plans' => $plans ] ) );
    }

    public function redirectToPayment(): Response
    {
        try {
            // Valida a requisição de pagamento
            $validated = PaymentPlanRequest::validate( $this->request );
            // Se a validação falhar, redireciona para a rota de erro
            if ( !$validated ) {
                return Redirect::redirect( '/plans/error' )->withMessage( 'message', 'Dados inválidos' );
            }
            $planSlug = $this->request->get( 'planSlug' );
            $response = $this->planService->createSubscription(
                $planSlug,
            );

            if ( !$response[ 'success' ] ) {
                return Redirect::redirect( "/plans" )->withMessage( 'error', $response[ 'message' ] );
            }
            $planSubscriptionAlreadyExists = $response[ 'planSubscriptionAlreadyExists' ] ?? false;
            if ( $planSubscriptionAlreadyExists ) {
                return Redirect::redirect( "/plans" )->withMessage( 'error', $response[ 'message' ] );
            }
            if ( isset( $response[ "createOrUpdateOrPendingPlanSubscription" ] ) ) {
                $this->activityLogger(
                    $this->authenticated->tenant_id,
                    $this->authenticated->user_id,
                    isset( $response[ "createOrUpdateOrPendingPlanSubscription" ][ "planSubscriptionCreated" ] ) ? 'plan_subscription_created' : 'plan_subscription_updated',
                    'plan_subscription',
                    $response[ "createOrUpdateOrPendingPlanSubscription" ][ "planSubscriptionId" ],
                    $response[ 'message' ],
                    $response[ "createOrUpdateOrPendingPlanSubscription" ],
                );
            }

            if ( $planSlug === 'free' ) {
                // Plano gratuito ativado com sucesso
                return Redirect::redirect( "/provider" )
                    ->withMessage( 'success', 'Plano gratuito ativado com sucesso!' );
            }

            return new Response(
                $this->twig->env->render( 'pages/payment/redirect.twig', [ 'payment_url' => $response[ 'payment_url' ] ] ),
            );

        } catch ( \RuntimeException $e ) {
            return Redirect::redirect( "/plans" )
                ->withMessage( 'error', $e->getMessage() );
        }
    }

    /**
     * Cancela a assinatura de plano pendente do usuário autenticado.
     *
     * @return Redirect
     */
    public function cancelPendingSubscription(): Redirect
    {
        try {
            $pendingSubscription = $this->planSubscription->getProviderPlan(
                $this->authenticated->id,
                $this->authenticated->tenant_id,
                'pending',
            );

            if ( $pendingSubscription instanceof EntityNotFound ) {
                return Redirect::redirect( '/plans' )->withMessage( 'info', 'Nenhuma assinatura pendente encontrada para cancelar.' );
            }

            // Tenta cancelar o pagamento pendente associado no Mercado Pago primeiro.
            /** @var PlansWithPlanSubscriptionEntity $pendingSubscription */
            $mercadoPagoPayment = $this->paymentMercadoPagoPlans->getLastPaymentByPlanSubscription(
                $this->authenticated->id,
                $this->authenticated->tenant_id,
                $pendingSubscription->id,
            );

            $mercadoPagoPaymentStatuses = [ 'pending', 'authorized', 'in_process', 'in_mediation' ];
            /** @var PaymentMercadoPagoPlansEntity $mercadoPagoPayment */
            if ( $mercadoPagoPayment[ 'success' ] && in_array( $mercadoPagoPayment[ 'data' ]->status, $mercadoPagoPaymentStatuses ) ) {
                $mercadoPagoPayment = $mercadoPagoPayment[ 'data' ];

                $wasCancelledOnMP = $this->paymentMercadoPagoPlanService->cancelPaymentOnMercadoPago( (int) $mercadoPagoPayment->payment_id );
                if ( $wasCancelledOnMP ) {
                    // Atualiza também o status do pagamento no nosso banco de dados local.
                    $response = $this->paymentMercadoPagoPlanService->updatePaymentStatus(
                        $mercadoPagoPayment->payment_id,
                        'cancelled',
                        $this->authenticated->tenant_id,
                    );
                    if ( !$response[ 'success' ] ) {
                        logger()->error( "Erro ao atualizar o status do pagamento {$mercadoPagoPayment->payment_id} no banco de dados local: {$response[ 'message' ]}" );

                        return Redirect::redirect( '/provider' )->withMessage( 'error', 'Não foi possível cancelar a assinatura pendente. Tente novamente.' );
                    }
                    $response = $this->planService->updateStatusCancelled(
                        $this->authenticated->tenant_id,
                        $this->authenticated->id,
                        $pendingSubscription->id,
                    );

                    if ( $response[ 'status' ] === 'success' ) {
                        $this->activityLogger(
                            $this->authenticated->tenant_id,
                            $this->authenticated->user_id,
                            'plan_subscription_cancelled',
                            'plan_subscription',
                            $pendingSubscription->id,
                            "Assinatura pendente para o plano {$pendingSubscription->name} foi cancelada pelo usuário.",
                            [ 'subscription_id' => $pendingSubscription->id ],
                        );

                        return Redirect::redirect( '/plans' )->withMessage( 'success', 'Sua assinatura pendente foi cancelada. Agora você pode escolher um novo plano.' );
                    }
                } else {
                    // Apenas registra um aviso. O cancelamento local é mais importante para a UX imediata.
                    logger()->warning( "Falha ao cancelar o pagamento {$mercadoPagoPayment->payment_id} no Mercado Pago. Tente novamente ou entre em contato com o suporte." );
                }
            }

            return Redirect::redirect( '/plans' )->withMessage( 'error', 'Não foi possível cancelar a assinatura pendente. Tente novamente.' );

        } catch ( \Throwable $e ) {
            getDetailedErrorInfo( $e );

            return Redirect::redirect( '/plans' )->withMessage( 'error', 'Ocorreu um erro inesperado. Entre em contato com o suporte.' );
        }
    }

    public function status(): Response
    {
        try {
            $pendingSubscription = $this->planSubscription->getProviderPlan(
                $this->authenticated->id,
                $this->authenticated->tenant_id,
                'pending',
            );

            if ( !$pendingSubscription[ 'success' ] ) {
                return Redirect::redirect( '/plans' )->withMessage( 'info', 'Nenhuma assinatura pendente encontrada.' );
            }
            $pendingSubscription = $pendingSubscription[ 'data' ];

            /** @var PlansWithPlanSubscriptionEntity $pendingSubscription */
            $localPayment = $this->paymentMercadoPagoPlans->getLastPaymentByPlanSubscription(
                $this->authenticated->id,
                $this->authenticated->tenant_id,
                $pendingSubscription->id,
            );

            if ( !$localPayment[ 'success' ] ) {
                // Se não há registro de pagamento, significa que o usuário nunca iniciou o processo.
                // Renderizamos a página de status com um estado que permite iniciar o pagamento.
                $fakePayment = (object) [ 'status' => 'not_started' ]; // Simula um pagamento cancelado para mostrar o botão de "Tentar Pagar Novamente".

                return new Response( $this->twig->env->render( 'pages/plan/status.twig', [ 
                    'subscription' => $pendingSubscription,
                    'payment'      => $fakePayment,
                ] ) );
            }

            // Busca os dados mais recentes do pagamento na API do Mercado Pago
            /** @var PaymentMercadoPagoPlansEntity $localPayment  */
            $localPayment       = $localPayment[ 'data' ];
            $mercadoPagoPayment = $this->paymentMercadoPagoPlanService->getPaymentFromMercadoPagoAPI( (int) $localPayment->payment_id );

            if ( !$mercadoPagoPayment ) {
                return Redirect::redirect( '/plans' )->withMessage( 'error', 'Não foi possível obter os detalhes do pagamento. Tente novamente.' );
            }

            return new Response( $this->twig->env->render( 'pages/plan/status.twig', [ 
                'subscription' => $pendingSubscription,
                'payment'      => $mercadoPagoPayment,
                'localPayment' => $localPayment,
            ] ) );

        } catch ( \Throwable $e ) {
            getDetailedErrorInfo( $e );

            return Redirect::redirect( '/plans' )->withMessage( 'error', 'Ocorreu um erro inesperado ao verificar o status do pagamento.' );
        }
    }

    public function paymentStatus(): Response
    {
        $status            = $this->request->get( 'status' );
        $externalReference = $this->request->get( 'external_reference' ); // Este é o nosso public_hash
        $data              = $this->request->all();
        if ( empty( $externalReference ) ) {
            return $this->error();
        }

        $plan = $this->plan->getActivePlanBySlug( $externalReference[ 'plan_slug' ] );

        if ( $plan instanceof EntityNotFound ) {
            return Redirect::redirect( '/not-found' )->withMessage( 'error', 'Plano não encontrada.' );
        }

        if ( $status === 'approved' ) {
            // Limpa o last updated session provider
            Session::remove( 'checkPlan' );
            Session::remove( 'last_updated_session_provider' );
        }

        // 3. Define messages based on payment status
        $statusData = match ( $status ) {
            'approved'              => [ 
                'status'               => 'success',
                'message'              => 'Pagamento Aprovado!',
                'details'              => 'Obrigado! Seu pagamento foi processado com sucesso, em breve seu plano será ativado.',
            ],
            'pending', 'in_process' => [ 
                'status'  => 'pending',
                'message' => 'Pagamento Pendente',
                'details' => 'Seu pagamento está sendo processado. Você será notificado assim que for concluído.',
            ],
            default                 => [ // 'failure', 'rejected', 'cancelled'
                'status'                  => 'failure',
                'message'                 => 'Pagamento Recusado',
                'details'                 => 'Houve um problema ao processar seu pagamento. Por favor, tente novamente ou contate seu banco.',
            ],
        };

        // 4. Render the status page with dynamic data
        return new Response(
            $this->twig->env->render( 'pages/public/plan/status.twig', array_merge( $statusData, [ 'plan' => $plan, 'payment_id' => $data[ 'payment_id' ] ] ) ),
        );
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

    public function activityLogger( int $tenant_id, int $user_id, string $action_type, string $entity_type, int $entity_id, string $description, array $metadata = [] ): void
    {
        $this->activityService->logActivity( $tenant_id, $user_id, $action_type, $entity_type, $entity_id, $description, $metadata );
    }

}