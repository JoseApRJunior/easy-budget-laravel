<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OperationStatus;
use App\Interfaces\ServiceInterface;
use App\Models\Category;
use App\Repositories\CategoryRepository;
use App\Services\Abstracts\BaseTenantService;
use App\Support\ServiceResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CategoryService extends BaseTenantService
{
    private CategoryRepository $categoryRepository;

    public function __construct( CategoryRepository $categoryRepository )
    {
        $this->categoryRepository = $categoryRepository;
    }

    protected function findEntityByIdAndTenantId( int $id, int $tenantId ): ?Model
    {
        $tenantId = (int) $tenantId;
        return $this->categoryRepository->findByIdAndTenantId( $id, $tenantId );
    }

    protected function listEntitiesByTenantId( int $tenantId, array $filters = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array
    {
        $tenantId = (int) $tenantId;
        return $this->categoryRepository->findAllByTenantId( $tenantId, $filters, $orderBy, $limit, $offset );
    }

    protected function createEntity( array $data, int $tenantId ): Model
    {
        $tenantId            = (int) $tenantId;
        $data[ 'tenant_id' ] = $tenantId;
        if ( isset( $data[ 'parent_id' ] ) ) {
            $data[ 'parent_id' ] = (int) $data[ 'parent_id' ];
        }
        $category = new Category();
        $category->fill( $data );
        return $category;
    }

    protected function updateEntity( Model $entity, array $data, int $tenantId ): void
    {
        $tenantId = (int) $tenantId;
        if ( isset( $data[ 'parent_id' ] ) ) {
            $data[ 'parent_id' ] = (int) $data[ 'parent_id' ];
        }
        $entity->fill( $data );
    }

    protected function saveEntity( Model $entity ): bool
    {
        return $entity->save();
    }

    protected function deleteEntity( Model $entity ): bool
    {
        return $entity->delete();
    }

    protected function belongsToTenant( Model $entity, int $tenantId ): bool
    {
        $tenantId = (int) $tenantId;
        return (int) $entity->tenant_id === $tenantId;
    }

    protected function canDeleteEntity( Model $entity ): bool
    {
        // Check if used in budgets or services
        $budgetCount  = \App\Models\Budget::where( 'category_id', $entity->id )->count();
        $serviceCount = \App\Models\Service::where( 'category_id', $entity->id )->count();
        return $budgetCount === 0 && $serviceCount === 0;
    }

    public function validateForTenant( array $data, int $tenantId, bool $isUpdate = false ): ServiceResult
    {
        $currentId = $data[ 'id' ] ?? null;
        $rules     = [ 
            'name'        => [ 
                'required',
                'string',
                'max:255',
                $isUpdate ? 'unique:categories,name,' . $currentId . ',id,tenant_id,' . (int) $tenantId : 'unique:categories,name,NULL,id,tenant_id,' . (int) $tenantId
            ],
            'description' => 'nullable|string|max:1000',
            'parent_id'   => [ 
                'nullable',
                Rule::exists( 'categories', 'id' )->where( fn( $q ) => $q->where( 'tenant_id', $tenantId ) ),
                $isUpdate && $currentId ? Rule::unique( 'categories', 'parent_id' )->ignore( $currentId ) : Rule::unique( 'categories', 'parent_id' )
            ],
            'status'      => 'required|in:active,inactive',
        ];
        $validator = Validator::make( $data, $rules );
        if ( $validator->fails() ) {
            $messages = $validator->errors()->all();
            return $this->error( OperationStatus::INVALID_DATA, implode( ', ', $messages ) );
        }
        return $this->success();
    }

    public function getByIdAndTenantId( int $id, int $tenantId ): ServiceResult
    {
        $tenantId = (int) $tenantId;
        $entity   = $this->findEntityByIdAndTenantId( $id, $tenantId );
        if ( !$entity ) {
            return $this->error( OperationStatus::NOT_FOUND, 'Categoria não encontrada.' );
        }
        return $this->success( $entity );
    }

    public function listByTenantId( int $tenantId, array $filters = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): ServiceResult
    {
        $tenantId = (int) $tenantId;
        $data     = $this->listEntitiesByTenantId( $tenantId, $filters, $orderBy, $limit, $offset );
        return $this->success( $data );
    }

    public function createByTenantId( array $data, int $tenantId ): ServiceResult
    {
        return parent::createByTenantId( $data, $tenantId );
    }

    public function updateByIdAndTenantId( int $id, array $data, int $tenantId ): ServiceResult
    {
        return parent::updateByIdAndTenantId( $id, $data, $tenantId );
    }

    public function deleteByIdAndTenantId( int $id, int $tenantId ): ServiceResult
    {
        $tenantId = (int) $tenantId;
        $entity   = $this->findEntityByIdAndTenantId( $id, $tenantId );
        if ( !$entity ) {
            return $this->error( OperationStatus::NOT_FOUND, 'Categoria não encontrada.' );
        }
        if ( !$this->canDeleteEntity( $entity ) ) {
            return $this->error( OperationStatus::CONFLICT, 'Não é possível deletar categoria usada.' );
        }
        $this->deleteEntity( $entity );
        return $this->success( true, 'Categoria deletada com sucesso.' );
    }

    public function deleteManyByTenantId( array $id, int $tenantId ): ServiceResult
    {
        $tenantId = (int) $tenantId;
        $deleted  = $this->categoryRepository->deleteManyByIdsAndTenantId( $id, $tenantId );
        return $this->success( $deleted, 'Categorias deletadas.' );
    }

    public function paginateByTenantId( int $tenantId, int $page = 1, int $perPage = 15, array $criteria = [], ?array $orderBy = null ): ServiceResult
    {
        $tenantId = (int) $tenantId;
        $data     = $this->categoryRepository->paginateByTenantId( $tenantId, $page, $perPage, $criteria, $orderBy );
        return $this->success( $data );
    }

    public function countByTenantId( int $tenantId, array $criteria = [] ): ServiceResult
    {
        $tenantId = (int) $tenantId;
        $count    = $this->categoryRepository->countByTenantId( $tenantId, $criteria );
        return $this->success( $count );
    }

    public function existsByTenantId( array $criteria, int $tenantId ): ServiceResult
    {
        $tenantId = (int) $tenantId;
        $count    = $this->categoryRepository->countByTenantId( $tenantId, $criteria );
        $exists   = $count > 0;
        return $this->success( $exists );
    }

    public function updateManyByTenantId( array $id, array $data, int $tenantId ): ServiceResult
    {
        $tenantId = (int) $tenantId;
        $updated  = $this->categoryRepository->updateManyByTenantId( $id, $data, $tenantId );
        return $this->success( $updated );
    }

    public function findByAndTenantId( array $criteria, int $tenantId, ?array $orderBy = null, ?int $limit = null ): ServiceResult
    {
        $tenantId = (int) $tenantId;
        $data     = $this->categoryRepository->findAllByTenantId( $tenantId, $criteria, $orderBy, $limit );
        return $this->success( $data );
    }

    public function listActiveByTenantId( int $tenantId, ?array $orderBy = null, ?int $limit = null ): ServiceResult
    {
        $tenantId = (int) $tenantId;
        $criteria = [ 'status' => 'active' ];
        $data     = $this->listEntitiesByTenantId( $tenantId, $criteria, $orderBy, $limit );
        return $this->success( $data, 'Categorias ativas listadas.' );
    }

    public function getBySlugAndTenantId( string $slug, int $tenantId ): ServiceResult
    {
        $tenantId = (int) $tenantId;
        $category = $this->categoryRepository->getBySlugAndTenantId( $slug, $tenantId );
        if ( !$category ) {
            return $this->error( OperationStatus::NOT_FOUND, 'Categoria não encontrada pelo slug.' );
        }
        return $this->success( $category );
    }

    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        return $this->validateForTenant( $data, $data[ 'tenant_id' ] ?? 0, $isUpdate );
    }

}
