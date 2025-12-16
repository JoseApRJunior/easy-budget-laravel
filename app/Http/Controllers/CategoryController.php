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
class CategoryController extends Controller
{
    public function __construct(
        private CategoryRepository $repository,
        private CategoryService $categoryService,
    ) {}

    /**
     * Dashboard de categorias com estatísticas.
     */
    public function dashboard()
    {
        $result = $this->categoryService->getDashboardData();

        if ( !$result->isSuccess() ) {
            return view( 'pages.category.dashboard', [
                'stats' => [],
                'error' => $result->getMessage(),
            ] );
        }

        return view( 'pages.category.dashboard', [
            'stats' => $result->getData(),
        ] );
    }

    /**
     * Lista categorias com filtros e paginação.
     */
    public function index( Request $request )
    {
        $filters        = $request->only( [ 'search', 'active', 'per_page', 'deleted' ] );
        $hasFilters     = $request->has( [ 'search', 'active', 'deleted' ] );
        $perPage        = (int) ( $filters[ 'per_page' ] ?? $request->input( 'per_page', 10 ) );
        $allowedPerPage = [ 10, 20, 50 ];
        if ( !in_array( $perPage, $allowedPerPage, true ) ) {
            $perPage = 10;
        }

        $service = app( CategoryService::class);

        if ( $hasFilters ) {
            $serviceFilters = [
                'search' => $filters[ 'search' ] ?? '',
                'active' => $filters[ 'active' ] ?? '',
            ];

            // Se request 'deleted' = 'only', mostrar apenas deletadas
            if ( isset( $filters[ 'deleted' ] ) && $filters[ 'deleted' ] === 'only' ) {
                $result = $service->paginate( $serviceFilters, $perPage, true );
            } else {
                $result = $service->paginate( $serviceFilters, $perPage, false );
            }

            $categories = $this->getServiceData( $result, collect() );
            if ( method_exists( $categories, 'appends' ) ) {
                $categories = $categories->appends( $request->query() );
            }
        } else {
            // Carregar categorias por padrão quando não há filtros
            $result = $service->listAll();
            if ( $result->isSuccess() ) {
                $categories = $this->getServiceData( $result, collect() );
                if ( method_exists( $categories, 'appends' ) ) {
                    $categories = $categories->appends( $request->query() );
                }
            } else {
                $categories = collect();
            }
        }

        // Carregar categorias pai para filtros na view
        $user     = auth()->user();
        $tenantId = $user->tenant_id ?? null;

        $parentCategories = $tenantId
            ? Category::query()
                ->where( 'tenant_id', $tenantId )
                ->whereNull( 'parent_id' )
                ->whereNull( 'deleted_at' )
                ->where( 'is_active', true )
                ->orderBy( 'name' )
                ->get( [ 'id', 'name' ] )
            : collect();

        return view( 'pages.category.index', [
            'categories'        => $categories,
            'filters'           => $filters,
            'parent_categories' => $parentCategories,
        ] );
    }

    /**
     * Form para criar categoria.
     */
    public function create()
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id ?? null;

        if ( !$tenantId ) {
            return redirect()->route( 'categories.index' )->with( 'error', 'Tenant não identificado' );
        }

        $parents = Category::query()
            ->where( 'tenant_id', $tenantId )
            ->whereNull( 'parent_id' )
            ->whereNull( 'deleted_at' )
            ->where( 'is_active', true )
            ->orderBy( 'name' )
            ->get( [ 'id', 'name' ] );

        $defaults = [ 'is_active' => true ];

        return view( 'pages.category.create', compact( 'parents', 'defaults' ) );
    }

    /**
     * Persiste nova categoria.
     */
    public function store( StoreCategoryRequest $request )
    {
        // DEBUG: Log what's happening
        error_log( "=== CATEGORY STORE DEBUG START ===" );
        error_log( "Request data: " . json_encode( $request->all() ) );

        $data = $request->validated();
        if ( isset( $data[ 'name' ] ) ) {
            $data[ 'name' ] = mb_convert_case( $data[ 'name' ], MB_CASE_TITLE, 'UTF-8' );
        }

        error_log( "Data after validation: " . json_encode( $data ) );

        $result = $this->categoryService->createCategory( $data );

        error_log( "Service result isError: " . ( $result->isError() ? 'YES' : 'NO' ) );
        error_log( "Service result message: " . $result->getMessage() );

        if ( $result->isError() ) {
            error_log( "ERROR: Service returned error, entering error handling" );

            // Converter ServiceResult errors em validation errors para campos específicos
            $message = $result->getMessage();

            // Se for erro de slug duplicado, adicionar erro de validação específico
            if ( strpos( $message, 'Slug já existe neste tenant' ) !== false ) {
                error_log( "SLUG ERROR DETECTED - returning validation errors" );
                return back()
                    ->withErrors( [ 'slug' => 'Este slug já está em uso nesta empresa. Escolha outro slug.' ] )
                    ->withInput();
            }

            error_log( "GENERAL ERROR - returning general error" );
            return back()->with( 'error', $message )->withInput();
        }

        error_log( "SUCCESS: Service returned success" );
        $category = $result->getData();
        $this->logOperation( 'categories_store', [ 'id' => $category->id, 'name' => $category->name ] );

        return $this->redirectSuccess( 'categories.index', 'Categoria criada com sucesso.' );
    }

    /**
     * Mostra detalhes da categoria por slug.
     */
    public function show( string $slug )
    {
        $result = $this->categoryService->findBySlug( $slug );
        if ( $result->isError() ) {
            abort( 404 );
        }

        $category = $result->getData();
        $category->load( 'parent' );

        return view( 'pages.category.show', compact( 'category' ) );
    }

    /**
     * Form para editar categoria.
     */
    public function edit( string $slug )
    {
        $result = $this->categoryService->findBySlug( $slug );
        if ( $result->isError() ) {
            abort( 404 );
        }

        $category = $result->getData();
        $user     = auth()->user();
        $tenantId = $user->tenant_id ?? null;

        if ( !$tenantId || $category->tenant_id !== $tenantId ) {
            return redirect()->route( 'categories.index' )->with( 'error', 'Categoria não encontrada' );
        }

        $parents = Category::query()
            ->where( 'tenant_id', $tenantId )
            ->whereNull( 'parent_id' )
            ->whereNull( 'deleted_at' )
            ->where( 'id', '!=', $category->id )
            ->where( 'is_active', true )
            ->orderBy( 'name' )
            ->get( [ 'id', 'name' ] );

        $canDeactivate = !( $category->hasChildren() );

        return view( 'pages.category.edit', compact( 'category', 'parents', 'canDeactivate' ) );
    }

    /**
     * Atualiza categoria.
     */
    public function update( UpdateCategoryRequest $request, string $slug )
    {
        $result = $this->categoryService->findBySlug( $slug );
        if ( $result->isError() ) {
            abort( 404 );
        }

        $category = $result->getData();
        $data     = $request->validated();
        if ( isset( $data[ 'name' ] ) ) {
            $data[ 'name' ] = mb_convert_case( $data[ 'name' ], MB_CASE_TITLE, 'UTF-8' );
        }

        $result = $this->categoryService->updateCategory( $category->id, $data );

        if ( $result->isError() ) {
            return redirect()->back()->with( 'error', $result->getMessage() )->withInput();
        }

        $this->logOperation( 'categories_update', [ 'id' => $category->id, 'name' => $category->name ] );

        return $this->redirectSuccess( 'categories.index', 'Categoria atualizada com sucesso.' );
    }

    /**
     * Exclui categoria.
     */
    public function destroy( string $slug )
    {
        $result = $this->categoryService->findBySlug( $slug );
        if ( $result->isError() ) {
            abort( 404 );
        }

        $category = $result->getData();

        $result = $this->categoryService->deleteCategory( $category->id );

        if ( $result->isError() ) {
            return $this->redirectError( 'categories.index', $result->getMessage() );
        }

        $this->logOperation( 'categories_destroy', [ 'id' => $category->id, 'slug' => $slug ] );

        return $this->redirectSuccess( 'categories.index', 'Categoria excluída com sucesso.' );
    }

    /**
     * Alterna status ativo/inativo da categoria.
     */
    public function toggle_status( string $slug )
    {
        $result = $this->categoryService->findBySlug( $slug );
        if ( $result->isError() ) {
            abort( 404 );
        }

        $category = $result->getData();
        $user     = auth()->user();
        $tenantId = $user->tenant_id ?? null;

        // Verificar se categoria pertence ao tenant atual
        if ( !$tenantId || $category->tenant_id !== $tenantId ) {
            return $this->redirectError( 'categories.index', 'Categoria não encontrada' );
        }

        // Alternar status
        $category->is_active = !$category->is_active;
        $category->save();

        $statusText = $category->is_active ? 'ativada' : 'desativada';
        $this->logOperation( 'categories_toggle_status', [
            'id'         => $category->id,
            'name'       => $category->name,
            'new_status' => $category->is_active ? 'active' : 'inactive'
        ] );

        return $this->redirectSuccess( 'categories.index', "Categoria {$statusText} com sucesso." );
    }

    /**
     * Restaura categoria deletada (soft delete).
     */
    public function restore( string $slug )
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id ?? null;

        if ( !$tenantId ) {
            return $this->redirectError( 'categories.index', 'Tenant não identificado' );
        }

        $category = Category::onlyTrashed()
            ->where( 'tenant_id', $tenantId )
            ->where( 'slug', $slug )
            ->firstOrFail();

        $category->restore();

        $this->logOperation( 'categories_restore', [ 'slug' => $slug, 'name' => $category->name ] );

        return $this->redirectSuccess( 'categories.index', 'Categoria restaurada com sucesso!' );
    }

    /**
     * Exporta categorias em xlsx, csv ou pdf.
     */
    public function export( Request $request )
    {
        $format = $request->get( 'format', 'xlsx' );

        $fileName = match ( $format ) {
            'csv'   => 'categories.csv',
            'xlsx'  => 'categories.xlsx',
            'pdf'   => 'categories.pdf',
            default => 'categories.xlsx',
        };

        $user     = auth()->user();
        $tenantId = $user->tenant_id ?? null;

        if ( !$tenantId ) {
            return redirect()->route( 'categories.index' )->with( 'error', 'Tenant não identificado' );
        }

        $search = trim( (string) $request->get( 'search', '' ) );
        $active = $request->get( 'active' );

        $query = Category::query()
            ->where( 'tenant_id', $tenantId )
            ->with( 'parent' );

        if ( $search !== '' ) {
            $query->where( function ( $q ) use ( $search ) {
                $q->where( 'name', 'like', "%{$search}%" )
                    ->orWhere( 'slug', 'like', "%{$search}%" )
                    ->orWhereHas( 'parent', function ( $p ) use ( $search ) {
                        $p->where( 'name', 'like', "%{$search}%" );
                    } );
            } );
        }

        if ( in_array( $active, [ '0', '1' ], true ) ) {
            $query->where( 'is_active', $active === '1' );
        }

        $categories = $query->orderBy( 'name' )->get();

        $collator   = class_exists( Collator::class) ? new Collator( 'pt_BR' ) : null;
        $categories = $categories->sort( function ( $a, $b ) use ( $collator ) {
            if ( $collator ) {
                return $collator->compare( $a->name, $b->name );
            }
            return strcasecmp( $a->name, $b->name );
        } )->values();

        if ( $format === 'pdf' ) {
            $rows = '';
            foreach ( $categories as $category ) {
                $createdAt        = $category->created_at instanceof \DateTimeInterface ? $category->created_at->format( 'd/m/Y H:i:s' ) : '';
                $updatedAt        = $category->updated_at instanceof \DateTimeInterface ? $category->updated_at->format( 'd/m/Y H:i:s' ) : '';
                $slugVal          = $category->slug ?: Str::slug( $category->name );
                $childrenCount    = $category->children()->where( 'is_active', true )->count();
                $categoryName     = $category->parent_id ? $category->parent->name : $category->name;
                $subcategoryName  = $category->parent_id ? $category->name : '—';
                $rows            .= '<tr>'
                    . '<td>' . e( $categoryName ) . '</td>'
                    . '<td>' . e( $subcategoryName ) . '</td>'
                    . '<td>' . e( $slugVal ) . '</td>'
                    . '<td>' . ( $category->is_active ? 'Sim' : 'Não' ) . '</td>'
                    . '<td class="text-center">' . $childrenCount . '</td>'
                    . '<td>' . e( $createdAt ) . '</td>'
                    . '<td>' . e( $updatedAt ) . '</td>'
                    . '</tr>';
            }

            $thead = '<thead><tr><th>Categoria</th><th>Subcategoria</th><th>Slug</th><th>Ativo</th><th style="text-align:center">Subcategorias Ativas</th><th>Data Criação</th><th>Data Atualização</th></tr></thead>';
            $html  = '<html><head><meta charset="utf-8"><style>table{border-collapse:collapse;width:100%;font-size:12px}th,td{border:1px solid #ddd;padding:6px;text-align:left}th{background:#f5f5f5}.text-center{text-align:center}</style></head><body>'
                . '<h3>Categorias</h3>'
                . '<table>'
                . $thead
                . '<tbody>' . $rows . '</tbody>'
                . '</table>'
                . '</body></html>';

            return response()->streamDownload( function () use ($html) {
                $mpdf = new Mpdf();
                $mpdf->WriteHTML( $html );
                echo $mpdf->Output( '', 'S' );
            }, $fileName, [
                'Content-Type' => 'application/pdf',
            ] );
        }

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $headers     = [ 'Categoria', 'Subcategoria', 'Slug', 'Ativo', 'Subcategorias Ativas', 'Data Criação', 'Data Atualização' ];
        $sheet->fromArray( [ $headers ] );

        // Centralizar coluna "Subcategorias Ativas"
        $subCatCol = 'E';
        $sheet->getStyle( $subCatCol . '1' )->getAlignment()->setHorizontal( \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER );

        $row = 2;
        foreach ( $categories as $category ) {
            $createdAt       = $category->created_at instanceof \DateTimeInterface ? $category->created_at->format( 'd/m/Y H:i:s' ) : '';
            $updatedAt       = $category->updated_at instanceof \DateTimeInterface ? $category->updated_at->format( 'd/m/Y H:i:s' ) : '';
            $childrenCount   = $category->children()->where( 'is_active', true )->count();
            $categoryName    = $category->parent_id ? $category->parent->name : $category->name;
            $subcategoryName = $category->parent_id ? $category->name : '—';
            $dataRow         = [
                $categoryName,
                $subcategoryName,
                ( $category->slug ?: Str::slug( $category->name ) ),
                $category->is_active ? 'Sim' : 'Não',
                $childrenCount,
                $createdAt,
                $updatedAt,
            ];
            $sheet->fromArray( [ $dataRow ], null, 'A' . $row );

            // Centralizar valor da coluna "Subcategorias Ativas"
            $subCatCol = 'E';
            $sheet->getStyle( $subCatCol . $row )->getAlignment()->setHorizontal( \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER );

            $row++;
        }

        foreach ( range( 'A', 'G' ) as $col ) {
            $sheet->getColumnDimension( $col )->setAutoSize( true );
        }

        $contentType = $format === 'csv' ? 'text/csv' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        return response()->streamDownload( function () use ($spreadsheet, $format) {
            if ( $format === 'csv' ) {
                $writer = new Csv( $spreadsheet );
            } else {
                $writer = new Xlsx( $spreadsheet );
            }
            $writer->save( 'php://output' );
        }, $fileName, [
            'Content-Type' => $contentType,
        ] );
    }

}
