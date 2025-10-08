<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Controller para tratamento de webhooks
 */
class WebhookController extends Controller
{
    /**
     * Trata webhooks do Mercado Pago para faturas.
     */
    public function handleMercadoPagoInvoice(Request $request)
    {
        // TODO: Implementar lógica de webhook para faturas
        // Por ora, retorna sucesso
        return response()->json(['status' => 'success']);
    }

    /**
     * Trata webhooks do Mercado Pago para planos.
     */
    public function handleMercadoPagoPlan(Request $request)
    {
        // TODO: Implementar lógica de webhook para planos
        // Por ora, retorna sucesso
        return response()->json(['status' => 'success']);
    }

    /**
     * Trata webhooks genéricos do Mercado Pago.
     */
    public function handleWebhookMercadoPago(Request $request)
    {
        // TODO: Implementar lógica de webhook genérica
        // Por ora, retorna sucesso
        return response()->json(['status' => 'success']);
    }

}
