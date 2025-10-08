<?php

namespace app\controllers;

use app\database\repositories\InvoiceRepository;
use app\database\repositories\InvoiceStatusesRepository;
use app\database\servicesORM\ActivityService;
use app\database\servicesORM\InvoiceService;
use app\database\servicesORM\NotificationService;
use app\database\servicesORM\PaymentMercadoPagoInvoiceService;
use app\database\servicesORM\PdfService;
use core\dbal\EntityNotFound;
use core\library\Response;
use core\library\Sanitize;
use core\library\Twig;
use http\Redirect;
use http\Request;

class InvoiceController extends AbstractController
{
    public function __construct(
        protected Twig $twig,
        Sanitize $sanitize,
        protected InvoiceService $invoiceService,
        ActivityService $activityService,
        private InvoiceStatusesRepository $invoiceStatusesRepository,
        protected NotificationService $notificationService,
        protected PdfService $pdfService,
        Request $request,
    ) {
        parent::__construct( $request, $activityService, $sanitize );
    }

    public function index(): Response
    {
        $invoice_statuses = $this->invoiceStatusesRepository->getAllStatuses();

        return new Response( $this->twig->env->render( 'pages/invoice/index.twig', [
            'invoice_statuses' => $invoice_statuses,
        ] ) );
    }

    public function create( string $code ): Response
    {
        try {
            $params = $this->autoSanitizeForEntity( [ 'code' => $code ], null );
            $code   = $params[ 'code' ];

            $response = $this->invoiceService->generateInvoiceDataFromService( $code, $this->authenticated->tenant_id );

            if ( !$response->isSuccess() ) {
                return Redirect::redirect( '/provider/services' )->withMessage( 'error', $response->message ?? 'Erro ao gerar dados da fatura.' );
            }

            $invoiceExists = $this->invoiceService->checkInvoiceExists( $response->data[ 'service_id' ] );  // Placeholder; implement in service

            if ( $invoiceExists->isSuccess() && $invoiceExists->data ) {
                return Redirect::redirect( '/provider/invoices' )->withMessage( 'error', 'Já existe uma fatura para este serviço.' );
            }

            return new Response( $this->twig->env->render( 'pages/invoice/create.twig', [
                'invoice' => $response->data,
            ] ) );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', 'Erro ao carregar criação de fatura.' );
            return Redirect::redirect( '/provider/invoices' );
        }
    }

    public function store(): Response
    {
        try {
            $data = $this->request->all();

            $invoiceData = $data[ 'invoice_data' ] ?? null;
            $payload     = [
                'service_code' => $data[ 'service_code' ],
                'invoice'      => $invoiceData,
            ];

            $response = $this->invoiceService->storeInvoice( $payload );

            if ( !$response->isSuccess() ) {
                return Redirect::redirect( '/provider/invoices/create/' . $data[ 'service_code' ] )->withMessage( 'error', $response->message ?? 'Erro ao armazenar fatura.' );
            }

            $entity = $response->data[ 'entity' ];

            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'invoice_created',
                'invoice',
                $entity->getId(),
                "Fatura {$entity->getCode()} criada para o serviço {$data[ 'service_code' ]}",
                [ 'entity' => $entity->jsonSerialize() ],
            );

            $invoiceResult = $this->invoiceService->getInvoiceFullByCode( $entity->getCode() );

            if ( !$invoiceResult->isSuccess() ) {
                return Redirect::redirect( '/provider/invoices' )->withMessage( 'error', 'Fatura não encontrada.' );
            }
            $invoice = $invoiceResult->data;

            $responseEmail = $this->notificationService->sendNewInvoiceNotification( $this->authenticated, $invoice, (object) $invoiceData[ 'customer_details' ] );
            if ( !$responseEmail->isSuccess() ) {
                return Redirect::redirect( '/provider/invoices/create/' . $data[ 'service_code' ] )->withMessage( 'error', 'Ocorreu um erro ao enviar o e-mail de notificação.' );
            }

            return Redirect::redirect( '/provider/invoices' )->withMessage( 'success', $response->message ?? 'Fatura criada com sucesso.' );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );

            return Redirect::redirect( '/provider/invoices' )->withMessage( 'error', 'Ocorreu um erro inesperado ao gerar a fatura.' );
        }
    }

    public function show( string $code ): Response
    {
        try {
            $params = $this->autoSanitizeForEntity( [ 'code' => $code ], null );
            $code   = $params[ 'code' ];

            $invoice = $this->invoiceService->getInvoiceFullByCode( $code );

            if ( !$invoice->isSuccess() ) {
                return Redirect::redirect( '/provider/invoices' )->withMessage( 'error', $invoice->message ?? 'Fatura não encontrada.' );
            }

            $invoice = $invoice->data;

            return new Response( $this->twig->env->render( 'pages/invoice/show.twig', [ 'invoice' => $invoice ] ) );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', 'Erro ao carregar fatura.' );
            return Redirect::redirect( '/provider/invoices' );
        }
    }

    public function print( string $code ): Response
    {
        try {
            $params = $this->autoSanitizeForEntity( [ 'code' => $code ], null );
            $code   = $params[ 'code' ];

            $invoice = $this->invoiceService->getInvoiceFullByCode( $code );

            if ( !$invoice->isSuccess() ) {
                return Redirect::redirect( '/provider/invoices' )->withMessage( 'error', $invoice->message ?? 'Fatura não encontrada.' );
            }

            $invoice = $invoice->data;

            /**  @var \app\database\entitiesORM\InvoiceEntity $invoice */
            $response = $this->pdfService->generateInvoicePdf( $this->authenticated, $invoice );

            $safeFilename = preg_replace( '/[^a-zA-Z0-9_.-]/', '_', $response[ 'fileName' ] );

            return new Response(
                $response[ 'content' ],
                200,
                [ 'Content-Type' => 'application/pdf', 'Content-Disposition' => "inline; filename=\"{$safeFilename}\"" ],
            );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', 'Erro ao imprimir fatura.' );
            return Redirect::redirect( '/provider/invoices' );
        }
    }

    // public function redirectToPayment( $code ): Response
    // {
    //     try {
    //         $code = $this->sanitize->sanitizeParamValue( $code, 'string' );

    //         $paymentUrl = $this->paymentMercadoPagoInvoiceService->createMercadoPagoPreference(
    //             $code,
    //             $this->authenticated->tenant_id,
    //         );

    //         // Redireciona o usuário para a página de pagamento do Mercado Pago
    //         return new Response(
    //             $this->twig->env->render( 'pages/invoice/payment/redirect.twig', [ 'payment_url' => $paymentUrl ] ),
    //         );

    //     } catch ( \RuntimeException $e ) {
    //         return Redirect::redirect( '/provider/invoices/show/' . $code )
    //             ->withMessage( 'error', $e->getMessage() );
    //     }
    // }

    // activityLogger herdado do AbstractController
}
