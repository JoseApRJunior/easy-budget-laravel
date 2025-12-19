<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Models\User;
use App\Repositories\CategoryRepository;
use App\Services\Domain\CategoryService;
use Collator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Log;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
    public function dashboard(): View
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
    public function index( Request $request ): View
    {
        if ( !$request->hasAny( [ 'search', 'active', 'per_page', 'deleted', 'all' ] ) ) {
            return view( 'pages.category.index', [
                'categories'        => collect(),
                'filters'           => [],
                'parent_categories' => collect(),
            ] );
        }
        $filters        = $request->only( [ 'search', 'active', 'per_page', 'deleted' ] );
        $perPage        = (int) ( $filters[ 'per_page' ] ?? 10 );
        $allowedPerPage = [ 10, 20, 50 ];
        if ( !in_array( $perPage, $allowedPerPage, true ) ) {
            $perPage = 10;
        }
        $filters[ 'per_page' ] = $perPage;
        try {

            $result = $this->categoryService->getCategories( $filters, $perPage );
            Log::info( 'Resultado da consulta de categorias', [ 'result' => $result ] );
            $categories = $result->isSuccess() ? $result->getData() : collect();
            if ( method_exists( $categories, 'appends' ) ) {
                $categories = $categories->appends( $request->query() );
            }
            // Carregar categorias pai para filtros na view
            $parentResult     = $this->categoryService->getParentCategories();
            $parentCategories = $parentResult->isSuccess() ? $parentResult->getData() : collect();
            Log::info( 'Categorias carregadas com sucesso', [
                'total'   => $categories->count(),
                'filters' => $filters,
            ] );

            return view( 'pages.category.index', [
                'categories'        => $categories,
                'filters'           => $filters,
                'parent_categories' => $parentCategories,
            ] );
        } catch ( \Exception ) {
            abort( 500, 'Erro ao carregar categorias' );
        }
    }

    /**
     * Form para criar categoria.
     */
    public function create(): View
    {
        /** @var User $user */
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
    public function store( StoreCategoryRequest $request ): RedirectResponse
    {
        $data = $request->validated();
        if ( isset( $data[ 'name' ] ) ) {
            $data[ 'name' ] = mb_convert_case( $data[ 'name' ], MB_CASE_TITLE, 'UTF-8' );
        }

        $result = $this->categoryService->createCategory( $data );

        if ( $result->isError() ) {
            // Converter ServiceResult errors em validation errors para campos específicos
            $message = $result->getMessage();

            // Se for erro de slug duplicado, adicionar erro de validação específico
            if ( strpos( $message, 'Slug já existe neste tenant' ) !== false ) {
                return back()
                    ->withErrors( [ 'slug' => 'Este slug já está em uso nesta empresa. Escolha outro slug.' ] )
                    ->withInput();
            }

            return back()->with( 'error', $message )->withInput();
        }

        $category = $result->getData();
        $this->logOperation( 'categories_store', [ 'id' => $category->id, 'name' => $category->name ] );

        return redirect()
            ->route( 'categories.create' )
            ->with( 'success', 'Categoria criada com sucesso! Você pode cadastrar outra categoria agora.' );
    }

    /**
     * Mostra detalhes da categoria por slug.
     */
    public function show( string $slug ): View
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
    public function edit( string $slug ): View
    {
        $result = $this->categoryService->findBySlug( $slug );
        if ( $result->isError() ) {
            abort( 404 );
        }

        $category = $result->getData();
        /** @var User $user */
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
    public function update( UpdateCategoryRequest $request, string $slug ): RedirectResponse
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
            $message = $result->getMessage();

            // Se for erro de referência circular ou validação específica de campo, usar withErrors
            if (
                strpos( $message, 'referência circular' ) !== false ||
                strpos( $message, 'Categoria não pode ser pai de si mesma' ) !== false ||
                strpos( $message, 'Categoria pai inválida' ) !== false
            ) {
                return back()
                    ->withErrors( [ 'parent_id' => $message ] )
                    ->withInput();
            }

            return redirect()->back()->with( 'error', $message )->withInput();
        }

        $this->logOperation( 'categories_update', [ 'id' => $category->id, 'name' => $category->name ] );

        return $this->redirectSuccess( 'categories.index', 'Categoria atualizada com sucesso.' );
    }

    /**
     * Exclui categoria.
     */
    public function destroy( string $slug ): RedirectResponse
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
    public function toggle_status( string $slug ): RedirectResponse
    {
        $result = $this->categoryService->findBySlug( $slug );
        if ( $result->isError() ) {
            abort( 404 );
        }

        $category = $result->getData();
        /** @var User $user */
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
    public function restore( string $slug ): RedirectResponse
    {
        /** @var User $user */
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
    public function export( Request $request ): BinaryFileResponse
    {
        $format = $request->get( 'format', 'xlsx' );

        $fileName = match ( $format ) {
            'csv'   => 'categories.csv',
            'xlsx'  => 'categories.xlsx',
            'pdf'   => 'categories.pdf',
            default => 'categories.xlsx',
        };

        /** @var User $user */
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
