<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Models\Product;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class ProductService extends AbstractBaseService
{
    /**
     * Retorna produtos ativos
     */
    public function getActive(): Collection
    {
        try {
            return Product::where( 'active', true )
                ->orderBy( 'name' )
                ->get();
        } catch ( Exception $e ) {
            Log::error( 'Erro ao buscar produtos ativos', [
                'error' => $e->getMessage()
            ] );
            return new Collection();
        }
    }

}
