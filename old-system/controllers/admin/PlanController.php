<?php

namespace app\controllers\admin;

use app\controllers\AbstractController;
use app\database\entitiesORM\PaymentMercadoPagoPlansEntity;
use app\database\entitiesORM\PlanSubscriptionEntity;
use app\database\models\PaymentMercadoPagoPlans;
use app\database\models\PlanSubscription;
use app\database\servicesORM\ActivityService;
use app\database\servicesORM\PaymentMercadoPagoPlanService;
use app\database\servicesORM\PlanService;
use core\dbal\EntityNotFound;
use core\library\Response;
use core\library\Twig;
use http\Redirect;
use http\Request;

class PlanController extends AbstractController
{
    public function __construct(
        protected Twig $twig,
        protected PlanService $planService,
        private PlanSubscription $planSubscription,
        private PaymentMercadoPagoPlans $paymentMercadoPagoPlans,
        protected PaymentMercadoPagoPlanService $paymentMercadoPagoPlanService,
        protected ActivityService $activityService,

        Request $request,
    ) {
        parent::__construct( $request );
    }

    /**
     * Displays the admin page for listing all plan subscriptions.
     *
     * @return Response
     */
    public function adminIndex(): Response
    {
        $subscriptions = $this->planSubscription->findAllWithDetails( 'active' );

        return new Response( $this->twig->env->render( 'pages/admin/plan/index.twig', [ 
            'subscriptions'   => $subscriptions,
            'page_title'      => 'Gerenciamento de Assinaturas (Ativas)',
            'is_history_page' => false,
        ] ) );
    }

    /**
     * Displays the details of a specific subscription for the admin.
     *
     * @param int $subscriptionId
     * @return Response
     */
    public function adminShow( int $subscriptionId ): Response
    {
        // Nota: O método `findSubscriptionWithDetailsById()` precisa ser criado no seu modelo `PlanSubscription`.
        $subscription = $this->planSubscription->findSubscriptionWithDetailsById( $subscriptionId );

        if ( $subscription instanceof EntityNotFound ) {
            return Redirect::redirect( "/admin/plans/subscription/show/" . $subscriptionId )->withMessage( 'error', 'Assinatura não encontrada.' );
        }

        $payment       = $this->paymentMercadoPagoPlans->getLastPaymentByPlanSubscription(
            $subscription->provider_id,
            $subscription->tenant_id,
            $subscription->id,
        );
        $payment_found = $payment instanceof EntityNotFound ? false : true;

        return new Response( $this->twig->env->render( 'pages/admin/plan/show.twig', [ 
            'subscription'  => $subscription,
            'payment'       => $payment,
            'payment_found' => $payment_found,

        ] ) );
    }

    /**
     * Displays the subscription history for a specific provider.
     *
     * @param int $providerId
     * @return Response
     */
    public function adminProviderHistory( int $providerId ): Response
    {
        $subscriptions = $this->planSubscription->findAllWithDetailsByProvider( $providerId );

        if ( empty( $subscriptions ) ) {
            return Redirect::back()->withMessage( 'info', 'Nenhum histórico de assinatura encontrado para este provedor.' );
        }

        return new Response( $this->twig->env->render( 'pages/admin/plan/index.twig', [ 
            'subscriptions'   => $subscriptions,
            'page_title'      => 'Histórico de Assinaturas de ' . $subscriptions[ 0 ][ 'provider_name' ],
            'is_history_page' => true,
        ] ) );
    }

    /**
     * Handles an admin request to cancel a pending subscription.
     *
     * @param int $subscriptionId
     * @return Redirect
     */
    public function adminCancelSubscription( int $subscriptionId ): Redirect
    {
        try {
            $subscription = $this->planSubscription->findBy( [ 'id' => $subscriptionId ] );
            if ( $subscription instanceof EntityNotFound ) {
                return Redirect::redirect( "/admin/plans/subscription/show/" . $subscriptionId )->withMessage( 'error', 'Assinatura não encontrada.' );
            }

            /** @var PlanSubscriptionEntity $subscription **/
            $payment = $this->paymentMercadoPagoPlans->getLastPaymentByPlanSubscription(
                $subscription->provider_id,
                $subscription->tenant_id,
                $subscription->id,
            );

            if ( $payment instanceof EntityNotFound ) {
                return Redirect::redirect( "/admin/plans/subscription/show/" . $subscriptionId )->withMessage( 'error', 'Nenhum pagamento associado encontrado para cancelar.' );
            }

            // Apenas cancela pagamentos pendentes
            /** @var PaymentMercadoPagoPlansEntity $payment */
            $cancellableStatuses = [ 'pending', 'authorized', 'in_process', 'in_mediation' ];
            if ( !in_array( $payment->status, $cancellableStatuses ) ) {
                return Redirect::redirect( "/admin/plans/subscription/show/" . $subscriptionId )->withMessage( 'error', "Não é possível cancelar um pagamento com status '{$payment->status}'. Considere um reembolso." );
            }

            // Cancela no Mercado Pago
            $wasCancelledOnMP = $this->paymentMercadoPagoPlanService->cancelPaymentOnMercadoPago( (int) $payment->payment_id );
            if ( !$wasCancelledOnMP ) {
                return Redirect::redirect( "/admin/plans/subscription/show/" . $subscriptionId )->withMessage( 'error', 'Falha ao cancelar o pagamento no Mercado Pago. Verifique os logs.' );
            }

            // Atualiza o status do pagamento local
            $this->paymentMercadoPagoPlanService->updatePaymentStatus( $payment->payment_id, 'cancelled', $subscription->tenant_id );

            // Atualiza o status da assinatura local
            $this->planService->updateStatusCancelled( $subscription->tenant_id, $subscription->provider_id, $subscription->id );

            // Registra a atividade
            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'plan_subscription_cancelled_admin',
                'plan_subscription',
                $subscription->id,
                "Assinatura #{$subscription->id} cancelada pelo administrador.",
                [ 'payment_id' => $payment->payment_id ],
            );

            return Redirect::redirect( "/admin/plans/subscription/show/" . $subscriptionId )->withMessage( 'success', 'Assinatura e pagamento pendente cancelados com sucesso.' );

        } catch ( \Throwable $e ) {
            getDetailedErrorInfo( $e );

            return Redirect::redirect( "/admin/plans/subscription/show/" . $subscriptionId )->withMessage( 'error', 'Ocorreu um erro inesperado ao cancelar a assinatura.' );
        }
    }

    /**
     * Handles an admin request to refund an approved subscription.
     *
     * @param int $subscriptionId
     * @return Redirect
     */
    public function adminRefundSubscription( int $subscriptionId ): Redirect
    {
        try {
            $subscription = $this->planSubscription->findBy( [ 'id' => $subscriptionId ] );
            if ( $subscription instanceof EntityNotFound ) {
                return Redirect::redirect( "/admin/plans/subscription/show/" . $subscriptionId )->withMessage( 'error', 'Assinatura não encontrada.' );
            }

            /** @var PlanSubscriptionEntity $subscription **/
            $payment = $this->paymentMercadoPagoPlans->getLastPaymentByPlanSubscription(
                $subscription->provider_id,
                $subscription->tenant_id,
                $subscription->id,
            );

            if ( $payment instanceof EntityNotFound ) {
                return Redirect::redirect( "/admin/plans/subscription/show/" . $subscriptionId )->withMessage( 'error', 'Nenhum pagamento associado encontrado para reembolsar.' );
            }

            // Apenas reembolsa pagamentos aprovados
            /** @var PaymentMercadoPagoPlansEntity $payment */
            if ( $payment->status !== 'approved' ) {
                return Redirect::redirect( "/admin/plans/subscription/show/" . $subscriptionId )->withMessage( 'error', "Não é possível reembolsar um pagamento com status '{$payment->status}'." );
            }

            // Reembolsa no Mercado Pago
            $wasRefundedOnMP = $this->paymentMercadoPagoPlanService->refundPaymentOnMercadoPago( (int) $payment->payment_id );
            if ( !$wasRefundedOnMP ) {
                return Redirect::redirect( "/admin/plans/subscription/show/" . $subscriptionId )->withMessage( 'error', 'Falha ao processar o reembolso no Mercado Pago. Verifique os logs.' );
            }

            // Atualiza o status do pagamento local
            $this->paymentMercadoPagoPlanService->updatePaymentStatus( $payment->payment_id, 'refunded', $subscription->tenant_id );

            // Atualiza o status da assinatura local para 'cancelled'
            $this->planService->updateStatusCancelled( $subscription->tenant_id, $subscription->provider_id, $subscription->id );

            // Registra a atividade
            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'plan_subscription_refunded_admin',
                'plan_subscription',
                $subscription->id,
                "Assinatura #{$subscription->id} reembolsada pelo administrador.",
                [ 'payment_id' => $payment->payment_id ],
            );

            return Redirect::redirect( "/admin/plans/subscription/show/" . $subscriptionId )->withMessage( 'success', 'Pagamento reembolsado e assinatura cancelada com sucesso.' );

        } catch ( \Throwable $e ) {
            getDetailedErrorInfo( $e );

            return Redirect::redirect( "/admin/plans/subscription/show/" . $subscriptionId )->withMessage( 'error', 'Ocorreu um erro inesperado ao reembolsar a assinatura.' );
        }
    }

    public function activityLogger( int $tenant_id, int $user_id, string $action_type, string $entity_type, int $entity_id, string $description, array $metadata = [] ): void
    {
        $this->activityService->logActivity( $tenant_id, $user_id, $action_type, $entity_type, $entity_id, $description, $metadata );
    }

}
