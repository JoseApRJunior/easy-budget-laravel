<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductService
{
    /**
     * Buscar produtos
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search( Request $request ): JsonResponse
    {
        try {
            // TODO: Implementar busca de produtos
            // Por enquanto, apenas retornar array vazio

            return response()->json( [] );
        } catch ( \Exception $e ) {
            return response()->json( [ 'error' => $e->getMessage() ], 500 );
        }
    }

    /**
     * Criar produto
     *
     * @param array $data
     * @return JsonResponse
     */
    public function create( array $data ): JsonResponse
    {
        try {
            // TODO: Implementar criação de produto
            // Por enquanto, apenas retornar sucesso

            return response()->json( [ 'status' => 'success' ] );
        } catch ( \Exception $e ) {
            return response()->json( [ 'error' => $e->getMessage() ], 500 );
        }
    }

}
