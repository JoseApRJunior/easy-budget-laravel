<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Models\Category;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class CategoryService extends AbstractBaseService
{
    /**
     * Retorna categorias ativas
     */
    public function getActive(): Collection
    {
        try {
            return Category::where( 'is_active', true )
                ->orderBy( 'name' )
                ->get();
        } catch ( Exception $e ) {
            Log::error( 'Erro ao buscar categorias ativas', [
                'error' => $e->getMessage()
            ] );
            return new Collection();
        }
    }

}
