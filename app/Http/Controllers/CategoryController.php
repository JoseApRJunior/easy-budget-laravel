<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Models\Tenant;
use App\Repositories\CategoryRepository;
use App\Services\Core\PermissionService;
use App\Services\Domain\CategoryManagementService;
use App\Services\Domain\CategoryService;
use Collator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CategoryController extends Controller
{
    public function __construct(
        private CategoryRepository $repository,
        private CategoryManagementService $managementService,
        private CategoryService $categoryService,
    ) {}

    private function resolveTenantId(): ?int
    {
        $testing = config( 'tenant.testing_id' );
        if ( $testing !== null ) {
            return (int) $testing;
        }
        $user = auth()->user();
        if ( $user && isset( $user->tenant_id ) && $user->tenant_id > 0 ) {
            return (int) $user->tenant_id;
        }
        $tenantParam = request()->integer( 'tenant_id' );
        return $tenantParam > 0 ? $tenantParam : null;
    }

    /**
     * Dashboard de categorias com estatísticas.
     */
    public function dashboard()
    {
        $tenantId = $this->resolveTenantId();
        $result   = $this->categoryService->getDashboardData( $tenantId );

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
        $tenantId       = $this->resolveTenantId();
        $filters        = $request->only( [ 'search', 'active', 'per_page', 'deleted' ] );
        $hasFilters     = collect( $filters )->filter( fn( $v ) => filled( $v ) )->isNotEmpty();
        $confirmAll     = $request->has( 'all' ) && in_array( (string) $request->input( 'all' ), [ '1', 'true', 'on', 'yes' ], true );
        $perPage        = (int) ( $filters[ 'per_page' ] ?? $request->input( 'per_page', 10 ) );
        $allowedPerPage = [ 10, 20, 50 ];
        if ( !in_array( $perPage, $allowedPerPage, true ) ) {
            $perPage = 10;
        }

        $user    = auth()->user();
        $isAdmin = $user ? app( PermissionService::class)->canManageGlobalCategories( $user ) : false;

        $serviceFilters = [
            'search' => $filters[ 'search' ] ?? '',
            'active' => $filters[ 'active' ] ?? '',
        ];
        $service        = app( CategoryService::class);

        if ( $isAdmin ) {
            // Admin pode ver deletados
            if ( isset( $filters[ 'deleted' ] ) && $filters[ 'deleted' ] === 'only' ) {
                $result = $service->paginateOnlyTrashed( $serviceFilters, $perPage );
            } else {
                $result = $service->paginateGlobalOnly( $serviceFilters, $perPage );
            }
            $categories = $this->getServiceData( $result, collect() );
            if ( method_exists( $categories, 'appends' ) ) {
                $categories = $categories->appends( $request->query() );
            }
        } else {
            // Verificar se o filtro "deleted" está ativo
            $showOnlyTrashed = ( $filters[ 'deleted' ] ?? '' ) === 'only';

            if ( $showOnlyTrashed ) {
                // Apenas usuários autenticados podem ver categorias deletadas
                if ( !auth()->check() ) {
                    return redirect()->route( 'login' )->with( 'error', 'Você deve estar logado para acessar essa funcionalidade.' );
                }

                // Prestadores sempre podem ver categorias custom deletadas do próprio tenant
                $userTenantId = $user ? $user->tenant_id : null;
                if ( $userTenantId === null ) {
                    // Fallback para resolver tenant_id do usuário se não estiver disponível
                    $userTenantId = $this->resolveTenantId();
                }

                if ( $userTenantId !== null ) {
                    $result     = $service->paginateOnlyTrashedForTenant( $serviceFilters, $perPage, $userTenantId );
                    $categories = $this->getServiceData( $result, collect() );
                    if ( method_exists( $categories, 'appends' ) ) {
                        $categories = $categories->appends( $request->query() );
                    }
                } else {
                    // Último fallback: usar o primeiro tenant ativo se for necessário
                    $firstTenant = Tenant::where( 'is_active', true )->first();
                    if ( $firstTenant ) {
                        $result     = $service->paginateOnlyTrashedForTenant( $serviceFilters, $perPage, $firstTenant->id );
                        $categories = $this->getServiceData( $result, collect() );
                        if ( method_exists( $categories, 'appends' ) ) {
                            $categories = $categories->appends( $request->query() );
                        }
                    } else {
                        // Sem tenant válido, mostrar listagem vazia
                        $categories = collect();
                    }
                }
            } else if ( $hasFilters || $confirmAll ) {
                $result     = $service->paginateWithGlobals( $serviceFilters, $perPage );
                $categories = $this->getServiceData( $result, collect() );
                if ( method_exists( $categories, 'appends' ) ) {
                    $categories = $categories->appends( $request->query() );
                }

                if ( method_exists( $categories, 'total' ) && (int) $categories->total() === 0 ) {
                    $result     = $service->paginateGlobalOnly( $serviceFilters, $perPage );
                    $categories = $this->getServiceData( $result, collect() );
                    if ( method_exists( $categories, 'appends' ) ) {
                        $categories = $categories->appends( $request->query() );
                    }
                }
            } else {
                // Prestadores sempre veem suas categorias ativas por padrão quando não há filtros
                $result     = $service->paginateWithGlobals( $serviceFilters, $perPage );
                $categories = $this->getServiceData( $result, collect() );
                if ( method_exists( $categories, 'appends' ) ) {
                    $categories = $categories->appends( $request->query() );
                }

                if ( method_exists( $categories, 'total' ) && (int) $categories->total() === 0 ) {
                    $result     = $service->paginateGlobalOnly( $serviceFilters, $perPage );
                    $categories = $this->getServiceData( $result, collect() );
                    if ( method_exists( $categories, 'appends' ) ) {
                        $categories = $categories->appends( $request->query() );
                    }
                }
            }
        }

        return view( 'pages.category.index', [
            'categories' => $categories,
            'filters'    => $filters,
        ] );
    }

    /**
     * Form para criar categoria.
     */
    public function create()
    {
        $user    = auth()->user();
        $isAdmin = $user ? app( PermissionService::class)->canManageGlobalCategories( $user ) : false;
        if ( $isAdmin ) {
            $parents = Category::query()
                ->globalOnly()
                ->withTrashed()
                ->orderBy( 'name' )
                ->get( [ 'id', 'name', 'deleted_at' ] );
        } else {
            $tenantId = $this->resolveTenantId();
            $parents  = $tenantId !== null
                ? Category::query()
                    ->forTenant( $tenantId )
                    ->withTrashed()
                    ->where( function ( $q ) use ( $tenantId ) {
                        $q->whereHas( 'tenants', function ( $t ) use ( $tenantId ) {
                            $t->where( 'tenant_id', $tenantId )
                                ->where( 'is_custom', true );
                        } )
                            ->orWhere( function ( $q2 ) use ( $tenantId ) {
                                $q2->where( 'is_active', true )
                                    ->whereDoesntHave( 'tenants', function ( $t ) {
                                        $t->where( 'is_custom', true );
                                    } )
                                    ->whereNotExists( function ( $sub ) use ( $tenantId ) {
                                        $sub->selectRaw( 1 )
                                            ->from( 'categories as c2' )
                                            ->join( 'category_tenant as ct2', 'ct2.category_id', '=', 'c2.id' )
                                            ->where( 'ct2.tenant_id', $tenantId )
                                            ->where( 'ct2.is_custom', true )
                                            ->whereColumn( 'c2.slug', 'categories.slug' );
                                    } );
                            } );
                    } )
                    ->orderBy( 'name' )
                    ->get( [ 'id', 'name', 'deleted_at' ] )
                : collect();
        }
        $defaults = [ 'is_active' => true ];

        return view( 'pages.category.create', compact( 'parents', 'defaults' ) );
    }

    /**
     * Persiste nova categoria.
     */
    public function store( StoreCategoryRequest $request )
    {
        $user     = auth()->user();
        $isAdmin  = $user ? app( PermissionService::class)->canManageGlobalCategories( $user ) : false;
        $tenantId = $isAdmin ? null : $this->resolveTenantId();

        $result = $this->managementService->createCategory( $request->validated(), $tenantId );

        if ( $result->isError() ) {
            return back()->with( 'error', $result->getMessage() )->withInput();
        }

        $category = $result->getData();
        $this->logOperation( 'categories_store', [ 'id' => $category->id, 'name' => $category->name ] );

        return $this->redirectSuccess( 'categories.index', 'Categoria criada com sucesso.' );
    }

    /**
     * Mostra detalhes da categoria por slug.
     */
    public function show( string $slug )
    {
        $tenantId = auth()->user()->tenant_id ?? null;
        $category = $this->repository->findBySlug( $slug );
        abort_unless( $category, 404 );
        $category->load( [ 'parent', 'tenants' => function ( $q ) use ( $tenantId ) {
            if ( $tenantId !== null ) {
                $q->where( 'tenant_id', $tenantId );
            }
        } ] );

        return view( 'pages.category.show', compact( 'category' ) );
    }

    /**
     * Form para editar categoria.
     */
    public function edit( int $id )
    {
        $category = Category::findOrFail( $id );
        $user     = auth()->user();
        $isAdmin  = $user ? app( PermissionService::class)->canManageGlobalCategories( $user ) : false;
        if ( $isAdmin && !$category->isGlobal() ) {
            return $this->redirectError( 'categories.index', 'Admin só pode editar categorias globais.' );
        }
        if ( $isAdmin ) {
            $parents = Category::query()
                ->globalOnly()
                ->where( 'id', '!=', $id )
                ->where( 'is_active', true )
                ->orderBy( 'name' )
                ->get( [ 'id', 'name' ] );
        } else {
            $tenantId = $user->tenant_id ?? null;
            $parents  = $tenantId !== null
                ? Category::query()
                    ->forTenant( $tenantId )
                    ->withTrashed()
                    ->where( 'id', '!=', $id )
                    ->where( function ( $q ) use ( $tenantId ) {
                        $q->whereHas( 'tenants', function ( $t ) use ( $tenantId ) {
                            $t->where( 'tenant_id', $tenantId )
                                ->where( 'is_custom', true );
                        } )
                            ->orWhere( function ( $q2 ) use ( $tenantId ) {
                                $q2->where( 'is_active', true )
                                    ->whereDoesntHave( 'tenants', function ( $t ) {
                                        $t->where( 'is_custom', true );
                                    } )
                                    ->whereNotExists( function ( $sub ) use ( $tenantId ) {
                                        $sub->selectRaw( 1 )
                                            ->from( 'categories as c2' )
                                            ->join( 'category_tenant as ct2', 'ct2.category_id', '=', 'c2.id' )
                                            ->where( 'ct2.tenant_id', $tenantId )
                                            ->where( 'ct2.is_custom', true )
                                            ->whereColumn( 'c2.slug', 'categories.slug' );
                                    } );
                            } );
                    } )
                    ->orderBy( 'name' )
                    ->get( [ 'id', 'name', 'deleted_at' ] )
                : collect();
        }

        $canDeactivate = !( $category->hasChildren() || $this->managementService->isInUse( $category ) );

        return view( 'pages.category.edit', compact( 'category', 'parents', 'canDeactivate' ) );
    }

    /**
     * Atualiza categoria.
     */
    public function update( UpdateCategoryRequest $request, int $id )
    {
        $category = Category::findOrFail( $id );

        $result = $this->managementService->updateCategory( $category, $request->validated() );

        if ( $result->isError() ) {
            return redirect()->back()->with( 'error', $result->getMessage() )->withInput();
        }

        $this->logOperation( 'categories_update', [ 'id' => $category->id, 'name' => $category->name ] );

        return $this->redirectSuccess( 'categories.index', 'Categoria atualizada com sucesso.' );
    }

    /**
     * Exclui categoria.
     */
    public function destroy( int $id )
    {
        $this->authorize( 'manage-custom-categories' );
        $category = Category::findOrFail( $id );

        $result = $this->managementService->deleteCategory( $category );

        if ( $result->isError() ) {
            return $this->redirectError( 'categories.index', $result->getMessage() );
        }

        $this->logOperation( 'categories_destroy', [ 'id' => $id ] );

        return $this->redirectSuccess( 'categories.index', 'Categoria excluída com sucesso.' );
    }

    /**
     * Alterna status ativo/inativo da categoria.
     */
    public function toggle_status( int $id )
    {
        $category = Category::findOrFail( $id );
        $user     = auth()->user();
        $isAdmin  = $user ? app( PermissionService::class)->canManageGlobalCategories( $user ) : false;

        // Verificar permissões
        if ( $isAdmin && !$category->isGlobal() ) {
            return $this->redirectError( 'categories.index', 'Admin só pode gerenciar categorias globais.' );
        }

        if ( !$isAdmin ) {
            $tenantId = $user->tenant_id ?? null;

            // Para providers, verificar se é categoria custom do próprio tenant ou global
            if ( $tenantId !== null ) {
                $isOwnCustomCategory = $category->tenants()
                    ->where( 'tenant_id', $tenantId )
                    ->where( 'is_custom', true )
                    ->exists();

                if ( !$isOwnCustomCategory && !$category->isGlobal() ) {
                    return $this->redirectError( 'categories.index', 'Você só pode gerenciar suas próprias categorias custom.' );
                }
            }
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
     * Admin pode restaurar qualquer categoria.
     * Prestadores podem restaurar apenas categorias custom do próprio tenant.
     */
    public function restore( int $id )
    {
        $user    = auth()->user();
        $isAdmin = app( PermissionService::class)->canManageGlobalCategories( $user );

        $category = Category::onlyTrashed()->findOrFail( $id );

        // Se não é admin, verificar se é categoria custom do próprio tenant
        if ( !$isAdmin ) {
            $tenantId = $user->tenant_id ?? null;

            // Verificar se a categoria é custom do tenant do usuário
            $isOwnCustomCategory = $category->tenants()
                ->where( 'tenant_id', $tenantId )
                ->where( 'is_custom', true )
                ->exists();

            if ( !$isOwnCustomCategory ) {
                return $this->redirectError( 'categories.index', 'Você só pode restaurar categorias custom do seu próprio tenant.' );
            }
        }

        $category->restore();

        $this->logOperation( 'categories_restore', [ 'id' => $id, 'name' => $category->name ] );

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
        $isAdmin  = $user ? app( PermissionService::class)->canManageGlobalCategories( $user ) : false;
        $tenantId = null;
        $search   = trim( (string) $request->get( 'search', '' ) );
        $active   = $request->get( 'active' );

        $search = trim( (string) $request->get( 'search', '' ) );
        $active = $request->get( 'active' );
        if ( $isAdmin ) {
            $query = Category::query()
                ->globalOnly()
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
        } else {
            $tenantId   = $this->resolveTenantId();
            $categories = $tenantId !== null
                ? ( function () use ($tenantId, $search, $active) {
                    $query = Category::query()
                        ->forTenant( $tenantId )
                        ->where( function ( $q ) use ( $tenantId ) {
                            $q->whereHas( 'tenants', function ( $t ) use ( $tenantId ) {
                                $t->where( 'tenant_id', $tenantId )
                                    ->where( 'is_custom', true );
                            } )
                                ->orWhere( function ( $q2 ) use ( $tenantId ) {
                                    $q2->where( 'is_active', true )
                                        ->whereDoesntHave( 'tenants', function ( $t ) {
                                            $t->where( 'is_custom', true );
                                        } )
                                        ->whereNotExists( function ( $sub ) use ( $tenantId ) {
                                            $sub->selectRaw( 1 )
                                                ->from( 'categories as c2' )
                                                ->join( 'category_tenant as ct2', 'ct2.category_id', '=', 'c2.id' )
                                                ->where( 'ct2.tenant_id', $tenantId )
                                                ->where( 'ct2.is_custom', true )
                                                ->whereColumn( 'c2.slug', 'categories.slug' );
                                        } );
                                } );
                        } )
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
                    return $query->orderBy( 'name' )->get();
                } )()
                : collect();
        }

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
                $createdAt      = $category->created_at instanceof \DateTimeInterface ? $category->created_at->format( 'd/m/Y H:i:s' ) : '';
                $updatedAt      = $category->updated_at instanceof \DateTimeInterface ? $category->updated_at->format( 'd/m/Y H:i:s' ) : '';
                $slugVal        = $category->slug ?: Str::slug( $category->name );
                $childrenCount  = $isAdmin
                    ? $category->children()->where( 'is_active', true )->count()
                    : $category->children()
                        ->where( 'is_active', true )
                        ->where( function ( $q ) use ( $tenantId ) {
                            $q->whereHas( 'tenants', function ( $t ) use ( $tenantId ) {
                                $t->where( 'tenant_id', $tenantId );
                            } )
                                ->orWhereHas( 'tenants', function ( $t ) {
                                    $t->where( 'is_custom', false );
                                } )
                                ->orWhereDoesntHave( 'tenants' );
                        } )
                        ->count();
                $rows          .= '<tr>'
                    . '<td>' . e( $category->name ) . '</td>'
                    . '<td>' . e( $category->parent ? $category->parent->name : '-' ) . '</td>'
                    . ( $isAdmin ? ( '<td>' . e( $slugVal ) . '</td>' ) : '' )
                    . '<td>' . ( $category->is_active ? 'Sim' : 'Não' ) . '</td>'
                    . '<td>' . $childrenCount . '</td>'
                    . '<td>' . e( $createdAt ) . '</td>'
                    . '<td>' . e( $updatedAt ) . '</td>'
                    . '</tr>';
            }

            $thead = '<thead><tr><th>Nome</th><th>Categoria Pai</th>' . ( $isAdmin ? '<th>Slug</th>' : '' ) . '<th>Ativo</th><th>Subcategorias Ativas</th><th>Data Criação</th><th>Data Atualização</th></tr></thead>';
            $html  = '<html><head><meta charset="utf-8"><style>table{border-collapse:collapse;width:100%;font-size:12px}th,td{border:1px solid #ddd;padding:6px;text-align:left}th{background:#f5f5f5}</style></head><body>'
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
        $headers     = $isAdmin
            ? [ 'Nome', 'Categoria Pai', 'Slug', 'Ativo', 'Subcategorias Ativas', 'Data Criação', 'Data Atualização' ]
            : [ 'Nome', 'Categoria Pai', 'Ativo', 'Subcategorias Ativas', 'Data Criação', 'Data Atualização' ];
        $sheet->fromArray( [ $headers ] );
        $row = 2;
        foreach ( $categories as $category ) {
            $createdAt     = $category->created_at instanceof \DateTimeInterface ? $category->created_at->format( 'd/m/Y H:i:s' ) : '';
            $updatedAt     = $category->updated_at instanceof \DateTimeInterface ? $category->updated_at->format( 'd/m/Y H:i:s' ) : '';
            $childrenCount = $isAdmin
                ? $category->children()->where( 'is_active', true )->count()
                : $category->children()
                    ->where( 'is_active', true )
                    ->where( function ( $q ) use ( $tenantId ) {
                        $q->whereHas( 'tenants', function ( $t ) use ( $tenantId ) {
                            $t->where( 'tenant_id', $tenantId );
                        } )
                            ->orWhereHas( 'tenants', function ( $t ) {
                                $t->where( 'is_custom', false );
                            } )
                            ->orWhereDoesntHave( 'tenants' );
                    } )
                    ->count();
            $dataRow       = $isAdmin
                ? [
                    $category->name,
                    $category->parent ? $category->parent->name : '-',
                    ( $category->slug ?: Str::slug( $category->name ) ),
                    $category->is_active ? 'Sim' : 'Não',
                    $childrenCount,
                    $createdAt,
                    $updatedAt,
                ]
                : [
                    $category->name,
                    $category->parent ? $category->parent->name : '-',
                    $category->is_active ? 'Sim' : 'Não',
                    $childrenCount,
                    $createdAt,
                    $updatedAt,
                ];
            $sheet->fromArray( [ $dataRow ], null, 'A' . $row );
            $row++;
        }
        foreach ( range( 'A', $isAdmin ? 'G' : 'F' ) as $col ) {
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

    /**
     * Define categoria padrão do tenant.
     */
    public function setDefault( Request $request, int $id )
    {
        $this->authorize( 'manage-custom-categories' );
        $category = Category::findOrFail( $id );
        $tenantId = auth()->user()->tenant_id ?? null;
        $user     = auth()->user();

        if ( $user && $user->isAdmin() && $request->filled( 'tenant_id' ) ) {
            $this->authorize( 'manage-global-categories' );
            $tenantCandidate = (int) $request->input( 'tenant_id' );
            if ( $tenantCandidate > 0 ) {
                $exists = Tenant::query()->where( 'id', $tenantCandidate )->exists();
                if ( $exists ) {
                    $tenantId = $tenantCandidate;
                }
            }
        }

        if ( $tenantId === null ) {
            return $this->redirectError( 'categories.index', 'Não foi possível determinar o tenant.' );
        }

        // Bloquear quando a categoria não está vinculada ao tenant
        $hasPivot = $category->tenants()->where( 'tenant_id', $tenantId )->exists();
        if ( !$hasPivot ) {
            return $this->redirectError( 'categories.index', 'Categoria não disponível para este espaço.' );
        }

        $result = $this->managementService->setDefaultCategory( $category, $tenantId );

        if ( $result->isError() ) {
            return $this->redirectError( 'categories.index', $result->getMessage() );
        }

        $this->logOperation( 'categories_set_default', [ 'id' => $category->id, 'tenant_id' => $tenantId ] );

        return $this->redirectSuccess( 'categories.index', 'Categoria definida como padrão com sucesso.' );
    }

    /**
     * Valida e normaliza slug informado.
     */
    public function checkSlug( Request $request )
    {
        $slugInput = (string) $request->get( 'slug', '' );
        $slug      = Str::slug( $slugInput );
        $tenantId  = $request->integer( 'tenant_id' ) ?: ( auth()->user()->tenant_id ?? null );

        $exists   = false;
        $attached = false;
        $id       = null;
        $editUrl  = null;

        if ( $slug !== '' ) {
            $query = Category::where( 'slug', $slug );

            if ( $tenantId !== null ) {
                $query->where( function ( $q ) use ( $tenantId ) {
                    $q->whereHas( 'tenants', fn( $t ) => $t->where( 'tenant_id', $tenantId ) )
                        ->orWhere( function ( $q2 ) {
                            $q2->globalOnly()->where( 'is_active', true );
                        } );
                } );
            } else {
                $query->globalOnly();
            }

            $exists = $query->exists();
        }

        $this->logOperation( 'categories_check_slug', [ 'slug' => $slug, 'exists' => $exists ] );

        return $this->jsonSuccess( [
            'slug'     => $slug,
            'exists'   => $exists,
            'attached' => $attached,
            'id'       => $id,
            'edit_url' => $editUrl,
        ] );
    }

}
