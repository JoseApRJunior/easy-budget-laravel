<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PaymentService;
use App\Services\ActivityService;
use App\Services\WebhookService;
use App\Services\ActivityService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Controlador para processamento de webhooks.
 * Implementa endpoints de webhook para pagamentos, integrações externas e notificações.
 * Migração do sistema legacy app/controllers/WebhookController.php.
 *
 * @package App\Http\Controllers
 * @author IA
 */
class WebhookController extends BaseController
{
    /**
     * @var WebhookService
     */
    protected WebhookService $webhookService;

    /**
     * @var PaymentService
     */
    protected PaymentService $paymentService;

    /**
     * Construtor da classe WebhookController.
     *
     * @param WebhookService $webhookService
     * @param PaymentService $paymentService
     */
    public function __construct(
        WebhookService $webhookService,
        PaymentService $paymentService,
    ) {
        parent::__construct($activityService);
        $this->webhookService = $webhookService;
        $this->paymentService = $paymentService;
    }

    /**
     * Endpoint principal para receber webhooks.
     * Valida assinatura e roteia para o handler apropriado.
     *
     * @param Request $request
     * @return Response|JsonResponse
     */
    public function handle( Request $request ): Response|JsonResponse
    {
        try {
            $this->validateRequest( $request );

            $payload = $request->all();
            $source  = $this->identifyWebhookSource( $request );

            Log::info( 'Webhook recebido', [
                'source'       => $source,
                'payload_keys' => array_keys( $payload ),
                'ip'           => $request->ip(),
                'user_agent'   => $request->userAgent()
            ] );

            $this->logActivity(
                action: 'receive_webhook',
                entity: 'webhooks',
                metadata: [
                    'source'       => $source,
                    'payload_keys' => array_keys( $payload ),
                    'ip_address'   => $request->ip(),
                    'user_agent'   => substr( $request->userAgent(), 0, 100 )
                ],
            );

            // Roteamento baseado na origem
            switch ( $source ) {
                case 'mercado_pago':
                    return $this->handleMercadoPago( $payload );
                case 'stripe':
                    return $this->handleStripe( $payload );
                case 'paypal':
                    return $this->handlePaypal( $payload );
                case 'internal':
                    return $this->handleInternal( $payload );
                case 'generic':
                    return $this->handleGeneric( $payload );
                default:
                    return $this->jsonError(
                        message: 'Origem do webhook não reconhecida.',
                        statusCode: 400,
                    );
            }

        } catch ( Exception $e ) {
            Log::error( 'Erro no webhook', [
                'source'  => $request->header( 'Webhook-Source', 'unknown' ),
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
                'payload' => $request->all()
            ] );

            return $this->jsonError(
                message: 'Erro interno no processamento do webhook.',
                statusCode: 500,
            );
        }
    }

    /**
     * Valida a requisição de webhook.
     *
     * @param Request $request
     * @return void
     * @throws Exception
     */
    private function validateRequest( Request $request ): void
    {
        $validator = Validator::make( $request->all(), [
            'event'     => 'required|string|max:100',
            'data'      => 'required|array',
            'timestamp' => 'nullable|date'
        ] );

        if ( $validator->fails() ) {
            Log::warning( 'Payload de webhook inválido', [
                'errors' => $validator->errors()->toArray(),
                'ip'     => $request->ip()
            ] );

            throw new Exception( 'Payload de webhook inválido.', 400 );
        }

        // Verificar assinatura se fornecida
        $signature = $request->header( 'X-Webhook-Signature' );
        if ( $signature ) {
            $this->validateSignature( $request, $signature );
        }

        // Verificar rate limiting
        $this->validateRateLimit( $request );
    }

    /**
     * Identifica a origem do webhook.
     *
     * @param Request $request
     * @return string
     */
    private function identifyWebhookSource( Request $request ): string
    {
        $headers = [
            'Webhook-Source'         => $request->header( 'Webhook-Source' ),
            'X-MP-Webhook'           => $request->header( 'X-MP-Webhook' ),
            'Stripe-Signature'       => $request->header( 'Stripe-Signature' ),
            'PayPal-Transmission-Id' => $request->header( 'PayPal-Transmission-Id' ),
            'X-PayPal-Signature'     => $request->header( 'X-PayPal-Signature' )
        ];

        foreach ( $headers as $key => $value ) {
            if ( $value ) {
                $sourceMap = [
                    'mercado_pago' => [ 'X-MP-Webhook' ],
                    'stripe'       => [ 'Stripe-Signature' ],
                    'paypal'       => [ 'PayPal-Transmission-Id', 'X-PayPal-Signature' ],
                    'internal'     => [ 'Webhook-Source' => 'internal' ]
                ];

                foreach ( $sourceMap as $source => $headers ) {
                    if ( in_array( $key, $headers ) || ( $key === 'Webhook-Source' && $value === 'internal' ) ) {
                        return $source;
                    }
                }
            }
        }

        return 'generic';
    }

    /**
     * Valida assinatura do webhook.
     *
     * @param Request $request
     * @param string $signature
     * @return void
     * @throws Exception
     */
    private function validateSignature( Request $request, string $signature ): void
    {
        $source  = $this->identifyWebhookSource( $request );
        $payload = $request->getContent();
        $secret  = $this->webhookService->getWebhookSecret( $source );

        if ( !$secret ) {
            Log::warning( 'Segredo de webhook não configurado', [ 'source' => $source ] );
            throw new Exception( 'Segredo de webhook não configurado.', 500 );
        }

        $expectedSignature = hash_hmac( 'sha256', $payload, $secret );

        if ( !hash_equals( $signature, $expectedSignature ) ) {
            Log::error( 'Assinatura de webhook inválida', [
                'source'             => $source,
                'received_signature' => substr( $signature, 0, 20 ) . '...',
                'ip'                 => $request->ip()
            ] );

            throw new Exception( 'Assinatura de webhook inválida.', 401 );
        }
    }

    /**
     * Valida rate limiting do webhook.
     *
     * @param Request $request
     * @return void
     * @throws Exception
     */
    private function validateRateLimit( Request $request ): void
    {
        $ip           = $request->ip();
        $key          = 'webhook_rate_limit:' . $ip;
        $attempts     = session( $key, 0 );
        $maxAttempts  = config( 'webhook.rate_limit', 10 );
        $decayMinutes = config( 'webhook.rate_limit_decay', 15 );

        if ( $attempts >= $maxAttempts ) {
            Log::warning( 'Rate limit excedido para webhook', [
                'ip'           => $ip,
                'attempts'     => $attempts,
                'max_attempts' => $maxAttempts
            ] );

            throw new Exception( 'Muitas tentativas. Tente novamente mais tarde.', 429 );
        }

        session( [ $key => $attempts + 1 ] );
        session()->forget( $key . ':lockout' );
        session()->save();
    }

    /**
     * Processa webhook do Mercado Pago.
     *
     * @param array $payload
     * @return JsonResponse
     */
    private function handleMercadoPago( array $payload ): JsonResponse
    {
        $event = $payload[ 'event' ] ?? 'unknown';
        $data  = $payload[ 'data' ] ?? [];

        Log::info( 'Processando webhook Mercado Pago', [
            'event'     => $event,
            'data_keys' => array_keys( $data )
        ] );

        switch ( $event ) {
            case 'payment.created':
                return $this->processMercadoPagoPayment( $data, 'created' );

            case 'payment.updated':
                return $this->processMercadoPagoPayment( $data, 'updated' );

            case 'payment.cancelled':
                return $this->processMercadoPagoPayment( $data, 'cancelled' );

            case 'merchant_order.updated':
                return $this->processMercadoPagoOrder( $data );

            case 'subscription.updated':
                return $this->processMercadoPagoSubscription( $data );

            default:
                Log::info( 'Evento Mercado Pago não implementado', [ 'event' => $event ] );
                return $this->jsonSuccess( [ 'message' => 'Evento recebido, mas não processado' ] );
        }
    }

    /**
     * Processa pagamento do Mercado Pago.
     *
     * @param array $data
     * @param string $status
     * @return JsonResponse
     */
    private function processMercadoPagoPayment( array $data, string $status ): JsonResponse
    {
        $paymentId = $data[ 'id' ] ?? null;
        $orderId   = $data[ 'order_id' ] ?? null;
        $status    = $data[ 'status' ] ?? $status;
        $amount    = $data[ 'transaction_amount' ] ?? 0;
        $currency  = $data[ 'currency_id' ] ?? 'BRL';

        if ( !$paymentId || !$orderId ) {
            Log::warning( 'Dados incompletos no webhook de pagamento Mercado Pago', $data );
            return $this->jsonError( 'Dados incompletos', 400 );
        }

        // Buscar e atualizar pagamento
        $payment = $this->paymentService->getPaymentByExternalId( $paymentId, 'mercado_pago' );

        if ( !$payment ) {
            Log::info( 'Pagamento não encontrado no sistema', [ 'payment_id' => $paymentId ] );
            return $this->jsonSuccess( [ 'message' => 'Pagamento não encontrado, será criado' ] );
        }

        $updateData = [
            'external_status'   => $status,
            'status_updated_at' => now(),
            'metadata'          => json_encode( [
                'webhook_received_at' => now()->toISOString(),
                'raw_data'            => $data
            ] )
        ];

        $result = $this->paymentService->updatePaymentStatus(
            paymentId: $payment->id,
            status: $status,
            gateway: 'mercado_pago',
            metadata: $updateData,
        );

        if ( $result->isSuccess() ) {
            $this->logActivity(
                action: 'webhook_payment_' . $status,
                entity: 'payments',
                entityId: $payment->id,
                metadata: [
                    'gateway'     => 'mercado_pago',
                    'amount'      => $amount,
                    'currency'    => $currency,
                    'external_id' => $paymentId
                ],
            );

            // Atualizar fatura relacionada se aplicável
            if ( $payment->invoice_id ) {
                $this->paymentService->syncInvoicePaymentStatus( $payment->invoice_id, $status );
            }

            // Notificar se necessário
            $this->webhookService->processPaymentNotification( $payment->id, $status );
        }

        return $this->jsonSuccess( [
            'message'    => 'Pagamento processado',
            'payment_id' => $paymentId,
            'status'     => $status
        ] );
    }

    /**
     * Processa ordem do Mercado Pago.
     *
     * @param array $data
     * @return JsonResponse
     */
    private function processMercadoPagoOrder( array $data ): JsonResponse
    {
        $orderId = $data[ 'id' ] ?? null;

        if ( !$orderId ) {
            Log::warning( 'ID da ordem não encontrado no webhook Mercado Pago', $data );
            return $this->jsonError( 'ID da ordem não encontrado', 400 );
        }

        // Buscar ordem no sistema
        $order = $this->paymentService->getOrderByExternalId( $orderId, 'mercado_pago' );

        if ( !$order ) {
            Log::info( 'Ordem não encontrada no sistema', [ 'order_id' => $orderId ] );
            return $this->jsonSuccess( [ 'message' => 'Ordem não encontrada, será criada' ] );
        }

        // Atualizar status da ordem
        $status = $data[ 'status' ] ?? 'pending';
        $result = $this->paymentService->updateOrderStatus(
            orderId: $order->id,
            status: $status,
            metadata: [ 'webhook_received_at' => now()->toISOString() ],
        );

        if ( $result->isSuccess() ) {
            $this->logActivity(
                action: 'webhook_order_' . $status,
                entity: 'orders',
                entityId: $order->id,
                metadata: [ 'gateway' => 'mercado_pago' ],
            );
        }

        return $this->jsonSuccess( [
            'message'  => 'Ordem processada',
            'order_id' => $orderId,
            'status'   => $status
        ] );
    }

    /**
     * Processa assinatura do Mercado Pago.
     *
     * @param array $data
     * @return JsonResponse
     */
    private function processMercadoPagoSubscription( array $data ): JsonResponse
    {
        $subscriptionId = $data[ 'id' ] ?? null;

        if ( !$subscriptionId ) {
            Log::warning( 'ID da assinatura não encontrado no webhook Mercado Pago', $data );
            return $this->jsonError( 'ID da assinatura não encontrado', 400 );
        }

        $subscription = $this->paymentService->getSubscriptionByExternalId( $subscriptionId, 'mercado_pago' );

        if ( !$subscription ) {
            Log::info( 'Assinatura não encontrada no sistema', [ 'subscription_id' => $subscriptionId ] );
            return $this->jsonSuccess( [ 'message' => 'Assinatura não encontrada' ] );
        }

        $event  = $data[ 'event' ] ?? 'unknown';
        $status = $this->mapSubscriptionEvent( $event );

        $result = $this->paymentService->updateSubscriptionStatus(
            subscriptionId: $subscription->id,
            status: $status,
            metadata: [ 'webhook_received_at' => now()->toISOString() ],
        );

        if ( $result->isSuccess() ) {
            $this->logActivity(
                action: 'webhook_subscription_' . $status,
                entity: 'subscriptions',
                entityId: $subscription->id,
                metadata: [ 'gateway' => 'mercado_pago', 'event' => $event ],
            );
        }

        return $this->jsonSuccess( [
            'message'         => 'Assinatura processada',
            'subscription_id' => $subscriptionId,
            'status'          => $status
        ] );
    }

    /**
     * Mapeia eventos de assinatura do Mercado Pago para status internos.
     *
     * @param string $event
     * @return string
     */
    private function mapSubscriptionEvent( string $event ): string
    {
        $mapping = [
            'subscriber.created'     => 'pending',
            'subscription.created'   => 'active',
            'subscription.updated'   => 'active',
            'subscription.cancelled' => 'cancelled',
            'subscription.paused'    => 'paused',
            'subscription.resumed'   => 'active'
        ];

        return $mapping[ $event ] ?? 'pending';
    }

    /**
     * Processa webhook do Stripe.
     *
     * @param array $payload
     * @return JsonResponse
     */
    private function handleStripe( array $payload ): JsonResponse
    {
        $event = $payload[ 'type' ] ?? 'unknown';
        $data  = $payload[ 'data' ][ 'object' ] ?? [];

        Log::info( 'Processando webhook Stripe', [ 'event' => $event ] );

        switch ( $event ) {
            case 'payment_intent.succeeded':
                return $this->processStripePayment( $data, 'succeeded' );

            case 'payment_intent.payment_failed':
                return $this->processStripePayment( $data, 'failed' );

            case 'invoice.payment_succeeded':
                return $this->processStripeInvoice( $data );

            case 'customer.subscription.created':
                return $this->processStripeSubscription( $data );

            case 'customer.subscription.updated':
                return $this->processStripeSubscription( $data );

            case 'customer.subscription.deleted':
                return $this->processStripeSubscription( $data );

            default:
                Log::info( 'Evento Stripe não implementado', [ 'event' => $event ] );
                return $this->jsonSuccess( [ 'message' => 'Evento Stripe recebido' ] );
        }
    }

    /**
     * Processa pagamento do Stripe.
     *
     * @param array $data
     * @param string $status
     * @return JsonResponse
     */
    private function processStripePayment( array $data, string $status ): JsonResponse
    {
        $paymentIntentId = $data[ 'id' ] ?? null;

        if ( !$paymentIntentId ) {
            Log::warning( 'ID do Payment Intent não encontrado no webhook Stripe', $data );
            return $this->jsonError( 'ID do pagamento não encontrado', 400 );
        }

        $payment = $this->paymentService->getPaymentByExternalId( $paymentIntentId, 'stripe' );

        if ( !$payment ) {
            Log::info( 'Pagamento Stripe não encontrado no sistema', [ 'payment_intent_id' => $paymentIntentId ] );
            return $this->jsonSuccess( [ 'message' => 'Pagamento não encontrado' ] );
        }

        $updateData = [
            'external_status'   => $status,
            'status_updated_at' => now(),
            'metadata'          => json_encode( [
                'webhook_received_at' => now()->toISOString(),
                'raw_data'            => $data
            ] )
        ];

        $result = $this->paymentService->updatePaymentStatus(
            paymentId: $payment->id,
            status: $status,
            gateway: 'stripe',
            metadata: $updateData,
        );

        if ( $result->isSuccess() ) {
            $this->logActivity(
                action: 'webhook_stripe_payment_' . $status,
                entity: 'payments',
                entityId: $payment->id,
                metadata: [ 'gateway' => 'stripe' ],
            );

            if ( $payment->invoice_id ) {
                $this->paymentService->syncInvoicePaymentStatus( $payment->invoice_id, $status );
            }
        }

        return $this->jsonSuccess( [
            'message'           => 'Pagamento Stripe processado',
            'payment_intent_id' => $paymentIntentId,
            'status'            => $status
        ] );
    }

    /**
     * Processa fatura do Stripe.
     *
     * @param array $data
     * @return JsonResponse
     */
    private function processStripeInvoice( array $data ): JsonResponse
    {
        $invoiceId = $data[ 'id' ] ?? null;

        if ( !$invoiceId ) {
            Log::warning( 'ID da fatura não encontrado no webhook Stripe', $data );
            return $this->jsonError( 'ID da fatura não encontrado', 400 );
        }

        $invoice = $this->paymentService->getInvoiceByExternalId( $invoiceId, 'stripe' );

        if ( !$invoice ) {
            Log::info( 'Fatura Stripe não encontrada no sistema', [ 'invoice_id' => $invoiceId ] );
            return $this->jsonSuccess( [ 'message' => 'Fatura não encontrada' ] );
        }

        $status = $data[ 'status' ] ?? 'pending';
        $result = $this->paymentService->updateInvoiceStatusFromStripe(
            invoiceId: $invoice->id,
            stripeStatus: $status,
            stripeData: $data,
        );

        if ( $result->isSuccess() ) {
            $this->logActivity(
                action: 'webhook_stripe_invoice_' . $status,
                entity: 'invoices',
                entityId: $invoice->id,
                metadata: [ 'gateway' => 'stripe' ],
            );
        }

        return $this->jsonSuccess( [
            'message'    => 'Fatura Stripe processada',
            'invoice_id' => $invoiceId,
            'status'     => $status
        ] );
    }

    /**
     * Processa assinatura do Stripe.
     *
     * @param array $data
     * @return JsonResponse
     */
    private function processStripeSubscription( array $data ): JsonResponse
    {
        $subscriptionId = $data[ 'id' ] ?? null;

        if ( !$subscriptionId ) {
            Log::warning( 'ID da assinatura não encontrado no webhook Stripe', $data );
            return $this->jsonError( 'ID da assinatura não encontrado', 400 );
        }

        $subscription = $this->paymentService->getSubscriptionByExternalId( $subscriptionId, 'stripe' );

        if ( !$subscription ) {
            Log::info( 'Assinatura Stripe não encontrada no sistema', [ 'subscription_id' => $subscriptionId ] );
            return $this->jsonSuccess( [ 'message' => 'Assinatura não encontrada' ] );
        }

        $status = $this->mapStripeSubscriptionStatus( $data[ 'status' ] ?? 'active' );

        $result = $this->paymentService->updateSubscriptionStatus(
            subscriptionId: $subscription->id,
            status: $status,
            metadata: [ 'webhook_received_at' => now()->toISOString() ],
        );

        if ( $result->isSuccess() ) {
            $this->logActivity(
                action: 'webhook_stripe_subscription_' . $status,
                entity: 'subscriptions',
                entityId: $subscription->id,
                metadata: [ 'gateway' => 'stripe' ],
            );
        }

        return $this->jsonSuccess( [
            'message'         => 'Assinatura Stripe processada',
            'subscription_id' => $subscriptionId,
            'status'          => $status
        ] );
    }

    /**
     * Mapeia status de assinatura do Stripe para status internos.
     *
     * @param string $stripeStatus
     * @return string
     */
    private function mapStripeSubscriptionStatus( string $stripeStatus ): string
    {
        $mapping = [
            'trialing' => 'active',
            'active'   => 'active',
            'past_due' => 'overdue',
            'canceled' => 'cancelled',
            'unpaid'   => 'pending',
            'paused'   => 'paused'
        ];

        return $mapping[ $stripeStatus ] ?? 'pending';
    }

    /**
     * Processa webhook do PayPal.
     *
     * @param array $payload
     * @return JsonResponse
     */
    private function handlePaypal( array $payload ): JsonResponse
    {
        $event    = $payload[ 'event_type' ] ?? 'unknown';
        $resource = $payload[ 'resource_type' ] ?? 'unknown';

        Log::info( 'Processando webhook PayPal', [
            'event'         => $event,
            'resource_type' => $resource
        ] );

        switch ( $resource ) {
            case 'payment':
                return $this->processPaypalPayment( $payload );

            case 'subscription':
                return $this->processPaypalSubscription( $payload );

            default:
                Log::info( 'Recurso PayPal não implementado', [ 'resource' => $resource ] );
                return $this->jsonSuccess( [ 'message' => 'Webhook PayPal recebido' ] );
        }
    }

    /**
     * Processa pagamento do PayPal.
     *
     * @param array $data
     * @return JsonResponse
     */
    private function processPaypalPayment( array $data ): JsonResponse
    {
        $paymentId = $data[ 'id' ] ?? null;

        if ( !$paymentId ) {
            Log::warning( 'ID do pagamento não encontrado no webhook PayPal', $data );
            return $this->jsonError( 'ID do pagamento não encontrado', 400 );
        }

        $payment = $this->paymentService->getPaymentByExternalId( $paymentId, 'paypal' );

        if ( !$payment ) {
            Log::info( 'Pagamento PayPal não encontrado no sistema', [ 'payment_id' => $paymentId ] );
            return $this->jsonSuccess( [ 'message' => 'Pagamento não encontrado' ] );
        }

        $status = $this->mapPaypalStatus( $data[ 'state' ] ?? 'pending' );

        $updateData = [
            'external_status'   => $status,
            'status_updated_at' => now(),
            'metadata'          => json_encode( [
                'webhook_received_at' => now()->toISOString(),
                'raw_data'            => $data
            ] )
        ];

        $result = $this->paymentService->updatePaymentStatus(
            paymentId: $payment->id,
            status: $status,
            gateway: 'paypal',
            metadata: $updateData,
        );

        if ( $result->isSuccess() ) {
            $this->logActivity(
                action: 'webhook_paypal_payment_' . $status,
                entity: 'payments',
                entityId: $payment->id,
                metadata: [ 'gateway' => 'paypal' ],
            );

            if ( $payment->invoice_id ) {
                $this->paymentService->syncInvoicePaymentStatus( $payment->invoice_id, $status );
            }
        }

        return $this->jsonSuccess( [
            'message'    => 'Pagamento PayPal processado',
            'payment_id' => $paymentId,
            'status'     => $status
        ] );
    }

    /**
     * Mapeia status do PayPal para status internos.
     *
     * @param string $paypalStatus
     * @return string
     */
    private function mapPaypalStatus( string $paypalStatus ): string
    {
        $mapping = [
            'created'    => 'pending',
            'completed'  => 'paid',
            'processing' => 'processing',
            'canceled'   => 'cancelled',
            'failed'     => 'failed',
            'refunded'   => 'refunded'
        ];

        return $mapping[ $paypalStatus ] ?? $paypalStatus;
    }

    /**
     * Processa assinatura do PayPal.
     *
     * @param array $data
     * @return JsonResponse
     */
    private function processPaypalSubscription( array $data ): JsonResponse
    {
        $subscriptionId = $data[ 'id' ] ?? null;

        if ( !$subscriptionId ) {
            Log::warning( 'ID da assinatura não encontrado no webhook PayPal', $data );
            return $this->jsonError( 'ID da assinatura não encontrado', 400 );
        }

        $subscription = $this->paymentService->getSubscriptionByExternalId( $subscriptionId, 'paypal' );

        if ( !$subscription ) {
            Log::info( 'Assinatura PayPal não encontrada no sistema', [ 'subscription_id' => $subscriptionId ] );
            return $this->jsonSuccess( [ 'message' => 'Assinatura não encontrada' ] );
        }

        $status = $this->mapPaypalSubscriptionStatus( $data[ 'status' ] ?? 'active' );

        $result = $this->paymentService->updateSubscriptionStatus(
            subscriptionId: $subscription->id,
            status: $status,
            metadata: [ 'webhook_received_at' => now()->toISOString() ],
        );

        if ( $result->isSuccess() ) {
            $this->logActivity(
                action: 'webhook_paypal_subscription_' . $status,
                entity: 'subscriptions',
                entityId: $subscription->id,
                metadata: [ 'gateway' => 'paypal' ],
            );
        }

        return $this->jsonSuccess( [
            'message'         => 'Assinatura PayPal processada',
            'subscription_id' => $subscriptionId,
            'status'          => $status
        ] );
    }

    /**
     * Mapeia status de assinatura do PayPal para status internos.
     *
     * @param string $paypalStatus
     * @return string
     */
    private function mapPaypalSubscriptionStatus( string $paypalStatus ): string
    {
        $mapping = [
            'ACTIVE'    => 'active',
            'SUSPENDED' => 'paused',
            'CANCELLED' => 'cancelled',
            'EXPIRED'   => 'expired'
        ];

        return $mapping[ $paypalStatus ] ?? 'pending';
    }

    /**
     * Processa webhooks internos do sistema.
     *
     * @param array $payload
     * @return JsonResponse
     */
    private function handleInternal( array $payload ): JsonResponse
    {
        $event = $payload[ 'event' ] ?? 'unknown';

        Log::info( 'Processando webhook interno', [ 'event' => $event ] );

        switch ( $event ) {
            case 'user.account_locked':
                return $this->processInternalAccountLocked( $payload[ 'data' ] ?? [] );

            case 'system.backup.completed':
                return $this->processInternalBackupCompleted( $payload[ 'data' ] ?? [] );

            case 'cron.job.failed':
                return $this->processInternalCronFailed( $payload[ 'data' ] ?? [] );

            case 'user.password_reset':
                return $this->processInternalPasswordReset( $payload[ 'data' ] ?? [] );

            default:
                Log::info( 'Evento interno não implementado', [ 'event' => $event ] );
                return $this->jsonSuccess( [ 'message' => 'Webhook interno recebido' ] );
        }
    }

    /**
     * Processa bloqueio de conta interna.
     *
     * @param array $data
     * @return JsonResponse
     */
    private function processInternalAccountLocked( array $data ): JsonResponse
    {
        $userId = $data[ 'user_id' ] ?? null;

        if ( !$userId ) {
            Log::warning( 'User ID não encontrado no webhook de bloqueio de conta' );
            return $this->jsonError( 'User ID não encontrado', 400 );
        }

        $result = $this->webhookService->processAccountLock( $userId );

        if ( $result->isSuccess() ) {
            $this->logActivity(
                action: 'internal_account_locked',
                entity: 'users',
                entityId: $userId,
                metadata: [ 'reason' => $data[ 'reason' ] ?? 'security_violation' ],
            );
        }

        return $this->jsonSuccess( [
            'message' => 'Bloqueio de conta processado',
            'user_id' => $userId
        ] );
    }

    /**
     * Processa conclusão de backup interno.
     *
     * @param array $data
     * @return JsonResponse
     */
    private function processInternalBackupCompleted( array $data ): JsonResponse
    {
        $backupId   = $data[ 'backup_id' ] ?? null;
        $backupType = $data[ 'backup_type' ] ?? 'full';

        if ( !$backupId ) {
            Log::warning( 'Backup ID não encontrado no webhook de backup completado' );
            return $this->jsonError( 'Backup ID não encontrado', 400 );
        }

        $result = $this->webhookService->processBackupCompleted( $backupId, $backupType );

        if ( $result->isSuccess() ) {
            $this->logActivity(
                action: 'internal_backup_completed',
                entity: 'backups',
                entityId: $backupId,
                metadata: [ 'backup_type' => $backupType ],
            );
        }

        return $this->jsonSuccess( [
            'message'   => 'Backup completado processado',
            'backup_id' => $backupId
        ] );
    }

    /**
     * Processa falha de job cron interno.
     *
     * @param array $data
     * @return JsonResponse
     */
    private function processInternalCronFailed( array $data ): JsonResponse
    {
        $jobName = $data[ 'job_name' ] ?? 'unknown';
        $error   = $data[ 'error' ] ?? 'Erro não especificado';

        Log::error( 'Falha de job cron processada via webhook', [
            'job_name' => $jobName,
            'error'    => $error
        ] );

        $this->logActivity(
            action: 'internal_cron_failed',
            entity: 'cron_jobs',
            metadata: [
                'job_name'     => $jobName,
                'error'        => substr( $error, 0, 200 ),
                'attempted_at' => $data[ 'attempted_at' ] ?? now()->toISOString()
            ],
        );

        // Notificar administradores
        $this->webhookService->notifyCronFailure( $jobName, $error );

        return $this->jsonSuccess( [
            'message'  => 'Falha de job cron registrada',
            'job_name' => $jobName
        ] );
    }

    /**
     * Processa reset de senha interno.
     *
     * @param array $data
     * @return JsonResponse
     */
    private function processInternalPasswordReset( array $data ): JsonResponse
    {
        $userId = $data[ 'user_id' ] ?? null;
        $token  = $data[ 'token' ] ?? null;

        if ( !$userId || !$token ) {
            Log::warning( 'Dados incompletos no webhook de reset de senha' );
            return $this->jsonError( 'Dados incompletos', 400 );
        }

        $result = $this->webhookService->processPasswordReset( $userId, $token );

        if ( $result->isSuccess() ) {
            $this->logActivity(
                action: 'internal_password_reset',
                entity: 'users',
                entityId: $userId,
                metadata: [ 'token_used' => true ],
            );
        }

        return $this->jsonSuccess( [
            'message' => 'Reset de senha processado',
            'user_id' => $userId
        ] );
    }

    /**
     * Processa webhooks genéricos.
     *
     * @param array $payload
     * @return JsonResponse
     */
    private function handleGeneric( array $payload ): JsonResponse
    {
        $event = $payload[ 'event' ] ?? 'unknown';
        $data  = $payload[ 'data' ] ?? [];

        Log::info( 'Processando webhook genérico', [
            'event'     => $event,
            'data_keys' => array_keys( $data )
        ] );

        // Armazenar webhook para processamento assíncrono se necessário
        $webhookLog = $this->webhookService->storeIncomingWebhook(
            event: $event,
            source: 'generic',
            payload: $payload,
            ip_address: request()->ip(),
            user_agent: request()->userAgent(),
            processed: false,
        );

        // Disparar job para processamento assíncrono
        if ( $webhookLog ) {
            $this->webhookService->dispatchProcessingJob( $webhookLog->id );
        }

        return $this->jsonSuccess( [
            'message'    => 'Webhook genérico recebido e enfileirado para processamento',
            'event'      => $event,
            'webhook_id' => $webhookLog->id ?? null
        ] );
    }

    /**
     * Retorna resposta padrão de webhook recebido.
     *
     * @return Response
     */
    private function webhookResponse(): Response
    {
        return response( '', 200, [
            'Content-Type'        => 'application/json',
            'X-Webhook-Status'    => 'received',
            'X-Webhook-Timestamp' => now()->toISOString()
        ] );
    }

    /**
     * Retorna erro padrão de webhook.
     *
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    private function webhookError( string $message, int $statusCode = 400 ): JsonResponse
    {
        Log::warning( 'Webhook error', [ 'message' => $message ] );

        return response()->json( [
            'error' => $message
        ], $statusCode );
    }

}

