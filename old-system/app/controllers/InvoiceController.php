<?php

namespace app\controllers;

use app\database\models\Invoice;
use app\database\models\InvoiceStatuses;
use app\database\services\ActivityService;
use app\database\services\InvoiceService;
use app\database\services\NotificationService;
use app\database\services\PaymentMercadoPagoInvoiceService;
use app\database\services\PdfService;
use core\dbal\EntityNotFound;
use core\library\Response;
use core\library\Sanitize;
use core\library\Twig;
use http\Redirect;
use http\Request;

class InvoiceController extends AbstractController
{
    public function __construct(
        private Twig $twig,
        private Sanitize $sanitize,
        private Invoice $invoiceModel,
        private InvoiceService $invoiceService,
        private ActivityService $activityService,
        private InvoiceStatuses $invoiceStatuses,
        private Invoice $invoice,
        private NotificationService $notificationService,
        private PdfService $pdfService,
        private PaymentMercadoPagoInvoiceService $paymentMercadoPagoInvoiceService,
        Request $request,
    ) {
        parent::__construct($request);
    }

    public function index(): Response
    {
        $invoice_statuses = $this->invoiceStatuses->getAllStatuses();

        return new Response($this->twig->env->render('pages/invoice/index.twig', [
            'invoice_statuses' => $invoice_statuses,
        ]));
    }

    public function create($code): Response
    {
        $code = $this->sanitize->sanitizeParamValue($code, 'string');

        $response = $this->invoiceService->generateInvoiceDataFromService($code);

        if ($response[ 'status' ] === 'error') {
            return Redirect::redirect('/provider/services')->withMessage('error', $response[ 'message' ]);
        }

        $invoiceExists = $this->invoiceModel->findBy([
            'tenant_id' => $this->authenticated->tenant_id,
            'service_id' => $response[ 'data' ][ 'service_id' ],
        ]);

        if (!$invoiceExists instanceof EntityNotFound) {
            return Redirect::redirect('/provider/invoices')->withMessage('error', 'Já existe uma fatura para este serviço.');
        }

        return new Response($this->twig->env->render('pages/invoice/create.twig', [
            'invoice' => $response[ 'data' ],
        ]));
    }

    public function store(): Response
    {
        try {
            $data = $this->request->all();

            // A validação do JSON agora é tratada pelo seu método get.
            $invoiceData = $data[ 'invoice_data' ] ?? null;
            $payload = [
                'service_code' => $data[ 'service_code' ],
                'invoice' => $invoiceData,
            ];

            $response = $this->invoiceService->storeInvoice($payload);

            if ($response[ 'status' ] === 'error') {
                return Redirect::redirect('/provider/invoices/create/' . $data[ 'service_code' ])->withMessage('error', $response[ 'message' ]);
            }

            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'invoice_created',
                'invoice',
                $response[ 'data' ][ 'id' ],
                "Fatura {$response[ 'data' ][ 'code' ]} criada para o serviço {$data[ 'service_code' ]}",
                $response[ 'data' ],
            );

            // Obter os dados completos da fatura recém-criada para o e-mail
            $invoice = $this->invoice->getInvoiceFullByCode($response[ 'data' ][ 'code' ], $this->authenticated->tenant_id);

            if ($invoice instanceof EntityNotFound) {
                return Redirect::redirect('/provider/invoices')->withMessage('error', 'Fatura não encontrada.');
            }

            // Enviar e-mail de notificação para o cliente

            $responseEmail = $this->notificationService->sendNewInvoiceNotification($this->authenticated, $invoice, (object) $invoiceData[ 'customer_details' ]);
            if (!$responseEmail) {
                return Redirect::redirect('/provider/invoices/create/' . $data[ 'service_code' ])->withMessage('error', 'Ocorreu um erro ao enviar o e-mail de notificação.');
            }

            return Redirect::redirect('/provider/invoices')->withMessage('success', $response[ 'message' ]);
        } catch (\Throwable $e) {
            getDetailedErrorInfo($e);

            return Redirect::redirect('/provider/invoices')->withMessage('error', 'Ocorreu um erro inesperado ao gerar a fatura.');
        }
    }

    public function show($code): Response
    {
        $code = $this->sanitize->sanitizeParamValue($code, 'string');

        $invoice = $this->invoice->getInvoiceFullByCode($code, $this->authenticated->tenant_id);

        if ($invoice instanceof EntityNotFound) {
            return Redirect::redirect('/provider/invoices')->withMessage('error', 'Fatura não encontrada.');
        }

        return new Response($this->twig->env->render('pages/invoice/show.twig', [ 'invoice' => $invoice ]));
    }

    public function print($code): Response
    {
        $code = $this->sanitize->sanitizeParamValue($code, 'string');

        $invoice = $this->invoice->getInvoiceFullByCode($code, $this->authenticated->tenant_id);

        if ($invoice instanceof EntityNotFound) {
            return Redirect::redirect('/provider/invoices')->withMessage('error', 'Fatura não encontrada.');
        }

        /**  @var \app\database\entities\InvoiceEntity $invoice */
        $response = $this->pdfService->generateInvoicePdf($this->authenticated, $invoice, );

        // Sanitize the filename to remove potentially harmful characters and prevent header injection.
        $safeFilename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $response[ 'fileName' ]);

        return new Response(
            $response[ 'content' ],
            200,
            [ 'Content-Type' => 'application/pdf', 'Content-Disposition' => "inline; filename=\"{$safeFilename}\"" ],
        );
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

    public function activityLogger(int $tenant_id, int $user_id, string $action_type, string $entity_type, int $entity_id, string $description, array $metadata = [])
    {
        $this->activityService->logActivity($tenant_id, $user_id, $action_type, $entity_type, $entity_id, $description, $metadata);
    }

}
