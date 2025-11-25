<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Services\Domain\InventoryService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    private InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Adiciona estoque a um produto.
     *
     * Rota: products.inventory.add
     */
    public function add(Request $request, int $productId): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $result = $this->inventoryService->addStock(
                $productId,
                (int) $request->input('quantity'),
                (string) $request->input('reason', '')
            );

            if (!$result->isSuccess()) {
                return response()->json([
                    'success' => false,
                    'message' => $result->getMessage()
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Estoque adicionado com sucesso',
                'data' => $result->getData()
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao adicionar estoque: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove estoque de um produto.
     *
     * Rota: products.inventory.remove
     */
    public function remove(Request $request, int $productId): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $result = $this->inventoryService->removeStock(
                $productId,
                (int) $request->input('quantity'),
                (string) $request->input('reason', '')
            );

            if (!$result->isSuccess()) {
                return response()->json([
                    'success' => false,
                    'message' => $result->getMessage()
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Estoque removido com sucesso',
                'data' => $result->getData()
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover estoque: ' . $e->getMessage()
            ], 500);
        }
    }
}
