<?php

namespace App\Services\Infrastructure;

use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookService
{
    /**
     * @var PaymentService
     */
    protected PaymentService $paymentService;

    /**
     * WebhookService constructor
     *
     * @param PaymentService $paymentService
     */
    public function __construct( PaymentService $paymentService )
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Processar webhook de pagamento
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function processWebhook( Request $request ): JsonResponse
    {
        try {
            // TODO: Implementar processamento de webhook
            // Por enquanto, apenas retornar sucesso

            return response()->json( [ 'status' => 'success' ] );
        } catch ( \Exception $e ) {
            return response()->json( [ 'status' => 'error', 'message' => $e->getMessage() ], 500 );
        }
    }

}
