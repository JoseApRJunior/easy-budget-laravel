<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Abstracts\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Controller para endpoints AJAX que não fazem parte da lógica de negócio principal.
 * Mantém apenas métodos auxiliares como consulta de CEP.
 */
class AjaxController extends Controller
{
    /**
     * Consulta de CEP usando BrasilAPI.
     */
    public function cep(Request $request): JsonResponse
    {
        $request->validate(['cep' => 'required|regex:/^\d{8}$/']);
        $cep = $request->input('cep');
        $response = Http::timeout(8)->get("https://brasilapi.com.br/api/cep/v1/{$cep}");
        if (! $response->ok()) {
            return response()->json(['success' => false, 'message' => 'CEP inválido ou serviço indisponível'], 400);
        }

        return response()->json(['success' => true, 'data' => $response->json()]);
    }
}
