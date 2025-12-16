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

class CategoryService extends AbstractBaseService
{
    private CategoryRepository $categoryRepository;
    public function __construct(
        CategoryRepository $repository,
        private CategoryManagementService $managementService,
    ) {
        parent::__construct( $repository );
        $this->categoryRepository = $repository;
    }

    protected function getSupportedFilters(): array
    {
        return [ 'id', 'name', 'slug', 'is_active', 'parent_id', 'created_at', 'updated_at' ];
    }

    public function generateUniqueSlug( string $name, ?int $tenantId = null, ?int $excludeId = null ): string
    {
        $base = Str::slug( $name );
        $slug = $base;
        $i    = 1;

        while ( $this->categoryRepository->existsBySlug( $slug, $tenantId, $excludeId ) ) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }

    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        $rules = Category::businessRules();

        if ( $isUpdate && isset( $data[ 'id' ] ) ) {
            // Remover validação de unicidade global
            $rules[ 'slug' ] = 'required|string|max:255';
        }

        $validator = Validator::make( $data, $rules );

        if ( $validator->fails() ) {
            $messages = implode( ', ', $validator->errors()->all() );
            return $this->error( OperationStatus::INVALID_DATA, $messages );
        }

        return $this->success( $data );
    }

    public function paginate( array $filters, int $perPage = 10, bool $isAdminGlobal = false, bool $onlyTrashed = false ): ServiceResult
    {
        try {
            $normalized = [];
            if ( !empty( $filters[ 'active' ] ) || $filters[ 'active' ] === '0' ) {
                $normalized[ 'is_active' ] = (string) $filters[ 'active' ] === '1';
            }
            if ( !empty( $filters[ 'name' ] ) ) {
                $normalized[ 'name' ] = [ 'operator' => 'like', 'value' => '%' . $filters[ 'name' ] . '%' ];
            }
            if ( !empty( $filters[ 'slug' ] ) ) {
                $normalized[ 'slug' ] = [ 'operator' => 'like', 'value' => '%' . $filters[ 'slug' ] . '%' ];
            }
            if ( !empty( $filters[ 'search' ] ) ) {
                $term                 = '%' . $filters[ 'search' ] . '%';
                $normalized[ 'name' ] = [ 'operator' => 'like', 'value' => $term ];
                $normalized[ 'slug' ] = [ 'operator' => 'like', 'value' => $term ];
            }

            $paginator = $this->categoryRepository->paginate( $perPage, $normalized, [ 'name' => 'asc' ], $isAdminGlobal, $onlyTrashed );
            return $this->success( $paginator, 'Categorias paginadas com sucesso.' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, 'Erro ao paginar categorias.', null, $e );
        }
    }

    public function createCategory( array $data, ?int $tenantId = null ): ServiceResult
    {
        try {
            return DB::transaction( function () use ($data, $tenantId) {
                // Gerar slug único considerando o contexto
                if ( !isset( $data[ 'slug' ] ) || empty( $data[ 'slug' ] ) ) {
                    $data[ 'slug' ] = $this->generateUniqueSlug( $data[ 'name' ], $tenantId );
                }

                // Validar slug único
                if ( !Category::validateUniqueSlug( $data[ 'slug' ], $tenantId ) ) {
                    return ServiceResult::error(
                        OperationStatus::INVALID_DATA,
                        'Slug já existe para este contexto',
                        null,
                        new Exception( 'Slug duplicado' ),
                    );
                }

                // Criar categoria
                $category = Category::create( [
                    'slug'      => $data[ 'slug' ],
                    'name'      => $data[ 'name' ],
                    'parent_id' => $data[ 'parent_id' ] ?? null,
                    'is_active' => $data[ 'is_active' ] ?? true,
                    'is_custom' => $tenantId !== null,
                    'tenant_id' => $tenantId,
                ] );

                // Se for categoria custom, vincular ao tenant na tabela pivot
                if ( $tenantId !== null ) {
                    $category->tenants()->attach( $tenantId );
                }

                return ServiceResult::success( $category, 'Categoria criada com sucesso' );
            } );
        } catch ( \Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao criar categoria: ' . $e->getMessage(), null, $e );
        }
    }

    public function updateCategory( int $id, array $data ): ServiceResult
    {
        try {
            $categoryResult = $this->findById( $id );
            if ( $categoryResult->isError() ) {
                return $categoryResult;
            }

            $category = $categoryResult->getData();
            $tenantId = $category->tenant_id;

            // Se o nome foi alterado e slug não foi fornecido, gerar novo slug
            if ( isset( $data[ 'name' ] ) && empty( $data[ 'slug' ] ) ) {
                $data[ 'slug' ] = $this->generateUniqueSlug( $data[ 'name' ], $tenantId, $id );
            }

            // Validar slug único
            if ( isset( $data[ 'slug' ] ) && !Category::validateUniqueSlug( $data[ 'slug' ], $tenantId, $id ) ) {
                return ServiceResult::error(
                    OperationStatus::INVALID_DATA,
                    'Slug já existe para este contexto',
                    null,
                    new Exception( 'Slug duplicado' ),
                );
            }

            return $this->update( $id, $data );
        } catch ( \Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao atualizar categoria: ' . $e->getMessage(), null, $e );
        }
    }

    public function deleteCategory( int $id ): ServiceResult
    {
        $categoryResult = $this->findById( $id );
        if ( $categoryResult->isError() ) {
            return $categoryResult;
        }

        /** @var Category $category */
        $category = $categoryResult->getData();

        // Usar CategoryManagementService para validação completa
        $canDeleteResult = $this->managementService->canDelete( $category );
        if ( $canDeleteResult->isError() ) {
            return $canDeleteResult;
        }

        return $this->delete( $id );
    }

    public function getActive(): Collection
    {
        return $this->repository->listActive( [ 'name' => 'asc' ] );
    }

    public function getActiveWithChildren(): Collection
    {
        return Category::whereNull( 'parent_id' )
            ->where( 'is_active', true )
            ->with( [ 'children' => function ( $query ) {
                $query->where( 'is_active', true )->orderBy( 'name', 'asc' );
            } ] )
            ->orderBy( 'name', 'asc' )
            ->get();
    }

    public function findBySlug( string $slug ): ServiceResult
    {
        $entity = $this->repository->findBySlug( $slug );
        if ( !$entity ) {
            return $this->error( 'Categoria não encontrada' );
        }
        return $this->success( $entity );
    }

    public function listAll(): ServiceResult
    {
        $list = $this->repository->findOrderedByName( 'asc' );
        return $this->success( $list );
    }

    /**
     * Retorna dados para o dashboard de categorias.
     *
     * @param bool $isAdminGlobal Indica se o usuário é admin global (apenas para admins globais)
     */
    public function getDashboardData( bool $isAdminGlobal = false ): ServiceResult
    {
        try {
            // Admin global deve ver apenas categorias globais
            if ( $isAdminGlobal ) {
                $total    = $this->categoryRepository->countGlobalCategories();
                $active   = $this->categoryRepository->countActiveGlobalCategories();
                $inactive = $total - $active;

                $recentCategories = $this->categoryRepository->getRecentGlobalCategories( 5 );
            } else {
                // Para providers, contar categorias globais + custom do próprio tenant
                $totalGlobal  = $this->categoryRepository->countGlobalCategories();
                $activeGlobal = $this->categoryRepository->countActiveGlobalCategories();

                // Contar categorias custom do tenant
                $tenantId     = auth()->user()->tenant_id ?? null;
                $totalCustom  = $tenantId ? $this->categoryRepository->countCustomCategoriesByTenant( $tenantId ) : 0;
                $activeCustom = $tenantId ? $this->categoryRepository->countActiveCustomCategoriesByTenant( $tenantId ) : 0;

                $total    = $totalGlobal + $totalCustom;
                $active   = $activeGlobal + $activeCustom;
                $inactive = $total - $active;

                // Categorias recentes: globais + custom do tenant
                $recentCategories = $tenantId
                    ? $this->categoryRepository->getRecentCategoriesByTenant( $tenantId, 5 )
                    : $this->categoryRepository->getRecentGlobalCategories( 5 );
            }

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
