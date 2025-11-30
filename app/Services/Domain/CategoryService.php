<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\OperationStatus;
use App\Models\Category;
use App\Repositories\CategoryRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Illuminate\Database\Eloquent\Collection;
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

    public function generateUniqueSlug( string $name, ?int $excludeId = null ): string
    {
        $base = Str::slug( $name );
        $slug = $base;
        $i    = 1;

        while ( $this->categoryRepository->existsBySlug( $slug, null, $excludeId ) ) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }

    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        $rules = Category::businessRules();

        if ( $isUpdate && isset( $data[ 'id' ] ) ) {
            $rules[ 'slug' ] = 'required|string|max:255|unique:categories,slug,' . $data[ 'id' ];
        }

        $validator = Validator::make( $data, $rules );

        if ( $validator->fails() ) {
            $messages = implode( ', ', $validator->errors()->all() );
            return $this->error( OperationStatus::INVALID_DATA, $messages );
        }

        return $this->success( $data );
    }

    public function paginateWithGlobals( array $filters, int $perPage = 15 ): ServiceResult
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

            $paginator = $this->categoryRepository->paginateWithGlobals( $perPage, $normalized, [ 'name' => 'asc' ] );
            return $this->success( $paginator, 'Categorias paginadas com sucesso.' );
        } catch ( \Exception $e ) {
            return $this->error( OperationStatus::ERROR, 'Erro ao paginar categorias.', null, $e );
        }
    }

    public function paginateGlobalOnly( array $filters, int $perPage = 15 ): ServiceResult
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
            $paginator = $this->categoryRepository->paginateOnlyGlobals( $perPage, $normalized, [ 'name' => 'asc' ] );
            return $this->success( $paginator, 'Categorias globais paginadas com sucesso.' );
        } catch ( \Exception $e ) {
            return $this->error( OperationStatus::ERROR, 'Erro ao paginar categorias globais: ' . $e->getMessage() );
        }
    }

    /**
     * Pagina apenas categorias globais deletadas (soft delete) - PARA ADMINS.
     *
     * Retorna apenas categorias globais (tenant_id NULL) que foram deletadas,
     * mantendo isolamento entre tenants.
     *
     * @param array $filters Filtros de busca
     * @param int $perPage Itens por página
     * @return ServiceResult
     */
    public function paginateOnlyTrashed( array $filters, int $perPage = 15 ): ServiceResult
    {
        try {
            $query = Category::onlyTrashed()
                ->globalOnly();  // APENAS categorias globais, nunca custom dos tenants!

            // Aplicar filtros
            if ( !empty( $filters[ 'search' ] ) ) {
                $term = '%' . $filters[ 'search' ] . '%';
                $query->where( function ( $q ) use ( $term ) {
                    $q->where( 'name', 'like', $term )
                        ->orWhere( 'slug', 'like', $term );
                } );
            }

            $paginator = $query->orderBy( 'deleted_at', 'desc' )
                ->orderBy( 'name', 'asc' )
                ->paginate( $perPage );

            return $this->success( $paginator, 'Categorias globais deletadas paginadas com sucesso.' );
        } catch ( \Exception $e ) {
            return $this->error( OperationStatus::ERROR, 'Erro ao paginar categorias globais deletadas: ' . $e->getMessage() );
        }
    }

    public function paginateOnlyTrashedForTenant( array $filters, int $perPage, int $tenantId ): ServiceResult
    {
        try {
            $paginator = $this->categoryRepository->paginateOnlyTrashedForTenant( $perPage, $filters, $tenantId );
            return $this->success( $paginator, 'Categorias deletadas do tenant paginadas com sucesso.' );
        } catch ( \Exception $e ) {
            return $this->error( OperationStatus::ERROR, 'Erro ao paginar categorias deletadas do tenant: ' . $e->getMessage() );
        }
    }

    public function createCategory( array $data ): ServiceResult
    {
        if ( !isset( $data[ 'slug' ] ) || empty( $data[ 'slug' ] ) ) {
            $data[ 'slug' ] = $this->generateUniqueSlug( $data[ 'name' ] ?? '' );
        }
        return $this->create( $data );
    }

    public function updateCategory( int $id, array $data ): ServiceResult
    {
        if ( isset( $data[ 'name' ] ) && empty( $data[ 'slug' ] ) ) {
            $data[ 'slug' ] = $this->generateUniqueSlug( $data[ 'name' ], $id );
        }
        return $this->update( $id, $data );
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

    public function getWithGlobals(): Collection
    {
        return $this->repository->listWithGlobals( [ 'name' => 'asc' ] );
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

}
