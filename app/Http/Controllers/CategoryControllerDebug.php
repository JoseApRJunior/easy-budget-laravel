<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Repositories\CategoryRepository;
use App\Services\Core\PermissionService;
use App\Services\Domain\CategoryService;
use Collator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Controller simplificado para gerenciamento de categorias.
 *
 * Categorias são isoladas por tenant - cada empresa gerencia suas próprias categorias.
 */
class CategoryControllerDebug extends Controller
{
    public function __construct(
        private CategoryRepository $repository,
        private CategoryService $categoryService,
    ) {}

    /**
     * Persiste nova categoria com debug.
     */
    public function store( StoreCategoryRequest $request )
    {
        echo "\n=== CATEGORY CONTROLLER DEBUG ===\n";

        $data = $request->validated();
        echo "Request data: " . json_encode( $data, JSON_PRETTY_PRINT ) . "\n";

        if ( isset( $data[ 'name' ] ) ) {
            $data[ 'name' ] = mb_convert_case( $data[ 'name' ], MB_CASE_TITLE, 'UTF-8' );
        }

        echo "Data after name conversion: " . json_encode( $data, JSON_PRETTY_PRINT ) . "\n";

        echo "Calling categoryService->createCategory...\n";
        $result = $this->categoryService->createCategory( $data );

        echo "Service result isError: " . ( $result->isError() ? 'YES' : 'NO' ) . "\n";
        echo "Service result message: " . $result->getMessage() . "\n";

        if ( $result->isError() ) {
            echo "ERROR: Service returned error, entering error handling\n";

            // Converter ServiceResult errors em validation errors para campos específicos
            $message = $result->getMessage();
            echo "Error message: {$message}\n";

            // Se for erro de slug duplicado, adicionar erro de validação específico
            if ( strpos( $message, 'Slug já existe neste tenant' ) !== false ) {
                echo "SLUG ERROR DETECTED - returning validation errors\n";
                return back()
                    ->withErrors( [ 'slug' => 'Este slug já está em uso nesta empresa. Escolha outro slug.' ] )
                    ->withInput();
            }

            echo "GENERAL ERROR - returning general error\n";
            return back()->with( 'error', $message )->withInput();
        }

        echo "SUCCESS: Service returned success\n";
        $category = $result->getData();
        echo "Category created: " . $category->name . " (ID: " . $category->id . ")\n";

        return $this->redirectSuccess( 'categories.index', 'Categoria criada com sucesso.' );
    }

}
