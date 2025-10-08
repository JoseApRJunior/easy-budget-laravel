<?php

namespace app\controllers;

use app\database\entitiesORM\InvoiceEntity;
use app\database\models\Invoice;
use app\database\servicesORM\ActivityService;
use app\database\servicesORM\PaymentMercadoPagoInvoiceService;
use core\dbal\EntityNotFound;
use core\library\Response;
use core\library\Sanitize;
use core\library\Twig;
use http\Redirect;
use http\Request;

class PublicInvoiceController extends AbstractController
{
    public function __construct(
        protected Twig $twig,
        private Invoice $invoiceModel,
        protected PaymentMercadoPagoInvoiceService $paymentMercadoPagoInvoiceService,
        protected Sanitize $sanitize,
        protected ActivityService $activityService,
        Request $request,
    ) {
        parent::__construct( $request );
    }

    public function show( string $hash ): Response
    {
        $hash    = $this->sanitize->sanitizeParamValue( $hash, 'string' );
        $invoice = $this->invoiceModel->getInvoiceFullByHash( $hash );

        if ( !$invoice[ 'success' ] ) {
            return Redirect::redirect( '/not-found' );
        }

        return new Response(
            $this->twig->env->render( 'pages/public/invoice/show.twig', [ 
                'invoice' => $invoice[ 'data' ],
            ] ),
        );
    }

    public function redirectToPayment( string $hash ): Response
    {
        try {
            $hash = $this->sanitize->sanitizeParamValue( $hash, 'string' );

            $invoice = $this->invoiceModel->getInvoiceFullByHash( $hash );
            if ( !$invoice[ 'success' ] ) {
                return Redirect::redirect( '/not-found' );
            }

            if ( $invoice[ 'data' ]->status_slug !== 'PENDING' ) {
                $message = match ( $invoice[ 'data' ]->status_slug ) {
                    'PAID'      => 'Esta fatura já foi paga e não pode ser alterada.',
                    'CANCELLED' => 'Esta fatura foi cancelada e não está mais disponível para pagamento.',
                    'OVERDUE'   => 'Esta fatura está vencida. Entre em contato para verificar as opções de pagamento.',
                    default     => 'Esta fatura não está disponível para pagamento no momento.'
                };

                return Redirect::redirect( "/invoices/view/$hash" )
                    ->withMessage( 'error', $message );
            }

            /** @var InvoiceEntity $invoice  */
            $response = $this->paymentMercadoPagoInvoiceService->createMercadoPagoPreference(
                $invoice[ 'data' ]->code,
                $invoice[ 'data' ]->tenant_id,
            );

            if ( !$response[ 'success' ] ) {
                return Redirect::redirect( "/invoices/view/$hash" )->withMessage( 'error', $response[ 'message' ] );
            }

            return new Response(
                $this->twig->env->render( 'pages/payment/redirect.twig', [ 'payment_url' => $response[ 'payment_url' ] ] ),
            );

        } catch ( \RuntimeException $e ) {
            return Redirect::redirect( "/invoices/view/$hash" )
                ->withMessage( 'error', $e->getMessage() );
        }
    }

    public function paymentStatus(): Response
    {
        // 1. Obter dados do redirecionamento do Mercado Pago
        $status            = $this->request->get( 'status' );
        $externalReference = $this->request->get( 'external_reference' ); // Este é o nosso public_hash

        if ( empty( $externalReference ) ) {
            return Redirect::redirect( '/' )->withMessage( 'error', 'Informações de pagamento inválidas ou ausentes.' );
        }
        // 2. Sanitize and find the invoice
        $hash    = $this->sanitize->sanitizeParamValue( $externalReference, 'string' );
        $invoice = $this->invoiceModel->getInvoiceFullByHash( $hash[ 'public_hash' ] );

        if ( !$invoice[ 'success' ] ) {
            return Redirect::redirect( '/not-found' )->withMessage( 'error', 'Fatura não encontrada.' );
        }

        // 3. Define messages based on payment status
        $statusData = match ( $status ) {
            'approved'              => [ 
                'status'               => 'success',
                'message'              => 'Pagamento Aprovado!',
                'details'              => 'Obrigado! Seu pagamento foi processado com sucesso e o prestador de serviço foi notificado.',
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
            $this->twig->env->render( 'pages/public/invoice/status.twig', array_merge( $statusData, [ 'invoice' => $invoice[ 'data' ] ] ) ),
        );
    }

    /**
     * @inheritDoc
     */
    public function activityLogger( int $tenant_id, int $user_id, string $action_type, string $entity_type, int $entity_id, string $description, array $metadata = [] ): void
    {
        $this->activityService->logActivity( $tenant_id, $user_id, $action_type, $entity_type, $entity_id, $description, $metadata );
    }

}
