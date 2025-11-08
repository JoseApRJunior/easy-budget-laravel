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
     * Retorna todas as categorias ordenadas por nome
     * Como a tabela categories não possui campo is_active, retornamos todas as categorias
     */
    public function getActive(): Collection
    {
        try {
            return Category::orderBy( 'name' )
                ->get();
        } catch ( Exception $e ) {
            Log::error( 'Erro ao buscar categorias', [
                'error' => $e->getMessage()
            ] );
            return new Collection();
        }
    }

}
