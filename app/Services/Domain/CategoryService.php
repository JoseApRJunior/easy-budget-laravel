<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\OperationStatus;
use App\Models\Category;
use App\Repositories\CategoryRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Serviço simplificado para gerenciamento de categorias.
 *
 * Categorias são isoladas por tenant - cada empresa gerencia suas próprias categorias.
 */
class CategoryService extends AbstractBaseService
{
    private CategoryRepository $categoryRepository;

    public function __construct( CategoryRepository $repository )
    {
        parent::__construct( $repository );
        $this->categoryRepository = $repository;
    }

    protected function getSupportedFilters(): array
    {
        return [ 'id', 'name', 'slug', 'is_active', 'parent_id', 'created_at', 'updated_at' ];
    }

    /**
     * Gera slug único para o tenant.
     */
    public function generateUniqueSlug( string $name, int $tenantId, ?int $excludeId = null ): string
    {
        $base = Str::slug( $name );
        $slug = $base;
        $i    = 1;

        while ( $this->categoryRepository->existsBySlugAndTenantId( $slug, $tenantId, $excludeId ) ) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    /**
     * Valida dados da categoria.
     */
    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        $rules = Category::businessRules();

        $validator = Validator::make( $data, $rules );

        if ( $validator->fails() ) {
            $messages = implode( ', ', $validator->errors()->all() );
            return $this->error( OperationStatus::INVALID_DATA, $messages );
        }

        return $this->success( $data );
    }

    /**
     * Pagina categorias do tenant.
     */
    public function paginate( array $filters, int $perPage = 10, bool $onlyTrashed = false ): ServiceResult
    {
        try {
            $tenantId = auth()->user()->tenant_id ?? null;

            if ( !$tenantId ) {
                return $this->error( OperationStatus::ERROR, 'Tenant não identificado' );
            }

            // Normalizar filtros para formato aceito pelo repository
            $normalized = [];
            if ( isset( $filters[ 'active' ] ) && ( !empty( $filters[ 'active' ] ) || $filters[ 'active' ] === '0' ) ) {
                $normalized[ 'is_active' ] = (string) $filters[ 'active' ] === '1';
            }
            if ( isset( $filters[ 'name' ] ) && !empty( $filters[ 'name' ] ) ) {
                $normalized[ 'name' ] = [ 'operator' => 'like', 'value' => '%' . $filters[ 'name' ] . '%' ];
            }
            if ( isset( $filters[ 'slug' ] ) && !empty( $filters[ 'slug' ] ) ) {
                $normalized[ 'slug' ] = [ 'operator' => 'like', 'value' => '%' . $filters[ 'slug' ] . '%' ];
            }
            if ( isset( $filters[ 'search' ] ) && !empty( $filters[ 'search' ] ) ) {
                $term                 = '%' . $filters[ 'search' ] . '%';
                $normalized[ 'name' ] = [ 'operator' => 'like', 'value' => $term ];
                $normalized[ 'slug' ] = [ 'operator' => 'like', 'value' => $term ];
            }

            // Para filtrar deletados, criar query específica
            if ( $onlyTrashed ) {
                $query = \App\Models\Category::withoutGlobalScope( \App\Models\Traits\TenantScope::class)
                    ->onlyTrashed()
                    ->where( 'tenant_id', $tenantId );

                // Aplicar filtros normalizados
                foreach ( $normalized as $field => $value ) {
                    if ( is_array( $value ) && isset( $value[ 'operator' ], $value[ 'value' ] ) ) {
                        $query->where( $field, $value[ 'operator' ], $value[ 'value' ] );
                    } else {
                        $query->where( $field, $value );
                    }
                }

                $paginator = $query->orderBy( 'name', 'asc' )->paginate( $perPage );
            } else {
                // Usar o método padrão do AbstractTenantRepository que aplica global scope automaticamente
                $paginator = $this->categoryRepository->paginateByTenant( $perPage, $normalized, [ 'name' => 'asc' ] );
            }

            return $this->success( $paginator, 'Categorias paginadas com sucesso.' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, 'Erro ao paginar categorias: ' . $e->getMessage(), null, $e );
        }
    }

    /**
     * Cria nova categoria para o tenant.
     */
    public function createCategory( array $data ): ServiceResult
    {
        try {
            $tenantId = auth()->user()->tenant_id ?? null;

            if ( !$tenantId ) {
                return $this->error( OperationStatus::ERROR, 'Tenant não identificado' );
            }

            return DB::transaction( function () use ($data, $tenantId) {
                // Gerar slug único se não fornecido
                if ( !isset( $data[ 'slug' ] ) || empty( $data[ 'slug' ] ) ) {
                    $data[ 'slug' ] = $this->generateUniqueSlug( $data[ 'name' ], $tenantId );
                }

                // Validar slug único - se falhar, retornar ServiceResult para o controller tratar
                if ( !Category::validateUniqueSlug( $data[ 'slug' ], $tenantId ) ) {
                    return ServiceResult::error(
                        OperationStatus::INVALID_DATA,
                        'Slug já existe neste tenant',
                        null,
                        new Exception( 'Slug duplicado' ),
                    );
                }

                // Validar parent_id se fornecido
                if ( isset( $data[ 'parent_id' ] ) && $data[ 'parent_id' ] ) {
                    $parentCategory = Category::find( $data[ 'parent_id' ] );
                    if ( !$parentCategory || $parentCategory->tenant_id !== $tenantId ) {
                        return $this->error( OperationStatus::INVALID_DATA, 'Categoria pai inválida' );
                    }

                    // Verificar referência circular criando instância temporária da nova categoria
                    $tempCategory = new Category( [
                        'tenant_id' => $tenantId,
                        'parent_id' => $data[ 'parent_id' ]
                    ] );

                    if ( $tempCategory->wouldCreateCircularReference( (int) $data[ 'parent_id' ] ) ) {
                        return $this->error( OperationStatus::INVALID_DATA, 'Não é possível criar referência circular' );
                    }
                }

                // Criar categoria
                $category = Category::create( [
                    'tenant_id' => $tenantId,
                    'slug'      => $data[ 'slug' ],
                    'name'      => $data[ 'name' ],
                    'parent_id' => $data[ 'parent_id' ] ?? null,
                    'is_active' => $data[ 'is_active' ] ?? true,
                ] );

                return ServiceResult::success( $category, 'Categoria criada com sucesso' );
            } );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao criar categoria: ' . $e->getMessage(), null, $e );
        }
    }

    /**
     * Atualiza categoria.
     */
    public function updateCategory( int $id, array $data ): ServiceResult
    {
        try {
            $categoryResult = $this->findById( $id );
            if ( $categoryResult->isError() ) {
                return $categoryResult;
            }

            $category = $categoryResult->getData();
            $tenantId = auth()->user()->tenant_id ?? null;

            // Verificar se categoria pertence ao tenant atual
            if ( $category->tenant_id !== $tenantId ) {
                return $this->error( OperationStatus::UNAUTHORIZED, 'Categoria não pertence ao tenant atual' );
            }

            // Se o nome foi alterado e slug não foi fornecido, gerar novo slug
            if ( isset( $data[ 'name' ] ) && empty( $data[ 'slug' ] ) ) {
                $data[ 'slug' ] = $this->generateUniqueSlug( $data[ 'name' ], $tenantId, $id );
            }

            // Validar slug único
            if ( isset( $data[ 'slug' ] ) && !Category::validateUniqueSlug( $data[ 'slug' ], $tenantId, $id ) ) {
                return ServiceResult::error(
                    OperationStatus::INVALID_DATA,
                    'Slug já existe neste tenant',
                    null,
                    new Exception( 'Slug duplicado' ),
                );
            }

            // Validar parent_id se fornecido
            if ( isset( $data[ 'parent_id' ] ) && $data[ 'parent_id' ] ) {
                if ( $data[ 'parent_id' ] == $id ) {
                    return $this->error( OperationStatus::INVALID_DATA, 'Categoria não pode ser pai de si mesma' );
                }

                $parentCategory = Category::find( $data[ 'parent_id' ] );
                if ( !$parentCategory || $parentCategory->tenant_id !== $tenantId ) {
                    return $this->error( OperationStatus::INVALID_DATA, 'Categoria pai inválida' );
                }

                // Verificar referência circular
                if ( $category->wouldCreateCircularReference( (int) $data[ 'parent_id' ] ) ) {
                    return $this->error( OperationStatus::INVALID_DATA, 'Não é possível criar referência circular' );
                }
            }

            return $this->update( $id, $data );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao atualizar categoria: ' . $e->getMessage(), null, $e );
        }
    }

    /**
     * Remove categoria.
     */
    public function deleteCategory( int $id ): ServiceResult
    {
        $categoryResult = $this->findById( $id );
        if ( $categoryResult->isError() ) {
            return $categoryResult;
        }

        /** @var Category $category */
        $category = $categoryResult->getData();
        $tenantId = auth()->user()->tenant_id ?? null;

        // Verificar se categoria pertence ao tenant atual
        if ( $category->tenant_id !== $tenantId ) {
            return $this->error( OperationStatus::UNAUTHORIZED, 'Categoria não pertence ao tenant atual' );
        }

        // Verificar se categoria tem filhos
        if ( $category->hasChildren() ) {
            return $this->error( OperationStatus::INVALID_DATA, 'Não é possível excluir categoria que possui subcategorias' );
        }

        return $this->delete( $id );
    }

    /**
     * Lista categorias ativas do tenant.
     */
    public function getActive(): Collection
    {
        $tenantId = auth()->user()->tenant_id ?? null;
        return $tenantId ? $this->categoryRepository->listActiveByTenantId( $tenantId, [ 'name' => 'asc' ] ) : collect();
    }

    /**
     * Lista categorias ativas com filhos (estrutura hierárquica).
     */
    public function getActiveWithChildren(): Collection
    {
        $tenantId = auth()->user()->tenant_id ?? null;

        if ( !$tenantId ) {
            return collect();
        }

        return Category::where( 'tenant_id', $tenantId )
            ->whereNull( 'parent_id' )
            ->where( 'is_active', true )
            ->with( [ 'children' => function ( $query ) {
                $query->where( 'is_active', true )->orderBy( 'name', 'asc' );
            } ] )
            ->orderBy( 'name', 'asc' )
            ->get();
    }

    /**
     * Busca categoria por slug dentro do tenant.
     */
    public function findBySlug( string $slug ): ServiceResult
    {
        $tenantId = auth()->user()->tenant_id ?? null;

        if ( !$tenantId ) {
            return $this->error( OperationStatus::ERROR, 'Tenant não identificado' );
        }

        $entity = $this->categoryRepository->findBySlugAndTenantId( $slug, $tenantId );
        if ( !$entity ) {
            return $this->error( 'Categoria não encontrada' );
        }
        return $this->success( $entity );
    }

    /**
     * Lista todas as categorias do tenant.
     */
    public function listAll(): ServiceResult
    {
        $tenantId = auth()->user()->tenant_id ?? null;

        if ( !$tenantId ) {
            return $this->error( OperationStatus::ERROR, 'Tenant não identificado' );
        }

        $list = $this->categoryRepository->findOrderedByNameAndTenantId( $tenantId, 'asc' );
        return $this->success( $list );
    }

    /**
     * Retorna dados para o dashboard de categorias.
     */
    public function getDashboardData(): ServiceResult
    {
        try {
            $tenantId = auth()->user()->tenant_id ?? null;

            if ( !$tenantId ) {
                return $this->error( OperationStatus::ERROR, 'Tenant não identificado' );
            }

            $total    = $this->categoryRepository->countByTenantId( $tenantId );
            $active   = $this->categoryRepository->countActiveByTenantId( $tenantId );
            $inactive = $total - $active;

            $recentCategories = $this->categoryRepository->getRecentByTenantId( $tenantId, 5 );

            $stats = [
                'total_categories'    => $total,
                'active_categories'   => $active,
                'inactive_categories' => $inactive,
                'recent_categories'   => $recentCategories,
            ];

            return $this->success( $stats, 'Estatísticas obtidas com sucesso' );
        } catch ( Exception $e ) {
            Log::error( 'Erro ao obter estatísticas de categorias', [ 'error' => $e->getMessage() ] );
            return $this->error( OperationStatus::ERROR, 'Erro ao obter estatísticas: ' . $e->getMessage(), null, $e );
        }
    }

}
