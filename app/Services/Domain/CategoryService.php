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
 * Serviço para gerenciamento de categorias com arquitetura refinada.
 *
 * Categorias são isoladas por tenant - cada empresa gerencia suas próprias categorias.
 * Implementa a arquitetura padronizada com validação robusta e operações transacionais.
 *
 * @property CategoryRepository $repository
 */
class CategoryService extends AbstractBaseService
{
    public function __construct( CategoryRepository $repository )
    {
        parent::__construct( $repository );
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

        while ( $this->repository->existsBySlugAndTenantId( $slug, $excludeId ) ) {
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
        $validator = Validator::make( $data, Category::businessRules() );

        if ( $validator->fails() ) {
            return $this->error( OperationStatus::INVALID_DATA, implode( ', ', $validator->errors()->all() ) );
        }

        return $this->success( $data );
    }

    /**
     * Lista categorias do tenant com filtros e paginação.
     */
    public function getCategories( array $filters = [], int $perPage = 10 ): ServiceResult
    {
        return $this->safeExecute(function() use ($filters, $perPage) {
            if (!$this->tenantId()) {
                return $this->error( OperationStatus::ERROR, 'Tenant não identificado' );
            }

            $paginator = $this->repository->getPaginated(
                $this->normalizeFilters( $filters ),
                $perPage,
                [ 'parent' ]
            );

            Log::info( 'Categorias carregadas', [ 'total' => $paginator->total() ] );
            return $paginator;
        }, 'Erro ao carregar categorias.');
    }

    /**
     * Normaliza filtros do request para formato aceito pelo repository.
     */
    private function normalizeFilters( array $filters ): array
    {
        // dd($filters);

        $normalized = [];

        if ( array_key_exists( 'all', $filters ) && $filters[ 'all' ] !== null ) {
            $normalized[ 'all' ] = (bool) $filters[ 'all' ];
        }

        // Status filter
        if ( isset( $filters[ 'active' ] ) && $filters[ 'active' ] !== '' && $filters[ 'active' ] !== null ) {
            $normalized[ 'is_active' ] = (string) $filters[ 'active' ] === '1' || $filters[ 'active' ] === 1;
        }

        // Search/Name/Slug filters
        if ( !empty( $filters[ 'search' ] ) ) {
            $normalized[ 'search' ] = (string) $filters[ 'search' ];
        }

        if ( !empty( $filters[ 'name' ] ) ) {
            $normalized[ 'name' ] = [ 'operator' => 'like', 'value' => '%' . $filters[ 'name' ] . '%' ];
        }

        if ( !empty( $filters[ 'slug' ] ) ) {
            $normalized[ 'slug' ] = [ 'operator' => 'like', 'value' => '%' . $filters[ 'slug' ] . '%' ];
        }

        // Soft delete filter
        if ( array_key_exists( 'deleted', $filters ) ) {
            $normalized[ 'deleted' ] = match ( $filters[ 'deleted' ] ) {
                'only', '1'    => 'only',
                'current', '0' => 'current',
                default        => '',
            };
        }

        return $normalized;
    }

    /**
     * Obtém categorias pai ativas para uso em formulários.
     */
    public function getParentCategories(): ServiceResult
    {
        try {
            $tenantId = $this->tenantId();

            if ( !$tenantId ) {
                return $this->error( OperationStatus::ERROR, 'Tenant não identificado' );
            }

            $parents = Category::query()
                ->withoutGlobalScope( \App\Models\Traits\TenantScope::class)
                ->where( 'tenant_id', $tenantId )
                ->whereNull( 'parent_id' )
                ->whereNull( 'deleted_at' )
                ->where( 'is_active', true )
                ->orderBy( 'name' )
                ->get( [ 'id', 'name' ] );

            return $this->success( $parents, 'Categorias pai carregadas com sucesso.' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, 'Erro ao carregar categorias pai: ' . $e->getMessage(), null, $e );
        }
    }

    /**
     * Cria nova categoria para o tenant.
     */
    public function createCategory( array $data ): ServiceResult
    {
        return $this->safeExecute(function() use ($data) {
            $tenantId = $this->ensureTenantId();

            if ( isset( $data[ 'name' ] ) ) {
                $data[ 'name' ] = mb_convert_case( $data[ 'name' ], MB_CASE_TITLE, 'UTF-8' );
            }

            if ( empty( $data[ 'slug' ] ) ) {
                $data[ 'slug' ] = $this->generateUniqueSlug( $data[ 'name' ], $tenantId );
            }

            if ( !Category::validateUniqueSlug( $data[ 'slug' ], $tenantId ) ) {
                return $this->error( OperationStatus::INVALID_DATA, 'Slug já existe neste tenant' );
            }

            if ( !empty($data[ 'parent_id' ]) ) {
                $parentResult = $this->validateAndGetParent( (int) $data[ 'parent_id' ], $tenantId );
                if ($parentResult->isError()) return $parentResult;

                if ( (new Category(['tenant_id' => $tenantId, 'parent_id' => $data['parent_id']]))->wouldCreateCircularReference((int)$data['parent_id']) ) {
                    return $this->error( OperationStatus::INVALID_DATA, 'Não é possível criar referência circular' );
                }
            }

            return DB::transaction(fn() => $this->repository->create( array_merge($data, ['tenant_id' => $tenantId]) ));
        }, 'Erro ao criar categoria.');
    }

    /**
     * Atualiza categoria.
     */
    public function updateCategory( int $id, array $data ): ServiceResult
    {
        return $this->safeExecute(function() use ($id, $data) {
            $ownerResult = $this->findAndVerifyOwnership( $id );
            if ( $ownerResult->isError() ) return $ownerResult;

            $category = $ownerResult->getData();

            $tenantId = $this->tenantId();

            if ( isset( $data[ 'name' ] ) ) {
                $data[ 'name' ] = mb_convert_case( $data[ 'name' ], MB_CASE_TITLE, 'UTF-8' );
            }

            if ( isset( $data[ 'name' ] ) && empty( $data[ 'slug' ] ) ) {
                $data[ 'slug' ] = $this->generateUniqueSlug( $data[ 'name' ], $tenantId, $id );
            }

            if ( isset( $data[ 'slug' ] ) && !Category::validateUniqueSlug( $data[ 'slug' ], $tenantId, $id ) ) {
                return $this->error( OperationStatus::INVALID_DATA, 'Slug já existe neste tenant' );
            }

            if ( !empty($data[ 'parent_id' ]) ) {
                if ( $data[ 'parent_id' ] == $id ) {
                    return $this->error( OperationStatus::INVALID_DATA, 'Categoria não pode ser pai de si mesma' );
                }

                $parentResult = $this->validateAndGetParent( (int) $data[ 'parent_id' ], $tenantId );
                if ($parentResult->isError()) return $parentResult;

                if ( $category->wouldCreateCircularReference( (int) $data[ 'parent_id' ] ) ) {
                    return $this->error( OperationStatus::INVALID_DATA, 'Não é possível criar referência circular' );
                }
            }

            return $this->update( $id, $data );
        }, 'Erro ao atualizar categoria.');
    }

    /**
     * Remove categoria.
     */
    public function deleteCategory( int $id ): ServiceResult
    {
        return $this->safeExecute(function() use ($id) {
            $ownerResult = $this->findAndVerifyOwnership( $id );
            if ( $ownerResult->isError() ) return $ownerResult;

            $category = $ownerResult->getData();

            if ( $category->hasChildren() ) {
                return $this->error( OperationStatus::INVALID_DATA, 'Não é possível excluir categoria que possui subcategorias' );
            }

            return $this->delete( $id );
        }, 'Erro ao remover categoria.');
    }

    /**
     * Busca categorias por nome/descrição com pesquisa parcial.
     */
    public function searchCategories(
        string $search,
        array $filters = [],
        ?array $orderBy = null,
        ?int $limit = null,
    ): ServiceResult {
        return $this->safeExecute(fn() =>
            $this->repository->searchCategories($search, $filters, $orderBy, $limit),
        'Erro ao buscar categorias.');
    }

    /**
     * Busca categorias ativas (não deletadas) do tenant.
     */
    public function getActiveCategories(
        array $filters = [],
        ?array $orderBy = null,
        ?int $limit = null,
    ): ServiceResult {
        return $this->safeExecute(fn() =>
            $this->repository->getActiveCategories($filters, $orderBy, $limit),
        'Erro ao buscar categorias ativas.');
    }

    /**
     * Busca categorias deletadas (soft delete) do tenant.
     */
    public function getDeletedCategories(
        array $filters = [],
        ?array $orderBy = null,
        ?int $limit = null,
    ): ServiceResult {
        return $this->safeExecute(fn() =>
            $this->repository->getDeletedCategories($filters, $orderBy, $limit),
        'Erro ao buscar categorias deletadas.');
    }

    /**
     * Restaura categorias deletadas (soft delete) por IDs.
     */
    public function restoreCategories( array $ids ): ServiceResult
    {
        return $this->safeExecute(fn() => $this->repository->restoreCategories($ids), 'Erro ao restaurar categorias.');
    }

    /**
     * Restaura categoria deletada (soft delete) por slug.
     */
    public function restoreCategoriesBySlug( string $slug ): ServiceResult
    {
        return $this->safeExecute(function() use ($slug) {
            $tenantId = $this->ensureTenantId();
            $category = Category::onlyTrashed()
                ->where('tenant_id', $tenantId)
                ->where('slug', $slug)
                ->first();

            if (!$category) {
                return $this->error(OperationStatus::NOT_FOUND, 'Categoria não encontrada ou não está excluída');
            }

            $category->restore();
            return $category;
        }, 'Erro ao restaurar categoria.');
    }

    /**
     * Busca categoria por slug dentro do tenant.
     */
    public function findBySlug( string $slug ): ServiceResult
    {
        return $this->safeExecute(function() use ($slug) {
            $entity = $this->repository->findBySlugAndTenantId( $slug );
            return $entity ?: $this->error( OperationStatus::NOT_FOUND, 'Categoria não encontrada' );
        }, 'Erro ao buscar categoria.');
    }

    /**
     * Lista todas as categorias do tenant ordenadas por nome.
     */
    public function listAll(): ServiceResult
    {
        return $this->safeExecute(fn() => $this->repository->findOrderedByNameAndTenantId('asc'), 'Erro ao listar categorias.');
    }

    /**
     * Retorna dados para o dashboard de categorias.
     */
    public function getDashboardData(): ServiceResult
    {
        return $this->safeExecute(function() {
            $total    = $this->repository->countByTenantId();
            $active   = $this->repository->countActiveByTenantId();
            $deleted  = $this->repository->countDeletedByTenantId();
            $recentCategories = $this->repository->getRecentByTenantId( 5 );

            return [
                'total_categories'    => $total,
                'active_categories'   => $active,
                'inactive_categories' => max(0, $total - $active),
                'deleted_categories'  => $deleted,
                'recent_categories'   => $recentCategories,
            ];
        }, 'Erro ao obter estatísticas de categorias.');
    }

    // --- Auxiliares Privados ---

    private function ensureTenantId(): int
    {
        $id = $this->tenantId();
        if ( !$id ) {
            throw new Exception( 'Tenant não identificado' );
        }
        return $id;
    }

    private function findAndVerifyOwnership( int $id ): ServiceResult
    {
        $result = $this->findById( $id );
        if ( $result->isError() ) return $result;

        $category = $result->getData();
        if ( $category->tenant_id !== $this->tenantId() ) {
            return $this->error( OperationStatus::UNAUTHORIZED, 'Categoria não pertence ao tenant atual' );
        }

        return $this->success( $category );
    }

    private function validateAndGetParent( int $parentId, int $tenantId ): ServiceResult
    {
        $parent = Category::find( $parentId );
        if ( !$parent || $parent->tenant_id !== $tenantId ) {
            return $this->error( OperationStatus::INVALID_DATA, 'Categoria pai inválida' );
        }
        return $this->success( $parent );
    }

}
