<?php

namespace app\controllers;

use app\database\models\Budget;
use app\database\models\Customer;
use app\database\models\Invoice;
use app\database\models\Product;
use app\database\models\Service;
use BrasilApi\Client;
use core\library\Response;
use Exception;
use http\Request;

class AjaxController extends AbstractController
{
    public function __construct(
        private Customer $customer,
        private Product $product,
        private Budget $budget,
        protected Service $service,
        private readonly Invoice $invoice,
        Request $request,
    ) {
        parent::__construct( $request );
    }

    public function buscarCep(): Response
    {
        try {
            $brasilApi = new Client();
            $dados     = $brasilApi->cep()->get( $this->request->get( 'cep' ) );

            return new Response(
                $dados,
                200,
                [ 'Content-Type' => 'application/json' ],
            );
        } catch ( Exception $e ) {
            return new Response(
                json_encode( [ 'error' => $e->getMessage() ] ),
                400,
                [ 'Content-Type' => 'application/json' ],
            );
        }
    }

    public function budgets_filter(): Response
    {
        try {
            $data = $this->request->all();

            $budgets = $this->budget->getBudgetsByFilterReport(
                $data,
                $this->authenticated->tenant_id,
            );

            return new Response(
                $budgets,
                200,
                [ 'Content-Type' => 'application/json' ],
            );
        } catch ( Exception $e ) {
            error_log( $e->getMessage() );

            return new Response(
                [ 'error' => $e->getMessage() ],
                400,
                [ 'Content-Type' => 'application/json' ],
            );
        }
    }

    public function services_filter(): Response
    {
        try {
            $data = $this->request->all();

            $services = $this->service->getServicesByFilterReport(
                $data,
                $this->authenticated->tenant_id,
            );

            return new Response(
                $services,
                200,
                [ 'Content-Type' => 'application/json' ],
            );
        } catch ( Exception $e ) {
            error_log( $e->getMessage() );

            return new Response(
                [ 'error' => $e->getMessage() ],
                400,
                [ 'Content-Type' => 'application/json' ],
            );
        }
    }

    public function customerSearch(): Response
    {
        try {

            $data = $this->request->all();

            $customers = $this->customer->getCustomersByFilter(
                $data,
                $this->authenticated->tenant_id,
            );

            return new Response(
                $customers,
                200,
                [ 'Content-Type' => 'application/json' ],
            );
        } catch ( Exception $e ) {
            error_log( $e->getMessage() );

            return new Response(
                [ 'error' => $e->getMessage() ],
                400,
                [ 'Content-Type' => 'application/json' ],
            );
        }
    }

    public function productSearch(): Response
    {
        try {
            $data = $this->request->all();

            $products = $this->product->getProductsByFilterReport(
                $data,
                $this->authenticated->tenant_id,
            );

            return new Response(
                $products,
                200,
                [ 'Content-Type' => 'application/json' ],
            );
        } catch ( Exception $e ) {
            error_log( $e->getMessage() );

            return new Response(
                [ 'error' => $e->getMessage() ],
                400,
                [ 'Content-Type' => 'application/json' ],
            );
        }
    }

    public function invoices_filter(): Response
    {
        try {
            $data = $this->request->all();

            $invoices = $this->invoice->getInvoicesByFilter( (array) $data, $this->authenticated->tenant_id );

            return new Response(
                $invoices,
                200,
                [ 'Content-Type' => 'application/json' ],
            );
        } catch ( Exception $e ) {
            error_log( $e->getMessage() );

            return new Response(
                [ 'error' => $e->getMessage() ],
                400,
                [ 'Content-Type' => 'application/json' ],
            );
        }
    }

    public function activityLogger( int $tenant_id, int $user_id, string $action_type, string $entity_type, int $entity_id, string $description, array $metadata = [] ): void {}

}
